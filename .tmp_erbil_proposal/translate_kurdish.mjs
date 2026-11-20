import fs from "node:fs/promises";
import JSZip from "jszip";
import { FileBlob, PresentationFile } from "@oai/artifact-tool";

const SOURCE = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/Erbil_Sports_Club_Football_Digital_Platform_Proposal_English_v7.pptx";
const RAW = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_v7_raw.pptx";
const OUT = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_v7.pptx";
const PREVIEW = "/Applications/MAMP/htdocs/coovix/.tmp_erbil_proposal/previews-kurdish-v7";

const tr = new Map(Object.entries({
  "BUSINESS PROPOSAL • FOOTBALL":"پێشنیاری بازرگانی • تۆپی پێ",
  "Erbil Sports Club\nDigital Football Platform":"پلاتفۆرمی دیجیتاڵی تۆپی پێ\nیانەی وەرزشی هەولێر",
  "Website, Mobile Application & Smart Football Facility Booking":"ماڵپەڕ، ئەپی مۆبایل و حجزکردنی زیرەکانەی یاریگا",
  "Prepared by: Coovix":"ئامادەکراوە لەلایەن: Coovix",
  "September 2026":"ئەیلوولی ٢٠٢٦",
  "A connected football experience for supporters and members.\nA clearer operating system for management.":"ئەزموونێکی پەیوەست بۆ هاندەران و ئەندامان.\nسیستەمێکی ڕوونتر بۆ بەڕێوەبردن.",
  "COOVIX • PROPOSAL • ERBIL, KURDISTAN REGION OF IRAQ":"COOVIX • پێشنیار • هەولێر، هەرێمی کوردستانی عێراق",
  "EXECUTIVE SUMMARY":"پوختەی بەڕێوەبەرایەتی",
  "One platform can become the club’s digital front door":"یەک پلاتفۆرم دەبێتە دەروازەی دیجیتاڵی یانە",
  "The proposed platform brings football news, facility booking, supporter communication and management into one connected experience.":"پلاتفۆرمە پێشنیارکراوەکە هەواڵی تۆپی پێ، حجزکردنی یاریگا، پەیوەندی لەگەڵ هاندەران و بەڕێوەبردن لە یەک ئەزموونی پەیوەستدا کۆدەکاتەوە.",
  "Members":"ئەندامان", "Follow club activity, find football facilities and confirm reservations in minutes.":"چالاکییەکانی یانە ببینن، یاریگا بدۆزنەوە و لە چەند خولەکێکدا حجزەکە پشتڕاست بکەنەوە.",
  "Operations":"کارگێڕی", "Manage schedules, pricing and customer records from one place.":"خشتەی کات، نرخ و تۆماری کڕیاران لە یەک شوێن بەڕێوەببەن.",
  "Management":"بەڕێوەبردن", "See bookings, revenue and peak-hour trends with greater clarity.":"حجزەکان، داهات و کاتە پڕداواکارییەکان بە ڕوونی ببینن.",
  "THE PROPOSAL":"پێشنیارەکە", "A modern website\n+ Android & iOS app\n+ Admin dashboard\n+ Smart booking system":"ماڵپەڕێکی مۆدێرن\n+ ئەپی Android و iOS\n+ داشبۆردی بەڕێوەبەر\n+ سیستەمی زیرەکی حجز",
  "DESIGNED TO SCALE":"ئامادە بۆ گەشەکردن", "A foundation for future digital services.":"بناغەیەک بۆ خزمەتگوزارییە دیجیتاڵییەکانی داهاتوو.",
  "ERBIL SPORTS CLUB DIGITAL PLATFORM":"پلاتفۆرمی دیجیتاڵی یانەی وەرزشی هەولێر",
  "CURRENT OPPORTUNITIES":"دەرفەتەکانی ئێستا", "Today’s friction points are clear opportunities to improve":"کێشەکانی ئەمڕۆ دەرفەتی ڕوونن بۆ باشترکردن",
  "The goal is to preserve the club’s personal service while making routine interactions faster, clearer and measurable.":"ئامانج پاراستنی خزمەتگوزاریی نزیکی یانەیە، لەگەڵ خێراتر و ڕوونترکردنی مامەڵە ڕۆژانەکان.",
  "Availability":"بەردەستبوون", "Time slots are difficult to check without calling.":"پشکنینی کاتە بەردەستەکان بەبێ پەیوەندی تەلەفۆنی ئاسان نییە.",
  "Reservations":"حجزەکان", "Phone-based and manual bookings require staff attention.":"حجزی تەلەفۆنی و دەستی کاتی ستاف دەبات.",
  "Accuracy":"وردی", "Disconnected schedules can increase avoidable booking errors.":"خشتەکاتی نەپەیوەست هەڵەی حجز زیاد دەکات.",
  "Communication":"پەیوەندی", "Updates and reminders do not reach every customer consistently.":"نوێکاری و بیرخستنەوەکان بە یەکسانی ناگەنە هەموو کڕیاران.",
  "Data":"داتا", "Booking information is harder to centralize and search.":"کۆکردنەوە و گەڕان بە زانیاریی حجز قورسترە.",
  "Reporting":"ڕاپۆرت", "Revenue, demand and peak-hour visibility can be strengthened.":"دەتوانرێت ڕوونی داهات، داواکاری و کاتە پڕکارەکان زیاد بکرێت.",
  "PROPOSED SOLUTION":"چارەسەری پێشنیارکراو", "One connected ecosystem keeps every channel in sync":"یەک سیستەمی پەیوەست هەموو کەناڵەکان هاوتا دەکات",
  "Members see one seamless experience; staff manage one reliable source of booking information.":"ئەندامان یەک ئەزموونی یەکگرتوو دەبینن و ستاف یەک سەرچاوەی باوەڕپێکراوی حجز بەڕێوەدەبات.",
  "WEBSITE":"ماڵپەڕ", "Information • News\nMembership • Booking":"زانیاری • هەواڵ\nئەندامێتی • حجز",
  "MOBILE APP":"ئەپی مۆبایل", "Android • iOS\nQR • Notifications":"Android • iOS\nQR • ئاگادارکردنەوە",
  "CLUB SERVICES":"خزمەتگوزارییەکانی یانە", "Availability\nReservations\nMembers & pricing":"بەردەستبوون\nحجزەکان\nئەندامان و نرخ",
  "ADMIN":"بەڕێوەبەر", "Schedules • Members\nReports • Revenue":"خشتەکات • ئەندامان\nڕاپۆرت • داهات",
  "STAFF":"ستاف", "Operations\nCheck-in • Support":"کارگێڕی\nتۆمارکردنی هاتن • پشتگیری",
  "SHARED MEMBER, CONTENT & BOOKING SERVICES":"خزمەتگوزاریی هاوبەشی ئەندام، ناوەڕۆک و حجز",
  "PROJECT PHASES":"قۆناغەکانی پڕۆژە", "Phase 1 delivers the paid core; later phases extend the platform":"قۆناغی ١ بنەمای پارەدراو دەگەیەنێت و قۆناغەکانی دواتر پلاتفۆرمەکە فراوان دەکەن",
  "PHASE 1":"قۆناغی ١", "Core Digital Platform":"پلاتفۆرمی دیجیتاڵی سەرەکی", "CURRENT PAID SCOPE":"مەودای پارەدراوی ئێستا",
  "• Responsive website":"• ماڵپەڕی وەڵامدەرەوە", "• Android and iOS app":"• ئەپی Android و iOS", "• Registration, login and OTP":"• تۆمارکردن، چوونەژوورەوە و OTP",
  "• Pitch and facility booking":"• حجزکردنی یاریگا و شوێنەکان", "• Live availability":"• بەردەستبوونی ڕاستەوخۆ", "• Booking history and cancellation":"• مێژووی حجز و هەڵوەشاندنەوە",
  "• Push notifications":"• ئاگادارکردنەوەی Push", "• QR booking confirmation":"• پشتڕاستکردنەوەی حجز بە QR", "• Online payment integration":"• پەیوەستکردنی پارەدانی ئۆنلاین",
  "• Payment confirmation and receipt":"• پشتڕاستکردنەوەی پارەدان و پسوڵە", "• Admin dashboard":"• داشبۆردی بەڕێوەبەر", "• Booking management":"• بەڕێوەبردنی حجز",
  "• Facility and schedule management":"• بەڕێوەبردنی شوێن و خشتەکات", "• Customer and pricing management":"• بەڕێوەبردنی کڕیار و نرخ", "• Payment status":"• دۆخی پارەدان",
  "• Basic revenue and booking reports":"• ڕاپۆرتی سەرەتایی داهات و حجز", "• Backend APIs":"• API ـەکانی پاشبنەما", "• Deployment and staff training":"• بڵاوکردنەوە و ڕاهێنانی ستاف",
  "PHASE 2":"قۆناغی ٢", "Membership & Growth":"ئەندامێتی و گەشە", "FUTURE OPTIONAL":"هەڵبژاردەی داهاتوو",
  "• Digital membership":"• ئەندامێتی دیجیتاڵی", "• Subscriptions":"• بەشداریکردن", "• Digital membership card":"• کارتی ئەندامێتی دیجیتاڵی", "• Loyalty points":"• خاڵی دڵسۆزی", "• Promotions":"• ئۆفەر و بانگەشە", "• Advanced analytics":"• شیکاریی پێشکەوتوو",
  "PHASE 3":"قۆناغی ٣", "Smart Club Services":"خزمەتگوزاریی زیرەکی یانە",
  "• Academy management":"• بەڕێوەبردنی ئەکادیمی", "• Coach booking":"• حجزکردنی ڕاهێنەر", "• Event ticketing":"• بلیتی بۆنەکان", "• Restaurant ordering":"• داواکاریی چێشتخانە",
  "• Smart access control":"• کۆنترۆڵی زیرەکی چوونەژوورەوە", "• Parking management":"• بەڕێوەبردنی پارکینگ", "• Additional services":"• خزمەتگوزاریی زیاتر",
  "Phase 2 and Phase 3 are not included in the Phase 1 price. Coovix will scope and price them separately.":"قۆناغی ٢ و ٣ لە نرخی قۆناغی ١دا نیین. Coovix مەودا و نرخەکانیان بە جیا دیاری دەکات.",
  "WEBSITE EXPERIENCE":"ئەزموونی ماڵپەڕ", "The website supports both club content and live pitch booking":"ماڵپەڕەکە ناوەڕۆکی یانە و حجزی ڕاستەوخۆی یاریگا پشتیوانی دەکات",
  "ERBIL SPORTS CLUB":"یانەی وەرزشی هەولێر", "Home":"سەرەکی", "Team":"تیم", "Fixtures":"یارییەکان", "News":"هەواڵ", "Facilities":"شوێنەکان", "MEMBER LOGIN":"چوونەژوورەوەی ئەندام",
  "WELCOME TO ERBIL SPORTS CLUB":"بەخێربێن بۆ یانەی وەرزشی هەولێر", "The pride of Hawler,\non and off the pitch.":"شانازیی هەولێر،\nلە ناو و دەرەوەی یاریگا.",
  "Fixtures, club news, membership and pitch booking - connected.":"یارییەکان، هەواڵ، ئەندامێتی و حجزی یاریگا لە یەک شوێن.", "EXPLORE THE CLUB":"یانە بناسە",
  "NEXT FIXTURE":"یاریی داهاتوو", "Erbil SC vs. Duhok":"هەولێر بەرامبەر دهۆک", "Friday | 7:30 PM":"هەینی | ٧:٣٠ پ.ن", "LATEST NEWS":"نوێترین هەواڵ", "First-team update":"نوێکاریی تیمی یەکەم", "Open the full story":"هەواڵەکە بخوێنەوە",
  "BOOK A PITCH":"حجزی یاریگا", "Training facilities":"یاریگاکانی ڕاهێنان", "Check live availability":"بەردەستبوون بپشکنە", "MY BOOKINGS":"حجزەکانی من",
  "Reserve a football pitch":"یاریگای تۆپی پێ حجز بکە", "Choose a facility and available time":"شوێن و کاتی بەردەست هەڵبژێرە", "FACILITY":"شوێن", "Training Pitch 1":"یاریگای ڕاهێنان ١", "DATE":"بەروار", "TIME":"کات", "Friday, 13 Sep":"هەینی، ١٣ی ئەیلوول", "TOTAL":"کۆی گشتی", "CONTINUE":"بەردەوام بە",
  "HOMEPAGE":"پەڕەی سەرەکی", "BOOKING PAGE":"پەڕەی حجز", "CLUB STORY  •  FIXTURES  •  NEWS  •  MEMBER ACCESS  •  LIVE AVAILABILITY  •  ONLINE PAYMENT":"مێژووی یانە • یارییەکان • هەواڵ • ئەندامان • بەردەستبوون • پارەدانی ئۆنلاین",
  "MOBILE APPLICATION":"ئەپی مۆبایل", "The mobile app makes football services easy to find and book":"ئەپی مۆبایل دۆزینەوە و حجزی خزمەتگوزارییەکانی تۆپی پێ ئاسان دەکات",
  "The primary journey moves from the club home screen to pitch selection and live availability.":"ڕێڕەوی سەرەکی لە پەڕەی سەرەکیی یانەوە بۆ هەڵبژاردنی یاریگا و بەردەستبوونی ڕاستەوخۆ دەچێت.",
  "Welcome back":"بەخێربێیتەوە", "Your club, all in\none place":"یانەکەت، هەموو شتێک\nلە یەک شوێن", "NEXT MATCH":"یاریی داهاتوو", "Match center":"ناوەندی یاری", "Training pitches":"یاریگاکانی ڕاهێنان", "Academy":"ئەکادیمی", "Club news":"هەواڵی یانە", "HOME       FIXTURES       PROFILE":"سەرەکی       یارییەکان       پرۆفایل",
  "Select a pitch":"یاریگایەک هەڵبژێرە", "Football facilities":"شوێنەکانی تۆپی پێ", "Available today":"ئەمڕۆ بەردەستە", "Training Pitch 2":"یاریگای ڕاهێنان ٢", "Next: 20:00":"دواتر: ٢٠:٠٠", "Main Pitch":"یاریگای سەرەکی", "Staff approval":"پەسەندی ستاف", "Request":"داواکاری", "VIEW AVAILABILITY":"بەردەستبوون ببینە",
  "Book a football pitch":"یاریگای تۆپی پێ حجز بکە", "Select date":"بەروار هەڵبژێرە", "THU":"پێنج", "FRI":"هەینی", "SAT":"شەممە", "Available time":"کاتی بەردەست", "CONFIRM TIME":"کات پشتڕاست بکەوە", "APP HOME":"سەرەکیی ئەپ", "PITCH SELECTION":"هەڵبژاردنی یاریگا", "BOOKING CALENDAR":"ڕۆژژمێری حجز",
  "BOOKING & PAYMENT FLOW":"ڕێڕەوی حجز و پارەدان", "Payment and booking confirmation stay in one clear journey":"پارەدان و پشتڕاستکردنەوەی حجز لە یەک ڕێڕەوی ڕووندا دەبن",
  "Phase 1 includes online payment integration, confirmation, a digital receipt and QR booking entry.":"قۆناغی ١ پەیوەستکردنی پارەدانی ئۆنلاین، پشتڕاستکردنەوە، پسوڵەی دیجیتاڵی و QR ـی چوونەژوورەوە دەگرێتەوە.",
  "Review and pay":"پێداچوونەوە و پارەدان", "Friday 18:30-20:00":"هەینی ١٨:٣٠-٢٠:٠٠", "PAYMENT METHOD":"شێوازی پارەدان", "Card ending 4242":"کارت کۆتایی بە ٤٢٤٢", "PAY 25,000 IQD":"٢٥,٠٠٠ دینار بدە", "PAYMENT":"پارەدان", "Payment successful":"پارەدان سەرکەوتوو بوو", "STATUS":"دۆخ", "PAID":"پارەدراو", "Receipt ESC-P18472":"پسوڵە ESC-P18472", "Digital receipt saved":"پسوڵەی دیجیتاڵی پاشەکەوت کرا", "VIEW BOOKING":"حجزەکە ببینە", "PAYMENT SUCCESS":"سەرکەوتنی پارەدان",
  "Booking confirmed":"حجز پشتڕاست کرایەوە", "Training Pitch 1 | Fri 18:30-20:00":"یاریگای ڕاهێنان ١ | هەینی ١٨:٣٠-٢٠:٠٠", "BOOKING ID  ESC-18472":"ناسنامەی حجز ESC-18472", "Scan at reception":"لە پێشوازی سکان بکە", "QR CONFIRMATION":"پشتڕاستکردنەوەی QR",
  "My bookings":"حجزەکانی من", "CONFIRMED":"پشتڕاستکراو", "25,000 IQD  |  PAID":"٢٥,٠٠٠ دینار | پارەدراو", "Previous booking":"حجزی پێشوو", "Training Pitch 2  |  Completed":"یاریگای ڕاهێنان ٢ | تەواوبوو", "HOME       BOOKINGS       PROFILE":"سەرەکی       حجزەکان       پرۆفایل",
  "ADMIN DASHBOARD":"داشبۆردی بەڕێوەبەر", "The dashboard connects bookings, payments and daily operations":"داشبۆردەکە حجز، پارەدان و کارگێڕیی ڕۆژانە پەیوەست دەکات", "ESC ADMIN":"بەڕێوەبەری ESC", "Overview":"پوختە", "Bookings":"حجزەکان", "Customers":"کڕیاران", "Payments":"پارەدانەکان", "Reports":"ڕاپۆرتەکان", "Today at a glance":"پوختەی ئەمڕۆ", "Utilization":"بەکارهێنان", "IQD revenue":"داهات بە دینار", "Recent booking":"نوێترین حجز", "Fri 18:30-20:00":"هەینی ١٨:٣٠-٢٠:٠٠", "PAID / CONFIRMED":"پارەدراو / پشتڕاستکراو", "Manage":"بەڕێوەبردن", "Status, cancellations and history":"دۆخ، هەڵوەشاندنەوە و مێژوو", "Schedules, availability and capacity":"خشتەکات، بەردەستبوون و ظرفیت", "Profiles, access and activity":"پرۆفایل، دەستگەیشتن و چالاکی", "Pricing & payments":"نرخ و پارەدان", "Rates, receipts and payment status":"نرخ، پسوڵە و دۆخی پارەدان", "Revenue, bookings and peak hours":"داهات، حجز و کاتە پڕکارەکان",
  "BUSINESS BENEFITS":"سوودە بازرگانییەکان", "Member convenience translates into stronger decisions":"ئاسانیی ئەندامان بڕیاری باشتر دروست دەکات", "Better experience":"ئەزموونی باشتر", "Fast self-service booking with payment and clear confirmation.":"حجزی خۆخزمەتگوزاریی خێرا لەگەڵ پارەدان و پشتڕاستکردنەوەی ڕوون.", "Lean operations":"کارگێڕیی کاراتر", "Fewer calls, repetitive tasks and manual schedule checks.":"پەیوەندی و کاری دووبارە کەمتر، لەگەڵ پشکنینی ئۆتۆماتیکی خشتەکات.", "Higher utilization":"بەکارهێنانی زیاتر", "Live availability helps staff use open capacity.":"بەردەستبوونی ڕاستەوخۆ یارمەتی ستاف دەدات کاتە بەتاڵەکان پڕ بکاتەوە.", "Fewer mistakes":"هەڵەی کەمتر", "One schedule reduces conflicts and duplicated bookings.":"یەک خشتەکات ناکۆکی و حجزە دووبارەکان کەم دەکاتەوە.", "Commercial clarity":"ڕوونیی بازرگانی", "Track payment status, pricing, revenue and demand.":"دۆخی پارەدان، نرخ، داهات و داواکاری بەدواداچوون بکە.", "Stronger brand":"براندێکی بەهێزتر", "A premium digital experience reflects the club’s ambition.":"ئەزموونێکی دیجیتاڵی پێشکەوتوو ئامانجی یانە پیشان دەدات.", "The platform improves service and visibility without assuming guaranteed revenue growth.":"پلاتفۆرمەکە خزمەتگوزاری و ڕوونی باشتر دەکات بەبێ بەڵێنی زیادبوونی داهات.",
  "PHASE 1 TIMELINE":"خشتەکاتی قۆناغی ١", "Phase 1 targets launch in 10-12 weeks":"ئامانجی دەستپێکردنی قۆناغی ١ لە ١٠-١٢ هەفتەدایە", "Workstreams overlap where practical, with review points for design, the core demo and final deployment.":"کارەکان لە شوێنی گونجاودا هاوکات دەچن، لەگەڵ پێداچوونەوەی دیزاین، دیمۆی سەرەکی و بڵاوکردنەوەی کۆتایی.", "Analysis & planning":"شیکردنەوە و پلاندانان", "1 week":"١ هەفتە", "UI/UX design":"دیزاینی UI/UX", "2 weeks":"٢ هەفتە", "Core platform & website":"پلاتفۆرمی سەرەکی و ماڵپەڕ", "3-4 weeks":"٣-٤ هەفتە", "Mobile application":"ئەپی مۆبایل", "4-5 weeks":"٤-٥ هەفتە", "Payment & system integration":"پارەدان و پەیوەستکردنی سیستەم", "1-2 weeks":"١-٢ هەفتە", "Testing, deployment & training":"تاقیکردنەوە، بڵاوکردنەوە و ڕاهێنان", "The schedule assumes timely approvals, content delivery and access to required third-party accounts.":"خشتەکاتەکە پەسەندکردنی بەکات، گەیاندنی ناوەڕۆک و دەستگەیشتن بە هەژمارە پێویستەکانی لایەنی سێیەم وەک بنەما دەگرێت.",
  "PHASE 1 COMMERCIAL PROPOSAL":"پێشنیاری بازرگانیی قۆناغی ١", "The complete Core Digital Platform is priced at $6,900 USD":"نرخی پلاتفۆرمی دیجیتاڵی سەرەکی: ٦,٩٠٠ دۆلار", "PHASE 1 INVESTMENT":"وەبەرهێنانی قۆناغی ١", "$6,900 USD":"٦,٩٠٠ دۆلار", "Target delivery: 10-12 weeks":"گەیاندنی ئامانج: ١٠-١٢ هەفتە", "INCLUDED IN PHASE 1":"لە قۆناغی ١دا لەخۆدەگرێت", "Website, Android and iOS app\nBooking, live availability and QR confirmation\nOnline payment integration and digital receipt\nAdmin dashboard, reports and Backend APIs\nDeployment and staff training":"ماڵپەڕ و ئەپی Android و iOS\nحجز، بەردەستبوونی ڕاستەوخۆ و QR\nپارەدانی ئۆنلاین و پسوڵەی دیجیتاڵی\nداشبۆرد، ڕاپۆرت و API ـەکانی پاشبنەما\nبڵاوکردنەوە و ڕاهێنانی ستاف", "PAYMENT TERMS":"مەرجەکانی پارەدان", "30% on approval":"٣٠٪ لە پەسەندکردن", "40% after core system demo":"٤٠٪ دوای دیمۆی سیستەمی سەرەکی", "30% after final deployment":"٣٠٪ دوای بڵاوکردنەوەی کۆتایی", "Phase 2 and Phase 3":"قۆناغی ٢ و قۆناغی ٣", "Future optional modules. Not included in this price and quoted separately.":"مۆدیولی هەڵبژاردەی داهاتوون و لە نرخی قۆناغی ١دا نیین.", "THIRD-PARTY FEES EXCLUDED":"تێچووی لایەنی سێیەم لەخۆناگرێت", "Payment gateway transaction fees":"کرێی مامەڵەی دەروازەی پارەدان", "Bank or provider fees":"کرێی بانک یان دابینکەر", "SMS and OTP usage fees":"کرێی بەکارهێنانی SMS و OTP", "Apple Developer fees":"کرێی Apple Developer", "Google Play fees":"کرێی Google Play", "Hosting renewal":"نوێکردنەوەی هۆستینگ", "Domain renewal":"نوێکردنەوەی دۆمەین",
  "FUTURE OPTIONAL PHASES":"قۆناغە هەڵبژاردەکانی داهاتوو", "Future phases include additional recommended club services":"قۆناغەکانی داهاتوو خزمەتگوزاریی پێشنیارکراوی زیاتری یانە دەگرنەوە", "Digital membership":"ئەندامێتی دیجیتاڵی", "Membership subscriptions":"بەشداریکردنی ئەندامێتی", "Digital membership card":"کارتی ئەندامێتی دیجیتاڵی", "Loyalty points and rewards":"خاڵ و خەڵاتی دڵسۆزی", "Promotions and offers":"بانگەشە و ئۆفەر", "Advanced reports and analytics":"ڕاپۆرت و شیکاریی پێشکەوتوو", "Corporate and family memberships":"ئەندامێتی کۆمپانیا و خێزان", "Referral and invitation program":"بەرنامەی ناساندن و بانگهێشت", "Member surveys and feedback":"ڕاپرسی و فیدباکی ئەندامان", "Automated renewal reminders":"بیرخستنەوەی ئۆتۆماتیکی نوێکردنەوە", "Sponsor benefits and partner offers":"سوودی سپۆنسەر و ئۆفەری هاوبەش", "Academy management":"بەڕێوەبردنی ئەکادیمی", "Coach booking":"حجزکردنی ڕاهێنەر", "Event ticketing":"بلیتی بۆنەکان", "Restaurant ordering":"داواکاریی چێشتخانە", "Smart access control":"کۆنترۆڵی زیرەکی چوونەژوورەوە", "Parking management":"بەڕێوەبردنی پارکینگ", "Club merchandise store":"فرۆشگای بەرهەمەکانی یانە", "Digital wallet and cashless payments":"جزدانی دیجیتاڵی و پارەدانی بێ کاش", "Digital guest passes":"مۆڵەتی دیجیتاڵی میوان", "Maintenance request management":"بەڕێوەبردنی داواکاریی چاککردنەوە", "Live match center and club media":"ناوەندی ڕاستەوخۆی یاری و میدیای یانە", "Additional club services":"خزمەتگوزاریی زیاتری یانە", "FUTURE SCOPE":"مەودای داهاتوو", "Phase 2 and Phase 3 are optional future modules outside the current Phase 1 scope. No pricing is presented for these phases.":"قۆناغی ٢ و ٣ مۆدیولی هەڵبژاردەی داهاتوون و لە مەودای قۆناغی ١دا نیین. هیچ نرخێک بۆ ئەم قۆناغانە پیشان نەدراوە.",
  "CONCLUSION":"کۆتایی", "Phase 1 gives Erbil Sports Club a complete digital operating foundation":"قۆناغی ١ بناغەیەکی دیجیتاڵی تەواو بۆ یانەی وەرزشی هەولێر دابین دەکات", "The proposed scope connects the club website, mobile booking, online payment and administration in one coordinated platform.":"مەودای پێشنیارکراو ماڵپەڕ، حجزی مۆبایل، پارەدانی ئۆنلاین و بەڕێوەبردن لە یەک پلاتفۆرمدا پەیوەست دەکات.", "Member experience":"ئەزموونی ئەندام", "Supporters can find football services, pay online and receive a confirmed booking with a QR code.":"هاندەران دەتوانن خزمەتگوزاریی تۆپی پێ بدۆزنەوە، ئۆنلاین پارە بدەن و حجزێکی پشتڕاستکراو بە QR وەربگرن.", "Operational control":"کۆنترۆڵی کارگێڕی", "Staff can manage schedules, customers, payment status and basic reports from one dashboard.":"ستاف دەتوانێت خشتەکات، کڕیاران، دۆخی پارەدان و ڕاپۆرتە سەرەتاییەکان لە یەک داشبۆرد بەڕێوەببات.", "Room to expand":"توانای فراوانبوون", "Future membership and smart club modules can be added under separately approved scopes.":"مۆدیولی ئەندامێتی و خزمەتگوزاریی زیرەکی داهاتوو دەتوانرێت بە مەودای جیاواز زیاد بکرێت.", "PROPOSED NEXT STEP":"هەنگاوی داهاتووی پێشنیارکراو", "Approve Phase 1 and begin analysis, content collection and experience design.":"قۆناغی ١ پەسەند بکەن و شیکردنەوە، کۆکردنەوەی ناوەڕۆک و دیزاینی ئەزموون دەستپێبکەن."
}));
tr.set("25,000 IQD", "٢٥,٠٠٠ دینار");

const deck = await PresentationFile.importPptx(await FileBlob.load(SOURCE));
const untranslated = [];
for (let si = 0; si < deck.slides.count; si++) {
  const slide = deck.slides.getItem(si);
  for (const shape of slide.shapes.items) {
    const original = shape.text?.toString?.();
    if (original && tr.has(original)) {
      const translated = tr.get(original);
      if (original.includes("\n") && original.split("\n").length === translated.split("\n").length) {
        const sourceLines = original.split("\n");
        const targetLines = translated.split("\n");
        sourceLines.forEach((line, index) => shape.text.replace(line, targetLines[index]));
      } else {
        shape.text.replace(original, translated);
      }
    }
    const current = shape.text?.toString?.();
    if (current && /[\u0600-\u06ff]/.test(current)) {
      shape.text.alignment = "right";
      shape.text.typeface = "Arial";
    } else if (current && /[A-Za-z]/.test(current) && !/^(Coovix|COOVIX|ERBIL SC|ESC-|IQD|Android|iOS|OTP|QR|API|UI\/UX|[A-Z0-9 .•:+&|/-]+)$/.test(current)) {
      untranslated.push(`slide ${si + 1}: ${current}`);
    }
    if (original === "$6,900 USD") shape.text.fontSize = 32;
    if (original === "Target delivery: 10-12 weeks") shape.text.fontSize = 11;
    if (original === "The complete Core Digital Platform is priced at $6,900 USD") shape.text.fontSize = 30;
    // Mirror narrative and commercial pages. Keep the detailed UI mockup geometry
    // unchanged so logos, photos, QR elements and device frames remain registered.
    if (si > 0 && !(si >= 5 && si <= 8)) {
      const p = shape.position;
      shape.position = { left: 1280 - p.left - p.width, top: p.top, width: p.width, height: p.height, rotation: p.rotation };
    }
  }
}

if (untranslated.length) console.warn("Untranslated text retained:\n" + untranslated.join("\n"));
await fs.mkdir(PREVIEW, { recursive: true });
for (let i = 0; i < deck.slides.count; i++) {
  const slide = deck.slides.getItem(i);
  const png = await deck.export({ slide, format: "png", scale: 1 });
  await fs.writeFile(`${PREVIEW}/slide-${String(i + 1).padStart(2, "0")}.png`, new Uint8Array(await png.arrayBuffer()));
}
await (await PresentationFile.exportPptx(deck)).save(RAW);

// Mark every PowerPoint paragraph as RTL and tag the run language as Sorani Kurdish.
const zip = await JSZip.loadAsync(await fs.readFile(RAW));
for (const name of Object.keys(zip.files).filter((n) => /^ppt\/slides\/slide\d+\.xml$/.test(n))) {
  let xml = await zip.file(name).async("string");
  xml = xml.replace(/<a:pPr\b([^>]*)>/g, (_m, attrs) => {
    const clean = attrs.replace(/\srtl="[^"]*"/g, "").replace(/\salgn="[^"]*"/g, "");
    return `<a:pPr${clean} rtl="1" algn="r">`;
  });
  xml = xml.replace(/<a:rPr\b([^>]*)>/g, (_m, attrs) => {
    const clean = attrs.replace(/\slang="[^"]*"/g, "");
    return `<a:rPr${clean} lang="ku-Arab">`;
  });
  xml = xml.replace(/<a:defRPr\b([^>]*)>/g, (_m, attrs) => {
    const clean = attrs.replace(/\slang="[^"]*"/g, "");
    return `<a:defRPr${clean} lang="ku-Arab">`;
  });
  zip.file(name, xml);
}
await fs.writeFile(OUT, await zip.generateAsync({ type: "nodebuffer", compression: "DEFLATE" }));
console.log(`Wrote ${OUT}`);
