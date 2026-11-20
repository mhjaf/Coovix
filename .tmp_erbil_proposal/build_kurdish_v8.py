from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
from io import BytesIO
from functools import lru_cache
from html import escape
import subprocess
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path('/Applications/MAMP/htdocs/coovix')
TMP = ROOT / '.tmp_erbil_proposal'
ASSETS = ROOT / 'deliverables/assets'
PDF_OUT = ROOT / 'deliverables/Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_Final.pdf'
DOCX_OUT = ROOT / 'deliverables/Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_Final.docx'

NAVY = '#073B55'; BLUE = '#0099D8'; PALE = '#E6F5FC'; LIGHT = '#F7FBFD'
MUTED = '#66818E'; YELLOW = '#FFD000'; BLACK = '#111111'; WHITE = '#FFFFFF'; LINE = '#DCEBF1'
FONT_REG = '/System/Library/Fonts/Supplemental/Arial.ttf'
FONT_BOLD = '/System/Library/Fonts/Supplemental/Arial Bold.ttf'

P2 = ['ئەندامێتی دیجیتاڵ','ئابوونەی مانگانە یان ساڵانە','کارتی ئەندامێتی دیجیتاڵ','سیستەمی خاڵ و خەڵات','ئۆفەر و داشکاندنەکان','ڕاپۆرت و شیکاریی پێشکەوتوو','ئەندامێتی خێزانی و کۆمپانیاکان','بەرنامەی بانگهێشت و ڕەوانەکردنی ئەندامی نوێ','فۆرمی ڕاوبۆچوون و هەڵسەنگاندنی ئەندامان','بیرخستنەوەی خۆکار بۆ نوێکردنەوەی ئەندامێتی','سوود و تایبەتمەندییەکانی سپۆنسەرەکان']
P3 = ['بەڕێوەبردنی ئەکادیمی','حجزکردنی ڕاهێنەر','فرۆشتنی بلیتی چالاکی و یارییەکان','داواکردنی خواردن لە ڕێگەی ئەپ','کۆنترۆڵی زیرەکی دەستگەیشتن','بەڕێوەبردنی پارکینگ','فرۆشگای بەرهەمەکانی یانە','جزدانی پارەی دیجیتاڵ','پارەدانی بێ نەقد','مۆڵەتی دیجیتاڵی میوان','بەڕێوەبردنی داواکارییەکانی چاکسازی','ناوەندی ڕاستەوخۆی یاری','میدیای یانە','هەر خزمەتگوزارییەکی تر کە لە داهاتوودا پێویست بێت']
PHASE1 = ['ماڵپەڕێکی پیشەیی و گونجاو لەگەڵ هەموو شاشەکان','ئەپی Android و iOS','تۆمارکردن، چوونەژوورەوە و OTP','حجزکردنی یاریگا و شوێنی وەرزشی','بینینی کاتە بەردەستەکان بە ڕاستەوخۆ','مێژووی حجزەکان','هەڵوەشاندنەوەی حجز','ئاگادارکردنەوەی خۆکار','پشتڕاستکردنەوەی حجز بە QR Code','تێکخستنی پارەدانی ئۆنلاین','پشتڕاستکردنەوەی پارەدان','وەرگرتنی وەسڵی دیجیتاڵ','داشبۆردی بەڕێوەبەر','بەڕێوەبردنی حجزەکان','بەڕێوەبردنی یاریگا و کات‌بەندی','بەڕێوەبردنی کڕیاران','بەڕێوەبردنی نرخەکان','بەڕێوەبردنی دۆخی پارەدان','ڕاپۆرتی سەرەتایی داهات و حجز','ڕووکارەکانی پەیوەندیی سیستەم','دامەزراندن و فێرکردنی ستاف']

def f(size, bold=False):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REG, size)

def measure(draw, text, font):
    return draw.textlength(text, font=font)

@lru_cache(maxsize=1024)
def text_image(text, size, color, bold):
    width = max(24, int(f(size, bold).getlength(text)) + 28)
    height = int(size * 1.55) + 8
    weight = '700' if bold else '400'
    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}">
    <text x="{width-8}" y="{int(size*1.2)}" text-anchor="end" font-family="Arial, sans-serif" font-size="{size}px" font-weight="{weight}" fill="{color}">{escape(text)}</text></svg>'''
    result = subprocess.run(['rsvg-convert'], input=svg.encode('utf-8'), stdout=subprocess.PIPE, check=True)
    return Image.open(BytesIO(result.stdout)).convert('RGBA')

def paste_rtl(draw, text, right, y, size, color, bold=False):
    art = text_image(text, size, color, bold)
    draw._image.paste(art, (int(right-art.width), int(y)), art)

def wrap(draw, text, font, width):
    words = text.split()
    lines, current = [], ''
    for word in words:
        trial = word if not current else current + ' ' + word
        if measure(draw, trial, font) <= width:
            current = trial
        else:
            if current: lines.append(current)
            current = word
    if current: lines.append(current)
    return lines

def rtl(draw, text, x, y, width, size=26, color=BLACK, bold=False, line=1.24, max_lines=None):
    font = f(size, bold)
    lines = wrap(draw, text, font, width)
    if max_lines: lines = lines[:max_lines]
    for value in lines:
        paste_rtl(draw, value, x + width, y, size, color, bold)
        y += int(size * line)
    return y

def bullet_list(draw, items, x, y, width, size=21, color=BLACK, gap=4):
    for item in items:
        lines = wrap(draw, item, f(size), width - 28)
        paste_rtl(draw, '•', x + width, y, size, BLUE, True)
        ly = y
        for value in lines:
            paste_rtl(draw, value, x + width - 25, ly, size, color, False)
            ly += int(size * 1.2)
        y = ly + gap
    return y

def card(draw, box, title, body=None, fill=WHITE, border=LINE, title_size=23, body_size=18):
    x,y,w,h=box
    draw.rounded_rectangle((x,y,x+w,y+h), radius=18, fill=fill, outline=border, width=2)
    dark = fill in (NAVY, '#052F43', '#12384C')
    cy = rtl(draw, title, x+22, y+18, w-44, title_size, WHITE if dark else NAVY, True, 1.15)
    if body:
        rtl(draw, body, x+22, cy+5, w-44, body_size, '#D9E8EE' if dark else MUTED, False, 1.25)

def slide_base(page, kicker, title, intro=None):
    im = Image.new('RGB',(1920,1080),WHITE); d=ImageDraw.Draw(im)
    d.text((1775,64),f'{page:02}',font=f(20,True),fill=MUTED,anchor='ra')
    rtl(d,kicker,150,70,820,20,BLUE,True,1.0)
    ty=rtl(d,title,150,120,1620,43,NAVY,True,1.12)
    d.line((150,ty+8,1770,ty+8),fill=LINE,width=2)
    if intro: rtl(d,intro,150,ty+33,1620,24,MUTED,False,1.3)
    paste_rtl(d,'پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر',740,1022,17,MUTED,True)
    d.rectangle((1745,1035,1775,1040),fill=BLUE)
    return im,d, max(245,ty+100)

def fit_image(path, size):
    im=Image.open(path).convert('RGB'); im.thumbnail(size,Image.Resampling.LANCZOS)
    return im

def rtl_panels(path, count, size):
    """Reorder an existing horizontal mockup flow for right-to-left reading."""
    source = Image.open(path).convert('RGB')
    panel_width = source.width // count
    output = Image.new('RGB', source.size, WHITE)
    for new_index, old_index in enumerate(reversed(range(count))):
        panel = source.crop((old_index * panel_width, 0, (old_index + 1) * panel_width, source.height))
        output.paste(panel, (new_index * panel_width, 0))
    output.thumbnail(size, Image.Resampling.LANCZOS)
    return output

def build_pdf():
    pages=[]
    # 1 cover
    im=Image.new('RGB',(1920,1080),NAVY); d=ImageDraw.Draw(im)
    d.ellipse((1290,-250,2070,530),fill='#12384C'); d.rectangle((0,880,1920,1080),fill='#052F43')
    coovix=fit_image(ASSETS/'coovix-logo.png',(300,100)); crest=fit_image(ASSETS/'erbil-sports-club-logo.png',(175,190))
    im.paste(coovix,(150,95)); im.paste(crest,(1600,65),crest if crest.mode=='RGBA' else None)
    rtl(d,'پێشنیاری بازرگانی • تۆپی پێ',160,270,1600,25,BLUE,True)
    rtl(d,'یانەی وەرزشی هەولێر',160,330,1600,58,WHITE,True)
    rtl(d,'پلاتفۆرمی دیجیتاڵی تۆپی پێ',160,405,1600,58,WHITE,True)
    rtl(d,'ماڵپەڕ • ئەپی مۆبایل • سیستەمی زیرەکی حجزکردنی یاریگای تۆپی پێ',160,500,1600,27,'#BBD3DF',False)
    rtl(d,'ئەزموونێکی تۆپی پێی یەکگرتوو بۆ هاندەران و ئەندامان',160,625,1600,28,WHITE,True)
    rtl(d,'سیستەمێکی بەڕێوەبردنی ڕوونتر و کاریگەرتر بۆ بەڕێوەبەرایەتی',160,674,1600,25,'#BBD3DF')
    rtl(d,'ئامادەکراوە لەلایەن: Coovix',160,915,1200,25,WHITE,True); rtl(d,'ئەیلوولی ٢٠٢٦',1400,915,360,24,'#BBD3DF')
    pages.append(im)
    # 2
    im,d,y=slide_base(2,'پوختەی بەڕێوەبەرایەتی','یەک پلاتفۆرم دەتوانێت ببێتە دەروازەی سەرەکیی دیجیتاڵی یانەکە','پلاتفۆرمە پێشنیارکراوەکە هەواڵ، حجزکردنی یاریگا، پەیوەندی لەگەڵ هاندەران و بەڕێوەبردنی یانە لە یەک سیستەمی یەکگرتوودا کۆدەکاتەوە.')
    card(d,(1265,310,505,245),'١. ئەندامان','چاودێری هەواڵ و چالاکییەکانی یانە • بینینی یاریگا و شوێنە وەرزشییەکان • پشتڕاستکردنەوەی حجز لە چەند خولەکێکدا',PALE)
    card(d,(707,310,505,245),'٢. کارگێڕی','بەڕێوەبردنی کات‌بەندی، نرخەکان و زانیاریی کڕیاران لە یەک شوێندا.',LIGHT)
    card(d,(150,310,505,245),'٣. بەڕێوەبەرایەتی','بینینی ژمارەی حجزەکان، داهات، کاتەکانی زۆرترین داواکاری و ڕاپۆرتی ڕوون بۆ بڕیاردان.',LIGHT)
    rtl(d,'پێشنیارەکە بریتییە لە',150,605,760,26,BLUE,True)
    bullet_list(d,['ماڵپەڕێکی مۆدێرن','ئەپی Android و iOS','داشبۆردی بەڕێوەبەر','سیستەمی زیرەکی حجزکردن'],150,655,760,23)
    card(d,(1010,620,760,230),'دروستکراوە بۆ گەشەکردنی داهاتوو','ئەم پلاتفۆرمە بناغەیەک دروست دەکات بۆ زیادکردنی خزمەتگوزاری دیجیتاڵی نوێ لە داهاتوودا.',NAVY,NAVY,26,22)
    pages.append(im)
    # 3
    im,d,y=slide_base(3,'دەرفەتەکانی ئێستا','کێشەکانی ئێستا دەکرێت بگۆڕدرێن بۆ دەرفەتی باشتر','ئامانج پاراستنی خزمەتگوزاری نزیک لە خەڵکە، لەگەڵ خێراتر، ڕوونتر و ئاسانترکردنی مامەڵە ڕۆژانەکان.')
    opp=[('١. کاتە بەردەستەکان','زانینی کاتی بەتاڵی یاریگا بە تەلەفۆن پێویستە و خێرا نییە.'),('٢. حجزکردن','حجزکردن بە تەلەفۆن و بە شێوەی دەستی کاتی زۆری ستاف دەخوات.'),('٣. وردی','ئەگەر کات‌بەندییەکان یەکگرتوو نەبن، هەڵەی حجزکردن زیاد دەبێت.'),('٤. پەیوەندی','ئاگادارکردنەوە و بیرخستنەوەکان بە یەک شێوە ناگەنە هەموو کڕیاران.'),('٥. زانیاری','کۆکردنەوە و گەڕان بە زانیاریی حجزەکان ئاسان نییە.'),('٦. ڕاپۆرتدان','بینینی داهات، داواکاری و کاتەکانی زۆرترین بەکارهێنان پێویستی بە باشترکردن هەیە.')]
    for i,(t,b) in enumerate(opp):
        col=i%2; row=i//2; card(d,(970-col*820,310+row*215,770,180),t,b,WHITE,LINE,23,19)
    pages.append(im)
    # 4
    im,d,y=slide_base(4,'چارەسەری پێشنیارکراو','یەک سیستەمی یەکگرتوو هەموو کەناڵەکان لە یەک شوێندا کۆدەکاتەوە','ئەندامان ئەزموونێکی یەکگرتوو هەیە و ستاف هەموو زانیارییەکانی حجز لە یەک سەرچاوەی متمانەپێکراو بەڕێوە دەبات.')
    sol=[('ماڵپەڕ',['زانیاریی یانە','هەواڵەکان','ئەندامێتی','حجزکردنی یاریگا']),('ئەپی مۆبایل',['Android و iOS','QR Code','ئاگادارکردنەوەکان']),('خزمەتگوزارییەکانی یانە',['کاتە بەردەستەکان','حجزکردنی یاریگا','ئەندامان و نرخەکان']),('داشبۆردی بەڕێوەبەر',['کات‌بەندی','ئەندامان','ڕاپۆرت و داهات']),('ستاف',['کارە ڕۆژانەکان','تۆمارکردنی هاتن','پاڵپشتی بەکارهێنەران'])]
    for i,(t,items) in enumerate(sol):
        x=1450-i*325; card(d,(x,310,285,360),t,None,PALE if i in(0,1) else LIGHT)
        bullet_list(d,items,x+20,385,245,19)
    card(d,(150,715,1620,205),'بنکەدراوەی یەکگرتوو','هەموو زانیارییەکانی ئەندامان، ناوەڕۆک و حجزەکان لە یەک سیستەمی ناوەندی پارێزراودا هەڵدەگیرێن، بۆ دڵنیابوون لە وردی و یەکگرتوویی زانیاری.',NAVY,NAVY,25,22)
    pages.append(im)
    # 5
    im,d,y=slide_base(5,'قۆناغەکانی پڕۆژە','قۆناغی یەکەم بنەمای پارەدراوە؛ قۆناغەکانی دواتر هەڵبژاردەن')
    d.rounded_rectangle((810,255,1770,965),radius=22,fill=LIGHT,outline=LINE,width=2)
    rtl(d,'قۆناغی یەکەم (Phase 1)',845,280,880,28,BLUE,True); rtl(d,'پلاتفۆرمی سەرەکی دیجیتاڵ • ئەو خزمەتگوزارییانەی لە نرخی ئێستادا هەن',845,326,880,20,MUTED)
    bullet_list(d,PHASE1,845,375,875,17,BLACK,1)
    d.rounded_rectangle((150,255,770,585),radius=22,fill=PALE,outline=LINE,width=2)
    rtl(d,'قۆناغی دووەم • ئەندامێتی و گەشە',180,280,560,25,NAVY,True); bullet_list(d,['ئەندامێتی دیجیتاڵ','ئابوونەی مانگانە','کارتی ئەندامێتی دیجیتاڵ','خاڵی خەڵات','ئۆفەر و داشکاندن','شیکاریی پێشکەوتوو'],180,335,550,18)
    d.rounded_rectangle((150,610,770,900),radius=22,fill=LIGHT,outline=LINE,width=2)
    rtl(d,'قۆناغی سێیەم • خزمەتگوزارییە زیرەکەکان',180,635,560,23,NAVY,True); bullet_list(d,['بەڕێوەبردنی ئەکادیمی','حجزکردنی ڕاهێنەر','فرۆشتنی بلیت','داواکردنی خواردن','کۆنترۆڵی زیرەکی دەستگەیشتن','پارکینگ و خزمەتگوزاریی زیاتر'],180,685,550,17)
    rtl(d,'تێبینی: قۆناغی دووەم و سێیەم لە نرخی قۆناغی یەکەمدا نین؛ بازنەی کار و نرخەکەیان بە جیا دیاری دەکرێت.',150,925,620,17,MUTED,True)
    pages.append(im)
    # 6
    im,d,y=slide_base(6,'ئەزموونی ماڵپەڕ','ماڵپەڕەکە زانیاریی یانە و حجزکردنی یاریگا لە یەک شوێندا کۆدەکاتەوە','بەکارهێنەر لە ڕووی سەرەکی ماڵپەڕ دەتوانێت هەموو خزمەتگوزارییە گرنگەکان بە ئاسانی بەدەستبهێنێت.')
    web=fit_image(TMP/'docx_assets_kurdish_v7/website.png',(1030,405)); im.paste(web,(150,320))
    card(d,(1220,305,550,330),'نموونەی حجز','یاریگا: مەیدانی ڕاهێنان ١\nڕێکەوت: هەینی ١٣ی ئەیلوول\nکات: ١٨:٣٠ - ٢٠:٠٠\nکۆی گشتی: ٢٥،٠٠٠ دیناری عێراقی',PALE,LINE,25,20)
    rtl(d,'لاپەڕەی سەرەکی و تایبەتمەندییەکان',150,770,1620,25,BLUE,True)
    bullet_list(d,['شانازی هەولێر، لە ناو و دەرەوەی یاریگا','هەولێر × دهۆک • هەینی 7:30 ئێوارە','خشتەی یارییەکان و هەواڵەکان','ئەندامێتی و چوونەژوورەوە','بینینی کاتە بەردەستەکان','پارەدانی ئۆنلاین'],150,815,790,19)
    bullet_list(d,['هەڵبژاردنی یاریگای دڵخواز','بینینی کاتە بەردەستەکان','دیاریکردنی ڕێکەوت و کات','بینینی نرخی گشتی','بەردەوامبوون بۆ پارەدان','خوێندنەوەی هەواڵی تەواو'],980,815,790,19)
    pages.append(im)
    # 7
    im,d,y=slide_base(7,'ئەپی مۆبایل','ئەپی مۆبایل خزمەتگوزارییەکانی یانە بە ئاسانی دەخاتە بەردەستی ئەندامان','بەکارهێنەر لە لاپەڕەی سەرەکی بۆ هەڵبژاردنی یاریگا، بینینی کاتە بەردەستەکان و تەواوکردنی حجز دەچێت.')
    mob=rtl_panels(TMP/'kurdish-mobile-fixed.png',3,(1170,470)); im.paste(mob,(120,315))
    rtl(d,'ڕێڕەوی سەرەکی',1340,325,430,24,BLUE,True)
    bullet_list(d,['بەخێربێیتەوە و خزمەتگوزارییەکان','یاریی داهاتوو و Match Center','یاریگاکانی ڕاهێنان، ئەکادیمی و هەواڵ','مەیدانی ڕاهێنان ١ • 25,000 دینار','مەیدانی ڕاهێنان ٢ • کاتی داهاتوو 20:00','مەیدانی سەرەکی • پەسەندی ستاف','ڕۆژ: پێنجشەممە، هەینی، شەممە','کات: 17:00 یان 18:30','دوگمەی پشتڕاستکردنی کات'],1340,380,430,18)
    pages.append(im)
    # 8
    im,d,y=slide_base(8,'ڕێڕەوی حجز و پارەدان','پارەدان و پشتڕاستکردنەوەی حجز لە یەک ڕێڕەوی ئاساندا ئەنجام دەدرێت','قۆناغی یەکەم پارەدانی ئۆنلاین، وەسڵی دیجیتاڵ و QR Code بۆ چوونەژوورەوەی یاریگا دابین دەکات.')
    flow=rtl_panels(TMP/'kurdish-booking-fixed.png',4,(1240,496)); im.paste(flow,(80,315))
    rtl(d,'وردەکاریی ڕێڕەو',1370,325,400,24,BLUE,True)
    bullet_list(d,['پێداچوونەوە: مەیدانی ڕاهێنان ١ • هەینی ١٨:٣٠ - ٢٠:٠٠ • ٢٥،٠٠٠ دینار','شێوازی پارەدان: کارتی بانکی یان پارەدانی ئەلیکترۆنی','ئەنجام: پارەدانەکە بە سەرکەوتوویی ئەنجام درا • دۆخ: پارەدراو','ژمارەی وەسڵ و وەسڵی دیجیتاڵ','پشتڕاستکردنەوەی حجز و ژمارەی تایبەت','QR Code بۆ چوونەژوورەوە','حجزەکانم: دۆخ، مێژوو و حجزە تەواوبووەکان'],1370,380,400,17)
    pages.append(im)
    # 9
    im,d,y=slide_base(9,'داشبۆردی بەڕێوەبەر','داشبۆردەکە هەموو حجز، پارەدان و کارە ڕۆژانەکان لە یەک شوێندا بەڕێوەدەبات','بەڕێوەبەران و ستاف دەتوانن هەموو زانیاریی گرنگی یانە بە ئاسانی بەڕێوەببەن.')
    admin=fit_image(TMP/'docx_assets_kurdish_v7/admin.png',(950,395)); im.paste(admin,(120,330))
    groups=[('دیمەنی گشتی',['حجزی ئەمڕۆ','ڕێژەی بەکارهێنان','داهاتی ئەمڕۆ','نوێترین حجزەکان']),('حجز و یاریگا',['دۆخ، هەڵوەشاندنەوە و مێژوو','کات‌بەندی، بەردەستبوون و توانای یاریگا']),('کڕیار و پارەدان',['پرۆفایل، مافی دەستگەیشتن و چالاکی','نرخنامە، وەسڵ و دۆخی پارەدان']),('ڕاپۆرت و سوود',['داهات، ژمارەی حجز و کاتەکانی زۆرترین داواکاری','کاتی کەمتر، هەڵەی کەمتر و بڕیاری بەهێزتر'])]
    for i,(t,items) in enumerate(groups):
        x=1450-(i%2)*330; yy=330+(i//2)*300; card(d,(x,yy,310,260),t,None,PALE if i%2==0 else LIGHT,title_size=21)
        bullet_list(d,items,x+18,405+(i//2)*300,274,16)
    pages.append(im)
    # 10
    im,d,y=slide_base(10,'سوودەکانی پڕۆژە بۆ یانە','باشترکردنی ئەزموونی ئەندامان یارمەتیدەرە بۆ بڕیاردانی باشتر','پلاتفۆرمەکە خزمەتگوزاری و زانیاریی پێویست بۆ بڕیاردان باشتر دەکات، بەبێ دڵنیایی لە زیادبوونی داهات.')
    benefits=[('١. ئەزموونێکی باشتر',['حجزی خۆکار و خێرا','پارەدانی ئۆنلاین','پشتڕاستکردنەوەی ڕوون','کاتی چاوەڕوانی کەمتر']),('٢. بەڕێوەبردنی کارامەتر',['پەیوەندیی تەلەفۆنی کەمتر','کارە دووبارەکان کەمتر','پشکنینی دەستیی کەمتر']),('٣. بەکارهێنانی باشتر',['کاتی بەردەست بە ڕاستەوخۆ','بەکارهێنانی شوێنە بەتاڵەکان','پلاندانانی باشتر']),('٤. هەڵەی کەمتر',['یەک سیستەمی کات‌بەندی','ڕێگری لە دووبارە حجزکردن','هەڵەی مرۆیی کەمتر']),('٥. ڕوونی بازرگانی',['دۆخی پارەدان','بەڕێوەبردنی نرخ','بینینی داهات و داواکاری']),('٦. براندێکی بەهێزتر',['ئەزموونێکی مۆدێرن و پیشەیی','وێنەیەکی باشتر لە یانە','نیشاندانی ئامانجی گەشە'])]
    for i,(t,items) in enumerate(benefits):
        col=i%3; row=i//3; x=1250-col*550; yy=310+row*315; card(d,(x,yy,500,280),t,None,PALE if row==0 else LIGHT,title_size=23)
        bullet_list(d,items,x+22,375+row*315,455,18)
    rtl(d,'تێبینی: پلاتفۆرمەکە خزمەتگوزاری و بینینی زانیاری باشتر دەکات، بەڵام دڵنیایی لە زیادبوونی داهات نادات.',150,950,1620,18,MUTED,True)
    pages.append(im)
    # 11
    im,d,y=slide_base(11,'کاتی جێبەجێکردنی قۆناغی یەکەم','ئامانج: تەواوکردنی پڕۆژە لە ماوەی ١٠ تا ١٢ هەفتە','کارەکان بە شێوەی هاوتەریب پێش دەچن، لەگەڵ خاڵی پشکنین بۆ دیزاین، پیشاندانی سیستەمی سەرەکی و دامەزراندنی کۆتایی.')
    rows=[('١','شیکاری و پلاندانان','١ هەفتە'),('٢','دیزاینی UI/UX','٢ هەفتە'),('٣','گەشەپێدانی پلاتفۆرم و ماڵپەڕ','٣ تا ٤ هەفتە'),('٤','گەشەپێدانی ئەپی مۆبایل','٤ تا ٥ هەفتە'),('٥','تێکخستنی سیستەمی پارەدان','١ تا ٢ هەفتە'),('٦','تاقیکردنەوە، دامەزراندن و فێرکردنی ستاف','١ تا ٢ هەفتە')]
    d.rounded_rectangle((150,310,1230,880),radius=18,fill=WHITE,outline=LINE,width=2)
    d.rectangle((150,310,1230,380),fill=NAVY); rtl(d,'خشتەی کاتی جێبەجێکردن',185,330,1000,23,WHITE,True)
    for i,(n,task,dur) in enumerate(rows):
        yy=395+i*76; d.line((170,yy+65,1210,yy+65),fill=LINE,width=1); rtl(d,n,1080,yy+12,90,20,NAVY,True); rtl(d,task,390,yy+12,650,20,BLACK); rtl(d,dur,185,yy+12,180,19,MUTED,True)
    card(d,(1280,310,490,570),'مەرجەکانی خشتە کاتییە',None,PALE)
    bullet_list(d,['پەسەندکردنەکان بە کاتی خۆیان ئەنجام بدرێن.','ناوەڕۆکی پێویست دابین بکرێت.','دەستگەیشتن بە هەژمار و سیستەمەکانی لایەنی سێیەم ئامادە بێت.'],1310,390,430,20)
    pages.append(im)
    # 12
    im,d,y=slide_base(12,'پێشنیاری بازرگانی • قۆناغی یەکەم','نرخی تەواوی پلاتفۆرمی دیجیتاڵی سەرەکی')
    card(d,(150,250,520,190),'6,900 دۆلاری ئەمریکی (USD)','ماوەی ئامادەبوون: ١٠ تا ١٢ هەفتە',NAVY,NAVY,31,22)
    rtl(d,'ئەوەی لە نرخی قۆناغی یەکەمدا هەیە',720,250,1050,24,BLUE,True)
    bullet_list(d,['ماڵپەڕ و ئەپی Android و iOS','سیستەمی حجز و کاتە بەردەستەکان','پشتڕاستکردنەوەی QR','پارەدانی ئۆنلاین و وەسڵی دیجیتاڵ','داشبۆرد و ڕاپۆرتەکان','ڕووکارەکانی پەیوەندیی سیستەم، دامەزراندن و فێرکردنی ستاف'],720,300,1050,19)
    rtl(d,'مەرجەکانی پارەدان',150,485,650,24,BLUE,True)
    terms=[('30%','لە کاتی پەسەندکردنی پڕۆژە'),('40%','پاش پیشاندانی نموونەی کارپێکراوی سیستەمی سەرەکی'),('30%','پاش دامەزراندن و گواستنەوەی پڕۆژە')]
    for i,(pct,txt) in enumerate(terms): card(d,(1230-i*540,535,500,150),pct,txt,PALE if i==1 else LIGHT,title_size=30,body_size=18)
    rtl(d,'ئەو خەرجیانەی لە نرخدا نییە',150,730,1620,24,BLUE,True)
    bullet_list(d,['کرێی مامەڵەکانی Payment Gateway','کرێی بانک یان دابینکەر','خەرجی SMS و OTP','کرێی Apple Developer و Google Play','نوێکردنەوەی Hosting و Domain'],150,780,780,18)
    rtl(d,'تێبینی: قۆناغی دووەم و سێیەم لە نرخی سەرەوەدا نین؛ بازنەی کار و نرخەکەیان لە داهاتوودا بە جیا دیاری دەکرێت.',980,790,790,20,MUTED,True)
    pages.append(im)
    # 13
    im,d,y=slide_base(13,'قۆناغە هەڵبژاردەکانی داهاتوو','قۆناغەکانی داهاتوو خزمەتگوزاریی زیاتری یانە پێشکەش دەکەن','ئەم خزمەتگوزارییانە هەڵبژاردەن و لە بازنەی کاری قۆناغی یەکەمدا نین.')
    d.rounded_rectangle((150,300,930,925),radius=20,fill=PALE,outline=LINE,width=2); d.rounded_rectangle((990,300,1770,925),radius=20,fill=LIGHT,outline=LINE,width=2)
    rtl(d,'قۆناغی دووەم • ئەندامێتی و گەشە',1020,325,720,25,NAVY,True); rtl(d,'بۆ زیادکردنی بەهای ئەندامێتی و پەیوەندیی بەهێزتر لەگەڵ هاندەران و ئەندامان.',1020,370,720,17,MUTED)
    bullet_list(d,P2,1020,425,720,17,BLACK,0)
    rtl(d,'قۆناغی سێیەم • خزمەتگوزارییە زیرەکەکان',180,325,720,24,NAVY,True); rtl(d,'بۆ گۆڕینی یانە بۆ یەکێک لە پێشکەوتووترین یانە دیجیتاڵییەکان.',180,370,720,17,MUTED)
    bullet_list(d,P3,180,425,720,16,BLACK,0)
    rtl(d,'تێبینی: قۆناغی دووەم و سێیەم هەڵبژاردەن؛ هیچ نرخێک لەم پێشنیارەدا دیاری نەکراوە و لە کاتی جێبەجێکردنیاندا بە جیاواز نرخ بۆ دیاری دەکرێت.',150,952,1620,16,MUTED,True)
    pages.append(im)
    # 14
    im,d,y=slide_base(14,'کۆتایی','قۆناغی یەکەم بناغەیەکی تەواوی دیجیتاڵ بۆ یانەی وەرزشی هەولێر دابین دەکات','پلاتفۆرمەکە ماڵپەڕ، ئەپی مۆبایل، حجز، پارەدانی ئۆنلاین و بەڕێوەبردن لە یەک ژینگەی یەکگرتوو کۆدەکاتەوە.')
    blocks=[('١. ئەزموونی ئەندامان',['دۆزینەوەی خزمەتگوزارییەکان','پارەدانی ئۆنلاین','حجزی پشتڕاستکراو و QR Code']),('٢. کۆنترۆڵی کارگێڕی',['کات‌بەندی و کڕیاران','دۆخی پارەدان','ڕاپۆرتە سەرەتاییەکان']),('٣. ئامادەیی بۆ گەشە',['سیستەمی ئەندامێتی','خزمەتگوزارییە زیرەکەکان','مۆدیولی نوێ بە پێی پێویستی یانە'])]
    for i,(t,items) in enumerate(blocks):
        x=1250-i*550; card(d,(x,300,500,250),t,None,PALE if i==0 else LIGHT,title_size=23); bullet_list(d,items,x+20,365,460,18)
    rtl(d,'هەنگاوی پێشنیارکراوی داهاتوو',150,600,1620,26,BLUE,True); rtl(d,'پەسەندکردنی قۆناغی یەکەم و دەستپێکردنی پڕۆژە',150,645,1620,24,NAVY,True)
    bullet_list(d,['شیکاری وردی پڕۆژە','کۆکردنەوەی ناوەڕۆک','دیزاینکردنی ڕووکاری بەکارهێنەر و ئەزموونی بەکارهێنەر','دەستپێکردنی گەشەپێدانی سیستەم'],150,700,750,19)
    card(d,(980,650,790,255),'سوپاس بۆ کاتی بەڕێزتان','ئێمە لە Coovix باوەڕمان وایە ئەم پلاتفۆرمە دەتوانێت ببێتە بناغەی گۆڕانکاریی دیجیتاڵی یانەی وەرزشی هەولێر و ئەزموونێکی مۆدێرن، خێرا و متمانەپێکراو بۆ ئەندامان، هاندەران و بەڕێوەبەرایەتی دابین بکات.',NAVY,NAVY,25,19)
    pages.append(im)
    page_dir = TMP / 'kurdish_final_pages'
    page_dir.mkdir(parents=True, exist_ok=True)
    for index, page in enumerate(pages, start=1):
        page.save(page_dir / f'page-{index:02}.png', 'PNG', optimize=True)
    pages[0].save(PDF_OUT,'PDF',resolution=150.0,save_all=True,append_images=pages[1:])
    return pages

# Word helpers
def hexrgb(value): return RGBColor.from_string(value.replace('#',''))
def set_rtl_p(p, center=False):
    p.alignment=WD_ALIGN_PARAGRAPH.CENTER if center else WD_ALIGN_PARAGRAPH.RIGHT
    ppr=p._p.get_or_add_pPr(); bidi=OxmlElement('w:bidi'); bidi.set(qn('w:val'),'1'); ppr.append(bidi)
def fmt(run,size=10,color=BLACK,bold=False):
    run.font.name='Arial'; run.font.size=Pt(size); run.font.bold=bold; run.font.color.rgb=hexrgb(color)
    rpr=run._element.get_or_add_rPr(); rpr.rFonts.set(qn('w:ascii'),'Arial'); rpr.rFonts.set(qn('w:hAnsi'),'Arial'); rpr.rFonts.set(qn('w:cs'),'Arial'); rtlx=OxmlElement('w:rtl'); rtlx.set(qn('w:val'),'1'); rpr.append(rtlx)
def para(doc,text,size=10,color=BLACK,bold=False,after=4,before=0,center=False,keep=False):
    p=doc.add_paragraph(); set_rtl_p(p,center); p.paragraph_format.space_after=Pt(after); p.paragraph_format.space_before=Pt(before); p.paragraph_format.line_spacing=1.08; p.paragraph_format.keep_with_next=keep; fmt(p.add_run(text),size,color,bold); return p
def bullet(doc,text,size=9,after=1): return para(doc,'• '+text,size,BLACK,False,after)
def heading(doc,text,size=15): return para(doc,text,size,BLUE,True,after=5,keep=True)
def title(doc,kicker,text,intro=None):
    para(doc,kicker,size=9,color=BLUE,bold=True,after=3,keep=True); para(doc,text,size=20,color=NAVY,bold=True,after=6,keep=True)
    if intro: para(doc,intro,size=9.5,color=MUTED,after=8)
def shade(cell,color):
    tcpr=cell._tc.get_or_add_tcPr(); shd=OxmlElement('w:shd'); shd.set(qn('w:fill'),color.replace('#','')); tcpr.append(shd)
def border(cell,color=LINE):
    tcpr=cell._tc.get_or_add_tcPr(); b=OxmlElement('w:tcBorders'); tcpr.append(b)
    for edge in ('top','left','bottom','right','insideH','insideV'):
        e=OxmlElement('w:'+edge); e.set(qn('w:val'),'single'); e.set(qn('w:sz'),'5'); e.set(qn('w:color'),color.replace('#','')); b.append(e)
def table_rtl(table):
    el=OxmlElement('w:bidiVisual'); el.set(qn('w:val'),'1'); table._tbl.tblPr.append(el)
def cell_text(cell,title_text,items=None,size=8.6,fill=LIGHT):
    shade(cell,fill); border(cell); cell.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.TOP
    p=cell.paragraphs[0]; set_rtl_p(p); p.paragraph_format.space_after=Pt(3); fmt(p.add_run(title_text),size+1.5,NAVY,True)
    for item in items or []:
        p=cell.add_paragraph(); set_rtl_p(p); p.paragraph_format.space_after=Pt(1); p.paragraph_format.line_spacing=1.0; fmt(p.add_run('• '+item),size)

def build_docx():
    doc=Document(); sec=doc.sections[0]; sec.page_width=Inches(8.5); sec.page_height=Inches(11); sec.top_margin=Inches(.62); sec.bottom_margin=Inches(.58); sec.left_margin=Inches(.72); sec.right_margin=Inches(.72); sec.header_distance=Inches(.3); sec.footer_distance=Inches(.3); sec.different_first_page_header_footer=True
    normal=doc.styles['Normal']; normal.font.name='Arial'; normal.font.size=Pt(10)
    h=sec.header.paragraphs[0]; set_rtl_p(h); fmt(h.add_run('پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر                                      COOVIX'),8,MUTED,True)
    fp=sec.footer.paragraphs[0]; set_rtl_p(fp); fmt(fp.add_run('ئامادەکراوە لەلایەن Coovix | '),8,MUTED); fld=OxmlElement('w:fldSimple'); fld.set(qn('w:instr'),'PAGE'); fp._p.append(fld)
    # cover
    logos=doc.add_table(rows=1,cols=2); logos.alignment=WD_TABLE_ALIGNMENT.CENTER
    for c in logos.rows[0].cells: border(c,WHITE)
    logos.cell(0,0).paragraphs[0].add_run().add_picture(str(ASSETS/'coovix-logo.png'),width=Inches(1.55))
    logos.cell(0,1).paragraphs[0].alignment=WD_ALIGN_PARAGRAPH.RIGHT; logos.cell(0,1).paragraphs[0].add_run().add_picture(str(ASSETS/'erbil-sports-club-logo.png'),height=Inches(1.2))
    para(doc,'پێشنیاری بازرگانی • تۆپی پێ',11,BLUE,True,after=15,before=28,center=True); para(doc,'یانەی وەرزشی هەولێر',26,NAVY,True,after=4,center=True); para(doc,'پلاتفۆرمی دیجیتاڵی تۆپی پێ',26,NAVY,True,after=10,center=True); para(doc,'ماڵپەڕ • ئەپی مۆبایل • سیستەمی زیرەکی حجزکردنی یاریگای تۆپی پێ',13,MUTED,False,after=24,center=True)
    para(doc,'ئەزموونێکی تۆپی پێی یەکگرتوو بۆ هاندەران و ئەندامان',12,NAVY,True,after=4,center=True); para(doc,'سیستەمێکی بەڕێوەبردنی ڕوونتر و کاریگەرتر بۆ بەڕێوەبەرایەتی',11,MUTED,False,after=28,center=True); para(doc,'ئامادەکراوە لەلایەن: Coovix',12,NAVY,True,after=3,center=True); para(doc,'ئەیلوولی ٢٠٢٦',10,MUTED,False,center=True)
    # 2
    doc.add_page_break(); title(doc,'کورتەی جێبەجێکردن (Executive Summary)','یەک پلاتفۆرم دەتوانێت دەروازەی سەرەکی دیجیتاڵی یانەکە بێت','پلاتفۆرمە پێشنیارکراوەکە هەواڵەکانی تۆپی پێ، حجزکردنی یاریگا، پەیوەندی لەگەڵ هاندەران و بەڕێوەبردنی یانە لە یەک سیستەمی یەکگرتوو کۆدەکاتەوە، بۆ دروستکردنی ئەزموونێکی ئاسان و هاوبەش.')
    t=doc.add_table(rows=1,cols=3); table_rtl(t)
    cell_text(t.cell(0,0),'١. ئەندامان',['چاودێری هەواڵ و چالاکییەکانی یانە','بینینی یاریگا و شوێنە وەرزشییەکان','پشتڕاستکردنەوەی حجز لە چەند خولەکێکدا'],8.5,PALE)
    cell_text(t.cell(0,1),'٢. کارگێڕی',['بەڕێوەبردنی کاتبەندی، نرخەکان و زانیاریی کڕیاران لە یەک شوێن'],8.5,LIGHT)
    cell_text(t.cell(0,2),'٣. بەڕێوەبەرایەتی',['ژمارەی حجزەکان','داهات','کاتی زۆرترین داواکاری (Peak Hours)','ڕاپۆرتی ڕوون بۆ بڕیاردان'],8.5,LIGHT)
    heading(doc,'پێشنیارەکە بریتییە لە',13); [bullet(doc,x,9) for x in ['ماڵپەڕێکی مۆدێرن','ئەپی Android و iOS','داشبۆردی بەڕێوەبەر','سیستەمی زیرەکی حجزکردن']]; heading(doc,'دروستکراوە بۆ گەشەکردنی داهاتوو',12); para(doc,'ئەم پلاتفۆرمە بناغەیەک دروست دەکات بۆ زیادکردنی خزمەتگوزاری دیجیتاڵی نوێ لە داهاتوودا.',9.5)
    # 3
    doc.add_page_break(); title(doc,'دەرفەتەکانی ئێستا','کێشەکانی ئێستا دەکرێت بگۆڕدرێن بۆ دەرفەتی باشتر','ئامانجی سەرەکی پاراستنی خزمەتگوزاری نزیک لە خەڵکە، لەگەڵ خێراتر، ڕوونتر و ئاسانترکردنی مامەڵە ڕۆژانەکان.')
    opp=[('١. بەردەستبوون (Availability)','زانینی کاتی بەتاڵی یاریگا بە تەلەفۆن پێویستە و خێرا نییە.'),('٢. حجزکردن (Reservations)','حجزکردنی تەلەفۆنی و دەستی کاتی زۆری ستاف دەخوات.'),('٣. دروستی (Accuracy)','ئەگەر کاتبەندییەکان یەکگرتوو نەبن، هەڵەی حجزکردن زیاد دەبێت.'),('٤. پەیوەندی (Communication)','هەموو ئاگادارکردنەوە و بیرخستنەوەکان بە یەک شێوە ناگەنە هەموو کڕیاران.'),('٥. داتا (Data)','کۆکردنەوە و گەڕان بە زانیاری حجزەکان ئاسان نییە.'),('٦. ڕاپۆرت (Reporting)','بینینی داهات، داواکاری و کاتەکانی زۆرترین بەکارهێنان دەکرێت باشتر بکرێت.')]
    t=doc.add_table(rows=3,cols=2); table_rtl(t)
    for i,(a,b) in enumerate(opp): cell_text(t.cell(i//2,i%2),a,[b],8.7,PALE if i%2==0 else LIGHT)
    # 4
    doc.add_page_break(); title(doc,'چارەسەری پێشنیارکراو','یەک سیستەمی یەکگرتوو هەموو کەناڵەکان لە یەک شوێندا کۆدەکاتەوە','ئەندامان ئەزموونێکی یەکگرتوو هەیە و ستاف هەموو زانیارییەکانی حجز لە یەک سەرچاوەی متمانەپێکراو بەڕێوە دەبات.')
    t=doc.add_table(rows=2,cols=3); table_rtl(t); groups=[('ماڵپەڕ (Website)',['زانیاریی یانە','هەواڵەکان','ئەندامێتی','حجزکردنی یاریگا']),('ئەپی مۆبایل',['Android و iOS','QR Code','ئاگادارکردنەوەکان']),('خزمەتگوزارییەکانی یانە',['کاتە بەردەستەکان','حجزکردنی یاریگا','ئەندامان','نرخەکان']),('داشبۆردی بەڕێوەبەر',['کاتبەندی','ئەندامان','ڕاپۆرتەکان','داهات']),('ستاف (Staff)',['کارە ڕۆژانەکان','Check-in','پاڵپشتی بەکارهێنەران']),('بنکەی هاوبەشی زانیاری',['زانیاریی ئەندامان، ناوەڕۆک و حجز لە یەک سیستەمی ناوەندی پارێزراو دەبن.'])]
    for i,(a,b) in enumerate(groups): cell_text(t.cell(i//3,i%3),a,b,8.2,PALE if i<3 else LIGHT)
    # 5
    doc.add_page_break(); title(doc,'قۆناغەکانی پڕۆژە','قۆناغی یەکەم پلاتفۆرمی سەرەکیی پارەدراوە؛ قۆناغەکانی دواتر هەڵبژاردەن')
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'قۆناغی یەکەم (Phase 1) • لە نرخی ئێستادا',PHASE1,7.45,PALE); cell_text(t.cell(0,1),'قۆناغی دووەم و سێیەم • داهاتوو',['قۆناغی ٢: ئەندامێتی دیجیتاڵ، بەشداری مانگانە، کارت، خاڵی خەڵات، ئۆفەر و شیکاری','قۆناغی ٣: ئەکادیمی، ڕاهێنەر، بلیت، خواردن، Smart Access Control، پارکینگ و خزمەتگوزاریی زیاتر','تێبینی: قۆناغی دووەم و سێیەم لە نرخی Phase 1 دا نییە و بە جیاواز نرخ بۆ دیاری دەکرێت.'],8.0,LIGHT)
    # 6
    doc.add_page_break(); title(doc,'ئەزموونی ماڵپەڕ (Website Experience)','ماڵپەڕەکە زانیاریی یانە و حجزکردنی یاریگا لە یەک شوێندا کۆدەکاتەوە','بەکارهێنەر لە ڕووی سەرەکی ماڵپەڕ دەتوانێت خزمەتگوزارییە گرنگەکان بە ئاسانی بەدەستبهێنێت.')
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.add_run().add_picture(str(TMP/'docx_assets_kurdish_v7/website.png'),width=Inches(6.7))
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'لاپەڕەی سەرەکی و یاری',['شانازی هەولێر لە ناو و دەرەوەی یاریگا','هەولێر × دهۆک • هەینی 7:30 ئێوارە','خشتەی یارییەکان، هەواڵ، ئەندامێتی و حجز'],7.8,PALE); cell_text(t.cell(0,1),'نموونەی حجز',['مەیدانی ڕاهێنان ١','هەینی 13 ئەیلوول','18:30 - 20:00','25,000 دیناری عێراقی','بینینی کات، نرخی گشتی و پارەدانی ئۆنلاین'],7.8,LIGHT)
    # 7
    doc.add_page_break(); title(doc,'ئەپی مۆبایل (Mobile Application)','ئەپی مۆبایل خزمەتگوزارییەکانی یانە بە ئاسانی دەخاتە بەردەستی ئەندامان','بەکارهێنەر لە لاپەڕەی سەرەکی بۆ هەڵبژاردنی یاریگا، بینینی کاتە بەردەستەکان و تەواوکردنی حجز دەچێت.')
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.add_run().add_picture(str(TMP/'kurdish-mobile-fixed.png'),width=Inches(6.7))
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'لاپەڕەی سەرەکی',['بەخێربێیتەوە','Match Center','یاریگاکانی ڕاهێنان','ئەکادیمی و هەواڵ'],7.6,PALE); cell_text(t.cell(0,1),'هەڵبژاردن و حجز',['مەیدانی ١: ئەمڕۆ • 25,000 دینار','مەیدانی ٢: 20:00 • 25,000 دینار','مەیدانی سەرەکی: پەسەندی ستاف','پێنجشەممە، هەینی، شەممە','17:00 یان 18:30 • پشتڕاستکردنی کات'],7.6,LIGHT)
    # 8
    doc.add_page_break(); title(doc,'ڕێڕەوی حجز و پارەدان','پارەدان و پشتڕاستکردنەوەی حجز لە یەک ڕێڕەوی ئاساندا ئەنجام دەدرێت','قۆناغی یەکەم پارەدانی ئۆنلاین، وەسڵی دیجیتاڵ و QR Code بۆ چوونە ناو یاریگا دابین دەکات.')
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.add_run().add_picture(str(TMP/'kurdish-booking-fixed.png'),width=Inches(6.7))
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'پێداچوونەوە و پارەدان',['مەیدانی ڕاهێنان ١ • هەینی 18:30 - 20:00','25,000 دیناری عێراقی','کارتی بانکی یان پارەدانی ئەلیکترۆنی','پارەدراو • ژمارەی وەسڵ • وەسڵی دیجیتاڵ'],7.6,PALE); cell_text(t.cell(0,1),'پشتڕاستکردنەوە و حجزەکانم',['ژمارەی تایبەتی حجز','QR Code بۆ چوونە ژوورەوە','بینینی هەموو حجزەکان','دۆخی هەر حجز و حجزە تەواوبووەکان'],7.6,LIGHT)
    # 9
    doc.add_page_break(); title(doc,'داشبۆردی بەڕێوەبەر (Admin Dashboard)','داشبۆردەکە حجز، پارەدان و کارە ڕۆژانەکان لە یەک شوێندا بەڕێوەدەبات','بەڕێوەبەران و ستاف دەتوانن هەموو زانیاریی گرنگی یانە بە ئاسانی بەڕێوەببەن.')
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.add_run().add_picture(str(TMP/'docx_assets_kurdish_v7/admin.png'),width=Inches(6.7))
    t=doc.add_table(rows=2,cols=2); table_rtl(t); groups=[('دیمەنی گشتی',['حجزی ئەمڕۆ، بەکارهێنان، داهات و دوا حجزکراوەکان']),('حجز و یاریگا',['دۆخ، هەڵوەشاندنەوە، مێژوو، کاتبەندی و بەردەستبوون']),('کڕیار و پارەدان',['پرۆفایل، مافی دەستگەیشتن، نرخنامە، وەسڵ و دۆخی پارەدان']),('ڕاپۆرت و سوود',['داهات، ژمارەی حجز و Peak Hours • کاتی کەمتر و بڕیاری بەهێزتر'])]
    for i,(a,b) in enumerate(groups): cell_text(t.cell(i//2,i%2),a,b,7.6,PALE if i%2==0 else LIGHT)
    # 10
    doc.add_page_break(); title(doc,'سوودەکانی پڕۆژە بۆ یانە','باشترکردنی ئەزموونی ئەندامان یارمەتیدەرە بۆ بڕیاردانی باشتر','پلاتفۆرمەکە خزمەتگوزاری و زانیاریی پێویست بۆ بڕیاردان باشتر دەکات، بەبێ دڵنیایی لە زیادبوونی داهات.')
    benefits=[('١. ئەزموونێکی باشتر',['حجزی خۆکار و خێرا','پارەدانی ئۆنلاین','پشتڕاستکردنەوەی ڕوون','کاتی چاوەڕوانی کەمتر']),('٢. بەڕێوەبردنی کارامەتر',['پەیوەندیی تەلەفۆنی کەمتر','کارە دووبارەکان کەمتر','پشکنینی دەستی کەمتر']),('٣. بەکارهێنانی باشتر',['کاتی بەردەست بە ڕاستەوخۆ','شوێنە بەتاڵەکان','پلاندانانی باشتر']),('٤. هەڵەی کەمتر',['یەک کاتبەندی','ڕێگری لە دووبارە حجزکردن','هەڵەی مرۆیی کەمتر']),('٥. ڕوونی بازرگانی',['دۆخی پارەدان','نرخ، داهات و داواکاری']),('٦. بەهێزکردنی براند',['ئەزموونی مۆدێرن و پڕۆفیشناڵ','وێنەیەکی باشتر لە یانە'])]
    t=doc.add_table(rows=3,cols=2); table_rtl(t)
    for i,(a,b) in enumerate(benefits): cell_text(t.cell(i//2,i%2),a,b,8.0,PALE if i%2==0 else LIGHT)
    para(doc,'تێبینی: پلاتفۆرمەکە خزمەتگوزاری و بینینی زانیاری باشتر دەکات، بەڵام دڵنیایی لە زیادبوونی داهات نادات.',8.5,MUTED,True,before=5)
    # 11
    doc.add_page_break(); title(doc,'کاتی جێبەجێکردنی قۆناغی یەکەم','ئامانج: تەواوکردنی پڕۆژە لە ماوەی 10 تا 12 هەفتە','کارەکان بە شێوەی هاوشێوە پێش دەچن، لەگەڵ خاڵەکانی پشکنین بۆ دیزاین، پیشاندانی سیستەمی سەرەکی و دامەزراندنی کۆتایی.')
    rows=[('١','شیکاری و پلاندانان','1 هەفتە'),('٢','دیزاینی UI/UX','2 هەفتە'),('٣','گەشەپێدانی پلاتفۆرم و ماڵپەڕ','3 تا 4 هەفتە'),('٤','گەشەپێدانی ئەپی مۆبایل','4 تا 5 هەفتە'),('٥','تێکخستنی سیستەمی پارەدان','1 تا 2 هەفتە'),('٦','تاقیکردنەوە، دامەزراندن و فێرکردنی ستاف','1 تا 2 هەفتە')]
    t=doc.add_table(rows=1,cols=3); table_rtl(t)
    for i,v in enumerate(['هەنگاو','کار','ماوە']): shade(t.cell(0,i),NAVY); border(t.cell(0,i)); p=t.cell(0,i).paragraphs[0]; set_rtl_p(p); fmt(p.add_run(v),9,WHITE,True)
    for row in rows:
        cells=t.add_row().cells
        for i,v in enumerate(row): border(cells[i]); shade(cells[i],LIGHT if len(t.rows)%2 else WHITE); p=cells[i].paragraphs[0]; set_rtl_p(p); fmt(p.add_run(v),8.6,NAVY if i==0 else BLACK,i==0)
    heading(doc,'مەرجەکانی ئەم خشتە کاتییە',12); [bullet(doc,x,8.5) for x in ['پەسەندکردنەکان بە کاتی خۆیان ئەنجام بدرێن.','ناوەڕۆکی پێویست دابین بکرێت.','دەستگەیشتن بە هەژمار و سیستەمەکانی لایەنی سێیەم ئامادە بێت.']]
    # 12
    doc.add_page_break(); title(doc,'پێشنیاری بازرگانی • قۆناغی یەکەم','نرخی تەواوی پلاتفۆرمی دیجیتاڵی سەرەکی')
    para(doc,'6,900 دۆلاری ئەمریکی (USD)',20,NAVY,True,after=2,center=True); para(doc,'ماوەی ئامادەبوون: 10 تا 12 هەفتە',10,MUTED,True,after=8,center=True)
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'لە نرخی قۆناغی یەکەمدا',['ماڵپەڕ و ئەپی Android و iOS','سیستەمی حجز و کاتە بەردەستەکان','QR و پارەدانی ئۆنلاین','وەسڵی دیجیتاڵ','داشبۆرد و ڕاپۆرتەکان','Backend APIs','دامەزراندن و فێرکردنی ستاف'],7.8,PALE); cell_text(t.cell(0,1),'مەرجەکانی پارەدان',['30% لە کاتی پەسەندکردن','40% پاش Demo ی سیستەمی سەرەکی','30% پاش دامەزراندن و گواستنەوە'],7.8,LIGHT)
    heading(doc,'ئەو خەرجیانەی لە نرخدا نییە',11); [bullet(doc,x,8.0) for x in ['کرێی مامەڵەکانی Payment Gateway','کرێی بانک یان دابینکەر','خەرجی SMS و OTP','کرێی Apple Developer','کرێی Google Play','نوێکردنەوەی Hosting','نوێکردنەوەی Domain']]; para(doc,'تێبینی: قۆناغی دووەم و سێیەم لە نرخی سەرەوەدا نییە و لە داهاتوودا بە جیاواز نرخ بۆ دیاری دەکرێت.',8.3,MUTED,True,before=4)
    # 13
    doc.add_page_break(); title(doc,'قۆناغە هەڵبژاردەکانی داهاتوو','قۆناغەکانی داهاتوو خزمەتگوزاریی زیاتری یانە پێشکەش دەکەن','ئەم خزمەتگوزارییانە هەڵبژاردەن و لە بازنەی کاری قۆناغی یەکەمدا نین.')
    t=doc.add_table(rows=1,cols=2); table_rtl(t); cell_text(t.cell(0,0),'قۆناغی دووەم • ئەندامێتی و گەشە',P2,7.25,PALE); cell_text(t.cell(0,1),'قۆناغی سێیەم • خزمەتگوزارییە زیرەکەکان',P3,6.95,LIGHT)
    para(doc,'تێبینی: قۆناغی دووەم و سێیەم هەڵبژاردەن و لە بازنەی کاری Phase 1 دانین. هیچ نرخێک دیاری نەکراوە و لە کاتی جێبەجێکردنیاندا بە جیاواز نرخ بۆ دیاری دەکرێت.',7.8,MUTED,True,before=4)
    # 14
    doc.add_page_break(); title(doc,'کۆتایی','قۆناغی یەکەم بناغەیەکی تەواوی دیجیتاڵ بۆ یانەی وەرزشی هەولێر دابین دەکات','پلاتفۆرمەکە ماڵپەڕ، ئەپی مۆبایل، حجزکردنی یاریگا، پارەدانی ئۆنلاین و سیستەمی بەڕێوەبردن لە یەک ژینگەی یەکگرتوو کۆدەکاتەوە.')
    t=doc.add_table(rows=1,cols=3); table_rtl(t); cell_text(t.cell(0,0),'١. ئەزموونی ئەندامان',['دۆزینەوەی خزمەتگوزارییەکان','پارەدانی ئۆنلاین','حجزی پشتڕاستکراو و QR Code'],7.5,PALE); cell_text(t.cell(0,1),'٢. کۆنترۆڵی کارگێڕی',['کاتبەندی','کڕیاران','دۆخی پارەدان','ڕاپۆرتە سەرەتاییەکان'],7.5,LIGHT); cell_text(t.cell(0,2),'٣. ئامادەیی بۆ گەشە',['سیستەمی ئەندامێتی','خزمەتگوزارییە زیرەکەکان','مۆدیولی نوێ بە پێی پێویستی یانە'],7.5,LIGHT)
    heading(doc,'هەنگاوی پێشنیارکراوی داهاتوو',12); para(doc,'پەسەندکردنی قۆناغی یەکەم و دەستپێکردنی پڕۆژە',9.5,NAVY,True); [bullet(doc,x,8.2) for x in ['شیکاری وردی پڕۆژە','کۆکردنەوەی ناوەڕۆک','دیزاینکردنی ئەزموونی بەکارهێنەر (UI/UX)','دەستپێکردنی گەشەپێدانی سیستەم']]
    heading(doc,'کۆتایی',11); para(doc,'سوپاس بۆ کاتی بەڕێزتان و بۆ هەلی پێشکەشکردنی ئەم پڕۆژەیە. ئێمە لە Coovix باوەڕمان وایە ئەم پلاتفۆرمە دەتوانێت ببێتە بناغەی گۆڕانکاریی دیجیتاڵی یانەی وەرزشی هەولێر و ئەزموونێکی مۆدێرن، خێرا و متمانەپێکراو بۆ ئەندامان، هاندەران و بەڕێوەبەرایەتی دابین بکات.',8.5,MUTED)
    doc.core_properties.title='پێشنیاری پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر'; doc.core_properties.author='Coovix'; doc.save(DOCX_OUT)

def build_docx_visual(pages):
    """Create a Word copy that preserves the approved 16:9 proposal design exactly."""
    doc = Document()
    sec = doc.sections[0]
    sec.page_width = Inches(13.333)
    sec.page_height = Inches(7.5)
    sec.top_margin = Inches(0)
    sec.bottom_margin = Inches(0)
    sec.left_margin = Inches(0)
    sec.right_margin = Inches(0)
    sec.header_distance = Inches(0)
    sec.footer_distance = Inches(0)
    sec.header.is_linked_to_previous = False
    sec.footer.is_linked_to_previous = False
    sec.header.paragraphs[0].text = ''
    sec.footer.paragraphs[0].text = ''
    first = doc.add_paragraph()
    for index, _page in enumerate(pages, start=1):
        p = first if index == 1 else doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(0)
        p.paragraph_format.line_spacing = 1
        p.paragraph_format.page_break_before = index > 1
        p.add_run().add_picture(
            str(TMP / 'kurdish_final_pages' / f'page-{index:02}.png'),
            width=Inches(13.30),
        )
    doc.core_properties.title = 'پێشنیاری پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر'
    doc.core_properties.author = 'Coovix'
    doc.save(DOCX_OUT)

if __name__=='__main__':
    rendered_pages = build_pdf(); build_docx_visual(rendered_pages); print(PDF_OUT); print(DOCX_OUT)
