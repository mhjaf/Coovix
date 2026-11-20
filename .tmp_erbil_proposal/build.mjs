import fs from "node:fs/promises";
import { Presentation, PresentationFile } from "@oai/artifact-tool";
import sharp from "sharp";

const OUT = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/Erbil_Sports_Club_Football_Digital_Platform_Proposal_English_v7.pptx";
const PREVIEW = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/previews-english-v7";
const ERBIL_LOGO_PATH = "/Applications/MAMP/htdocs/coovix/deliverables/assets/erbil-sports-club-logo.png";
const COOVIX_LOGO_PATH = "/Applications/MAMP/htdocs/coovix/deliverables/assets/coovix-logo.png";
const FOOTBALL_PHOTO_PATH = "/Applications/MAMP/htdocs/coovix/deliverables/assets/erbil-football-mobile-experience-yellow-v2.png";
const TEAM_PHOTO_PATH = "/Applications/MAMP/htdocs/coovix/deliverables/assets/erbil-football-team-photo.jpg";
const QR_PATH = "/Applications/MAMP/htdocs/coovix/deliverables/assets/erbil-booking-qr.png";

function makeQrMatrix(value) {
  const version = 3, size = 29, dataCodewords = 55, eccCodewords = 15;
  const exp = new Uint8Array(512), log = new Uint8Array(256);
  let v = 1;
  for (let i = 0; i < 255; i++) { exp[i] = v; log[v] = i; v <<= 1; if (v & 0x100) v ^= 0x11D; }
  for (let i = 255; i < 512; i++) exp[i] = exp[i - 255];
  const mul = (a, b) => a && b ? exp[log[a] + log[b]] : 0;
  const divisor = new Uint8Array(eccCodewords); divisor[eccCodewords - 1] = 1;
  let root = 1;
  for (let i = 0; i < eccCodewords; i++) {
    for (let j = 0; j < eccCodewords; j++) {
      divisor[j] = mul(divisor[j], root);
      if (j + 1 < eccCodewords) divisor[j] ^= divisor[j + 1];
    }
    root = mul(root, 2);
  }
  const bits = [];
  const append = (num, len) => { for (let i = len - 1; i >= 0; i--) bits.push((num >>> i) & 1); };
  const bytes = new TextEncoder().encode(value);
  append(0x4, 4); append(bytes.length, 8); bytes.forEach(b => append(b, 8));
  append(0, Math.min(4, dataCodewords * 8 - bits.length));
  while (bits.length % 8) bits.push(0);
  const data = [];
  for (let i = 0; i < bits.length; i += 8) data.push(bits.slice(i, i + 8).reduce((a, b) => (a << 1) | b, 0));
  for (let pad = 0; data.length < dataCodewords; pad++) data.push(pad % 2 ? 0x11 : 0xEC);
  const rem = new Uint8Array(eccCodewords);
  data.forEach(b => {
    const factor = b ^ rem[0];
    rem.copyWithin(0, 1); rem[eccCodewords - 1] = 0;
    for (let i = 0; i < eccCodewords; i++) rem[i] ^= mul(divisor[i], factor);
  });
  const codewords = [...data, ...rem];
  const modules = Array.from({ length: size }, () => Array(size).fill(false));
  const func = Array.from({ length: size }, () => Array(size).fill(false));
  const set = (x, y, dark, isFunc = true) => { if (x >= 0 && y >= 0 && x < size && y < size) { modules[y][x] = dark; if (isFunc) func[y][x] = true; } };
  const finder = (cx, cy) => { for (let dy = -4; dy <= 4; dy++) for (let dx = -4; dx <= 4; dx++) { const d = Math.max(Math.abs(dx), Math.abs(dy)); set(cx + dx, cy + dy, d !== 2 && d !== 4); } };
  finder(3, 3); finder(size - 4, 3); finder(3, size - 4);
  for (let i = 0; i < size; i++) { if (!func[6][i]) set(i, 6, i % 2 === 0); if (!func[i][6]) set(6, i, i % 2 === 0); }
  for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) set(22 + dx, 22 + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
  const drawFormat = (mask) => {
    const dataBits = (1 << 3) | mask;
    let remBits = dataBits;
    for (let i = 0; i < 10; i++) remBits = (remBits << 1) ^ (((remBits >>> 9) & 1) * 0x537);
    const f = ((dataBits << 10) | remBits) ^ 0x5412;
    const bit = i => ((f >>> i) & 1) !== 0;
    for (let i = 0; i <= 5; i++) set(8, i, bit(i));
    set(8, 7, bit(6)); set(8, 8, bit(7)); set(7, 8, bit(8));
    for (let i = 9; i < 15; i++) set(14 - i, 8, bit(i));
    for (let i = 0; i < 8; i++) set(size - 1 - i, 8, bit(i));
    for (let i = 8; i < 15; i++) set(8, size - 15 + i, bit(i));
    set(8, size - 8, true);
  };
  drawFormat(0);
  let bitIndex = 0;
  for (let right = size - 1; right >= 1; right -= 2) {
    if (right === 6) right = 5;
    for (let vert = 0; vert < size; vert++) {
      const upward = ((right + 1) & 2) === 0;
      const y = upward ? size - 1 - vert : vert;
      for (let j = 0; j < 2; j++) {
        const x = right - j;
        if (!func[y][x]) {
          let dark = false;
          if (bitIndex < codewords.length * 8) dark = ((codewords[bitIndex >>> 3] >>> (7 - (bitIndex & 7))) & 1) !== 0;
          bitIndex++;
          if ((x + y) % 2 === 0) dark = !dark;
          modules[y][x] = dark;
        }
      }
    }
  }
  drawFormat(0);
  return modules;
}

const qr = makeQrMatrix("https://erbilsc.com/booking/ESC-18472");
const quiet = 4, modulePx = 12, qrSize = (qr.length + quiet * 2) * modulePx;
const qrRects = qr.flatMap((row, y) => row.map((dark, x) => dark ? `<rect x="${(x + quiet) * modulePx}" y="${(y + quiet) * modulePx}" width="${modulePx}" height="${modulePx}"/>` : "")).join("");
const qrSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="${qrSize}" height="${qrSize}" viewBox="0 0 ${qrSize} ${qrSize}"><rect width="100%" height="100%" fill="white"/><g fill="#111111">${qrRects}</g></svg>`;
await sharp(Buffer.from(qrSvg)).png().toFile(QR_PATH);

const C = {
  navy: "#073B55",
  navy2: "#12384C",
  blue: "#0099D8",
  gold: "#0099D8",
  gold2: "#E6F5FC",
  ink: "#212529",
  muted: "#66818E",
  pale: "#F7FBFD",
  line: "#DCEBF1",
  white: "#FFFFFF",
  green: "#0077B3",
};

const E = {
  yellow: "#FFD000",
  yellowDark: "#C79600",
  pale: "#FFF2A8",
  black: "#111111",
  charcoal: "#252525",
  white: "#FFFFFF",
  gray: "#6B6B66",
  line: "#E7E0C4",
};

const deck = Presentation.create({ slideSize: { width: 1280, height: 720 } });

async function bytes(path) {
  const data = await fs.readFile(path);
  return data.buffer.slice(data.byteOffset, data.byteOffset + data.byteLength);
}

const ERBIL_LOGO = await bytes(ERBIL_LOGO_PATH);
const COOVIX_LOGO = await bytes(COOVIX_LOGO_PATH);
const FOOTBALL_PHOTO = await bytes(FOOTBALL_PHOTO_PATH);
const TEAM_PHOTO = await bytes(TEAM_PHOTO_PATH);
const QR_CODE = await bytes(QR_PATH);

function picture(slide, blob, x, y, w, h, alt, fit = "contain", radius = 0) {
  return slide.images.add({
    blob,
    contentType: "image/png",
    alt,
    fit,
    position: { left: x, top: y, width: w, height: h },
    ...(radius ? { geometry: "roundRect", borderRadius: radius } : {}),
  });
}

function shape(slide, geometry, x, y, w, h, fill = "none", line = "none", radius = 0, name = undefined) {
  return slide.shapes.add({
    geometry,
    name,
    position: { left: x, top: y, width: w, height: h },
    fill,
    line: { style: "solid", fill: line, width: line === "none" ? 0 : 1 },
    ...(radius ? { borderRadius: radius } : {}),
  });
}

function text(slide, value, x, y, w, h, size = 18, color = C.ink, bold = false, align = "left", name = undefined) {
  const t = shape(slide, "textbox", x, y, w, h, "none", "none", 0, name);
  t.text = value;
  t.text.style = { fontSize: size, color, bold, alignment: align, fontFamily: "Aptos" };
  return t;
}

function line(slide, x, y, w, h, color = C.line, width = 1) {
  return slide.shapes.add({
    geometry: "line",
    position: { left: x, top: y, width: w, height: h },
    fill: "none",
    line: { style: "solid", fill: color, width },
  });
}

function header(slide, no, title, kicker) {
  text(slide, kicker.toUpperCase(), 72, 42, 500, 24, 13, C.gold, true);
  text(slide, title, 72, 72, 1080, 52, 36, C.navy, true);
  line(slide, 72, 132, 1136, 0, C.line, 1);
  text(slide, String(no).padStart(2, "0"), 1165, 43, 42, 22, 13, C.muted, true, "right");
}

function footer(slide, label = "ERBIL SPORTS CLUB DIGITAL PLATFORM") {
  text(slide, label, 72, 681, 520, 16, 10, C.muted, true);
  shape(slide, "rect", 1188, 681, 20, 3, C.gold, "none");
}

function note(slide, extras = []) {
  const sources = [
    "User-provided Erbil Sports Club digital platform brief, September 2026.",
    "Coovix local brand palette and official Coovix logo from the project workspace.",
    ...extras,
  ];
  slide.speakerNotes.textFrame.setText(`[Sources]\n${sources.map((s) => `- ${s}`).join("\n")}\n[/Sources]`);
}

function bullet(slide, title, body, x, y, w, accent = C.gold) {
  shape(slide, "ellipse", x, y + 6, 9, 9, accent, "none");
  text(slide, title, x + 20, y, w - 20, 24, 17, C.ink, true);
  if (body) text(slide, body, x + 20, y + 26, w - 20, 39, 14, C.muted, false);
}

function browserMock(slide, x, y, w, h) {
  shape(slide, "roundRect", x, y, w, h, E.white, E.line, 14);
  shape(slide, "rect", x, y, w, 46, E.black, "none");
  picture(slide, ERBIL_LOGO, x + 18, y + 5, 38, 38, "Official Erbil Sports Club crest");
  text(slide, "ERBIL SPORTS CLUB", x + 64, y + 14, 158, 18, 11, E.white, true);
  ["Home", "Team", "Fixtures", "News", "Facilities"].forEach((item, i) =>
    text(slide, item, x + 250 + i * 60, y + 15, 55, 16, 9, i === 0 ? E.yellow : E.white, i === 0, "center")
  );
  shape(slide, "roundRect", x + w - 120, y + 9, 102, 28, E.yellow, "none", 9);
  text(slide, "MEMBER LOGIN", x + w - 114, y + 17, 90, 12, 7, E.black, true, "center");

  shape(slide, "rect", x + 16, y + 62, w - 32, 166, E.pale, "none");
  text(slide, "WELCOME TO ERBIL SPORTS CLUB", x + 34, y + 80, 300, 16, 9, E.yellowDark, true);
  text(slide, "The pride of Hawler,\non and off the pitch.", x + 34, y + 105, 330, 54, 23, E.black, true);
  text(slide, "Fixtures, club news, membership and pitch booking - connected.", x + 34, y + 166, 330, 34, 11, E.gray, false);
  shape(slide, "roundRect", x + 34, y + 202, 112, 22, E.yellow, "none", 7);
  text(slide, "EXPLORE THE CLUB", x + 42, y + 207, 96, 11, 7, E.black, true, "center");
  picture(slide, TEAM_PHOTO, x + w - 310, y + 62, 294, 166, "Erbil Sports Club football team", "cover");

  const cards = [
    ["NEXT FIXTURE", "Erbil SC vs. Duhok", "Friday | 7:30 PM"],
    ["LATEST NEWS", "First-team update", "Open the full story"],
    ["BOOK A PITCH", "Training facilities", "Check live availability"],
  ];
  cards.forEach((c, i) => {
    const cw = (w - 64) / 3;
    const cx = x + 16 + i * (cw + 16);
    shape(slide, "roundRect", cx, y + 246, cw, 112, i === 2 ? E.black : "#FAF9F3", E.line, 10);
    text(slide, c[0], cx + 16, y + 262, cw - 32, 14, 8, i === 2 ? E.yellow : E.yellowDark, true);
    text(slide, c[1], cx + 16, y + 287, cw - 32, 25, 12, i === 2 ? E.white : E.black, true);
    text(slide, c[2], cx + 16, y + 324, cw - 32, 16, 8, i === 2 ? "#D8D5C8" : E.gray, false);
  });
}

function browserBookingMock(slide, x, y, w, h) {
  shape(slide, "roundRect", x, y, w, h, E.white, E.line, 14);
  shape(slide, "rect", x, y, w, 46, E.black, "none");
  picture(slide, ERBIL_LOGO, x + 16, y + 5, 38, 38, "Official Erbil Sports Club crest");
  text(slide, "BOOK A PITCH", x + 65, y + 14, 150, 18, 11, E.white, true);
  shape(slide, "roundRect", x + w - 106, y + 10, 88, 26, E.yellow, "none", 8);
  text(slide, "MY BOOKINGS", x + w - 99, y + 17, 74, 12, 7, E.black, true, "center");
  text(slide, "Reserve a football pitch", x + 24, y + 68, w - 48, 28, 20, E.black, true);
  text(slide, "Choose a facility and available time", x + 24, y + 98, w - 48, 18, 10, E.gray, false);
  text(slide, "FACILITY", x + 24, y + 132, 100, 14, 8, E.yellowDark, true);
  shape(slide, "roundRect", x + 24, y + 151, w - 48, 46, "#FAF9F3", E.line, 9);
  text(slide, "Training Pitch 1", x + 40, y + 165, w - 80, 18, 12, E.black, true);
  text(slide, "DATE", x + 24, y + 215, 80, 14, 8, E.yellowDark, true);
  text(slide, "TIME", x + 205, y + 215, 80, 14, 8, E.yellowDark, true);
  shape(slide, "roundRect", x + 24, y + 234, 160, 42, "#FAF9F3", E.line, 9);
  text(slide, "Friday, 13 Sep", x + 38, y + 246, 132, 18, 11, E.black, true);
  shape(slide, "roundRect", x + 205, y + 234, w - 229, 42, E.pale, E.line, 9);
  text(slide, "18:30-20:00", x + 216, y + 246, w - 251, 18, 11, E.black, true, "center");
  shape(slide, "roundRect", x + 24, y + 296, w - 48, 48, E.black, "none", 10);
  text(slide, "TOTAL", x + 40, y + 306, 80, 14, 8, E.yellow, true);
  text(slide, "25,000 IQD", x + 40, y + 322, 120, 16, 13, E.white, true);
  shape(slide, "roundRect", x + w - 150, y + 305, 110, 30, E.yellow, "none", 8);
  text(slide, "CONTINUE", x + w - 140, y + 313, 90, 14, 8, E.black, true, "center");
}

function phoneMock(slide, x, y, w, h) {
  shape(slide, "roundRect", x, y, w, h, C.navy, C.navy, 28);
  shape(slide, "roundRect", x + 8, y + 8, w - 16, h - 16, C.white, "none", 23);
  shape(slide, "roundRect", x + w / 2 - 28, y + 14, 56, 7, C.navy, "none", 4);
  text(slide, "Book your court", x + 24, y + 45, w - 48, 48, 18, C.navy, true);
  text(slide, "Choose a sport", x + 24, y + 99, w - 48, 18, 11, C.muted, false);
  const labels = ["Football", "Tennis", "Padel"];
  for (let i = 0; i < 3; i++) {
    shape(slide, "roundRect", x + 24, y + 125 + i * 52, w - 48, 40, i === 1 ? "#E6F5FC" : C.pale, "none", 10);
    shape(slide, "ellipse", x + 36, y + 137 + i * 52, 16, 16, i === 1 ? C.gold : C.blue, "none");
    text(slide, labels[i], x + 64, y + 134 + i * 52, w - 104, 18, 12, C.ink, true);
  }
  shape(slide, "roundRect", x + 24, y + h - 60, w - 48, 34, C.gold, "none", 12);
  text(slide, "CONTINUE", x + 34, y + h - 52, w - 68, 16, 10, C.navy, true, "center");
}

function conceptPhone(slide, x, y, w, h, screen) {
  shape(slide, "roundRect", x, y, w, h, E.black, E.black, 28);
  shape(slide, "roundRect", x + 8, y + 8, w - 16, h - 16, E.white, "none", 23);
  shape(slide, "roundRect", x + w / 2 - 27, y + 14, 54, 7, E.black, "none", 4);
  picture(slide, ERBIL_LOGO, x + 19, y + 32, 30, 32, "Erbil Sports Club crest");
  text(slide, "ERBIL SC", x + 57, y + 38, w - 82, 18, 11, E.black, true);
  text(slide, "9:41", x + 20, y + 15, 36, 10, 7, E.gray, true);
  text(slide, "●  ▰", x + w - 60, y + 15, 38, 10, 7, E.gray, true, "right");

  if (screen === "home") {
    text(slide, "Welcome back", x + 20, y + 79, w - 40, 18, 11, E.gray, false);
    text(slide, "Your club, all in\none place", x + 20, y + 101, w - 40, 48, 18, E.black, true);
    shape(slide, "roundRect", x + 20, y + 168, w - 40, 64, E.black, "none", 13);
    text(slide, "NEXT MATCH", x + 34, y + 181, w - 68, 15, 9, E.pale, true);
    text(slide, "Match center", x + 34, y + 204, w - 68, 18, 13, E.white, true);
    ["Training pitches", "Academy", "Club news"].forEach((it, i) => {
      shape(slide, "roundRect", x + 20, y + 240 + i * 44, w - 40, 36, i === 0 ? E.pale : "#FAF9F3", "none", 9);
      shape(slide, "ellipse", x + 32, y + 250 + i * 44, 15, 15, E.yellow, "none");
      text(slide, it, x + 57, y + 248 + i * 44, w - 88, 17, 11, E.black, true);
    });
    line(slide, x + 26, y + h - 34, w - 52, 0, E.line, 1);
    text(slide, "HOME       FIXTURES       PROFILE", x + 25, y + h - 26, w - 50, 11, 7, E.gray, true, "center");
  } else if (screen === "pitches") {
    text(slide, "Select a pitch", x + 20, y + 82, w - 40, 28, 18, E.black, true);
    text(slide, "Football facilities", x + 20, y + 114, w - 40, 16, 10, E.gray, false);
    const facilities = [
      ["Training Pitch 1", "Available today", "25,000 IQD"],
      ["Training Pitch 2", "Next: 20:00", "25,000 IQD"],
      ["Main Pitch", "Staff approval", "Request"],
    ];
    facilities.forEach((it, i) => {
      const fy = y + 145 + i * 70;
      shape(slide, "roundRect", x + 20, fy, w - 40, 58, i === 0 ? E.pale : "#FAF9F3", E.line, 10);
      text(slide, it[0], x + 34, fy + 10, w - 68, 17, 11, E.black, true);
      text(slide, it[1], x + 34, fy + 30, 100, 14, 8, E.gray, false);
      text(slide, it[2], x + w - 105, fy + 30, 72, 14, 8, E.yellowDark, true, "right");
    });
    shape(slide, "roundRect", x + 20, y + h - 54, w - 40, 32, E.yellow, "none", 10);
    text(slide, "VIEW AVAILABILITY", x + 30, y + h - 47, w - 60, 15, 8, E.black, true, "center");
  } else if (screen === "booking" || screen === "calendar") {
    text(slide, "Book a football pitch", x + 20, y + 80, w - 40, 28, 18, E.black, true);
    text(slide, "Select date", x + 20, y + 124, w - 40, 16, 10, E.gray, true);
    const days = [["THU", "12"], ["FRI", "13"], ["SAT", "14"]];
    days.forEach((d, i) => {
      const dx = x + 20 + i * 61;
      shape(slide, "roundRect", dx, y + 148, 52, 58, i === 1 ? E.yellow : "#FAF9F3", "none", 9);
      text(slide, d[0], dx + 5, y + 157, 42, 12, 8, i === 1 ? E.black : E.gray, true, "center");
      text(slide, d[1], dx + 5, y + 176, 42, 18, 13, E.black, true, "center");
    });
    text(slide, "Available time", x + 20, y + 226, w - 40, 16, 10, E.gray, true);
    ["17:00", "18:30"].forEach((it, i) => {
      shape(slide, "roundRect", x + 20, y + 251 + i * 48, w - 40, 38, i === 1 ? E.pale : "#FAF9F3", "none", 9);
      text(slide, it, x + 32, y + 261 + i * 48, w - 64, 16, 11, E.black, true, "center");
    });
    shape(slide, "roundRect", x + 20, y + h - 54, w - 40, 32, E.yellow, "none", 10);
    text(slide, "CONFIRM TIME", x + 30, y + h - 47, w - 60, 15, 9, E.black, true, "center");
  } else if (screen === "payment") {
    text(slide, "Review and pay", x + 20, y + 82, w - 40, 28, 18, E.black, true);
    shape(slide, "roundRect", x + 20, y + 124, w - 40, 102, "#FAF9F3", E.line, 10);
    text(slide, "Training Pitch 1", x + 34, y + 139, w - 68, 18, 12, E.black, true);
    text(slide, "Friday 18:30-20:00", x + 34, y + 166, w - 68, 16, 9, E.gray, false);
    text(slide, "25,000 IQD", x + 34, y + 194, w - 68, 18, 13, E.black, true);
    text(slide, "PAYMENT METHOD", x + 20, y + 248, w - 40, 14, 8, E.yellowDark, true);
    shape(slide, "roundRect", x + 20, y + 269, w - 40, 48, E.white, E.line, 9);
    text(slide, "Card ending 4242", x + 35, y + 284, w - 70, 18, 10, E.black, true);
    shape(slide, "roundRect", x + 20, y + h - 54, w - 40, 32, E.yellow, "none", 10);
    text(slide, "PAY 25,000 IQD", x + 30, y + h - 47, w - 60, 15, 8, E.black, true, "center");
  } else if (screen === "paymentSuccess") {
    shape(slide, "ellipse", x + w / 2 - 27, y + 82, 54, 54, E.yellow, "none");
    text(slide, "✓", x + w / 2 - 16, y + 95, 32, 26, 21, E.black, true, "center");
    text(slide, "Payment successful", x + 20, y + 153, w - 40, 26, 16, E.black, true, "center");
    text(slide, "25,000 IQD", x + 20, y + 188, w - 40, 20, 14, E.black, true, "center");
    shape(slide, "roundRect", x + 26, y + 231, w - 52, 88, "#FAF9F3", E.line, 9);
    text(slide, "STATUS", x + 40, y + 246, 70, 14, 8, E.gray, true);
    text(slide, "PAID", x + w - 86, y + 246, 46, 14, 9, E.yellowDark, true, "right");
    text(slide, "Receipt ESC-P18472", x + 40, y + 276, w - 80, 16, 9, E.black, true);
    text(slide, "Digital receipt saved", x + 40, y + 298, w - 80, 14, 8, E.gray, false);
    shape(slide, "roundRect", x + 20, y + h - 54, w - 40, 32, E.black, "none", 10);
    text(slide, "VIEW BOOKING", x + 30, y + h - 47, w - 60, 15, 8, E.white, true, "center");
  } else if (screen === "myBookings") {
    text(slide, "My bookings", x + 20, y + 82, w - 40, 28, 18, E.black, true);
    shape(slide, "roundRect", x + 20, y + 126, w - 40, 130, E.pale, E.line, 10);
    text(slide, "CONFIRMED", x + 34, y + 141, 90, 14, 8, E.yellowDark, true);
    text(slide, "Training Pitch 1", x + 34, y + 167, w - 68, 18, 12, E.black, true);
    text(slide, "Friday 18:30-20:00", x + 34, y + 193, w - 68, 16, 9, E.gray, false);
    text(slide, "25,000 IQD  |  PAID", x + 34, y + 219, w - 68, 16, 9, E.black, true);
    shape(slide, "roundRect", x + 20, y + 272, w - 40, 78, "#FAF9F3", E.line, 10);
    text(slide, "Previous booking", x + 34, y + 287, w - 68, 16, 10, E.black, true);
    text(slide, "Training Pitch 2  |  Completed", x + 34, y + 314, w - 68, 16, 8, E.gray, false);
    line(slide, x + 26, y + h - 34, w - 52, 0, E.line, 1);
    text(slide, "HOME       BOOKINGS       PROFILE", x + 25, y + h - 26, w - 50, 11, 7, E.gray, true, "center");
  } else {
    shape(slide, "ellipse", x + w / 2 - 27, y + 78, 54, 54, E.yellow, "none");
    text(slide, "✓", x + w / 2 - 16, y + 91, 32, 26, 21, E.black, true, "center");
    text(slide, "Booking confirmed", x + 20, y + 148, w - 40, 26, 16, E.black, true, "center");
    text(slide, "Training Pitch 1 | Fri 18:30-20:00", x + 22, y + 179, w - 44, 30, 9, E.gray, false, "center");
    shape(slide, "roundRect", x + 49, y + 220, w - 98, 130, E.white, E.line, 8);
    picture(slide, QR_CODE, x + 55, y + 226, w - 110, 118, "Scannable booking confirmation QR code");
    text(slide, "BOOKING ID  ESC-18472", x + 20, y + 360, w - 40, 14, 8, E.black, true, "center");
    text(slide, "Scan at reception", x + 20, y + 380, w - 40, 16, 9, E.gray, true, "center");
  }
}

// 1 — Cover
{
  const s = deck.slides.add();
  s.background.fill = C.navy;
  shape(s, "rect", 0, 0, 18, 720, C.gold, "none");
  shape(s, "ellipse", 930, 0, 350, 350, C.navy2, "none");
  shape(s, "ellipse", 1010, 0, 270, 270, "none", C.gold2);
  picture(s, ERBIL_LOGO, 1074, 58, 142, 152, "Official Erbil Sports Club crest");
  shape(s, "roundRect", 72, 50, 198, 92, C.white, "none", 16);
  picture(s, COOVIX_LOGO, 94, 74, 154, 43, "Official Coovix logo");
  text(s, "BUSINESS PROPOSAL • FOOTBALL", 78, 226, 520, 26, 14, C.gold, true);
  text(s, "Erbil Sports Club\nDigital Football Platform", 78, 262, 820, 145, 51, C.white, true);
  text(s, "Website, Mobile Application & Smart Football Facility Booking", 82, 420, 770, 45, 22, C.gold2, false);
  line(s, 82, 495, 650, 0, "#4A4A46", 1);
  text(s, "Prepared by: Coovix", 82, 525, 250, 24, 16, C.white, true);
  text(s, "September 2026", 340, 525, 210, 24, 16, "#D8D5C8", false);
  text(s, "A connected football experience for supporters and members.\nA clearer operating system for management.", 850, 458, 335, 104, 21, C.white, true);
  text(s, "COOVIX • PROPOSAL • ERBIL, KURDISTAN REGION OF IRAQ", 82, 665, 620, 16, 10, "#BBB7A8", true);
  note(s, ["Official Coovix logo from the Coovix project workspace.", "Official Erbil Sports Club crest supplied by the user."]);
}

// 2 — Executive summary
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 2, "One platform can become the club’s digital front door", "Executive summary");
  text(s, "The proposed platform brings football news, facility booking, supporter communication and management into one connected experience.", 72, 165, 760, 70, 24, C.ink, true);
  const outcomes = [
    ["Members", "Follow club activity, find football facilities and confirm reservations in minutes."],
    ["Operations", "Manage schedules, pricing and customer records from one place."],
    ["Management", "See bookings, revenue and peak-hour trends with greater clarity."],
  ];
  outcomes.forEach((o, i) => {
    const y = 282 + i * 104;
    text(s, `0${i + 1}`, 72, y, 48, 30, 20, C.gold, true);
    line(s, 132, y + 12, 60, 0, C.gold, 2);
    text(s, o[0], 214, y - 2, 170, 26, 20, C.navy, true);
    text(s, o[1], 390, y - 2, 430, 48, 16, C.muted, false);
  });
  shape(s, "roundRect", 878, 162, 330, 430, C.navy, "none", 22);
  text(s, "THE PROPOSAL", 910, 196, 250, 22, 13, C.gold, true);
  text(s, "A modern website\n+ Android & iOS app\n+ Admin dashboard\n+ Smart booking system", 910, 242, 250, 166, 24, C.white, true);
  shape(s, "roundRect", 910, 454, 250, 88, C.navy2, "none", 12);
  text(s, "DESIGNED TO SCALE", 930, 471, 210, 18, 11, C.gold2, true);
  text(s, "A foundation for future digital services.", 930, 499, 205, 34, 15, C.white, false);
  footer(s); note(s);
}

// 3 — Current opportunities
{
  const s = deck.slides.add(); s.background.fill = C.pale; header(s, 3, "Today’s friction points are clear opportunities to improve", "Current opportunities");
  text(s, "The goal is to preserve the club’s personal service while making routine interactions faster, clearer and measurable.", 72, 154, 1040, 52, 20, C.muted, false);
  const items = [
    ["Availability", "Time slots are difficult to check without calling."],
    ["Reservations", "Phone-based and manual bookings require staff attention."],
    ["Accuracy", "Disconnected schedules can increase avoidable booking errors."],
    ["Communication", "Updates and reminders do not reach every customer consistently."],
    ["Data", "Booking information is harder to centralize and search."],
    ["Reporting", "Revenue, demand and peak-hour visibility can be strengthened."],
  ];
  items.forEach((it, i) => {
    const col = i % 2, row = Math.floor(i / 2);
    const x = 72 + col * 570, y = 238 + row * 124;
    shape(s, "roundRect", x, y, 536, 94, C.white, C.line, 12);
    text(s, String(i + 1).padStart(2, "0"), x + 20, y + 18, 42, 24, 15, C.gold, true);
    text(s, it[0], x + 74, y + 15, 250, 24, 17, C.navy, true);
    text(s, it[1], x + 74, y + 43, 430, 36, 14, C.muted, false);
  });
  footer(s); note(s);
}

// 4 — Architecture
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 4, "One connected ecosystem keeps every channel in sync", "Proposed solution");
  text(s, "Members see one seamless experience; staff manage one reliable source of booking information.", 72, 151, 920, 42, 20, C.muted, false);
  const nodes = [
    { x: 90, y: 258, w: 210, h: 110, t: "WEBSITE", d: "Information • News\nMembership • Booking" },
    { x: 90, y: 426, w: 210, h: 110, t: "MOBILE APP", d: "Android • iOS\nQR • Notifications" },
    { x: 535, y: 328, w: 240, h: 146, t: "CLUB SERVICES", d: "Availability\nReservations\nMembers & pricing" },
    { x: 988, y: 258, w: 210, h: 110, t: "ADMIN", d: "Schedules • Members\nReports • Revenue" },
    { x: 988, y: 426, w: 210, h: 110, t: "STAFF", d: "Operations\nCheck-in • Support" },
  ];
  // connectors first
  line(s, 300, 313, 235, 64, C.gold, 2); line(s, 300, 421, 235, 60, C.gold, 2);
  line(s, 775, 313, 213, 63, C.gold, 2); line(s, 775, 424, 213, 57, C.gold, 2);
  nodes.forEach((n, i) => {
    shape(s, "roundRect", n.x, n.y, n.w, n.h, i === 2 ? C.navy : C.pale, i === 2 ? C.navy : C.line, 16);
    text(s, n.t, n.x + 18, n.y + 20, n.w - 36, 24, 16, i === 2 ? C.gold2 : C.navy, true, "center");
    text(s, n.d, n.x + 18, n.y + 52, n.w - 36, 45, 13, i === 2 ? C.white : C.muted, false, "center");
  });
  shape(s, "roundRect", 430, 558, 420, 50, C.gold2, "none", 12);
  text(s, "SHARED MEMBER, CONTENT & BOOKING SERVICES", 450, 573, 380, 20, 14, C.blue, true, "center");
  footer(s); note(s);
}

// 5 — Project phases
{
  const s = deck.slides.add(); s.background.fill = C.pale; header(s, 5, "Phase 1 delivers the paid core; later phases extend the platform", "Project phases");
  shape(s, "roundRect", 72, 174, 526, 420, C.navy, "none", 18);
  text(s, "PHASE 1", 98, 198, 120, 20, 12, C.gold2, true);
  text(s, "Core Digital Platform", 98, 228, 420, 34, 24, C.white, true);
  text(s, "CURRENT PAID SCOPE", 98, 270, 180, 18, 10, C.gold, true);
  const coreA = ["Responsive website", "Android and iOS app", "Registration, login and OTP", "Pitch and facility booking", "Live availability", "Booking history and cancellation", "Push notifications", "QR booking confirmation", "Online payment integration"];
  const coreB = ["Payment confirmation and receipt", "Admin dashboard", "Booking management", "Facility and schedule management", "Customer and pricing management", "Payment status", "Basic revenue and booking reports", "Backend APIs", "Deployment and staff training"];
  coreA.forEach((it, i) => text(s, `• ${it}`, 98, 307 + i * 28, 225, 19, 12, C.white, false));
  coreB.forEach((it, i) => text(s, `• ${it}`, 332, 307 + i * 28, 240, 19, 12, C.white, false));
  const futureCards = [
    ["PHASE 2", "Membership & Growth", ["Digital membership", "Subscriptions", "Digital membership card", "Loyalty points", "Promotions", "Advanced analytics"]],
    ["PHASE 3", "Smart Club Services", ["Academy management", "Coach booking", "Event ticketing", "Restaurant ordering", "Smart access control", "Parking management", "Additional services"]],
  ];
  futureCards.forEach((c, i) => {
    const x = 626 + i * 291;
    shape(s, "roundRect", x, 174, 263, 420, C.white, C.line, 18);
    text(s, c[0], x + 24, 198, 100, 20, 12, C.gold, true);
    text(s, c[1], x + 24, 230, 215, 52, 22, C.navy, true);
    text(s, "FUTURE OPTIONAL", x + 24, 292, 160, 18, 10, C.blue, true);
    c[2].forEach((it, j) => text(s, `• ${it}`, x + 24, 329 + j * 34, 215, 24, 13, C.ink, false));
  });
  shape(s, "roundRect", 72, 613, 1136, 40, C.gold2, "none", 10);
  text(s, "Phase 2 and Phase 3 are not included in the Phase 1 price. Coovix will scope and price them separately.", 94, 624, 1092, 18, 14, C.blue, true, "center");
  footer(s); note(s);
}

// 6 — Website
{
  const s = deck.slides.add(); s.background.fill = C.pale; header(s, 6, "The website supports both club content and live pitch booking", "Website experience");
  browserMock(s, 72, 176, 690, 382);
  browserBookingMock(s, 790, 176, 418, 382);
  text(s, "HOMEPAGE", 72, 575, 690, 18, 11, C.blue, true, "center");
  text(s, "BOOKING PAGE", 790, 575, 418, 18, 11, C.blue, true, "center");
  shape(s, "roundRect", 72, 612, 1136, 40, C.navy, "none", 10);
  text(s, "CLUB STORY  •  FIXTURES  •  NEWS  •  MEMBER ACCESS  •  LIVE AVAILABILITY  •  ONLINE PAYMENT", 96, 623, 1088, 18, 14, C.white, true, "center");
  footer(s); note(s, ["Official Erbil Sports Club crest supplied by the user.", "Erbil Sports Club football team photograph supplied by the user."]);
}

// 7 — Mobile application
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 7, "The mobile app makes football services easy to find and book", "Mobile application");
  text(s, "The primary journey moves from the club home screen to pitch selection and live availability.", 72, 151, 1040, 38, 18, C.muted, false);
  conceptPhone(s, 130, 205, 230, 414, "home");
  conceptPhone(s, 525, 205, 230, 414, "pitches");
  conceptPhone(s, 920, 205, 230, 414, "calendar");
  line(s, 374, 402, 125, 0, C.blue, 2); line(s, 769, 402, 125, 0, C.blue, 2);
  text(s, "APP HOME", 130, 635, 230, 18, 11, C.blue, true, "center");
  text(s, "PITCH SELECTION", 525, 635, 230, 18, 11, C.blue, true, "center");
  text(s, "BOOKING CALENDAR", 920, 635, 230, 18, 11, C.blue, true, "center");
  footer(s); note(s, ["Official Erbil Sports Club crest supplied by the user. All interface screens are proposal concepts."]);
}

// 8 — Booking and payment
{
  const s = deck.slides.add(); s.background.fill = C.pale; header(s, 8, "Payment and booking confirmation stay in one clear journey", "Booking & payment flow");
  text(s, "Phase 1 includes online payment integration, confirmation, a digital receipt and QR booking entry.", 72, 151, 1080, 38, 18, C.muted, false);
  const screens = [[72, "payment", "PAYMENT"], [363, "paymentSuccess", "PAYMENT SUCCESS"], [654, "confirmed", "QR CONFIRMATION"], [945, "myBookings", "MY BOOKINGS"]];
  screens.forEach(([x, screen, label]) => {
    conceptPhone(s, x, 202, 220, 408, screen);
    text(s, label, x, 626, 220, 18, 10, C.blue, true, "center");
  });
  footer(s); note(s, ["The QR code is a functional proposal demonstration linked to booking ID ESC-18472."]);
}

// 9 — Admin dashboard
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 9, "The dashboard connects bookings, payments and daily operations", "Admin dashboard");
  shape(s, "roundRect", 72, 170, 750, 414, C.pale, C.line, 16);
  shape(s, "rect", 72, 170, 150, 414, C.navy, "none");
  text(s, "ESC ADMIN", 94, 195, 108, 22, 14, C.gold2, true);
  ["Overview", "Bookings", "Facilities", "Customers", "Payments", "Reports"].forEach((it, i) => {
    if (i === 0) shape(s, "roundRect", 86, 238 + i * 42, 122, 30, "#183F60", "none", 8);
    text(s, it, 101, 245 + i * 42, 96, 18, 11, C.white, i === 0);
  });
  text(s, "Today at a glance", 252, 194, 300, 28, 20, C.navy, true);
  const stats = [["48", "Bookings"], ["72%", "Utilization"], ["1.2M", "IQD revenue"]];
  stats.forEach((it, i) => {
    const x = 252 + i * 177;
    shape(s, "roundRect", x, 238, 157, 76, C.white, C.line, 10);
    text(s, it[0], x + 12, 251, 133, 26, 21, i === 2 ? C.green : C.navy, true, "center");
    text(s, it[1], x + 12, 282, 133, 18, 10, C.muted, false, "center");
  });
  text(s, "Recent booking", 252, 340, 220, 22, 14, C.navy, true);
  shape(s, "roundRect", 252, 375, 520, 96, C.white, C.line, 10);
  text(s, "ESC-18472", 270, 391, 110, 18, 11, C.navy, true);
  text(s, "Training Pitch 1", 388, 391, 160, 18, 11, C.ink, true);
  text(s, "Fri 18:30-20:00", 270, 424, 150, 16, 10, C.muted, false);
  text(s, "25,000 IQD", 438, 424, 100, 16, 10, C.ink, true);
  shape(s, "roundRect", 630, 388, 116, 28, E.pale, "none", 8);
  text(s, "PAID / CONFIRMED", 639, 396, 98, 13, 7, E.black, true, "center");
  text(s, "Manage", 865, 174, 190, 28, 23, C.navy, true);
  const groups = [
    ["Bookings", "Status, cancellations and history"],
    ["Facilities", "Schedules, availability and capacity"],
    ["Customers", "Profiles, access and activity"],
    ["Pricing & payments", "Rates, receipts and payment status"],
    ["Reports", "Revenue, bookings and peak hours"],
  ];
  groups.forEach((g, i) => bullet(s, g[0], g[1], 865, 224 + i * 76, 330, i === 4 ? C.green : C.gold));
  footer(s); note(s);
}

// 10 — Benefits
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 10, "Member convenience translates into stronger decisions", "Business benefits");
  const benefits = [
    ["01", "Better experience", "Fast self-service booking with payment and clear confirmation."],
    ["02", "Lean operations", "Fewer calls, repetitive tasks and manual schedule checks."],
    ["03", "Higher utilization", "Live availability helps staff use open capacity."],
    ["04", "Fewer mistakes", "One schedule reduces conflicts and duplicated bookings."],
    ["05", "Commercial clarity", "Track payment status, pricing, revenue and demand."],
    ["06", "Stronger brand", "A premium digital experience reflects the club’s ambition."],
  ];
  benefits.forEach((b, i) => {
    const col = i % 3, row = Math.floor(i / 3);
    const x = 72 + col * 382, y = 182 + row * 208;
    text(s, b[0], x, y, 54, 32, 21, C.gold, true);
    line(s, x, y + 46, 318, 0, C.line, 1);
    text(s, b[1], x, y + 65, 318, 28, 20, C.navy, true);
    text(s, b[2], x, y + 104, 318, 54, 15, C.muted, false);
  });
  shape(s, "roundRect", 72, 592, 1136, 54, C.pale, "none", 12);
  text(s, "The platform improves service and visibility without assuming guaranteed revenue growth.", 96, 608, 1088, 22, 16, C.blue, true, "center");
  footer(s); note(s);
}

// 11 — Future phases
{
  const s = deck.slides.add(); s.background.fill = C.pale; header(s, 13, "Future phases include additional recommended club services", "Future optional phases");
  const cards = [
    {x:72, label:"PHASE 2", title:"Membership & Growth", items:["Digital membership", "Membership subscriptions", "Digital membership card", "Loyalty points and rewards", "Promotions and offers", "Advanced reports and analytics", "Corporate and family memberships", "Referral and invitation program", "Member surveys and feedback", "Automated renewal reminders", "Sponsor benefits and partner offers"]},
    {x:654, label:"PHASE 3", title:"Smart Club Services", items:["Academy management", "Coach booking", "Event ticketing", "Restaurant ordering", "Smart access control", "Parking management", "Club merchandise store", "Digital wallet and cashless payments", "Digital guest passes", "Maintenance request management", "Live match center and club media", "Additional club services"]},
  ];
  cards.forEach((c, idx) => {
    shape(s, "roundRect", c.x, 174, 554, 390, idx === 0 ? C.white : C.navy, idx === 0 ? C.line : C.navy, 18);
    text(s, c.label, c.x + 30, 204, 120, 20, 12, idx === 0 ? C.gold : C.gold2, true);
    text(s, c.title, c.x + 30, 239, 470, 36, 26, idx === 0 ? C.navy : C.white, true);
    c.items.forEach((it, i) => {
      shape(s, "ellipse", c.x + 32, 296 + i * 22, 9, 9, idx === 0 ? C.blue : C.gold, "none");
      text(s, it, c.x + 54, 290 + i * 22, 450, 20, 12.5, idx === 0 ? C.ink : C.white, false);
    });
  });
  shape(s, "roundRect", 72, 590, 1136, 64, C.gold2, "none", 12);
  text(s, "FUTURE SCOPE", 96, 606, 180, 18, 11, C.blue, true);
  text(s, "Phase 2 and Phase 3 are optional future modules outside the current Phase 1 scope. No pricing is presented for these phases.", 286, 602, 890, 38, 14, C.navy, true);
  footer(s); note(s);
}

// 12 — Phase 1 timeline
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 11, "Phase 1 targets launch in 10-12 weeks", "Phase 1 timeline");
  text(s, "Workstreams overlap where practical, with review points for design, the core demo and final deployment.", 72, 151, 1020, 45, 19, C.muted, false);
  const phases = [
    ["1", "Analysis & planning", "1 week", 1],
    ["2", "UI/UX design", "2 weeks", 2],
    ["3", "Core platform & website", "3-4 weeks", 4],
    ["4", "Mobile application", "4-5 weeks", 5],
    ["5", "Payment & system integration", "1-2 weeks", 2],
    ["6", "Testing, deployment & training", "1-2 weeks", 2],
  ];
  const startX = 390, weekW = 66;
  for (let w = 1; w <= 12; w++) {
    text(s, String(w), startX + (w - 1) * weekW, 214, weekW, 18, 11, C.muted, true, "center");
    line(s, startX + (w - 1) * weekW, 245, 0, 340, "#E5EAEE", 1);
  }
  const starts = [1, 2, 3, 5, 9, 11];
  phases.forEach((p, i) => {
    const y = 265 + i * 55;
    text(s, p[0], 72, y + 5, 30, 22, 14, C.gold, true);
    text(s, p[1], 112, y + 3, 235, 24, 14, C.navy, true);
    text(s, p[2], 112, y + 27, 160, 18, 12, C.muted, false);
    shape(s, "roundRect", startX + (starts[i] - 1) * weekW + 5, y + 5, p[3] * weekW - 10, 28, i === 5 ? C.green : (i < 2 ? C.gold : C.blue), "none", 9);
  });
  shape(s, "roundRect", 72, 616, 1136, 34, C.pale, "none", 10);
  text(s, "The schedule assumes timely approvals, content delivery and access to required third-party accounts.", 92, 624, 1096, 18, 13, C.muted, false, "center");
  footer(s); note(s);
}

// 13 — Phase 1 commercial proposal
{
  const s = deck.slides.add(); s.background.fill = C.navy;
  text(s, "PHASE 1 COMMERCIAL PROPOSAL", 72, 40, 500, 24, 13, C.gold, true);
  text(s, "The complete Core Digital Platform is priced at $6,900 USD", 72, 76, 1050, 52, 35, C.white, true);
  shape(s, "roundRect", 72, 156, 360, 150, C.gold, "none", 18);
  text(s, "PHASE 1 INVESTMENT", 100, 181, 300, 20, 13, C.navy, true);
  text(s, "$6,900 USD", 100, 215, 300, 52, 39, C.navy, true);
  text(s, "Target delivery: 10-12 weeks", 100, 273, 300, 18, 13, C.navy, true);
  text(s, "INCLUDED IN PHASE 1", 486, 160, 260, 20, 12, C.gold2, true);
  text(s, "Website, Android and iOS app\nBooking, live availability and QR confirmation\nOnline payment integration and digital receipt\nAdmin dashboard, reports and Backend APIs\nDeployment and staff training", 486, 194, 650, 136, 15, C.white, false);
  shape(s, "roundRect", 72, 342, 1136, 84, C.navy2, "none", 12);
  text(s, "PAYMENT TERMS", 96, 359, 170, 18, 11, C.gold2, true);
  text(s, "30% on approval", 282, 356, 220, 24, 16, C.white, true);
  text(s, "40% after core system demo", 520, 356, 300, 24, 16, C.white, true);
  text(s, "30% after final deployment", 840, 356, 320, 24, 16, C.white, true);
  text(s, "Phase 2 and Phase 3", 96, 394, 200, 18, 11, C.gold, true);
  text(s, "Future optional modules. Not included in this price and quoted separately.", 282, 391, 860, 24, 14, C.white, false);
  text(s, "THIRD-PARTY FEES EXCLUDED", 72, 462, 300, 20, 12, C.gold2, true);
  const exclusions = ["Payment gateway transaction fees", "Bank or provider fees", "SMS and OTP usage fees", "Apple Developer fees", "Google Play fees", "Hosting renewal", "Domain renewal"];
  exclusions.forEach((it, i) => {
    const col = i % 2, row = Math.floor(i / 2);
    shape(s, "ellipse", 78 + col * 560, 505 + row * 34, 10, 10, C.gold, "none");
    text(s, it, 98 + col * 560, 497 + row * 34, 500, 24, 14, C.white, false);
  });
  text(s, "12", 1165, 43, 42, 22, 13, "#BBB7A8", true, "right");
  footer(s); note(s);
}

// 14 — Conclusion
{
  const s = deck.slides.add(); s.background.fill = C.white; header(s, 14, "Phase 1 gives Erbil Sports Club a complete digital operating foundation", "Conclusion");
  text(s, "The proposed scope connects the club website, mobile booking, online payment and administration in one coordinated platform.", 72, 164, 1080, 58, 21, C.muted, false);
  const outcomes = [
    ["01", "Member experience", "Supporters can find football services, pay online and receive a confirmed booking with a QR code."],
    ["02", "Operational control", "Staff can manage schedules, customers, payment status and basic reports from one dashboard."],
    ["03", "Room to expand", "Future membership and smart club modules can be added under separately approved scopes."],
  ];
  outcomes.forEach((o, i) => {
    const y = 270 + i * 108;
    text(s, o[0], 72, y, 48, 28, 20, C.gold, true);
    text(s, o[1], 148, y - 2, 260, 28, 20, C.navy, true);
    text(s, o[2], 430, y - 2, 700, 50, 16, C.muted, false);
    line(s, 148, y + 65, 960, 0, C.line, 1);
  });
  shape(s, "roundRect", 72, 604, 1136, 52, C.navy, "none", 12);
  text(s, "PROPOSED NEXT STEP", 96, 619, 190, 18, 11, C.gold2, true);
  text(s, "Approve Phase 1 and begin analysis, content collection and experience design.", 300, 615, 870, 26, 16, C.white, true);
  footer(s); note(s);
}

// Complete all Phase 1 pages before presenting the future optional phases.
const originalSlides = [...deck.slides.items];
const reorderedSlides = [...originalSlides.slice(0, 10), originalSlides[11], originalSlides[12], originalSlides[10], originalSlides[13]];
deck.slides.replace(reorderedSlides.map((slide) => slide.toProto()));

await fs.mkdir(PREVIEW, { recursive: true });
for (const [i, s] of deck.slides.items.entries()) {
  const png = await deck.export({ slide: s, format: "png", scale: 1 });
  await fs.writeFile(`${PREVIEW}/slide-${String(i + 1).padStart(2, "0")}.png`, new Uint8Array(await png.arrayBuffer()));
  const layout = await s.export({ format: "layout" });
  await fs.writeFile(`${PREVIEW}/slide-${String(i + 1).padStart(2, "0")}.layout.json`, await layout.text());
}
const montage = await deck.export({ format: "webp", montage: true, scale: 1 });
await fs.writeFile(`${PREVIEW}/montage.webp`, new Uint8Array(await montage.arrayBuffer()));
const pptx = await PresentationFile.exportPptx(deck);
await pptx.save(OUT);
console.log(`Wrote ${OUT}`);
