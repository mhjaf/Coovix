import ast
from pathlib import Path
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.style import WD_STYLE_TYPE

ROOT = Path('/Applications/MAMP/htdocs/coovix')
OUT = ROOT / 'Coovix_Brand_Content_Playbook_Edition_01_Simplified.docx'
BLUE, NAVY, DEEP, PALE, MIST, GREY, INK, WHITE = '0099D8','12384C','073B55','E6F5FC','F7FBFD','66818E','212529','FFFFFF'

def load_literal(name):
    tree = ast.parse((ROOT/'build_coovix_playbook.py').read_text())
    for node in tree.body:
        if isinstance(node, ast.Assign) and any(isinstance(t, ast.Name) and t.id == name for t in node.targets):
            return eval(compile(ast.Expression(node.value), '<content-data>', 'eval'), {'dict': dict})
    raise RuntimeError(name)

posts, reels = load_literal('posts'), load_literal('reels')

doc=Document(); sec=doc.sections[0]
sec.page_width, sec.page_height=Inches(8.5), Inches(11)
sec.top_margin=sec.bottom_margin=sec.left_margin=sec.right_margin=Inches(1)
sec.header_distance=sec.footer_distance=Inches(.49)

styles=doc.styles
normal=styles['Normal']; normal.font.name='Arial'; normal.font.size=Pt(10.5); normal.font.color.rgb=RGBColor.from_string(INK)
normal.paragraph_format.space_after=Pt(5); normal.paragraph_format.line_spacing=1.15
for name,size,color,before,after in [('Title',32,NAVY,0,12),('Heading 1',22,NAVY,10,9),('Heading 2',14,BLUE,10,5),('Heading 3',10.5,DEEP,6,3)]:
    s=styles[name]; s.font.name='Arial'; s.font.size=Pt(size); s.font.bold=True; s.font.color.rgb=RGBColor.from_string(color)
    s.paragraph_format.space_before=Pt(before); s.paragraph_format.space_after=Pt(after); s.paragraph_format.keep_with_next=True
for name,size,color,bold in [('Kicker',8.5,BLUE,True),('Copy',11,INK,False),('Copy Lead',14,NAVY,True),('Small',8.5,GREY,False)]:
    s=styles.add_style(name,WD_STYLE_TYPE.PARAGRAPH); s.font.name='Arial'; s.font.size=Pt(size); s.font.color.rgb=RGBColor.from_string(color); s.font.bold=bold
    s.paragraph_format.space_after=Pt(5); s.paragraph_format.line_spacing=1.15

def font_all(run,size=None,bold=None,color=None):
    run.font.name='Arial'; run._element.rPr.rFonts.set(qn('w:ascii'),'Arial'); run._element.rPr.rFonts.set(qn('w:hAnsi'),'Arial'); run._element.rPr.rFonts.set(qn('w:cs'),'Arial')
    if size: run.font.size=Pt(size)
    if bold is not None: run.bold=bold
    if color: run.font.color.rgb=RGBColor.from_string(color)

def rtl(par):
    par.alignment=WD_ALIGN_PARAGRAPH.RIGHT
    pPr=par._p.get_or_add_pPr(); bidi=OxmlElement('w:bidi'); pPr.append(bidi)
    for run in par.runs:
        rPr=run._r.get_or_add_rPr(); rPr.append(OxmlElement('w:rtl')); font_all(run)

def shade_par(par,fill=PALE,border=BLUE):
    pPr=par._p.get_or_add_pPr(); shd=OxmlElement('w:shd'); shd.set(qn('w:fill'),fill); pPr.append(shd)
    pBdr=OxmlElement('w:pBdr'); left=OxmlElement('w:left'); left.set(qn('w:val'),'single'); left.set(qn('w:sz'),'18'); left.set(qn('w:space'),'8'); left.set(qn('w:color'),border); pBdr.append(left); pPr.append(pBdr)
    ind=OxmlElement('w:ind'); ind.set(qn('w:left'),'180'); ind.set(qn('w:right'),'140'); pPr.append(ind)
    sp=OxmlElement('w:spacing'); sp.set(qn('w:before'),'100'); sp.set(qn('w:after'),'100'); pPr.append(sp)

def page(kicker,title,subtitle=None):
    if doc.paragraphs or doc.tables: doc.add_page_break()
    add(kicker,'Kicker'); add(title,'Heading 1')
    if subtitle: add(subtitle,'Copy Lead')

def add(text='',style=None,is_rtl=False,align=None):
    par=doc.add_paragraph(style=style); par.add_run(text)
    if align is not None: par.alignment=align
    if is_rtl: rtl(par)
    return par

def label(text,is_rtl=False): return add(text,'Heading 3',is_rtl)

def copybox(text,is_rtl=False,lead=False):
    par=add(text,'Copy Lead' if lead else 'Copy',is_rtl); shade_par(par); return par

def set_cell_margins(cell,top=90,start=110,bottom=90,end=110):
    tcPr=cell._tc.get_or_add_tcPr(); tcMar=OxmlElement('w:tcMar')
    for name,val in [('top',top),('start',start),('bottom',bottom),('end',end)]:
        e=OxmlElement('w:'+name); e.set(qn('w:w'),str(val)); e.set(qn('w:type'),'dxa'); tcMar.append(e)
    tcPr.append(tcMar)

def table(rows,widths,header=True):
    t=doc.add_table(rows=len(rows),cols=len(widths)); t.style='Table Grid'; t.alignment=WD_TABLE_ALIGNMENT.CENTER; t.autofit=False
    tblPr=t._tbl.tblPr; tblW=tblPr.find(qn('w:tblW')); tblW.set(qn('w:w'),str(sum(widths))); tblW.set(qn('w:type'),'dxa')
    grid=t._tbl.tblGrid
    for child in list(grid): grid.remove(child)
    for w in widths:
        gc=OxmlElement('w:gridCol'); gc.set(qn('w:w'),str(w)); grid.append(gc)
    for ri,row in enumerate(rows):
        for ci,txt in enumerate(row):
            c=t.cell(ri,ci); c.text=str(txt); c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER; set_cell_margins(c)
            tcW=c._tc.get_or_add_tcPr().find(qn('w:tcW')); tcW.set(qn('w:w'),str(widths[ci])); tcW.set(qn('w:type'),'dxa')
            for p in c.paragraphs:
                p.paragraph_format.space_after=Pt(0); p.paragraph_format.line_spacing=1.05
                for r in p.runs: font_all(r,8.8,bold=(ri==0 and header),color=(WHITE if ri==0 and header else INK))
            if ri==0 and header:
                shd=OxmlElement('w:shd'); shd.set(qn('w:fill'),DEEP); c._tc.get_or_add_tcPr().append(shd)
    if header:
        trPr=t.rows[0]._tr.get_or_add_trPr(); rep=OxmlElement('w:tblHeader'); rep.set(qn('w:val'),'true'); trPr.append(rep)
    return t

def field(run,instruction):
    for kind,text in [('begin',None),('instr',instruction),('separate',None),('text','1'),('end',None)]:
        if kind in ('begin','separate','end'):
            e=OxmlElement('w:fldChar'); e.set(qn('w:fldCharType'),kind)
        elif kind=='instr': e=OxmlElement('w:instrText'); e.set(qn('xml:space'),'preserve'); e.text=text
        else: e=OxmlElement('w:t'); e.text=text
        run._r.append(e)

# Header and footer
h=sec.header.paragraphs[0]; h.text='COOVIX  /  SOCIAL CONTENT COPY DECK'; h.style='Small'
f=sec.footer.paragraphs[0]; f.alignment=WD_ALIGN_PARAGRAPH.RIGHT; rr=f.add_run('PAGE '); field(rr,'PAGE'); font_all(rr,8.5,color=GREY)

# Cover
add('COOVIX','Kicker',align=WD_ALIGN_PARAGRAPH.CENTER)
for _ in range(5): add('')
if (ROOT/'logo.png').exists():
    q=doc.add_paragraph(); q.alignment=WD_ALIGN_PARAGRAPH.CENTER; shape=q.add_run().add_picture(str(ROOT/'logo.png'),width=Inches(3.1)); shape._inline.docPr.set('title','Coovix logo'); shape._inline.docPr.set('descr','Coovix company logo in blue.')
add('SOCIAL CONTENT\nCOPY DECK','Title',align=WD_ALIGN_PARAGRAPH.CENTER)
add('6 posts • 3 reels • English • Arabic • Kurdish Sorani','Copy Lead',align=WD_ALIGN_PARAGRAPH.CENTER)
add('Simplified designer edition  /  Edition 01','Small',align=WD_ALIGN_PARAGRAPH.CENTER)

# Index
page('QUICK INDEX','Find the text in seconds','Every asset has only two pages: artwork copy, then caption or voice-over.')
rows=[('Page','Asset','Campaign'),('4–5','Post 01 — Digital partner','Meet Coovix'),('6–7','Post 02 — Every digital layer','Meet Coovix'),('8–9','Post 03 — Fkrah','Digital Transformation'),('10–11','Post 04 — Barber Management','Digital Transformation'),('12–13','Post 05 — Champions Digital Menu','How We Build Success'),('14–15','Post 06 — Development process','How We Build Success'),('16–17','Reel 01 — Idea to infrastructure','Meet Coovix'),('18–19','Reel 02 — Every booking has two sides','Digital Transformation'),('20–21','Reel 03 — How Coovix builds success','How We Build Success'),('22','Publishing order and final checklist','—')]
table(rows,[1150,4560,3650])

page('HOW TO USE','What the designer should copy')
add('ON THE DESIGN','Heading 2'); copybox('Use only the text under “Artwork text.” Do not place the full caption on the graphic.',lead=True)
add('IN THE SOCIAL CAPTION','Heading 2'); copybox('Use the caption for the selected language. Hashtags appear once at the end.',lead=True)
add('LANGUAGE FILES','Heading 2'); copybox('Export English, Arabic, and Kurdish as separate designs. Arabic and Kurdish use right-to-left alignment.',lead=True)
add('DESIGN PRIORITY','Heading 2'); copybox('Headline first. Supporting line second. CTA third. Coovix logo last.',lead=True)
add('Naming example','Heading 2'); add('P01_EN.jpg  •  P01_AR.jpg  •  P01_KU.jpg','Copy')

def artwork_block(lang,headline,sub,cta,rtl_flag=False):
    add(lang,'Heading 2',is_rtl=rtl_flag)
    label('Headline' if not rtl_flag else ('العنوان' if lang=='العربية' else 'سەردێڕ'),rtl_flag); copybox(headline,rtl_flag,True)
    label('Supporting line' if not rtl_flag else ('السطر الداعم' if lang=='العربية' else 'هێڵی پشتگیر'),rtl_flag); copybox(sub,rtl_flag)
    label('CTA' if not rtl_flag else ('الدعوة إلى الإجراء' if lang=='العربية' else 'بانگهێشت بۆ هەنگاو'),rtl_flag); copybox(cta,rtl_flag)

def caption_block(lang,caption,rtl_flag=False):
    add(lang,'Heading 2',is_rtl=rtl_flag); copybox(caption,rtl_flag)

for x in posts:
    page(f"POST {x['n']:02d}  /  ARTWORK TEXT",x['title'],x['c'])
    artwork_block('ENGLISH',x['title'],x['sub'],x['cta'])
    artwork_block('العربية',x['ar_h'],x['ar_s'],x['ar_cta'],True)
    artwork_block('کوردی — سۆرانی',x['ku_h'],x['ku_s'],x['ku_cta'],True)

    page(f"POST {x['n']:02d}  /  CAPTIONS",'Copy for publishing')
    caption_block('ENGLISH',x['caption'])
    caption_block('العربية',x['ar_cap'],True)
    caption_block('کوردی — سۆرانی',x['ku_cap'],True)
    add('HASHTAGS','Heading 2'); copybox('#Coovix #DigitalSolutions #WebDevelopment #MobileApps #BusinessSystems')
    add('DESIGN NOTE','Heading 2'); add(x['image']); add('Layout: '+x['layout']); add('Use Coovix blue #0099D8, deep navy #073B55, white space, and authentic project screens where relevant.','Small')

ar_on_screen=[
['لعملك فكرة.','نصوغ التجربة.','نبني كل طبقة رقمية.','من المنتج إلى البنية التحتية.','ابنِ مشروعك مع Coovix.'],
['يجب ألا يبطئ الحجز يوم العمل.','اختر الخدمة.','اختر الشخص والوقت المناسبين.','فريقك يرى يوم العمل بوضوح.','العميل يحجز. وفريقك يبقى مسيطراً.'],
['افهم الهدف.','صمّم التجربة.','ابنِ المنتج.','اختبر كل مسار أساسي.','أطلق بثقة واستمر مع الدعم.']]
ku_on_screen=[
['بازرگانییەکەت بیرۆکەیەکی هەیە.','ئەزموونەکە دادەڕێژین.','هەموو چینە دیجیتاڵییەکان دروست دەکەین.','لە بەرهەمەوە بۆ ژێرخان.','لەگەڵ Coovix دروستی بکە.'],
['حجز نابێت ڕۆژی کار هێواش بکات.','خزمەتگوزاری هەڵبژێرە.','کەس و کاتی گونجاو هەڵبژێرە.','تیمەکەت ڕۆژی کار بە ڕوونی دەبینێت.','کڕیار کات دەگرێت. تیمەکەت کۆنترۆڵی دەبێت.'],
['ئامانجەکە تێبگە.','ئەزموونەکە دیزاین بکە.','بەرهەمەکە دروست بکە.','هەموو ڕێڕەوە گرنگەکان تاقی بکەرەوە.','بە متمانە بڵاوی بکەرەوە و بە پشتگیری بەردەوام بە.']]

for x in reels:
    page(f"REEL {x['n']:02d}  /  SCENE TEXT",x['title'],f"{x['duration']}  •  {x['c']}")
    rows=[('Time','What happens','English on-screen text')]+[(tm,vis,txt) for tm,vis,txt in x['scenes']]
    table(rows,[1250,4380,3730])
    add('ARABIC ON-SCREEN TEXT','Heading 2')
    for i,txt in enumerate(ar_on_screen[x['n']-1],1): add(f'{i}. {txt}','Copy',True)
    add('KURDISH SORANI ON-SCREEN TEXT','Heading 2')
    for i,txt in enumerate(ku_on_screen[x['n']-1],1): add(f'{i}. {txt}','Copy',True)

    page(f"REEL {x['n']:02d}  /  VOICE-OVER",'Recording and ending text')
    caption_block('ENGLISH',x['en_vo'])
    label('End CTA'); copybox(x['cta'][0])
    caption_block('العربية',x['ar_vo'],True)
    label('الدعوة الختامية',True); copybox(x['cta'][1],True)
    caption_block('کوردی — سۆرانی',x['ku_vo'],True)
    label('بانگهێشتی کۆتایی',True); copybox(x['cta'][2],True)
    add('MOTION NOTE','Heading 2'); add('Vertical 9:16. Use clean screen transitions, subtle interface sounds, burned-in captions, and a 1.5–2 second Coovix end frame. Keep all essential text inside platform-safe zones.','Small')

page('PUBLISHING','Recommended order and final check')
table([('Week','Sunday','Tuesday','Thursday'),('1','Post 01','Reel 01','Post 02'),('2','Post 03','Reel 02','Post 04'),('3','Post 05','Reel 03','Post 06')],[1200,2720,2720,2720])
add('FINAL CHECK','Heading 2')
for text in ['Correct asset number and language','Headline matches this document exactly','Arabic and Kurdish are right-aligned','Caption pasted in the correct language','CTA appears once','Logo is clear and not crowded','Real project screens are used','Reel subtitles remain inside safe zones']:
    par=doc.add_paragraph(text,style='List Bullet'); par.paragraph_format.space_after=Pt(4)
add('Core visual rule','Heading 2'); copybox('One idea. One focal point. One action.',lead=True)

doc.core_properties.title='Coovix Social Content Copy Deck — Simplified Edition 01'
doc.core_properties.subject='Designer-ready trilingual copy for six posts and three reels'
doc.core_properties.author='Coovix'
settings=doc.settings._element; upd=OxmlElement('w:updateFields'); upd.set(qn('w:val'),'true'); settings.append(upd)
doc.save(OUT); print(OUT)
