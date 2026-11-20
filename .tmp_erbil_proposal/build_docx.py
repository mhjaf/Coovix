from pathlib import Path
from PIL import Image
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.section import WD_SECTION
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path('/Applications/MAMP/htdocs/coovix')
OUT = ROOT / 'deliverables/Erbil_Sports_Club_Football_Digital_Platform_Proposal_English_v7.docx'
ASSETS = ROOT / 'deliverables/assets'
PREVIEWS = ROOT / '.tmp_erbil_proposal/previews-english-v7'
DOC_ASSETS = ROOT / '.tmp_erbil_proposal/docx_assets_english_v7'
DOC_ASSETS.mkdir(parents=True, exist_ok=True)

NAVY = '073B55'
NAVY2 = '12384C'
BLUE = '0099D8'
PALE = 'E6F5FC'
LIGHT = 'F7FBFD'
LINE = 'DCEBF1'
INK = '212529'
MUTED = '66818E'
YELLOW = 'FFD000'
BLACK = '111111'
WHITE = 'FFFFFF'

def set_cell_shading(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn('w:shd'))
    if shd is None:
        shd = OxmlElement('w:shd')
        tc_pr.append(shd)
    shd.set(qn('w:fill'), fill)

def set_cell_margins(cell, top=100, start=120, bottom=100, end=120):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in('w:tcMar')
    if tc_mar is None:
        tc_mar = OxmlElement('w:tcMar')
        tc_pr.append(tc_mar)
    for m, value in [('top', top), ('start', start), ('bottom', bottom), ('end', end)]:
        node = tc_mar.find(qn(f'w:{m}'))
        if node is None:
            node = OxmlElement(f'w:{m}')
            tc_mar.append(node)
        node.set(qn('w:w'), str(value))
        node.set(qn('w:type'), 'dxa')

def set_cell_border(cell, color=LINE, size='6'):
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in('w:tcBorders')
    if borders is None:
        borders = OxmlElement('w:tcBorders')
        tc_pr.append(borders)
    for edge in ('top', 'left', 'bottom', 'right', 'insideH', 'insideV'):
        tag = qn(f'w:{edge}')
        el = borders.find(tag)
        if el is None:
            el = OxmlElement(f'w:{edge}')
            borders.append(el)
        el.set(qn('w:val'), 'single')
        el.set(qn('w:sz'), size)
        el.set(qn('w:color'), color)

def set_fixed_table(table, widths_dxa, indent=120):
    total = sum(widths_dxa)
    table.alignment = WD_TABLE_ALIGNMENT.LEFT
    table.autofit = False
    tbl_pr = table._tbl.tblPr
    layout = tbl_pr.find(qn('w:tblLayout'))
    if layout is None:
        layout = OxmlElement('w:tblLayout')
        tbl_pr.append(layout)
    layout.set(qn('w:type'), 'fixed')
    tbl_w = tbl_pr.find(qn('w:tblW'))
    tbl_w.set(qn('w:w'), str(total)); tbl_w.set(qn('w:type'), 'dxa')
    tbl_ind = tbl_pr.find(qn('w:tblInd'))
    if tbl_ind is None:
        tbl_ind = OxmlElement('w:tblInd'); tbl_pr.append(tbl_ind)
    tbl_ind.set(qn('w:w'), str(indent)); tbl_ind.set(qn('w:type'), 'dxa')
    grid = table._tbl.tblGrid
    for child in list(grid): grid.remove(child)
    for width in widths_dxa:
        col = OxmlElement('w:gridCol'); col.set(qn('w:w'), str(width)); grid.append(col)
    for row in table.rows:
        for idx, cell in enumerate(row.cells):
            width = widths_dxa[idx]
            tc_w = cell._tc.get_or_add_tcPr().find(qn('w:tcW'))
            tc_w.set(qn('w:w'), str(width)); tc_w.set(qn('w:type'), 'dxa')
            cell.width = Inches(width / 1440)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_cell_margins(cell)

def set_run(run, size=11, color=INK, bold=False, italic=False, name='Calibri'):
    run.font.name = name
    run._element.get_or_add_rPr().rFonts.set(qn('w:ascii'), name)
    run._element.get_or_add_rPr().rFonts.set(qn('w:hAnsi'), name)
    run.font.size = Pt(size)
    run.font.color.rgb = RGBColor.from_string(color)
    run.bold = bold
    run.italic = italic

def set_alt(inline_shape, description):
    inline_shape._inline.docPr.set('descr', description)
    inline_shape._inline.docPr.set('title', description)

def mark_header_row(row):
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement('w:tblHeader')
    tbl_header.set(qn('w:val'), 'true')
    tr_pr.append(tbl_header)

def add_para(doc, text='', size=11, color=INK, bold=False, align=WD_ALIGN_PARAGRAPH.LEFT,
             before=0, after=8, line=1.333, italic=False, keep=False):
    p = doc.add_paragraph()
    p.alignment = align
    p.paragraph_format.space_before = Pt(before)
    p.paragraph_format.space_after = Pt(after)
    p.paragraph_format.line_spacing = line
    p.paragraph_format.keep_with_next = keep
    r = p.add_run(text)
    set_run(r, size=size, color=color, bold=bold, italic=italic)
    return p

def add_heading(doc, text, level=1):
    p = doc.add_paragraph(style=f'Heading {level}')
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    return p

def add_bullet(doc, text, bold_label=None):
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.left_indent = Inches(0.375)
    p.paragraph_format.first_line_indent = Inches(-0.194)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.208
    if bold_label and text.startswith(bold_label):
        r1 = p.add_run(bold_label); set_run(r1, bold=True)
        r2 = p.add_run(text[len(bold_label):]); set_run(r2)
    else:
        r = p.add_run(text); set_run(r)
    return p

def add_number(doc, label, text):
    p = doc.add_paragraph(style='List Number')
    p.paragraph_format.left_indent = Inches(0.375)
    p.paragraph_format.first_line_indent = Inches(-0.194)
    p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.line_spacing = 1.208
    r1 = p.add_run(label + ' - '); set_run(r1, bold=True, color=NAVY)
    r2 = p.add_run(text); set_run(r2)
    return p

def add_callout(doc, title, text, fill=NAVY):
    table = doc.add_table(rows=1, cols=1)
    set_fixed_table(table, [9360])
    cell = table.cell(0, 0)
    set_cell_shading(cell, fill)
    set_cell_border(cell, fill)
    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run(title.upper()); set_run(r, size=10, color=BLUE if fill == NAVY else NAVY, bold=True)
    p2 = cell.add_paragraph()
    p2.paragraph_format.space_after = Pt(0)
    r2 = p2.add_run(text); set_run(r2, size=12, color=WHITE if fill == NAVY else INK, bold=True)
    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    return table

def add_caption(doc, text):
    return add_para(doc, text, size=9, color=MUTED, italic=True, align=WD_ALIGN_PARAGRAPH.CENTER, after=8, line=1.0)

def add_page_title(doc, kicker, title, intro=None):
    add_para(doc, kicker.upper(), size=9, color=BLUE, bold=True, after=4, line=1.0, keep=True)
    p = add_para(doc, title, size=22, color=NAVY, bold=True, after=8, line=1.05, keep=True)
    p.paragraph_format.keep_with_next = True
    if intro:
        add_para(doc, intro, size=11, color=MUTED, after=12, line=1.25)

def crop(src_name, out_name, box):
    src = PREVIEWS / src_name
    out = DOC_ASSETS / out_name
    with Image.open(src) as im:
        im.crop(box).save(out, quality=95)
    return out

website_img = crop('slide-06.png', 'website-concept.png', (55, 190, 1225, 650))
mobile_img = crop('slide-07.png', 'mobile-concept.png', (65, 190, 1215, 650))
journey_img = crop('slide-08.png', 'booking-payment-flow.png', (55, 200, 1225, 650))
admin_img = crop('slide-09.png', 'admin-concept.png', (55, 165, 1225, 650))

doc = Document()
sec = doc.sections[0]
sec.page_width = Inches(8.5); sec.page_height = Inches(11)
sec.top_margin = Inches(1); sec.bottom_margin = Inches(1)
sec.left_margin = Inches(1); sec.right_margin = Inches(1)
sec.header_distance = Inches(0.492); sec.footer_distance = Inches(0.492)
sec.different_first_page_header_footer = True

styles = doc.styles
normal = styles['Normal']
normal.font.name = 'Calibri'; normal.font.size = Pt(11); normal.font.color.rgb = RGBColor.from_string(INK)
normal._element.rPr.rFonts.set(qn('w:ascii'), 'Calibri'); normal._element.rPr.rFonts.set(qn('w:hAnsi'), 'Calibri')
normal.paragraph_format.space_after = Pt(8); normal.paragraph_format.line_spacing = 1.333
for level, size, before, after, color in [(1,16,18,10,NAVY),(2,13,12,6,BLUE),(3,12,8,4,NAVY2)]:
    st = styles[f'Heading {level}']
    st.font.name = 'Calibri'; st.font.size = Pt(size); st.font.bold = True; st.font.color.rgb = RGBColor.from_string(color)
    st._element.rPr.rFonts.set(qn('w:ascii'), 'Calibri'); st._element.rPr.rFonts.set(qn('w:hAnsi'), 'Calibri')
    st.paragraph_format.space_before = Pt(before); st.paragraph_format.space_after = Pt(after)

header = sec.header
hp = header.paragraphs[0]
hp.alignment = WD_ALIGN_PARAGRAPH.LEFT
hr = hp.add_run('ERBIL SPORTS CLUB DIGITAL PLATFORM'); set_run(hr, size=8.5, color=MUTED, bold=True)
hr2 = hp.add_run('                                               COOVIX'); set_run(hr2, size=8.5, color=BLUE, bold=True)

footer = sec.footer
fp = footer.paragraphs[0]
fp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
fr = fp.add_run('Prepared by Coovix  |  '); set_run(fr, size=8.5, color=MUTED)
fld = OxmlElement('w:fldSimple'); fld.set(qn('w:instr'), 'PAGE')
fp._p.append(fld)

# Page 1 - Cover
logos = doc.add_table(rows=1, cols=2)
set_fixed_table(logos, [4680, 4680], indent=0)
for c in logos.rows[0].cells:
    set_cell_border(c, WHITE, '0')
cover_coovix = logos.cell(0,0).paragraphs[0].add_run().add_picture(str(ASSETS / 'coovix-logo.png'), width=Inches(1.55))
set_alt(cover_coovix, 'Coovix logo')
logos.cell(0,1).paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.RIGHT
cover_erbil = logos.cell(0,1).paragraphs[0].add_run().add_picture(str(ASSETS / 'erbil-sports-club-logo.png'), height=Inches(1.25))
set_alt(cover_erbil, 'Erbil Sports Club crest')
add_para(doc, 'BUSINESS PROPOSAL | FOOTBALL', size=10, color=BLUE, bold=True, align=WD_ALIGN_PARAGRAPH.CENTER, before=46, after=16, line=1.0)
add_para(doc, 'Erbil Sports Club\nDigital Football Platform', size=30, color=NAVY, bold=True, align=WD_ALIGN_PARAGRAPH.CENTER, after=12, line=1.05)
add_para(doc, 'Website, Mobile Application & Smart Football Facility Booking', size=14, color=MUTED, align=WD_ALIGN_PARAGRAPH.CENTER, after=34, line=1.2)
add_callout(doc, 'Proposal objective', 'A connected football experience for supporters and members, with clearer operating control for management.', fill=NAVY)
add_para(doc, 'Prepared by: Coovix', size=12, color=NAVY, bold=True, align=WD_ALIGN_PARAGRAPH.CENTER, before=26, after=3, line=1.0)
add_para(doc, 'September 2026 | Erbil, Kurdistan Region of Iraq', size=10, color=MUTED, align=WD_ALIGN_PARAGRAPH.CENTER, after=0, line=1.0)

# V7: one section per page, matching the approved 14-page presentation.
doc.add_page_break()
add_page_title(doc, 'Executive summary', "One platform can become the club's digital front door", 'Phase 1 connects customers, football facility booking, payments and club operations through one consistent experience.')
add_heading(doc, 'Business outcomes', 2)
add_bullet(doc, 'Customers: See live availability, pay online and receive a verified booking confirmation.', 'Customers:')
add_bullet(doc, 'Operations: Manage bookings, schedules, facilities, customers and pricing from one dashboard.', 'Operations:')
add_bullet(doc, 'Management: Monitor booking volume, payment status and basic revenue performance.', 'Management:')
add_callout(doc, 'Phase 1 objective', 'Launch a dependable website, mobile application and administration platform within 10-12 weeks.')

doc.add_page_break()
add_page_title(doc, 'Current opportunities', "Turn today's service gaps into a stronger customer experience", 'A connected platform can reduce manual coordination while making club services easier to discover, book and manage.')
for label, body in [('Availability','Let customers check live pitch and facility time slots without calling.'),('Reservations','Reduce staff effort around manual booking, changes and cancellations.'),('Accuracy','Synchronize schedules to reduce conflicts and duplicate reservations.'),('Communication','Deliver booking updates and reminders consistently through push notifications.'),('Payments','Provide a clear online payment, confirmation and receipt journey.'),('Reporting','Centralize booking and payment information for better operating visibility.')]:
    add_bullet(doc, f'{label}: {body}', f'{label}:')

doc.add_page_break()
add_page_title(doc, 'Proposed solution', 'One connected ecosystem keeps every channel in sync', 'Customers receive one seamless experience while staff manage one reliable source of booking and payment information.')
for title, body in [('Responsive website','Club content, football information and a production-ready facility booking journey.'),('Android and iOS application','Account access, live availability, reservations, payments, notifications and booking history.'),('Admin dashboard','Booking, facility, schedule, customer, pricing and payment-status management.'),('Connected platform services','Backend APIs, online payment integration, QR confirmations, reports and notifications.')]:
    add_heading(doc, title, 2); add_para(doc, body, after=5, line=1.2)
add_callout(doc, 'Operating principle', 'One customer experience. One booking process. One management view.')

doc.add_page_break()
add_page_title(doc, 'Project phases', 'Launch the core platform first, then expand with confidence', 'The roadmap separates the current paid scope from future optional modules so investment and delivery remain clear.')
for title, body in [('Phase 1 - Core Digital Platform','Current paid scope: website, mobile apps, booking, payments, QR confirmation, admin dashboard, backend APIs, deployment and staff training.'),('Phase 2 - Membership & Growth','Future optional services: digital membership, subscriptions, membership card, loyalty, promotions and advanced analytics.'),('Phase 3 - Smart Club Services','Future optional services: academy, coach booking, ticketing, restaurant ordering, access control, parking and additional club services.')]:
    add_heading(doc, title, 2); add_para(doc, body, after=8, line=1.2)
add_callout(doc, 'Scope boundary', 'Phase 2 and Phase 3 are not included in the Phase 1 price. They will be scoped and priced separately.')

doc.add_page_break()
add_page_title(doc, 'Website', 'The digital home of Erbil football', 'A responsive public website presents the club professionally and gives customers a clear path from discovery to confirmed facility booking.')
shape = doc.add_picture(str(website_img), width=Inches(6.5)); set_alt(shape, 'Production-style concepts for the Erbil Sports Club homepage and pitch booking page')
add_caption(doc, 'Homepage and booking page concepts using the existing Erbil yellow-and-black product language.')
add_bullet(doc, 'Responsive across desktop, tablet and mobile browsers.')
add_bullet(doc, 'Live availability, facility details, date and time selection, and booking review.')

doc.add_page_break()
add_page_title(doc, 'Mobile application', 'A focused experience for everyday club services', 'The Android and iOS applications prioritize account access, pitch selection and booking a live time slot.')
shape = doc.add_picture(str(mobile_img), width=Inches(6.5)); set_alt(shape, 'Production-style mobile screens showing home, pitch selection and booking calendar')
add_caption(doc, 'Mobile concepts: app home, Training Pitch 1 selection and Friday booking calendar.')
add_bullet(doc, 'Registration, login and OTP verification with customer profile access.')
add_bullet(doc, 'Live availability, booking history, cancellation and push notifications.')

doc.add_page_break()
add_page_title(doc, 'Booking & payment flow', 'From selection to verified entry in minutes', 'The flow makes price, payment status and booking details clear at every step.')
shape = doc.add_picture(str(journey_img), width=Inches(6.5)); set_alt(shape, 'Payment, payment success, QR booking confirmation and My Bookings mobile screens')
add_caption(doc, 'Training Pitch 1 | Friday 18:30-20:00 | 25,000 IQD | Booking ID ESC-18472 | Paid and Confirmed.')
add_bullet(doc, 'Online payment integration, payment confirmation and a digital receipt are included in Phase 1.')
add_bullet(doc, 'A real scannable QR design supports reception or entry validation.')

doc.add_page_break()
add_page_title(doc, 'Admin dashboard', 'Daily activity becomes operational control', 'Authorized staff receive a clear workspace for bookings, facilities, customers, pricing and payment status.')
shape = doc.add_picture(str(admin_img), width=Inches(6.5)); set_alt(shape, 'Production-style administration dashboard with booking, revenue and payment status information')
add_caption(doc, 'Dashboard example: booking ESC-18472, Training Pitch 1, 25,000 IQD, Paid and Confirmed.')
add_bullet(doc, 'Booking, facility, schedule, customer and pricing management.')
add_bullet(doc, 'Payment status plus basic booking and revenue reports.')

doc.add_page_break()
add_page_title(doc, 'Business benefits', 'Customer convenience translates into stronger decisions', 'The platform improves both the customer-facing experience and the internal operating model.')
benefits = doc.add_table(rows=1, cols=2); set_fixed_table(benefits, [4680,4680]); mark_header_row(benefits.rows[0])
for i,h in enumerate(['Customer value','Club and management value']):
    set_cell_shading(benefits.cell(0,i),NAVY); set_cell_border(benefits.cell(0,i),NAVY); r=benefits.cell(0,i).paragraphs[0].add_run(h); set_run(r,size=11,color=WHITE,bold=True)
for left,right in [('Fast self-service booking with clear confirmation.','Fewer calls, repetitive tasks and manual checks.'),('Consistent access to football and facility services.','Centralized booking, customer and pricing information.'),('Clear availability, reminders and booking history.','Visibility into revenue, utilization and peak hours.'),('A modern experience aligned with the club brand.','A scalable foundation for future club services.')]:
    cells=benefits.add_row().cells
    for i,value in enumerate([left,right]):
        set_cell_border(cells[i]);
        if len(benefits.rows)%2==1: set_cell_shading(cells[i],LIGHT)
        r=cells[i].paragraphs[0].add_run(value); set_run(r,size=10.5)
set_fixed_table(benefits,[4680,4680])
add_para(doc,'',after=4); add_callout(doc,'Management value','Clearer booking and payment data supports better scheduling, pricing and service decisions.')

doc.add_page_break()
add_page_title(doc, 'Phase 1 timeline', 'A focused delivery plan targets launch in 10-12 weeks', 'Workstreams overlap where practical, while review points keep management aligned on scope and experience.')
timeline = doc.add_table(rows=1, cols=3); set_fixed_table(timeline,[900,5940,2520]); mark_header_row(timeline.rows[0])
for i,h in enumerate(['Step','Workstream','Target']):
    set_cell_shading(timeline.cell(0,i),NAVY); set_cell_border(timeline.cell(0,i),NAVY); r=timeline.cell(0,i).paragraphs[0].add_run(h); set_run(r,size=10,color=WHITE,bold=True)
for row in [('1','Discovery and solution planning','Week 1'),('2','Experience design and approval','Weeks 2-3'),('3','Core platform, website and administration','Weeks 3-7'),('4','Android and iOS applications','Weeks 5-9'),('5','Payment, QR, testing and acceptance','Weeks 9-11'),('6','Deployment and staff training','Weeks 11-12')]:
    cells=timeline.add_row().cells
    for i,value in enumerate(row):
        set_cell_border(cells[i]);
        if len(timeline.rows)%2==1: set_cell_shading(cells[i],LIGHT)
        r=cells[i].paragraphs[0].add_run(value); set_run(r,size=10.5,bold=(i==0),color=NAVY if i==0 else INK)
set_fixed_table(timeline,[900,5940,2520]); add_para(doc,'',after=4)
add_callout(doc, 'Planning assumption', 'The target assumes timely approvals, content delivery and third-party account readiness.')

doc.add_page_break()
add_page_title(doc, 'Phase 1 commercial proposal', 'A complete core football platform for $6,900 USD')
pricing = doc.add_table(rows=1, cols=2); set_fixed_table(pricing,[7200,2160]); mark_header_row(pricing.rows[0])
for i,h in enumerate(['Phase 1 scope','Status']):
    set_cell_shading(pricing.cell(0,i),NAVY); set_cell_border(pricing.cell(0,i),NAVY); r=pricing.cell(0,i).paragraphs[0].add_run(h); set_run(r,size=10,color=WHITE,bold=True)
for item,status in [('Responsive website and Android/iOS apps','Included'),('Booking, live availability, history and cancellation','Included'),('Online payment, receipt and QR confirmation','Included'),('Admin dashboard and basic reports','Included'),('Backend APIs, deployment and staff training','Included')]:
    cells=pricing.add_row().cells
    for i,value in enumerate([item,status]):
        set_cell_border(cells[i]);
        if len(pricing.rows)%2==1: set_cell_shading(cells[i],LIGHT)
        r=cells[i].paragraphs[0].add_run(value); set_run(r,size=10.2,bold=(i==1))
cells=pricing.add_row().cells
for i,value in enumerate(['Phase 1 investment','$6,900 USD']):
    set_cell_shading(cells[i],PALE); set_cell_border(cells[i],BLUE); r=cells[i].paragraphs[0].add_run(value); set_run(r,size=11,color=NAVY,bold=True)
set_fixed_table(pricing,[7200,2160])
add_heading(doc,'Payment terms',2); add_para(doc,'30% on approval | 40% after core system demo | 30% after final deployment',size=10,after=5,line=1.1)
add_heading(doc,'Third-party fees excluded',2); add_para(doc,'Payment gateway transaction fees | Bank/provider fees | SMS/OTP usage fees | Apple Developer fees | Google Play fees | Hosting renewal | Domain renewal',size=9.2,after=6,line=1.1)
add_callout(doc,'Scope note','Payment development and integration are included. Phase 2 and Phase 3 are not included in the Phase 1 price.')

doc.add_page_break()
add_page_title(doc, 'Future optional phases', 'Extend the platform as club priorities grow', 'The Phase 1 foundation can support later modules without placing them inside the current paid scope.')
future = doc.add_table(rows=1, cols=2); set_fixed_table(future, [4680, 4680]); mark_header_row(future.rows[0])
for i, heading in enumerate(['Phase 2 - Membership & Growth', 'Phase 3 - Smart Club Services']):
    set_cell_shading(future.cell(0, i), NAVY); set_cell_border(future.cell(0, i), NAVY)
    r = future.cell(0, i).paragraphs[0].add_run(heading); set_run(r, size=10.5, color=WHITE, bold=True)
phase2 = ['Digital membership', 'Membership subscriptions', 'Digital membership card', 'Loyalty points and rewards', 'Promotions and offers', 'Advanced reports and analytics', 'Corporate and family memberships', 'Referral and invitation program', 'Member surveys and feedback', 'Automated renewal reminders', 'Sponsor benefits and partner offers']
phase3 = ['Academy management', 'Coach booking', 'Event ticketing', 'Restaurant ordering', 'Smart access control', 'Parking management', 'Club merchandise store', 'Digital wallet and cashless payments', 'Digital guest passes', 'Maintenance request management', 'Live match center and club media', 'Additional club services']
cells = future.add_row().cells
for idx, items in enumerate([phase2, phase3]):
    set_cell_border(cells[idx]); cells[idx].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
    for pidx, item in enumerate(items):
        p = cells[idx].paragraphs[0] if pidx == 0 else cells[idx].add_paragraph()
        p.style = doc.styles['List Bullet']
        p.paragraph_format.left_indent = Inches(0.22); p.paragraph_format.first_line_indent = Inches(-0.15)
        p.paragraph_format.space_after = Pt(1.5); p.paragraph_format.line_spacing = 1.0
        r = p.add_run(item); set_run(r, size=8.7)
set_fixed_table(future, [4680, 4680])
add_para(doc, '', after=2)
add_callout(doc, 'Commercial note', 'Phase 2 and Phase 3 are optional future modules outside the current Phase 1 scope. No pricing is presented; each phase will be scoped and priced separately when Erbil Sports Club chooses to proceed.')

doc.add_page_break()
add_page_title(doc, 'Conclusion', 'A practical first phase with room to grow', 'Phase 1 gives Erbil Sports Club a complete digital foundation for football facility discovery, booking, payment and daily management.')
for text in ['Professional responsive website and Android/iOS applications.','Clear booking, payment, digital receipt and QR confirmation experience.','Central administration for facilities, schedules, customers, pricing and payments.','Target delivery within 10-12 weeks, including deployment and staff training.']:
    add_bullet(doc,text)
add_callout(doc,'Phase 1 investment','$6,900 USD | 30% approval | 40% core demo | 30% final deployment')
add_para(doc,'Prepared by: Coovix',size=12,color=NAVY,bold=True,align=WD_ALIGN_PARAGRAPH.CENTER,before=28,after=3,line=1.0)
add_para(doc,'Erbil Sports Club Digital Football Platform',size=10,color=MUTED,align=WD_ALIGN_PARAGRAPH.CENTER,after=0,line=1.0)

doc.core_properties.title = 'Erbil Sports Club Digital Football Platform Proposal'
doc.core_properties.subject = 'Phase 1 Website, Mobile Applications, Booking and Payment Platform'
doc.core_properties.author = 'Coovix'
doc.core_properties.keywords = 'Erbil Sports Club, Coovix, football, mobile app, website, booking, payment'
doc.save(OUT)
print(f'Wrote {OUT}')
raise SystemExit(0)

# Page 2 - Executive summary and opportunities
doc.add_page_break()
add_page_title(doc, 'Executive summary', "One platform can become the club's digital front door", 'The proposed platform brings football information, facility booking, supporter communication and management into one connected experience.')
add_heading(doc, 'Business outcomes', 2)
add_bullet(doc, 'Members: Follow club activity, find facilities and confirm reservations in minutes.', 'Members:')
add_bullet(doc, 'Operations: Manage schedules, pricing and customer records from one place.', 'Operations:')
add_bullet(doc, 'Management: See bookings, revenue and peak-hour trends with greater clarity.', 'Management:')
add_heading(doc, 'Current opportunities', 2)
for label, body in [
    ('Availability', 'Make available time slots easier to check without calling.'),
    ('Reservations', 'Reduce staff effort required for phone-based and manual booking.'),
    ('Accuracy', 'Keep schedules synchronized to reduce avoidable booking conflicts.'),
    ('Communication', 'Reach members consistently with updates and reminders.'),
    ('Data and reporting', 'Centralize booking information and improve operational visibility.'),
]: add_bullet(doc, f'{label}: {body}', f'{label}:')

# Page 3 - Proposed solution
doc.add_page_break()
add_page_title(doc, 'Proposed solution', 'One connected ecosystem keeps every channel in sync', 'Members see one seamless experience while staff manage one reliable source of booking information.')
for title, body in [
    ('Website', 'Club information, football news, fixtures, membership and facility booking.'),
    ('Mobile application', 'Member access, availability, reservations, notifications and booking history.'),
    ('Admin dashboard', 'Schedules, facilities, customers, pricing, promotions and performance reports.'),
    ('Shared club services', 'Connected member, content and booking information across every channel.'),
]:
    add_heading(doc, title, 2)
    add_para(doc, body, after=5, line=1.2)
add_callout(doc, 'Operating principle', 'One member experience. One booking process. One management view.')

# Page 4 - Website
doc.add_page_break()
add_page_title(doc, 'Website experience', 'The digital home of Erbil football', 'A responsive public website can present the club professionally while guiding supporters toward news, membership and facility booking.')
website_shape = doc.add_picture(str(website_img), width=Inches(6.5))
set_alt(website_shape, 'Concept design for the Erbil Sports Club website')
add_caption(doc, 'Website concept: club navigation, football storytelling, member access and pitch booking.')
add_bullet(doc, 'Club identity, history, leadership and contact information.')
add_bullet(doc, 'First team, academy, fixtures, results, news and events.')
add_bullet(doc, 'Membership information, profile access and facility reservations.')

# Page 5 - Mobile
doc.add_page_break()
add_page_title(doc, 'Mobile application', 'A clear journey from discovery to entry', 'The mobile experience prioritizes the actions members need most: follow the club, select a pitch, reserve a time and present a booking confirmation.')
mobile_shape = doc.add_picture(str(mobile_img), width=Inches(6.5))
set_alt(mobile_shape, 'Three mobile application screens showing discovery, booking and QR confirmation')
add_caption(doc, 'Application concept: home, time selection and scannable QR booking confirmation.')
add_bullet(doc, 'Registration, secure verification and member profile access.')
add_bullet(doc, 'Facility discovery, live dates and available time slots.')
add_bullet(doc, 'Reservations, booking history, cancellation and notifications.')
add_bullet(doc, 'QR confirmation designed for reception or entry validation.')

# Page 6 - Admin
doc.add_page_break()
add_page_title(doc, 'Admin dashboard', 'Daily activity becomes operational control', 'The administration workspace gives authorized staff a clear view of activity and the tools to manage day-to-day operations.')
admin_shape = doc.add_picture(str(admin_img), width=Inches(6.5))
set_alt(admin_shape, 'Concept design for the club administration dashboard')
add_caption(doc, 'Dashboard concept: statistics, navigation, booking activity and management controls.')
for text in [
    'Booking, facility, schedule and pricing management.',
    'Member, customer, staff and permission management.',
    'Discounts, promotions and targeted notifications.',
    'Revenue, booking and peak-hour reporting.',
]: add_bullet(doc, text)

# Page 7 - Journey
doc.add_page_break()
add_page_title(doc, 'Booking journey', 'From intent to confirmation in minutes', 'The journey removes uncertainty for members and reduces manual follow-up for staff.')
journey_shape = doc.add_picture(str(journey_img), width=Inches(6.5))
set_alt(journey_shape, 'Eight-step football facility booking journey')
add_caption(doc, 'Illustrative booking flow across member access, facility selection, payment and confirmation.')
steps = [
    ('Login or register', 'Access an existing account or create a member profile.'),
    ('Select service', 'Choose football and the required facility.'),
    ('Choose date and time', 'Review available slots and select a suitable time.'),
    ('Confirm booking', 'Review the booking details and applicable price.'),
    ('Payment', 'Complete payment when enabled for the selected service.'),
    ('Receive QR confirmation', 'Store the booking and present the code at reception.'),
]
for a,b in steps: add_number(doc, a, b)

# Page 8 - Benefits
doc.add_page_break()
add_page_title(doc, 'Business benefits', 'Member convenience translates into stronger decisions', 'The platform is designed to improve both the customer-facing experience and the internal operating model.')
table = doc.add_table(rows=1, cols=2)
set_fixed_table(table, [4680,4680])
mark_header_row(table.rows[0])
headers = ['Member and supporter value', 'Club and management value']
for i,h in enumerate(headers):
    set_cell_shading(table.cell(0,i), NAVY); set_cell_border(table.cell(0,i), NAVY)
    r = table.cell(0,i).paragraphs[0].add_run(h); set_run(r, size=11, color=WHITE, bold=True)
rows = [
    ('Fast, self-service booking with clear confirmation.', 'Fewer calls, repetitive tasks and manual schedule checks.'),
    ('Consistent access to fixtures, news and club services.', 'Centralized booking, member and pricing information.'),
    ('Clear availability, reminders and booking history.', 'Better visibility into revenue, utilization and peak hours.'),
    ('A modern experience aligned with the club brand.', 'A scalable foundation for future digital services.'),
]
for left,right in rows:
    cells = table.add_row().cells
    for i,value in enumerate([left,right]):
        set_cell_border(cells[i]);
        if len(table.rows) % 2 == 1: set_cell_shading(cells[i], LIGHT)
        p = cells[i].paragraphs[0]; r = p.add_run(value); set_run(r, size=10.5)
set_fixed_table(table, [4680,4680])
add_para(doc, '', after=4)
add_callout(doc, 'Management value', 'More visibility does not guarantee revenue, but it supports better scheduling, pricing and service decisions.')

# Page 9 - Readiness and expansion
doc.add_page_break()
add_page_title(doc, 'Platform readiness and expansion', 'A dependable platform can grow with the club', 'The proposal prioritizes reliable daily operation today and practical room for tomorrow.')
add_heading(doc, 'Designed for confident operation', 2)
for label,body in [
    ('Connected experience', 'Website, mobile and administration remain aligned.'),
    ('Secure member access', 'Clear login, verification and account controls.'),
    ('Role-based management', 'Staff access is limited to relevant responsibilities.'),
    ('Reliable continuity', 'Routine backups, monitoring and support practices.'),
    ('Integration readiness', 'Prepared for payments, access and messaging services.'),
]: add_bullet(doc, f'{label}: {body}', f'{label}:')
add_heading(doc, 'Optional future capabilities', 2)
for item in ['Membership subscriptions and loyalty points', 'Academy management and coach booking', 'Restaurant ordering and event ticketing', 'Access control and smart QR entry', 'Parking management']:
    add_bullet(doc, item)

# Page 10 - Timeline
doc.add_page_break()
add_page_title(doc, 'Project timeline', 'A focused delivery plan targets launch in 10-12 weeks', 'Workstreams overlap where practical, while review points keep management aligned on scope and experience.')
timeline = doc.add_table(rows=1, cols=3)
set_fixed_table(timeline, [900, 5940, 2520])
mark_header_row(timeline.rows[0])
for i,h in enumerate(['Phase','Workstream','Target duration']):
    set_cell_shading(timeline.cell(0,i), NAVY); set_cell_border(timeline.cell(0,i), NAVY)
    p=timeline.cell(0,i).paragraphs[0]; r=p.add_run(h); set_run(r,size=10,color=WHITE,bold=True)
phases = [
    ('1','Analysis and planning','1 week'), ('2','UI/UX design','2 weeks'),
    ('3','Core platform and website','3-4 weeks'), ('4','Mobile application','4-5 weeks'),
    ('5','Testing and integration','1-2 weeks'), ('6','Deployment and training','1 week'),
]
for row in phases:
    cells=timeline.add_row().cells
    for i,value in enumerate(row):
        set_cell_border(cells[i]);
        if len(timeline.rows)%2==1: set_cell_shading(cells[i],LIGHT)
        p=cells[i].paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.CENTER if i != 1 else WD_ALIGN_PARAGRAPH.LEFT
        r=p.add_run(value); set_run(r,size=10.5,bold=(i==0),color=NAVY if i==0 else INK)
set_fixed_table(timeline, [900,5940,2520])
add_para(doc, '', after=4)
add_callout(doc, 'Planning assumption', 'The target duration assumes timely approvals, content delivery and third-party account readiness.')

# Page 11 - Commercial proposal
doc.add_page_break()
add_page_title(doc, 'Commercial proposal', 'A complete football platform at a special project price')
pricing = doc.add_table(rows=1, cols=2)
set_fixed_table(pricing, [7200,2160])
mark_header_row(pricing.rows[0])
for i,h in enumerate(['Scope item','Investment']):
    set_cell_shading(pricing.cell(0,i), NAVY); set_cell_border(pricing.cell(0,i), NAVY)
    p=pricing.cell(0,i).paragraphs[0]; p.paragraph_format.space_after=Pt(0); p.paragraph_format.line_spacing=1.0
    r=p.add_run(h); set_run(r,size=10,color=WHITE,bold=True)
prices = [('Website development','$1,500'),('Admin dashboard','$1,500'),('Mobile app - Android & iOS','$2,500'),('UI/UX design','$500'),('Booking system and integrations','$1,000'),('Testing, deployment and staff training','$500')]
for item,price in prices:
    cells=pricing.add_row().cells
    for i,value in enumerate([item,price]):
        set_cell_border(cells[i]);
        if len(pricing.rows)%2==1: set_cell_shading(cells[i],LIGHT)
        p=cells[i].paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.RIGHT if i==1 else WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_after=Pt(0); p.paragraph_format.line_spacing=1.0
        r=p.add_run(value); set_run(r,size=10.5,bold=(i==1))
cells=pricing.add_row().cells
for i,value in enumerate(['Standard total','$7,500 USD']):
    set_cell_shading(cells[i],PALE); set_cell_border(cells[i],BLUE)
    p=cells[i].paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.RIGHT if i==1 else WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_after=Pt(0); p.paragraph_format.line_spacing=1.0
    r=p.add_run(value); set_run(r,size=11,color=NAVY,bold=True)
set_fixed_table(pricing,[7200,2160])
add_para(doc,'SPECIAL PROJECT PRICE: $6,900 USD',size=18,color=BLUE,bold=True,align=WD_ALIGN_PARAGRAPH.CENTER,before=10,after=10,line=1.0)
add_heading(doc, 'Included', 2)
add_para(doc, 'Website | Android app | iOS app | Admin dashboard | Booking system | Platform integrations | Deployment | Staff training | Source code delivery | 12 months bug-fix technical support', size=9.5, after=6, line=1.15)
add_heading(doc, 'Payment terms', 2)
add_para(doc, '30% on approval | 40% after the core system demonstration | 30% after final delivery and deployment', size=10, after=6, line=1.15)
add_heading(doc, 'Exclusions', 2)
add_para(doc, 'Hosting/server renewal, domain renewal, app-store account fees, SMS/verification fees, payment transaction fees, and major new features outside the agreed scope.', size=9.5, after=8, line=1.15)
add_callout(doc, 'Conclusion', 'A modern football experience, stronger management and a scalable foundation - ready to begin.')

doc.core_properties.title = 'Erbil Sports Club Digital Football Platform Proposal'
doc.core_properties.subject = 'Website, Mobile Application and Smart Football Facility Booking'
doc.core_properties.author = 'Coovix'
doc.core_properties.keywords = 'Erbil Sports Club, Coovix, football, mobile app, website, booking'
doc.save(OUT)
print(f'Wrote {OUT}')
