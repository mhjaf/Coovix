from __future__ import annotations

import hashlib
import shutil
import tempfile
import zipfile
from copy import deepcopy
from pathlib import Path

from lxml import etree


REFERENCE = Path('/Users/mh/Downloads/COOVIX_Reels_Final.docx')
OUTPUT = Path('/Applications/MAMP/htdocs/coovix/COOVIX_8_Reels_Final.docx')
EXPECTED_SHA = 'bc1029206949da6d0ab9c278d853549127386ffe400276d6eeafe16d6f5524a9'

W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS = {'w': W}
qn = lambda tag: f'{{{W}}}{tag}'


REELS = [
    {
        'number': 4,
        'title': 'Meet Coovix',
        'ku': [
            ('Scene 1', 'هەر سەرکەوتنێک بە بیرۆکە دەست پێ دەکات.'),
            ('Scene 2', 'بەڵام بیرۆکە بە تەنها کافی نییە.'),
            ('Scene 3', 'پێویستی بە ستراتیژی،\nدیزاین\nو تەکنەلۆژی هەیە.'),
            ('Scene 4', 'لە COOVIX ماڵپەڕ، ئەپی مۆبایل، سیستەمی تایبەت و چارەسەری دیجیتاڵ دروست دەکەین.'),
            ('Scene 5', 'بینینەکەت.\nتەکنەلۆژیی ئێمە.\nسەرکەوتنی تۆ.'),
        ],
        'ar': [
            ('المشهد 1', 'كل مشروع ناجح يبدأ بفكرة.'),
            ('المشهد 2', 'لكن الفكرة وحدها لا تكفي.'),
            ('المشهد 3', 'تحتاج إلى رؤية.\nوتصميم.\nوتقنية.'),
            ('المشهد 4', 'في COOVIX نبني مواقع إلكترونية، تطبيقات، أنظمة مخصصة، وحلولاً رقمية تساعد الشركات على النمو.'),
            ('المشهد 5', 'رؤيتك.\nتقنيتنا.\nنجاحك.'),
        ],
        'en': [
            ('Scene 1', 'Every great business starts with an idea.'),
            ('Scene 2', 'But ideas alone don’t change the world.'),
            ('Scene 3', 'They need strategy.\nDesign.\nTechnology.'),
            ('Scene 4', 'At COOVIX, we build websites, mobile apps, custom systems, and digital solutions that help businesses grow.'),
            ('Scene 5', 'Your Vision.\nOur Technology.\nYour Success.'),
        ],
    },
    {
        'number': 5,
        'title': 'From Idea to Product',
        'ku': [
            'هەر بەرهەمێکی سەرکەوتوو بە گفتوگۆ دەست پێ دەکات.',
            'گوێ دەگرین.',
            'پلان دادەنێین.',
            'دیزاین دەکەین.',
            'پەرەپێدەدەین.',
            'تاقی دەکەینەوە.',
            'Launch دەکەین.',
            'بیرۆکەت دەبێتە ڕاستی.',
        ],
        'ar': [
            'كل منتج ناجح يبدأ بحوار.',
            'نستمع.',
            'نخطط.',
            'نصمم.',
            'نطور.',
            'نختبر.',
            'ثم نطلق المنتج.',
            'فكرتك تصبح واقعاً.',
        ],
        'en': [
            'Every successful product begins with a conversation.',
            'We listen.',
            'We plan.',
            'We design.',
            'We develop.',
            'We test.',
            'We launch.',
            'Your idea becomes reality.',
        ],
    },
    {
        'number': 6,
        'title': 'Your Website Never Sleeps',
        'ku': [
            'ئۆفیسەکەت دادەخرێت.',
            'بەڵام ماڵپەڕەکەت نا.',
            '٢٤ کاتژمێر.',
            '٧ ڕۆژ لە هەفتەدا.',
            'با ماڵپەڕەکەت بۆت کار بکات.',
        ],
        'ar': [
            'قد يغلق مكتبك…',
            'لكن موقعك الإلكتروني لا يتوقف.',
            '24 ساعة.',
            '7 أيام في الأسبوع.',
            'اجعل موقعك يعمل من أجلك.',
            'مع COOVIX.',
        ],
        'en': [
            'Your office closes.',
            'Your website doesn’t.',
            '24 hours a day.',
            '7 days a week.',
            'Your website represents your business.',
            'Make every second count.',
            'Build it with COOVIX.',
        ],
    },
    {
        'number': 7,
        'title': 'Mobile Apps',
        'ku': [
            'کڕیارەکانت هەموو کات لەسەر مۆبایلن.',
            'ئایا دەتوانن بە ئاسانی بگەن بە خزمەتگوزارییەکانت؟',
            'لە COOVIX ئەپی مۆدێرن دروست دەکەین.',
        ],
        'ar': [
            'عملاؤك يستخدمون هواتفهم كل يوم.',
            'هل يستطيعون الوصول إلى خدماتك بسهولة؟',
            'في COOVIX نبني تطبيقات تجعل أعمالك أقرب إلى عملائك.',
        ],
        'en': [
            'Your customers are already on their phones.',
            'The only question is…',
            'Can they reach your business?',
            'We build apps that keep you connected.',
        ],
    },
    {
        'number': 8,
        'title': 'Digital Transformation',
        'ku': [
            'گەشە واتای کاری زیاتر نییە.',
            'واتای کاری زیرەکانەیە.',
            'تەکنەلۆژیی مۆدێرن.',
            'پرۆسەی ئۆتۆماتیکی.',
            'بڕیاری باشتر.',
            'گۆڕانی دیجیتاڵ لە ئێستا دەست پێ دەکات.',
        ],
        'ar': [
            'النمو لا يعني العمل أكثر.',
            'بل يعني العمل بذكاء.',
            'تقنيات حديثة.',
            'عمليات مؤتمتة.',
            'وقرارات أفضل.',
            'ابدأ رحلتك الرقمية مع COOVIX.',
        ],
        'en': [
            'Growth isn’t about working harder.',
            'It’s about working smarter.',
            'Modern technology.',
            'Automated processes.',
            'Better decisions.',
            'Digital transformation starts today.',
        ],
    },
]


def sha256(path: Path) -> str:
    h = hashlib.sha256()
    with path.open('rb') as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def first_rpr(p: etree._Element):
    run = p.find('w:r', NS)
    if run is None:
        return None
    rpr = run.find('w:rPr', NS)
    return deepcopy(rpr) if rpr is not None else None


def clear_inline_content(p: etree._Element) -> None:
    for child in list(p):
        if child.tag in {qn('r'), qn('hyperlink'), qn('fldSimple')}:
            p.remove(child)


def append_run(p: etree._Element, text: str, rpr=None, bold: bool | None = None) -> None:
    r = etree.Element(qn('r'))
    props = deepcopy(rpr) if rpr is not None else etree.Element(qn('rPr'))
    if bold is not None:
        existing = props.find(qn('b'))
        if bold and existing is None:
            props.append(etree.Element(qn('b')))
        elif not bold and existing is not None:
            props.remove(existing)
    if len(props):
        r.append(props)
    parts = text.split('\n')
    for index, part in enumerate(parts):
        t = etree.Element(qn('t'))
        if part.startswith(' ') or part.endswith(' '):
            t.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
        t.text = part
        r.append(t)
        if index < len(parts) - 1:
            r.append(etree.Element(qn('br')))
    p.append(r)


def replace_text(p: etree._Element, text: str) -> None:
    rpr = first_rpr(p)
    clear_inline_content(p)
    append_run(p, text, rpr=rpr)


def replace_scene(p: etree._Element, label: str, text: str) -> None:
    rpr = first_rpr(p)
    clear_inline_content(p)
    append_run(p, label, rpr=rpr, bold=True)
    br_run = etree.Element(qn('r'))
    br_run.append(etree.Element(qn('br')))
    p.append(br_run)
    append_run(p, text, rpr=rpr, bold=False)


def set_page_break_before(p: etree._Element) -> None:
    ppr = p.find(qn('pPr'))
    if ppr is None:
        ppr = etree.Element(qn('pPr'))
        p.insert(0, ppr)
    if ppr.find(qn('pageBreakBefore')) is None:
        ppr.append(etree.Element(qn('pageBreakBefore')))


def clone_text_para(template: etree._Element, item) -> etree._Element:
    p = deepcopy(template)
    if isinstance(item, tuple):
        replace_scene(p, item[0], item[1])
    else:
        replace_text(p, item)
    return p


def update_core_xml(data: bytes) -> bytes:
    root = etree.fromstring(data)
    ns = {'dc': 'http://purl.org/dc/elements/1.1/'}
    title = root.find('dc:title', ns)
    if title is not None:
        title.text = 'COOVIX — 8 Reels'
    return etree.tostring(root, xml_declaration=True, encoding='UTF-8', standalone='yes')


def main() -> None:
    if sha256(REFERENCE) != EXPECTED_SHA:
        raise RuntimeError('Reference changed after template distillation.')

    with zipfile.ZipFile(REFERENCE, 'r') as zin:
        root = etree.fromstring(zin.read('word/document.xml'))
        body = root.find('w:body', NS)
        paras = body.findall('w:p', NS)

        title_template = paras[27]
        divider_template = paras[28]
        label_templates = {'ku': paras[29], 'ar': paras[37], 'en': paras[45]}
        body_templates = {'ku': paras[30], 'ar': paras[38], 'en': paras[46]}
        lockup_templates = {'ku': paras[35], 'ar': paras[43], 'en': paras[51]}
        labels = {'ku': 'کوردی', 'ar': 'العربية', 'en': 'English'}

        sect_pr = body.find('w:sectPr', NS)
        insert_at = body.index(sect_pr)

        for reel in REELS:
            title = deepcopy(title_template)
            replace_text(title, f'REEL {reel["number"]:02d} — {reel["title"]}')
            set_page_break_before(title)
            nodes = [title, deepcopy(divider_template)]

            for lang_index, lang in enumerate(('ku', 'ar', 'en')):
                label = deepcopy(label_templates[lang])
                replace_text(label, labels[lang])
                nodes.append(label)
                nodes.extend(clone_text_para(body_templates[lang], item) for item in reel[lang])
                lockup = deepcopy(lockup_templates[lang])
                replace_text(lockup, 'COOVIX\nTechnology Solutions')
                nodes.append(lockup)
                if lang_index < 2:
                    nodes.append(deepcopy(divider_template))

            for node in nodes:
                body.insert(insert_at, node)
                insert_at += 1

        document_xml = etree.tostring(
            root, xml_declaration=True, encoding='UTF-8', standalone='yes'
        )

        with tempfile.NamedTemporaryFile(dir=OUTPUT.parent, suffix='.docx', delete=False) as tmp:
            tmp_path = Path(tmp.name)
        try:
            with zipfile.ZipFile(tmp_path, 'w') as zout:
                for info in zin.infolist():
                    data = zin.read(info.filename)
                    if info.filename == 'word/document.xml':
                        data = document_xml
                    elif info.filename == 'docProps/core.xml':
                        data = update_core_xml(data)
                    zout.writestr(info, data)
            shutil.move(tmp_path, OUTPUT)
        finally:
            if tmp_path.exists():
                tmp_path.unlink()

    print(OUTPUT)


if __name__ == '__main__':
    main()
