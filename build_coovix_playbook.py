from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.style import WD_STYLE_TYPE
from pathlib import Path

OUT = Path('/Applications/MAMP/htdocs/coovix/Coovix_Brand_Content_Playbook_Edition_01.docx')
LOGO = Path('/Applications/MAMP/htdocs/coovix/logo.png')

BLUE = '0099D8'; NAVY = '12384C'; DEEP = '073B55'; PALE = 'E6F5FC'
MIST = 'F7FBFD'; GREY = '66818E'; LINE = 'DCEBF1'; WHITE = 'FFFFFF'; INK = '212529'

doc = Document()
sec = doc.sections[0]
sec.page_width, sec.page_height = Inches(8.5), Inches(11)
sec.top_margin = sec.bottom_margin = sec.left_margin = sec.right_margin = Inches(1)
sec.header_distance = sec.footer_distance = Inches(.49)

styles = doc.styles
normal = styles['Normal']
normal.font.name = 'Arial'; normal.font.size = Pt(10.3); normal.font.color.rgb = RGBColor.from_string(INK)
normal._element.rPr.rFonts.set(qn('w:ascii'), 'Arial')
normal._element.rPr.rFonts.set(qn('w:hAnsi'), 'Arial')
normal._element.rPr.rFonts.set(qn('w:cs'), 'Arial')
normal.paragraph_format.space_after = Pt(6)
normal.paragraph_format.line_spacing = 1.15

for name, size, color, before, after in [
    ('Title', 34, NAVY, 0, 14), ('Subtitle', 15, GREY, 0, 10),
    ('Heading 1', 22, NAVY, 18, 10), ('Heading 2', 15, BLUE, 14, 7),
    ('Heading 3', 11.5, DEEP, 9, 4)]:
    st = styles[name]
    st.font.name = 'Arial'; st.font.size = Pt(size); st.font.color.rgb = RGBColor.from_string(color)
    st.font.bold = name != 'Subtitle'
    st._element.rPr.rFonts.set(qn('w:ascii'), 'Arial'); st._element.rPr.rFonts.set(qn('w:hAnsi'), 'Arial'); st._element.rPr.rFonts.set(qn('w:cs'), 'Arial')
    st.paragraph_format.space_before = Pt(before); st.paragraph_format.space_after = Pt(after)
    st.paragraph_format.keep_with_next = True

for name, color, size in [('Kicker', BLUE, 8.5), ('Lead', NAVY, 13), ('Small', GREY, 8.5), ('RTL', INK, 10.8)]:
    st = styles.add_style(name, WD_STYLE_TYPE.PARAGRAPH)
    st.font.name = 'Arial'; st.font.size = Pt(size); st.font.color.rgb = RGBColor.from_string(color)
    st._element.rPr.rFonts.set(qn('w:ascii'), 'Arial'); st._element.rPr.rFonts.set(qn('w:hAnsi'), 'Arial'); st._element.rPr.rFonts.set(qn('w:cs'), 'Arial')
    st.paragraph_format.space_after = Pt(6); st.paragraph_format.line_spacing = 1.15
styles['Kicker'].font.bold = True
styles['Kicker'].font.all_caps = True
styles['Lead'].font.bold = True

def shade(cell, fill):
    tcPr = cell._tc.get_or_add_tcPr(); shd = OxmlElement('w:shd'); shd.set(qn('w:fill'), fill); tcPr.append(shd)

def set_cell_margins(cell, top=100, start=120, bottom=100, end=120):
    tc = cell._tc; tcPr = tc.get_or_add_tcPr(); tcMar = tcPr.first_child_found_in('w:tcMar')
    if tcMar is None: tcMar = OxmlElement('w:tcMar'); tcPr.append(tcMar)
    for m, v in [('top',top),('start',start),('bottom',bottom),('end',end)]:
        node = tcMar.find(qn('w:'+m))
        if node is None: node = OxmlElement('w:'+m); tcMar.append(node)
        node.set(qn('w:w'), str(v)); node.set(qn('w:type'), 'dxa')

def set_table_widths(table, widths):
    table.autofit = False
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    tblPr = table._tbl.tblPr
    tblW = tblPr.find(qn('w:tblW'))
    if tblW is None: tblW = OxmlElement('w:tblW'); tblPr.append(tblW)
    tblW.set(qn('w:w'), str(sum(widths))); tblW.set(qn('w:type'), 'dxa')
    tblInd = tblPr.find(qn('w:tblInd'))
    if tblInd is None: tblInd = OxmlElement('w:tblInd'); tblPr.append(tblInd)
    tblInd.set(qn('w:w'), '120'); tblInd.set(qn('w:type'), 'dxa')
    grid = table._tbl.tblGrid
    for child in list(grid): grid.remove(child)
    for w in widths:
        gc=OxmlElement('w:gridCol'); gc.set(qn('w:w'),str(w)); grid.append(gc)
    for row in table.rows:
        for i, cell in enumerate(row.cells):
            tcW=cell._tc.get_or_add_tcPr().find(qn('w:tcW'))
            if tcW is None: tcW=OxmlElement('w:tcW'); cell._tc.get_or_add_tcPr().append(tcW)
            tcW.set(qn('w:w'),str(widths[i])); tcW.set(qn('w:type'),'dxa')
            cell.width=Inches(widths[i]/1440); set_cell_margins(cell)

def rtl(p):
    p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    pPr = p._p.get_or_add_pPr(); bidi = pPr.find(qn('w:bidi'))
    if bidi is None: bidi = OxmlElement('w:bidi'); pPr.append(bidi)
    for run in p.runs:
        rPr=run._r.get_or_add_rPr(); node=rPr.find(qn('w:rtl'))
        if node is None: node=OxmlElement('w:rtl'); rPr.append(node)
        run.font.name='Arial'; run._element.rPr.rFonts.set(qn('w:cs'),'Arial')

def field(run, instruction):
    begin=OxmlElement('w:fldChar'); begin.set(qn('w:fldCharType'),'begin')
    instr=OxmlElement('w:instrText'); instr.set(qn('xml:space'),'preserve'); instr.text=instruction
    sep=OxmlElement('w:fldChar'); sep.set(qn('w:fldCharType'),'separate')
    textel=OxmlElement('w:t'); textel.text='1'
    end=OxmlElement('w:fldChar'); end.set(qn('w:fldCharType'),'end')
    run._r.extend([begin,instr,sep,textel,end])

header = sec.header
hp = header.paragraphs[0]; hp.text = 'COOVIX  /  BRAND CONTENT PLAYBOOK  /  EDITION 01'
hp.style='Small'; hp.alignment=WD_ALIGN_PARAGRAPH.LEFT
footer = sec.footer
ft = footer.add_table(rows=1, cols=2, width=Inches(6.5)); set_table_widths(ft,[7200,2160])
ft_trPr=ft.rows[0]._tr.get_or_add_trPr(); ft_rep=OxmlElement('w:tblHeader'); ft_rep.set(qn('w:val'),'true'); ft_trPr.append(ft_rep)
ft.cell(0,0).text='coovix.com  •  Designer-ready working document'
ft.cell(0,1).text=''
ft.cell(0,1).paragraphs[0].alignment=WD_ALIGN_PARAGRAPH.RIGHT
r=ft.cell(0,1).paragraphs[0].add_run('PAGE '); field(r,'PAGE')
for c in ft.row_cells(0):
    for p in c.paragraphs:
        p.style='Small'; p.paragraph_format.space_after=Pt(0)

def page(title=None, kicker=None, subtitle=None):
    if len(doc.paragraphs) or len(doc.tables): doc.add_page_break()
    if kicker: doc.add_paragraph(kicker, style='Kicker')
    if title: doc.add_paragraph(title, style='Heading 1')
    if subtitle: doc.add_paragraph(subtitle, style='Lead')

def p(text='', style=None, bold_lead=None, align=None, is_rtl=False):
    para=doc.add_paragraph(style=style)
    if bold_lead and text.startswith(bold_lead):
        para.add_run(bold_lead).bold=True; para.add_run(text[len(bold_lead):])
    else: para.add_run(text)
    if align is not None: para.alignment=align
    if is_rtl: rtl(para)
    return para

def bullet(text, is_rtl=False):
    para=doc.add_paragraph(text, style='List Bullet')
    para.paragraph_format.left_indent=Inches(.38); para.paragraph_format.first_line_indent=Inches(-.19)
    para.paragraph_format.space_after=Pt(4); para.paragraph_format.line_spacing=1.15
    if is_rtl: rtl(para)

def kv(items):
    t=doc.add_table(rows=0,cols=2); t.style='Table Grid'; set_table_widths(t,[2500,6860])
    for i,(a,b) in enumerate(items):
        cells=t.add_row().cells; cells[0].text=a; cells[1].text=b
        shade(cells[0], PALE if i%2==0 else MIST); shade(cells[1], WHITE if i%2==0 else MIST)
        cells[0].vertical_alignment=cells[1].vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
        cells[0].paragraphs[0].runs[0].bold=True
        cells[0].paragraphs[0].runs[0].font.color.rgb=RGBColor.from_string(DEEP)
        for c in cells:
            for para in c.paragraphs: para.paragraph_format.space_after=Pt(2); para.paragraph_format.line_spacing=1.08
    doc.add_paragraph().paragraph_format.space_after=Pt(0)

def section_intro(num, title, statement):
    return

# COVER
p('COOVIX', 'Kicker', align=WD_ALIGN_PARAGRAPH.CENTER)
p('', align=WD_ALIGN_PARAGRAPH.CENTER)
if LOGO.exists():
    q=doc.add_paragraph(); q.alignment=WD_ALIGN_PARAGRAPH.CENTER
    logo_shape=q.add_run().add_picture(str(LOGO), width=Inches(3.4))
    logo_shape._inline.docPr.set('title','Coovix logo')
    logo_shape._inline.docPr.set('descr','Coovix company logo in the brand blue wordmark.')
for _ in range(4): p('')
p('COOVIX BRAND\nCONTENT PLAYBOOK', 'Title', align=WD_ALIGN_PARAGRAPH.CENTER)
p('Edition 01', 'Subtitle', align=WD_ALIGN_PARAGRAPH.CENTER)
p('English  /  العربية  /  کوردی', 'Lead', align=WD_ALIGN_PARAGRAPH.CENTER)
for _ in range(5): p('')
p('Strategy • Messaging • Social Copy • Creative Direction', 'Small', align=WD_ALIGN_PARAGRAPH.CENTER)
p('Prepared for design and publishing teams  |  September 2026', 'Small', align=WD_ALIGN_PARAGRAPH.CENTER)

# TOC placeholder
page('Contents', 'NAVIGATION', 'A working map for strategy, creative production, and publishing.')
toc = doc.add_table(rows=0, cols=2); toc.style='Table Grid'; set_table_widths(toc,[8060,1300])
for label, num in [
    ('How to use this playbook',3),('Evidence and scope',4),('Brand foundation',5),
    ('Visual identity system',13),('Content strategy',21),('Post 01 — Meet Coovix',23),
    ('Post 02 — Every digital layer',28),('Post 03 — Fkrah',33),
    ('Post 04 — Barber Management',38),('Post 05 — Champions Digital Menu',43),
    ('Post 06 — The Coovix process',48),('Reel 01 — From idea to infrastructure',53),
    ('Reel 02 — Every booking has two sides',58),('Reel 03 — How Coovix builds success',63),
    ('Publishing and production',68),('Future idea bank',73),('Source register',79),('Final handoff',80)]:
    cells=toc.add_row().cells; cells[0].text=label; cells[1].text=str(num)
    cells[1].paragraphs[0].alignment=WD_ALIGN_PARAGRAPH.RIGHT
    shade(cells[0], WHITE); shade(cells[1], PALE)
p('Page numbers are fixed to this Edition 01 release.', 'Small')

page('How to use this playbook', 'START HERE', 'One source of truth from brief to published asset.')
p('This document is structured for three collaborators: the marketing lead, the copy reviewer, and the designer or motion editor. Every campaign begins with one business idea, then carries that idea consistently across English, Arabic, and Kurdish Sorani.')
kv([('Marketing lead','Select the asset, platform, publishing date, and desired action.'),('Copy reviewer','Review meaning and local fluency. Avoid word-for-word correction across languages.'),('Designer','Follow the hierarchy and production direction; keep the message legible before adding decoration.'),('Publisher','Use the approved caption, CTA, tags, alt text, and final checklist.')])
p('Working principle', 'Heading 2'); p('Educate first. Demonstrate capability second. Invite a conversation third.', 'Lead')

page('Evidence and scope', 'SOURCE STANDARD', 'Built from Coovix’s live website and portfolio source pages.')
p('The playbook uses only services, project capabilities, locations, values, and process language visible on Coovix-owned pages as of 2 September 2026. Strategic statements in this document organize that evidence; they do not add new products or unsupported performance claims.')
p('Verified service set', 'Heading 2')
for x in ['Web development','Mobile applications','UI/UX design','Custom software','Maintenance and support','Cloud, hosting and DevOps','Smart digital menus and ordering experiences']: bullet(x)
p('Verified portfolio set', 'Heading 2')
for x in ['Fkrah online-learning ecosystem','OnZone marketplace ecosystem','Barber Management Platform','Car Wash Management Platform','Champions Cafe Digital Menu','Powerful Energy Company corporate platform','Digital Menu System (listed as coming soon)']: bullet(x)

# ABOUT BRAND
section_intro(1,'Brand foundation','A precise articulation of what Coovix stands for and how it should sound.')
page('About Coovix', '01 / BRAND FOUNDATION')
p('Coovix is a team of developers and designers headquartered in Erbil, Kurdistan. It works with clients across the Middle East and beyond, combining local insight with global standards to create digital solutions that support real business operations.')
p('The company’s offer spans the full digital lifecycle: user experience, websites, mobile applications, custom systems, infrastructure, maintenance, and ongoing support. Its portfolio demonstrates this through platforms for education, commerce, appointments, hospitality, automotive services, and renewable energy.')
p('Brand summary', 'Heading 2'); p('A trusted technology partner that turns business ideas and operational needs into connected, usable digital products.', 'Lead')

page('Mission, vision and values', '01 / BRAND FOUNDATION')
kv([('Mission','Turn ideas into exceptional digital experiences that transform businesses and enhance lives.'),('Vision','A limitless vision for technological solutions: helping brands perform better, become more visible, and grow through thoughtful technology.'),('Excellence','Care in every detail, from clean code and polished interfaces to the client experience.'),('Partnership','Work with clients as collaborators and build relationships around trust and shared success.'),('Innovation','Use emerging technology purposefully to create future-ready solutions.'),('Reliability','Deliver consistent quality, dependable support, and responsible follow-through.')])
p('Editorial safeguard', 'Heading 2'); p('Values should be demonstrated through project details and process clarity. Avoid describing Coovix as “the best,” “revolutionary,” or “world-leading.”')

page('Why Coovix', '01 / BRAND FOUNDATION')
for h,b in [('End-to-end thinking','Design, product development, systems, deployment, and support can be considered as one connected experience.'),('Business-shaped systems','Projects are organized around how customers and teams actually work: learning, booking, ordering, selling, reporting, and managing content.'),('Local context, global standards','Coovix is based in Erbil and works across the Middle East and beyond.'),('Multilingual execution','Portfolio work includes English, Arabic, and Kurdish experiences.'),('Built for growth','Responsive interfaces, scalable administration, secure infrastructure, and ongoing support are part of the operating model.')]:
    p(h,'Heading 2'); p(b)

page('Unique value proposition', '01 / BRAND FOUNDATION')
p('Core proposition', 'Heading 2'); p('From customer-facing experience to the system behind it, Coovix builds the connected digital foundation a business needs to operate and grow.', 'Lead')
p('Proof structure', 'Heading 2')
kv([('Customer experience','Responsive websites, mobile apps, booking paths, digital menus, and product discovery.'),('Business control','Admin dashboards, schedules, content workflows, orders, reporting, staff, and operational visibility.'),('Technical foundation','Hosting, deployment, security configuration, monitoring, backups, maintenance, and support.'),('Partnership model','Discovery, design, development, testing, launch, and continued improvement.')])

page('Personality and voice', '01 / BRAND FOUNDATION')
kv([('Professional','Specific, composed, and useful.'),('Modern','Short sentences, clear structure, contemporary visual language.'),('Innovative','Shows what technology makes possible without turning innovation into a slogan.'),('Premium','Confident restraint; quality is visible in the details.'),('Trusted','Evidence before assertion. No unsupported claims.'),('Business-oriented','Connect features to customer journeys and operational value.'),('Technology partner','Use “we build with you” language, not vendor language.')])
p('Voice formula', 'Heading 2'); p('Clear enough for a business owner. Credible enough for a technical decision maker. Human enough to invite a conversation.', 'Lead')

page('Tone by context', '01 / BRAND FOUNDATION')
kv([('Brand awareness','Confident and spacious. Lead with one strong idea.'),('Education','Plain-language, structured, practical.'),('Portfolio','Factual, visual, and outcome-oriented. State what the platform connects or enables.'),('Lead generation','Direct and respectful. Offer a next step without pressure.'),('Support or maintenance','Calm, accountable, and precise.'),('Arabic and Kurdish','Natural business language; preserve the idea, not English sentence order.')])

page('Messaging pillars', '01 / BRAND FOUNDATION')
for h,b in [('01 — From idea to infrastructure','Coovix considers the complete path from strategy and interface to deployment and support.'),('02 — Designed around the business','Digital systems should reflect real customers, teams, workflows, and decisions.'),('03 — Connected experiences','Customer touchpoints and business-control tools work better when they are designed as one system.'),('04 — Built for every screen','Responsive, intuitive experiences should remain clear across desktop and mobile.'),('05 — Partnership that continues','Launch is a milestone, not the end of the working relationship.')]:
    p(h,'Heading 2'); p(b)

page('Writing guidelines', '01 / BRAND FOUNDATION')
for x in ['Open with the business situation, not the technology stack.','Use one central thought per asset.','Prefer concrete verbs: discover, book, manage, publish, monitor, learn, order.','Keep artwork headlines under eight words where possible.','Explain features through the journey they improve.','Use sentence case. Keep punctuation restrained.','Name the project and verified capability when presenting proof.','End with one clear next action.']: bullet(x)
p('Do / Don’t', 'Heading 2')
kv([('Do','“Customers book. Your team stays in control.”'),('Don’t','“Revolutionize your business with cutting-edge innovation.”'),('Do','“One platform for lessons, practice, revision, and support.”'),('Don’t','“The ultimate all-in-one solution for everyone.”')])

# VISUAL IDENTITY
section_intro(2,'Visual identity system','A working social-media system derived from Coovix’s current website design language.')
page('Designer guidelines', '02 / VISUAL IDENTITY')
p('The visual system should feel technical without becoming cold: bright blue signals action and connectivity; deep navy adds business credibility; generous white space keeps the work premium. Interfaces and real product screens are the primary proof.')
for x in ['Build around a clear reading order: campaign label → headline → proof → CTA.','Use one hero visual, not a collage of unrelated stock imagery.','Let device mockups show the relationship between customer experience and business control.','Keep decorative effects quiet: soft gradients, fine grid lines, subtle glow, and restrained depth.','Design English, Arabic, and Kurdish as separate exports using the same grid.']: bullet(x)

page('Color palette', '02 / VISUAL IDENTITY')
kv([('Coovix Blue — #0099D8','Primary action, key words, icons, dividers, motion traces.'),('Deep Navy — #073B55','High-authority backgrounds, headings, closing frames.'),('Brand Navy — #12384C','Editorial headings and dark interface framing.'),('Pale Blue — #E6F5FC','Information surfaces, cards, subtle section fields.'),('Mist — #F7FBFD','Soft background alternative to pure white.'),('White — #FFFFFF','Primary breathing space and reversed text.'),('Ink — #212529','Long-form copy and high-legibility text.'),('Muted Blue Grey — #66818E','Secondary labels and supporting text.')])
p('Use ratio', 'Heading 2'); p('Aim for approximately 65% white or mist, 20% navy, 10% Coovix blue, and 5% supporting neutrals. This is a production recommendation derived from the current site—not a replacement for a formal identity manual.')

page('Typography', '02 / VISUAL IDENTITY')
kv([('Latin','Poppins, matching the website. Use SemiBold/Bold for display and Regular/Medium for body.'),('Arabic','Noto Sans Arabic or a clean equivalent with strong screen legibility.'),('Kurdish Sorani','Rudaw for brand continuity; use a Unicode-safe fallback for production exports.'),('Numerals','Use one numeral system consistently inside each language version.'),('Display scale','Headline 48–72 px at 1080 × 1350; subheadline 25–34 px; labels 18–22 px.'),('Caption-safe copy','Do not place full captions on artwork. Keep supporting artwork copy to two short lines.')])
p('Typesetting rule', 'Heading 2'); p('Match visual weight—not point size—across scripts. Arabic and Sorani may need slightly more line height and fewer words per line.')

page('Photography and illustration', '02 / VISUAL IDENTITY')
p('Photography style', 'Heading 2')
for x in ['Real business contexts: teams, customers, devices, classrooms, restaurants, shops, and service environments.','Natural directional light with controlled contrast; avoid neon “future tech” imagery.','Candid working moments over posed handshakes.','Frame screens so interface details remain readable and glare-free.']: bullet(x)
p('Illustration style', 'Heading 2')
for x in ['Geometric, modular, and connected.','Thin blue paths may connect customer touchpoints to dashboards.','Use abstract systems only when a real UI screen is unavailable.','Avoid generic robots, brains, floating holograms, and circuit-board clichés.']: bullet(x)

page('Icons, layout and white space', '02 / VISUAL IDENTITY')
kv([('Icons','Simple outline icons with consistent 2 px stroke at social scale. Use filled icons only for small status badges.'),('Layout','Editorial grid, strong left edge in English, strong right edge in Arabic and Sorani.'),('White space','Keep at least 8% clear margin around the canvas. Preserve quiet space around logo and CTA.'),('Cards','Use 12–20 px corner radius, pale fill, thin border, restrained shadow.'),('UI mockups','Use real project screens in clean browser or device frames. Never distort perspective beyond readability.'),('Hierarchy','One dominant headline, one supporting proof layer, one action.')])

page('Logo and CTA placement', '02 / VISUAL IDENTITY')
p('Logo placement', 'Heading 2'); p('Place the logo in a consistent top corner or bottom lock-up. Use the opposite corner from the primary CTA. Maintain clear space equal to the height of the “C” symbol where practical. Never place the logo over a busy interface area.')
p('CTA placement', 'Heading 2'); p('Treat the CTA as navigation, not decoration. Use a compact button or text-arrow lock-up near the final reading point. Reels should reserve the last 1.5–2 seconds for the CTA and logo.')
p('Recommended CTA family', 'Heading 2')
for x in ['Explore our work','See how it works','Start your project','Build with Coovix','Talk to Coovix']: bullet(x)

page('Carousel and feed consistency', '02 / VISUAL IDENTITY')
kv([('Feed rhythm','Alternate bright editorial cards, dark authority frames, and interface-led portfolio posts.'),('Carousel cover','One promise; no more than eight headline words.'),('Interior slides','One point per slide with a stable page marker and repeated margin system.'),('Final slide','Summarize the value and present one CTA.'),('Language exports','Keep composition stable, but mirror the grid for Arabic and Sorani.'),('Thumbnail test','At 25% scale, the subject, headline, and brand must still be identifiable.')])

page('Reel and animation style', '02 / VISUAL IDENTITY')
for h,b in [('Motion behavior','Smooth, purposeful, and interface-led. Use 250–450 ms movements; reserve faster cuts for problem statements.'),('Transitions','Mask reveals, vertical scrolls, match cuts between device frames, and line-trace connections.'),('UI motion','Cursor, tap, scroll, and dashboard-state changes should demonstrate a real journey.'),('Text motion','Short phrase builds; no bouncing word-by-word captions.'),('Sound','Modern minimal electronic beds with subtle interface clicks. Avoid aggressive trailer music.'),('Accessibility','Burn in captions, maintain high contrast, and keep essential text inside platform-safe zones.')]:
    p(h,'Heading 2'); p(b)

# STRATEGY
section_intro(3,'Content strategy','A connected system that moves from recognition to understanding to conversation.')
page('Content roles', '03 / CONTENT STRATEGY')
kv([('Educational','Explain how digital systems improve a customer journey or operating workflow.'),('Authority','Show Coovix’s end-to-end thinking and development process.'),('Trust','Use real project screens, multilingual capability, and precise descriptions.'),('Portfolio','Demonstrate what was built and how its parts connect.'),('Lead generation','Invite a relevant conversation after value has been established.')])

p('Audience priorities', 'Heading 1')
kv([('Business owners','Clarity, control, customer experience, and practical next steps.'),('Startups','A partner who can shape, build, and launch a coherent product.'),('SMEs','Systems that reduce fragmentation and support growth.'),('Enterprises','Scalable platforms, clear administration, multilingual experiences, and reliability.'),('Organizations','Accessible digital services and structured content management.'),('Government and decision makers','Professional delivery, clarity, accountability, and systems built around users and operations.')])

p('Campaign architecture', 'Heading 1')
kv([('Campaign 1 — Meet Coovix','Posts 1–2 and Reel 1. Establish the partner position and complete capability.'),('Campaign 2 — Digital Transformation','Posts 3–4 and Reel 2. Prove how connected platforms improve learning and service operations.'),('Campaign 3 — How We Build Success','Posts 5–6 and Reel 3. Show practical customer journeys and the delivery process.')])
p('Narrative flow', 'Heading 2'); p('Who Coovix is → what connected technology changes → how Coovix turns that change into a reliable product.', 'Lead')

page('Publishing principles', '03 / CONTENT STRATEGY')
for x in ['Publish two feed posts and one reel per week for this launch sequence.','Use Instagram and Facebook for multilingual reach and visual proof.','Use LinkedIn for business framing, project decisions, and process authority.','Treat all “best time” guidance as a starting hypothesis; optimize from account insights after four weeks.','Respond to comments in the language used by the audience member.','Use portfolio screens only at a size where the interface remains understandable.']: bullet(x)
p('Recommended starting windows — Erbil time', 'Heading 2')
kv([('Instagram / Facebook','Sunday–Thursday, 12:00–2:00 PM or 7:00–9:00 PM.'),('LinkedIn','Sunday–Thursday, 9:00–11:00 AM.'),('Reels','Tuesday or Thursday, 7:00–9:00 PM.')])

posts = [
dict(n=1,c='Campaign 1 — Meet Coovix',title='Your digital partner. From idea to infrastructure.',objective='Introduce Coovix as an end-to-end technology partner.',audience='Business owners, startups, SMEs, decision makers',platform='Instagram, Facebook, LinkedIn',graphic='Single-image brand statement',goal='Awareness and positioning',pain='Businesses often coordinate separate teams for design, development, infrastructure, and support.',key='Coovix connects the full digital journey through one partnership.',sub='Strategy, design, development, infrastructure, and support—connected.',hook='A strong digital product needs more than code.',body='Coovix brings the customer experience, business system, and technical foundation into one clear delivery path.',caption='A digital product should feel connected from the first idea to the day it goes live—and long after. Coovix designs and builds websites, mobile applications, custom systems, and the infrastructure that keeps them running. One partner. One clear path from idea to infrastructure.',cta='Start your project',ar_h='شريكك الرقمي. من الفكرة إلى البنية التحتية.',ar_s='استراتيجية وتصميم وتطوير وبنية تحتية ودعم—ضمن مسار واحد.',ar_hook='المنتج الرقمي القوي يحتاج إلى أكثر من البرمجة.',ar_body='تجمع Coovix تجربة العميل ونظام العمل والأساس التقني ضمن رحلة تنفيذ واضحة ومتكاملة.',ar_cap='يجب أن يكون المنتج الرقمي مترابطاً منذ لحظة الفكرة وحتى الإطلاق وما بعده. تصمم Coovix المواقع والتطبيقات والأنظمة المخصصة، وتبني البنية التحتية التي تحافظ على استمراريتها. شريك واحد ومسار واضح من الفكرة إلى البنية التحتية.',ar_cta='ابدأ مشروعك',ku_h='هاوبەشی دیجیتاڵیت. لە بیرۆکەوە بۆ ژێرخان.',ku_s='ستراتیژی، دیزاین، گەشەپێدان، ژێرخان و پشتگیری—لە یەک ڕێڕەودا.',ku_hook='بەرهەمێکی دیجیتاڵی بەهێز تەنها کۆد نییە.',ku_body='Coovix ئەزموونی کڕیار، سیستەمی کار و بنەمای تەکنیکی لە یەک پرۆسەی ڕوون و بەستراودا کۆدەکاتەوە.',ku_cap='بەرهەمێکی دیجیتاڵی دەبێت لە یەکەم بیرۆکەوە تا ڕۆژی بڵاوکردنەوە و دوای ئەوەش بەستراو بێت. Coovix وێبسایت، ئەپی مۆبایل و سیستەمی تایبەت دیزاین و دروست دەکات، لەگەڵ ئەو ژێرخانەی کە بەردڵەوەی کارکردنیان دەپارێزێت. یەک هاوبەش، یەک ڕێڕەوی ڕوون.',ku_cta='پرۆژەکەت دەست پێ بکە',image='Minimal Coovix workspace with a website, mobile app, dashboard, and server layer connected by one blue line.',icons='Compass, interface window, mobile device, server, support',layout='White editorial field; oversized headline left; connected device stack right; blue CTA bottom-right.',anim='Blue path draws from idea card through interface, code, cloud, and live product.'),
dict(n=2,c='Campaign 1 — Meet Coovix',title='One partner. Every digital layer.',objective='Explain the verified Coovix service ecosystem without presenting a generic service list.',audience='SMEs, enterprises, organizations, technical and commercial decision makers',platform='Instagram carousel, Facebook, LinkedIn',graphic='Six-slide service carousel',goal='Consideration and service understanding',pain='Disconnected vendors create gaps between customer experience, operations, and infrastructure.',key='Coovix aligns design, software, infrastructure, and support.',sub='Built to launch, scale, and sustain your digital presence.',hook='The interface is only one part of the product.',body='Coovix connects web development, mobile applications, UI/UX, custom software, maintenance, and cloud operations around one business goal.',caption='What customers see and what your team manages should work as one product. Coovix connects responsive web experiences, mobile applications, user-first design, custom systems, hosting, DevOps, maintenance, and support around the way your business needs to operate.',cta='Explore our services',ar_h='شريك واحد. لكل طبقات مشروعك الرقمي.',ar_s='حلول مصممة للإطلاق والنمو واستمرار حضورك الرقمي.',ar_hook='الواجهة ليست سوى جزء واحد من المنتج.',ar_body='تربط Coovix تطوير الويب والتطبيقات وتصميم UI/UX والأنظمة المخصصة والصيانة والعمليات السحابية بهدف تجاري واحد.',ar_cap='ما يراه العميل وما يديره فريقك يجب أن يعملا كمنتج واحد. تربط Coovix تجارب الويب المتجاوبة والتطبيقات والتصميم المتمحور حول المستخدم والأنظمة المخصصة والاستضافة وDevOps والصيانة والدعم بطريقة عمل نشاطك.',ar_cta='استكشف خدماتنا',ku_h='یەک هاوبەش. بۆ هەموو چینە دیجیتاڵییەکان.',ku_s='دروستکراوە بۆ دەستپێکردن، گەشە و بەردەوامی ئامادەبوونی دیجیتاڵیت.',ku_hook='ڕووکار تەنها بەشێکە لە بەرهەمەکە.',ku_body='Coovix گەشەپێدانی وێب، ئەپی مۆبایل، UI/UX، سیستەمی تایبەت، چاکسازی و کاری کلاود بە یەک ئامانجی بازرگانی دەبەستێتەوە.',ku_cap='ئەوەی کڕیار دەیبینێت و ئەوەی تیمەکەت بەڕێوەی دەبات، دەبێت وەک یەک بەرهەم کار بکەن. Coovix وێب، ئەپ، دیزاینی بەکارهێنەر، سیستەمی تایبەت، میوانداری، DevOps و پشتگیری بە شێوازی کاری بازرگانییەکەت دەبەستێتەوە.',ku_cta='خزمەتگوزارییەکانمان ببینە',image='Exploded product architecture: customer-facing screens above, management dashboard in the middle, infrastructure below.',icons='Web, smartphone, pen tool, database, cloud, shield',layout='Carousel: cover, customer layer, business layer, infrastructure layer, support layer, CTA.',anim='Layered cards assemble into one coherent product stack.'),
dict(n=3,c='Campaign 2 — Digital Transformation',title='Learning, connected in one platform.',objective='Use Fkrah as proof of Coovix’s ability to build a multi-surface learning ecosystem.',audience='Education businesses, institutions, organizations, product founders',platform='Instagram carousel, Facebook, LinkedIn',graphic='Portfolio case-study carousel',goal='Authority and trust',pain='Students lose focus when courses, practice, revision, and support are fragmented.',key='Fkrah connects the learning journey across web, iOS, Android, and administration.',sub='Courses, practice, flashcards, and AI assistance—working together.',hook='A learning platform should support the whole study journey.',body='Fkrah brings structured video courses, practice exams, smart flashcards, and an AI learning assistant into one focused ecosystem.',caption='Fkrah brings learning, practice, revision, and academic support into one connected experience. Students access video courses, practice exams, smart flashcards, and AI assistance across a responsive website and mobile applications, while the Fkrah team manages teachers, content, and users through a centralized system.',cta='Explore the Fkrah project',ar_h='تعلّم مترابط في منصة واحدة.',ar_s='دروس واختبارات وبطاقات ذكية ومساعدة بالذكاء الاصطناعي—تعمل معاً.',ar_hook='منصة التعليم الجيدة تدعم رحلة الدراسة كاملة.',ar_body='تجمع Fkrah دروس الفيديو والاختبارات التدريبية والبطاقات الذكية ومساعد التعلم بالذكاء الاصطناعي ضمن منظومة واحدة.',ar_cap='تجمع Fkrah التعلم والتدريب والمراجعة والدعم الأكاديمي في تجربة مترابطة. يصل الطلبة إلى دروس الفيديو والاختبارات والبطاقات الذكية والمساعدة بالذكاء الاصطناعي عبر الموقع والتطبيقات، بينما يدير فريق Fkrah المدرسين والمحتوى والمستخدمين من خلال نظام مركزي.',ar_cta='استكشف مشروع Fkrah',ku_h='فێربوون، لە یەک پلاتفۆرمدا بەستراوە.',ku_s='وانە، تاقیکردنەوە، فلەشکارت و یارمەتی زیرەکی دەستکرد—پێکەوە کار دەکەن.',ku_hook='پلاتفۆرمی فێربوون دەبێت تەواوی گەشتی خوێندن پشتگیری بکات.',ku_body='Fkrah وانە ڤیدیۆییەکان، تاقیکردنەوەی ڕاهێنان، فلەشکارتی زیرەک و یاریدەدەری فێربوونی AI لە یەک ژینگەدا کۆدەکاتەوە.',ku_cap='Fkrah فێربوون، ڕاهێنان، پێداچوونەوە و پشتگیری ئەکادیمی لە یەک ئەزموونی بەستراودا کۆدەکاتەوە. قوتابیان لە وێبسایت و ئەپەکاندا دەگەن بە وانە ڤیدیۆییەکان، تاقیکردنەوە، فلەشکارت و یارمەتی AI؛ تیمی Fkrahیش مامۆستا، ناوەڕۆک و بەکارهێنەر لە سیستەمێکی ناوەندییەوە بەڕێوە دەبات.',ku_cta='پرۆژەی Fkrah ببینە',image='Real Fkrah screens on laptop and phones, supported by course, exam, flashcard, and AI feature cards.',icons='Play, exam sheet, cards, AI sparkle, teacher, dashboard',layout='Dark navy cover; product ecosystem revealed across four interior slides; final Coovix CTA.',anim='Student journey moves from lesson to practice to recall to assistance.'),
dict(n=4,c='Campaign 2 — Digital Transformation',title='Customers book. Your team stays in control.',objective='Show how the Barber Management Platform connects acquisition, booking, and daily operations.',audience='Service businesses, barbershops, salons, SMEs',platform='Instagram, Facebook, LinkedIn',graphic='Before/after process carousel',goal='Portfolio proof and qualified interest',pain='Phone-based appointments, schedule conflicts, and disconnected daily records create avoidable work.',key='A customer website and management dashboard can operate as one booking system.',sub='A smoother appointment journey for customers. Clearer control for the business.',hook='Every booking has two sides.',body='Customers discover services, choose a barber, select an available time, and submit a booking. The business manages appointments, schedules, customers, services, and reports from one dashboard.',caption='The Barber Management Platform was designed around both sides of the appointment. Customers get a fast, mobile-ready booking experience. The business gets a focused dashboard for bookings, staff schedules, customers, services, and reporting. One connected flow from discovery to the working day.',cta='See the platform',ar_h='العميل يحجز. وفريقك يبقى مسيطراً.',ar_s='رحلة أسهل للعميل وتحكم أوضح للأعمال.',ar_hook='لكل حجز جانبان.',ar_body='يستكشف العميل الخدمات ويختار الحلاق والوقت المتاح ويرسل الحجز. ويدير النشاط المواعيد والجداول والعملاء والخدمات والتقارير من لوحة واحدة.',ar_cap='صُممت منصة إدارة الحلاقة حول جانبي الموعد. يحصل العميل على تجربة حجز سريعة ومهيأة للموبايل، ويحصل النشاط على لوحة مركزة لإدارة الحجوزات وجداول الموظفين والعملاء والخدمات والتقارير. مسار مترابط من اكتشاف الخدمة إلى إدارة يوم العمل.',ar_cta='شاهد المنصة',ku_h='کڕیار کات دەگرێت. تیمەکەت کۆنترۆڵی دەبێت.',ku_s='گەشتێکی ئاسانتر بۆ کڕیار؛ بەڕێوەبردنێکی ڕوونتر بۆ بازرگانی.',ku_hook='هەر حجزێک دوو لایەنی هەیە.',ku_body='کڕیار خزمەتگوزاری دەبینێت، دەلاک هەڵدەبژێرێت، کاتی بەردەست دیاری دەکات و حجزەکە دەنێرێت. بازرگانییەکەش حجز، کات، کڕیار، خزمەتگوزاری و ڕاپۆرت لە یەک داشبۆردەوە بەڕێوە دەبات.',ku_cap='پلاتفۆرمی بەڕێوەبردنی دەلاکخانە بۆ هەردوو لایەنی حجز دیزاین کراوە. کڕیار ئەزموونێکی خێرا و گونجاوی مۆبایل وەردەگرێت؛ بازرگانییەکەش داشبۆردێکی ڕوون بۆ حجز، کاتی ستاف، کڕیار، خزمەتگوزاری و ڕاپۆرت. یەک ڕێڕەوی بەستراو لە دۆزینەوەی خزمەتگوزاری تا بەڕێوەبردنی ڕۆژی کار.',ku_cta='پلاتفۆرمەکە ببینە',image='Split composition: customer booking flow on mobile and operations dashboard on desktop.',icons='Service, barber profile, calendar, confirmation, analytics',layout='Five-slide journey: problem, customer side, business side, connected value, CTA.',anim='Appointment card travels from mobile booking into the dashboard schedule.'),
dict(n=5,c='Campaign 3 — How We Build Success',title='Scan the table. Explore the menu.',objective='Present Champions Cafe as a focused example of mobile-first hospitality design.',audience='Restaurant and café owners, hospitality groups, decision makers',platform='Instagram, Facebook, LinkedIn',graphic='Four-slide product journey',goal='Education, portfolio, and lead generation',pain='Printed menus are difficult to update and cannot adapt easily to multilingual guests.',key='A branded QR touchpoint can lead to a fast, multilingual, visual menu.',sub='No app. No account. A menu designed for the phone.',hook='The guest experience can begin with one scan.',body='Champions Cafe’s digital menu connects a branded table QR code with a responsive menu in English, Arabic, and Kurdish.',caption='At Champions Cafe, the table and the phone are part of one menu experience. Guests scan a branded QR code, choose a language, browse visual categories, and see product names and prices without installing an app or creating an account. Simple at the table. Clear on every modern phone.',cta='Explore the live menu',ar_h='امسح الرمز. واستكشف القائمة.',ar_s='لا تطبيق ولا حساب. قائمة مصممة للموبايل.',ar_hook='يمكن أن تبدأ تجربة الضيف بمسح واحد.',ar_body='تربط قائمة Champions Cafe الرقمية رمز QR على الطاولة بقائمة متجاوبة بالإنجليزية والعربية والكردية.',ar_cap='في Champions Cafe تشكل الطاولة والهاتف تجربة قائمة واحدة. يمسح الضيف رمز QR المصمم بهوية المقهى، ويختار اللغة، ويتصفح الأقسام المصورة، ويرى أسماء المنتجات والأسعار دون تنزيل تطبيق أو إنشاء حساب. بسيطة على الطاولة وواضحة على كل هاتف حديث.',ar_cta='استكشف القائمة المباشرة',ku_h='کۆدەکە سکەن بکە. مینیوەکە ببینە.',ku_s='بێ ئەپ و بێ هەژمار. مینیوێک بۆ مۆبایل.',ku_hook='ئەزموونی میوان دەتوانێت بە یەک سکەن دەست پێ بکات.',ku_body='مێنوی دیجیتاڵی Champions Cafe کۆدی QRی براندکراوی سەر مێز بە مینیوێکی گونجاوی ئینگلیزی، عەرەبی و کوردی دەبەستێتەوە.',ku_cap='لە Champions Cafe، مێز و مۆبایل یەک ئەزموونی مینیو پێکدەهێنن. میوان کۆدی QRی براندکراو سکەن دەکات، زمان هەڵدەبژێرێت، بەشە وێنەییەکان دەگەڕێت و ناو و نرخی بەرهەم دەبینێت—بێ داگرتنی ئەپ یان دروستکردنی هەژمار. ئاسان لەسەر مێز و ڕوون لەسەر مۆبایل.',ku_cta='مێنووە ڕاستەوخۆکە ببینە',image='Premium café table with branded QR card; phone displays the real Champions menu.',icons='QR, language, category grid, product image, mobile',layout='Warm photography paired with Coovix-blue UI annotations and a clean white caption field.',anim='Camera approaches QR, scan line activates, phone interface opens and scrolls.'),
dict(n=6,c='Campaign 3 — How We Build Success',title='A clear process creates a stronger product.',objective='Explain Coovix’s published website and app development process.',audience='Founders, business owners, organizations, procurement and project leads',platform='Instagram carousel, Facebook, LinkedIn',graphic='Process carousel',goal='Trust and conversion',pain='Digital projects feel risky when decisions, milestones, and responsibilities are unclear.',key='Coovix moves from discovery and design through development, testing, launch, and support.',sub='Discover. Design. Develop. Test. Launch. Support.',hook='Confidence begins before development.',body='Coovix starts by understanding requirements, audience, and business goals—then turns them into a roadmap, interface, tested product, and supported launch.',caption='A dependable digital product is built through clear decisions. Coovix begins with discovery and planning, shapes the experience through UI/UX, develops with modern technology, tests across devices and scenarios, and supports the product through launch and continued updates. A clear path. Shared visibility. A product built around the goal.',cta='Build with Coovix',ar_h='العملية الواضحة تصنع منتجاً أقوى.',ar_s='اكتشاف. تصميم. تطوير. اختبار. إطلاق. دعم.',ar_hook='الثقة تبدأ قبل مرحلة التطوير.',ar_body='تبدأ Coovix بفهم المتطلبات والجمهور وأهداف العمل، ثم تحولها إلى خارطة طريق وواجهة ومنتج مختبر وإطلاق مدعوم.',ar_cap='يُبنى المنتج الرقمي الموثوق من خلال قرارات واضحة. تبدأ Coovix بالاكتشاف والتخطيط، ثم تصوغ التجربة عبر UI/UX، وتطوّر باستخدام تقنيات حديثة، وتختبر على أجهزة وسيناريوهات مختلفة، وتدعم المنتج عند الإطلاق وبعده. مسار واضح ورؤية مشتركة ومنتج مبني حول الهدف.',ar_cta='ابنِ مشروعك مع Coovix',ku_h='پرۆسەیەکی ڕوون بەرهەمێکی بەهێزتر دروست دەکات.',ku_s='دۆزینەوە. دیزاین. گەشەپێدان. تاقیکردنەوە. بڵاوکردنەوە. پشتگیری.',ku_hook='متمانە پێش گەشەپێدان دەست پێ دەکات.',ku_body='Coovix سەرەتا پێداویستی، بینەری ئامانج و ئامانجی بازرگانی تێدەگات؛ پاشان دەیانگۆڕێت بۆ نەخشەی ڕێگا، ڕووکار، بەرهەمی تاقیکراو و بڵاوکردنەوەی پشتگیریکراو.',ku_cap='بەرهەمێکی دیجیتاڵی متمانەپێکراو بە بڕیاری ڕوون دروست دەبێت. Coovix بە دۆزینەوە و پلاندانان دەست پێ دەکات، ئەزموونەکە بە UI/UX دادەڕێژێت، بە تەکنەلۆژیای نوێ گەشەی پێدەدات، لە ئامێر و سیناریۆی جیاواز تاقی دەکاتەوە و لە بڵاوکردنەوە و دوای ئەوە پشتگیری دەکات. ڕێڕەوێکی ڕوون، دیدێکی هاوبەش و بەرهەمێک لەسەر بنەمای ئامانج.',ku_cta='لەگەڵ Coovix دروستی بکە',image='A five-stage horizontal system built from real artifacts: brief, wireframe, code, QA device grid, live product.',icons='Search, pen tool, code, check, rocket, support',layout='Six-slide process: cover plus one stage per slide, with support folded into launch slide.',anim='A precise blue progress line advances stage by stage; artifacts resolve from outline to finished UI.')
]

def lang_page(post, lang, new_page=True):
    labels={'en':('English version','Headline','Subheadline','Hook','Body copy','Caption','Call to action'), 'ar':('النسخة العربية','العنوان','العنوان الفرعي','الافتتاحية','النص الأساسي','التعليق','الدعوة إلى الإجراء'), 'ku':('وەشانی کوردی — سۆرانی','سەردێڕ','ژێر سەردێڕ','دەستپێک','دەقی سەرەکی','کەپشن','بانگهێشت بۆ هەنگاو')}
    vals = {'en':(post['title'],post['sub'],post['hook'],post['body'],post['caption'],post['cta']), 'ar':(post['ar_h'],post['ar_s'],post['ar_hook'],post['ar_body'],post['ar_cap'],post['ar_cta']), 'ku':(post['ku_h'],post['ku_s'],post['ku_hook'],post['ku_body'],post['ku_cap'],post['ku_cta'])}
    L=labels[lang]; V=vals[lang]
    if new_page:
        page(L[0], f"POST {post['n']:02d} / COPY")
    else:
        p(L[0], 'Heading 1', is_rtl=lang!='en')
    for lab,val in zip(L[1:],V):
        pp=p(lab,'Heading 2',is_rtl=lang!='en'); pp=p(val,'Lead' if lab in [L[1],L[3]] else None,is_rtl=lang!='en')
    tag = '#Coovix #DigitalSolutions #WebDevelopment #MobileApps #BusinessSystems' if lang=='en' else ('#كوفكس #حلول_رقمية #تطوير_الويب #تطبيقات #أنظمة_الأعمال' if lang=='ar' else '#Coovix #چارەسەری_دیجیتاڵی #گەشەپێدانی_وێب #ئەپی_مۆبایل')
    p('Hashtags' if lang=='en' else ('الوسوم' if lang=='ar' else 'هاشتاگ'),'Heading 2',is_rtl=lang!='en'); p(tag,is_rtl=lang!='en')

for post in posts:
    page(post['title'], f"POST {post['n']:02d} / {post['c'].upper()}", post['key'])
    kv([('Objective',post['objective']),('Audience',post['audience']),('Platform',post['platform']),('Graphic type',post['graphic']),('Marketing goal',post['goal']),('Customer pain point',post['pain']),('Key message',post['key'])])
    p('Keywords', 'Heading 2'); p('technology partner, connected digital experience, business systems, responsive design, Coovix')
    p('SEO keywords', 'Heading 2'); p('software company Erbil, web development Kurdistan, mobile app development Iraq, custom business systems, multilingual digital platforms')
    lang_page(post,'en',True)
    lang_page(post,'ar',False)
    lang_page(post,'ku',True)
    page('Designer production brief', f"POST {post['n']:02d} / CREATIVE DIRECTION")
    kv([('Suggested image',post['image']),('Suggested illustration','Modular blue system lines connecting the key steps or product surfaces.'),('Suggested icons',post['icons']),('Suggested layout',post['layout']),('Suggested animation',post['anim']),('Photography direction','Natural, business-real context; crisp screens; controlled light; no posed corporate clichés.'),('Color direction','White or mist foundation, Coovix blue for action, deep navy for authority, muted blue-grey for support.'),('Typography direction','Poppins Latin; Noto Sans Arabic; Rudaw or Unicode-safe Sorani equivalent. Match visual weight across scripts.'),('Graphic hierarchy','Campaign label → headline → hero proof → supporting line → CTA → logo.'),('Background','Subtle white-to-pale-blue gradient with fine structural grid or quiet interface texture.'),('Lighting','Soft directional light; readable screens; restrained shadow and glow.'),('Composition','Keep one dominant focal point and at least 8% safe margin.'),('UI mockups','Use authentic Coovix/project screens. Do not fabricate product states.'),('Spacing','Minimum 24 px internal card padding; 48–72 px between major groups at 1080 × 1350.'),('Logo placement','Top-left in English; top-right in Arabic/Sorani, or consistent bottom lock-up.'),('CTA placement','Opposite the logo at the final reading point; short text-arrow treatment.'),('Designer notes','Create independent English, Arabic, and Sorani exports. Mirror alignment for RTL; do not merely flip interface screenshots.')])

reels=[
dict(n=1,c='Campaign 1 — Meet Coovix',title='From idea to infrastructure',duration='20 seconds',goal='Position Coovix as the connected technology partner.',aud='Business owners, startups, SMEs, decision makers',scenes=[('0–3s','An idea note appears on a clean white field.','Your business has an idea.'),('3–7s','The note becomes a wireframe and polished web screen.','We shape the experience.'),('7–11s','A mobile app and dashboard join the website.','We build every digital layer.'),('11–16s','Cloud, security, deployment, and monitoring connect beneath.','From product to infrastructure.'),('16–20s','All elements resolve into the Coovix logo and CTA.','Build with Coovix.')],en_vo='A business idea becomes valuable when every part works together. Coovix shapes the experience, builds the product, and supports the infrastructure behind it. From idea to infrastructure.',ar_vo='تتحول فكرة العمل إلى قيمة عندما تعمل كل أجزائها معاً. تصوغ Coovix التجربة، وتبني المنتج، وتدعم البنية التحتية التي تقف خلفه. من الفكرة إلى البنية التحتية.',ku_vo='بیرۆکەی بازرگانی کاتێک دەبێتە بەها کە هەموو بەشەکانی پێکەوە کار بکەن. Coovix ئەزموونەکە دادەڕێژێت، بەرهەمەکە دروست دەکات و پشتگیری ژێرخانەکەی دەکات. لە بیرۆکەوە بۆ ژێرخان.',cta=('Build with Coovix','ابنِ مشروعك مع Coovix','لەگەڵ Coovix دروستی بکە')),
dict(n=2,c='Campaign 2 — Digital Transformation',title='Every booking has two sides',duration='25 seconds',goal='Demonstrate connected customer and business workflows through the barber platform.',aud='Service-business owners and SME decision makers',scenes=[('0–4s','Phone rings; paper calendar and messages overlap.','Booking should not slow the day down.'),('4–8s','Customer opens the mobile-ready website.','Choose a service.'),('8–12s','Barber profile and available times appear.','Choose the right person and time.'),('12–18s','Booking enters the management dashboard.','Your team sees the day clearly.'),('18–25s','Split view: happy customer and organized dashboard.','Customers book. Your team stays in control.')],en_vo='Every appointment has a customer side and a business side. Coovix connected both in one barber management platform—from service discovery and booking to schedules, customers, and reporting.',ar_vo='لكل موعد جانب يخص العميل وجانب يخص العمل. ربطت Coovix الجانبين في منصة واحدة لإدارة الحلاقة، من اكتشاف الخدمة والحجز إلى الجداول والعملاء والتقارير.',ku_vo='هەر حجزێک لایەنی کڕیار و لایەنی بازرگانی هەیە. Coovix هەردووکی لە یەک پلاتفۆرمی بەڕێوەبردنی دەلاکخانەدا بەستەوە؛ لە دۆزینەوەی خزمەتگوزاری و حجزەوە تا کات، کڕیار و ڕاپۆرت.',cta=('See the platform','شاهد المنصة','پلاتفۆرمەکە ببینە')),
dict(n=3,c='Campaign 3 — How We Build Success',title='How Coovix builds success',duration='30 seconds',goal='Build process confidence and connect the full campaign.',aud='Founders, organizations, enterprises, project and procurement leads',scenes=[('0–5s','Discovery notes align into a clear brief.','Understand the goal.'),('5–10s','Wireframes become a polished UI.','Design the experience.'),('10–16s','Code and system modules connect.','Build the product.'),('16–22s','Device grid shows tests passing.','Test every critical path.'),('22–30s','Live product, monitoring view, and Coovix lock-up.','Launch with confidence. Continue with support.')],en_vo='Strong digital products begin with clear decisions. We understand the goal, design the experience, build the system, test the critical paths, and support the launch. That is how Coovix turns an idea into a dependable product.',ar_vo='تبدأ المنتجات الرقمية القوية بقرارات واضحة. نفهم الهدف، ونصمم التجربة، ونبني النظام، ونختبر المسارات الأساسية، وندعم الإطلاق. هكذا تحول Coovix الفكرة إلى منتج يمكن الاعتماد عليه.',ku_vo='بەرهەمی دیجیتاڵی بەهێز بە بڕیاری ڕوون دەست پێ دەکات. ئامانجەکە تێدەگەین، ئەزموونەکە دادەڕێژین، سیستەمەکە دروست دەکەین، ڕێڕەوە گرنگەکان تاقی دەکەینەوە و بڵاوکردنەوە پشتگیری دەکەین. بەم شێوەیە Coovix بیرۆکە دەگۆڕێت بۆ بەرهەمێکی متمانەپێکراو.',cta=('Start your project','ابدأ مشروعك','پرۆژەکەت دەست پێ بکە'))]

for reel in reels:
    page(reel['title'], f"REEL {reel['n']:02d} / {reel['c'].upper()}")
    kv([('Duration',reel['duration']),('Goal',reel['goal']),('Target audience',reel['aud']),('Format','9:16 vertical, 1080 × 1920, caption-safe composition'),('Ending','Coovix logo, short CTA, coovix.com'),('Background music','Minimal modern electronic pulse; calm build, confident resolution.'),('Sound effects','Soft tap, interface confirm, line connection, restrained transition sweep.')])
    p('Storyboard', 'Heading 2')
    t=doc.add_table(rows=1,cols=3); t.style='Table Grid'; set_table_widths(t,[1300,4100,3960])
    for c,tx in zip(t.rows[0].cells,['Time','Visual description','On-screen text']): c.text=tx; shade(c,DEEP); c.paragraphs[0].runs[0].font.color.rgb=RGBColor(255,255,255); c.paragraphs[0].runs[0].bold=True
    for tm,vis,txt in reel['scenes']:
        row=t.add_row().cells
        for c,tx in zip(row,[tm,vis,txt]): c.text=tx
    page('English script',f"REEL {reel['n']:02d} / COPY")
    p('On-screen sequence','Heading 2')
    for _,_,tx in reel['scenes']: bullet(tx)
    p('Voice-over','Heading 2'); p(reel['en_vo'],'Lead')
    p('CTA','Heading 2'); p(reel['cta'][0])
    p('Caption','Heading 2'); p(reel['en_vo']+' '+reel['cta'][0]+'.')
    page('النص العربي',f"REEL {reel['n']:02d} / COPY")
    p('النص على الشاشة','Heading 2',is_rtl=True)
    ar_os=[['لعملك فكرة.','نصوغ التجربة.','نبني كل طبقة رقمية.','من المنتج إلى البنية التحتية.','ابنِ مشروعك مع Coovix.'],['يجب ألا يبطئ الحجز يوم العمل.','اختر الخدمة.','اختر الشخص والوقت المناسبين.','فريقك يرى يوم العمل بوضوح.','العميل يحجز. وفريقك يبقى مسيطراً.'],['افهم الهدف.','صمّم التجربة.','ابنِ المنتج.','اختبر كل مسار أساسي.','أطلق بثقة واستمر مع الدعم.']][reel['n']-1]
    for x in ar_os: bullet(x,True)
    p('التعليق الصوتي','Heading 2',is_rtl=True); p(reel['ar_vo'],'Lead',is_rtl=True)
    p('الدعوة إلى الإجراء','Heading 2',is_rtl=True); p(reel['cta'][1],is_rtl=True)
    page('دەقی کوردی — سۆرانی',f"REEL {reel['n']:02d} / COPY")
    ku_os=[['بازرگانییەکەت بیرۆکەیەکی هەیە.','ئەزموونەکە دادەڕێژین.','هەموو چینە دیجیتاڵییەکان دروست دەکەین.','لە بەرهەمەوە بۆ ژێرخان.','لەگەڵ Coovix دروستی بکە.'],['حجز نابێت ڕۆژی کار هێواش بکات.','خزمەتگوزاری هەڵبژێرە.','کەس و کاتی گونجاو هەڵبژێرە.','تیمەکەت ڕۆژی کار بە ڕوونی دەبینێت.','کڕیار کات دەگرێت. تیمەکەت کۆنترۆڵی دەبێت.'],['ئامانجەکە تێبگە.','ئەزموونەکە دیزاین بکە.','بەرهەمەکە دروست بکە.','هەموو ڕێڕەوە گرنگەکان تاقی بکەرەوە.','بە متمانە بڵاوی بکەرەوە و بە پشتگیری بەردەوام بە.']][reel['n']-1]
    p('دەقی سەر شاشە','Heading 2',is_rtl=True)
    for x in ku_os: bullet(x,True)
    p('دەنگی پشتەوە','Heading 2',is_rtl=True); p(reel['ku_vo'],'Lead',is_rtl=True)
    p('بانگهێشت بۆ هەنگاو','Heading 2',is_rtl=True); p(reel['cta'][2],is_rtl=True)
    page('Motion production brief',f"REEL {reel['n']:02d} / CREATIVE DIRECTION")
    kv([('Camera movement','Controlled push-ins on customer moments; stable orthographic movement for UI and dashboards.'),('Transitions','Match cuts between screens, masked device reveals, vertical scroll transitions, and one blue connector line.'),('Motion graphics','Use Coovix blue paths, clean labels, subtle counters, and restrained interface highlights.'),('Background','White/mist for explanation; deep navy for authority and the closing frame.'),('Lighting','Soft and directional. Screens must remain readable; avoid strong reflections.'),('Composition','Keep primary action centered inside the 9:16 safe zone. Reserve lower area for subtitles.'),('UI mockups','Use authentic project interfaces; show only workflows that exist in the source project.'),('Typography','Large phrase-based captions. Maximum two lines. Arabic/Sorani right-aligned when composition permits.'),('Logo placement','Final frame centered; optional small top-corner watermark before ending.'),('CTA placement','Final frame below the promise, held for 1.5–2 seconds.'),('Export','Master without text plus three localized exports. Include burned-in captions and clean audio master.')])

# FINAL OPERATING SYSTEM
section_intro(4,'Publishing and production','The operating layer for scheduling, review, handoff, and future growth.')
page('Recommended posting calendar', '04 / PUBLISHING')
t=doc.add_table(rows=1,cols=4); t.style='Table Grid'; set_table_widths(t,[900,1900,3960,2600])
for c,tx in zip(t.rows[0].cells,['Week','Day','Asset','Primary role']): c.text=tx; shade(c,DEEP); c.paragraphs[0].runs[0].font.color.rgb=RGBColor(255,255,255); c.paragraphs[0].runs[0].bold=True
rows=[('1','Sun','Post 1 — From idea to infrastructure','Brand positioning'),('1','Tue','Reel 1 — From idea to infrastructure','Awareness'),('1','Thu','Post 2 — Every digital layer','Service understanding'),('2','Sun','Post 3 — Fkrah','Portfolio authority'),('2','Tue','Reel 2 — Every booking has two sides','Problem–solution proof'),('2','Thu','Post 4 — Barber platform','Portfolio consideration'),('3','Sun','Post 5 — Champions digital menu','Hospitality lead generation'),('3','Tue','Reel 3 — How Coovix builds success','Process trust'),('3','Thu','Post 6 — Clear process','Conversion')]
for row in rows:
    cells=t.add_row().cells
    for c,tx in zip(cells,row): c.text=tx
p('Scheduling note','Heading 2'); p('Use the suggested times as launch hypotheses. Compare reach, saves, profile visits, qualified messages, and completion rate by language and platform; then adjust.')

page('Platform recommendations', '04 / PUBLISHING')
kv([('Instagram','Lead with visual proof, carousels, and Reels. Keep caption opening lines concise.'),('Facebook','Use complete multilingual captions, portfolio links, and local business context.'),('LinkedIn','Use English or bilingual business framing, process insight, and project case-study detail.'),('Stories','Support each feed asset with one teaser, one proof frame, and one CTA or poll.'),('Website','Link portfolio assets to the exact project page wherever available.'),('Language plan','Publish separate artwork versions. Captions may include three compact language blocks when operationally useful.')])

page('Content workflow', '04 / PUBLISHING')
steps=[('1. Select','Choose one audience, one pain point, and one desired action.'),('2. Verify','Confirm every capability and project statement against Coovix-owned sources.'),('3. Write','Create the English master idea, then localize Arabic and Sorani by meaning.'),('4. Design','Build the master grid, then create independent RTL layouts.'),('5. Review','Run brand, factual, language, accessibility, and platform checks.'),('6. Approve','Record final copy, design version, links, owner, and publish time.'),('7. Publish','Use platform-native formatting, alt text, captions, and link tracking.'),('8. Learn','Review results weekly and carry the strongest insight into the next brief.')]
for h,b in steps: p(h,'Heading 2'); p(b)

page('Designer checklist', '04 / PUBLISHING')
for x in ['Canvas and safe zones match the selected platform.','Headline is readable at thumbnail scale.','Real project UI is used and remains undistorted.','English, Arabic, and Sorani files use the same concept with language-appropriate alignment.','Arabic and Sorani shaping and punctuation render correctly.','Color contrast is sufficient for all essential text.','Logo has clear space and consistent placement.','CTA appears once and sits at the final reading point.','Source files, fonts, linked assets, and exports are packaged clearly.','Reel captions remain inside vertical-video safe zones.']: bullet(x)

page('Social media checklist', '04 / PUBLISHING')
for x in ['Caption opening line communicates value without needing “see more.”','Project link points to the correct live or portfolio page.','No unsupported claims, invented results, or altered product capabilities.','Hashtags are relevant and restrained.','Alt text describes the meaningful visual and project screen.','Arabic and Sorani copy has received native-language review before paid promotion.','Mentions, links, thumbnails, and cover frames are tested.','Publishing time is recorded in Erbil time.','Comments and direct messages have an assigned response owner.','Performance is reviewed against awareness, consideration, or lead goal—not vanity metrics alone.']: bullet(x)

future=[
'What “from idea to infrastructure” means in practice','Five questions to answer before building a business website','Website or custom system: how to choose','What makes a mobile experience feel intuitive','The role of an admin dashboard','Responsive design beyond screen size','How clear navigation supports business goals','Why discovery comes before development','What QA protects before launch','What happens after a digital product goes live',
'Inside Fkrah’s connected learning journey','How video, practice, flashcards, and AI support different study moments','The customer and seller sides of OnZone','Why a marketplace needs more than a storefront','Inside a service-booking journey','How schedules and bookings work together','What a car-wash dashboard needs to manage','From vehicle details to an organized queue','Why multilingual websites matter in Iraq','How Powerful Energy organizes technical information',
'Product catalogs that support better decisions','Turning corporate expertise into a clear website','What a content-management workflow changes','How restaurants update digital menus','QR menus versus printed menus','Designing food categories for mobile browsing','Why customer-facing and business tools should connect','The difference between a feature and a useful workflow','How Coovix approaches UI/UX','The value of consistent experience across web and mobile',
'Cloud hosting explained for business owners','What SSL protects','Why backups and monitoring matter','Automated deployment in plain language','Maintenance is product care, not emergency repair','How to prepare content before a website project','A founder’s checklist for app discovery','Choosing between native and cross-platform development','How to review a prototype','What to include in a project brief',
'Meet the values: Excellence','Meet the values: Partnership','Meet the values: Innovation','Meet the values: Reliability','Erbil insight, regional ambition','A week inside a Coovix project','Before and after: disconnected versus connected operations','Three signs your workflow needs a custom system','Questions to ask a technology partner','The Coovix portfolio: one principle across different industries']
for start in range(0,50,25):
    page(f'Future content ideas {start+1}–{start+25}', '04 / IDEA BANK')
    for i,x in enumerate(future[start:start+25],start+1): p(f'{i:02d}  {x}', 'Small')

future_reels=['An idea becomes a live product','Website, app, dashboard: the connected trio','A 15-second tour of Coovix services','Discovery to launch in five frames','One student journey through Fkrah','From lesson to practice to flashcard','Customer app versus store app in OnZone','How a booking reaches the dashboard','A day inside the barber platform','From wash package to vehicle queue','Scan-to-menu in five seconds','Three languages, one menu experience','Inside Powerful Energy’s product catalog','Corporate website before and after structure','What responsive really looks like','One interface across three screen sizes','The anatomy of a useful dashboard','How we test a customer journey','What happens after launch','Five UI details that reduce friction','From paper process to digital workflow','Meet the Coovix values','One week, one project milestone','A clean code-to-screen transition','Admin control behind a simple customer experience','Why multilingual UX needs real localization','Three screens that tell a project story','A project brief turns into a wireframe','Launch day without the drama','Your next system starts with one conversation']
for start in range(0,30,15):
    page(f'Future reel ideas {start+1}–{start+15}', '04 / IDEA BANK')
    for i,x in enumerate(future_reels[start:start+15],start+1): p(f'{i:02d}  {x}', 'Small')

carousels=['From idea to infrastructure: the six layers','What your customers see / what your team controls','Coovix services mapped to the product lifecycle','The five-stage development process','Fkrah feature journey','Fkrah ecosystem: students, teachers, content','OnZone: shopper, seller, platform','Barber booking in four steps','Barber dashboard capability map','Car-wash booking in four steps','Vehicle queue and daily control','Champions QR menu journey','Three-language product experience','Powerful Energy website information architecture','Solar product discovery journey','Five principles of responsive design','What makes a dashboard useful','Launch-readiness checklist','Website project content checklist','Choosing the right digital solution']
for start in range(0,20,20):
    page(f'Carousel ideas {start+1}–{start+20}', '04 / IDEA BANK')
    for i,x in enumerate(carousels[start:start+20],start+1): p(f'{i:02d}  {x}', 'Small')

stories=['Poll: website, app, or system?','Question box: what slows your daily operations?','This-or-that: printed menu or QR menu','Project screen of the week','One Coovix value in action','Quick tip: simplify one customer step','Behind the wireframe','Before the dashboard / after the dashboard','Language switch demo','Website speed and responsive check','Ask us about mobile apps','Feature spotlight: smart flashcards','Feature spotlight: real-time availability','Feature spotlight: product categories','Feature spotlight: training content','Countdown to a project reveal','Meet the process: Discovery','Meet the process: Testing','Link sticker to the portfolio','CTA: tell us what you want to build']
for start in range(0,20,20):
    page(f'Story ideas {start+1}–{start+20}', '04 / IDEA BANK')
    for i,x in enumerate(stories[start:start+20],start+1): p(f'{i:02d}  {x}', 'Small')

page('Source register', 'APPENDIX', 'Coovix-owned references used for factual scope.')
sources=[('Company website','https://coovix.com/'),('Portfolio index','https://coovix.com/Projects/projects-index.html'),('Fkrah','https://coovix.com/Projects/fkrah/fkrah-detail.html'),('OnZone','https://coovix.com/Projects/onzone/onzone-detail.html'),('Barber Management','https://coovix.com/Projects/barber/barber-detail.html'),('Car Wash Management','https://coovix.com/Projects/carwash/carwash-detail.html'),('Champions Cafe Digital Menu','https://coovix.com/Projects/menu/menu-detail.html'),('Powerful Energy Company','https://coovix.com/Projects/powerful/powerful-detail.html')]
kv(sources)
p('Source discipline','Heading 2'); p('The content avoids invented client results, fabricated statistics, and unverified technical promises. Any future performance claim should be supported by approved analytics or client evidence before publication.')

page('Final handoff', 'APPENDIX', 'A disciplined campaign is a repeatable system, not a collection of isolated posts.')
p('The first nine assets establish a clear sequence: meet the partner, understand the transformation, and see how the work is built. Keep that sequence intact during publishing so each asset adds context to the next.')
p('Approval line', 'Heading 2')
kv([('Marketing owner','____________________________'),('Language reviewer — Arabic','____________________________'),('Language reviewer — Sorani','____________________________'),('Design owner','____________________________'),('Final approval','____________________________'),('Campaign start date','____________________________')])
p('COOVIX  /  A limitless vision for technological solutions', 'Kicker', align=WD_ALIGN_PARAGRAPH.CENTER)

# Document metadata and field-update flag
doc.core_properties.title='COOVIX BRAND CONTENT PLAYBOOK — Edition 01'
doc.core_properties.subject='Trilingual brand, content, social, and creative direction playbook'
doc.core_properties.author='Coovix'
settings=doc.settings._element
upd=OxmlElement('w:updateFields'); upd.set(qn('w:val'),'true'); settings.append(upd)

# Repeat table headers and set table typography.
for table in doc.tables:
    if table.rows:
        trPr=table.rows[0]._tr.get_or_add_trPr(); rep=OxmlElement('w:tblHeader'); rep.set(qn('w:val'),'true'); trPr.append(rep)
    for row in table.rows:
        for cell in row.cells:
            for para in cell.paragraphs:
                for run in para.runs:
                    run.font.name='Arial'; run.font.size=Pt(8.7); run._element.rPr.rFonts.set(qn('w:ascii'),'Arial'); run._element.rPr.rFonts.set(qn('w:hAnsi'),'Arial'); run._element.rPr.rFonts.set(qn('w:cs'),'Arial')

doc.save(OUT)
print(OUT)
