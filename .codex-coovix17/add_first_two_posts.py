from __future__ import annotations

import hashlib
import re
import shutil
import tempfile
import zipfile
from copy import deepcopy
from pathlib import Path

from lxml import etree


REFERENCE = Path('/Applications/MAMP/htdocs/coovix/COOVIX_15_Social_Media_Posts_Final.docx')
OUTPUT = Path('/Applications/MAMP/htdocs/coovix/COOVIX_17_Social_Media_Posts_Final.docx')
EXPECTED_SHA = '4ffb4426925a4a2f88febd2f9bd7e2526800e32342258be2174e804026ab0c48'

W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS = {'w': W}
qn = lambda tag: f'{{{W}}}{tag}'


POSTS = [
    {
        'title': 'Meet Coovix',
        'ku': [
            'بینینەکەت، تەکنەلۆژیی ئێمە، سەرکەوتنی تۆ.',
            'هەر بزنسێکی سەرکەوتوو بە بیرۆکە دەست پێ دەکات، بەڵام گۆڕینی بیرۆکە بۆ چارەسەری دیجیتاڵی پێویستی بە هاوبەشێکی تەکنەلۆژی هەیە.\nلە Coovix ماڵپەڕ، ئەپی مۆبایل، سیستەمی تایبەت و چارەسەری دیجیتاڵی دروست دەکەین کە یارمەتی گەشەی بزنسەکان دەدات.',
            'لە پلاندانان هەتا پەرەپێدان و پشتگیری، ئامانجی ئێمە دروستکردنی چارەسەریەکە کە بەهای ڕاستەقینە بۆ بزنسەکەت دروست بکات.\nئێمە تەنها نەرمامێر دروست ناکەین.',
            'ئێمە داهاتووی بزنسەکەت دروست دەکەین.\nئامادەیت بزنسەکەت بەرزتر بکەیت؟ پەیوەندی بە Coovix بکە.',
        ],
        'ar': [
            'رؤيتك… تقنيتنا… ونجاحك يبدأ من هنا.',
            'كل مشروع ناجح يبدأ بفكرة، لكن تحويل هذه الفكرة إلى حل رقمي احترافي يحتاج إلى شريك تقني موثوق.\nفي Coovix نطوّر مواقع إلكترونية، وتطبيقات للهواتف، وأنظمة أعمال مخصصة، وحلولاً رقمية تساعد الشركات على النمو ومواكبة المستقبل.',
            'من التخطيط وحتى الإطلاق والدعم المستمر، نعمل على بناء حلول تقنية تحقق قيمة حقيقية لأعمالك.\nنحن لا نبني برمجيات فقط…',
            'بل نبني مستقبل أعمالك.\nهل أنت مستعد لتطوير أعمالك؟ تواصل مع Coovix اليوم.',
        ],
        'en': [
            'Your Vision. Our Technology. Your Success.',
            'Every successful business starts with an idea. Turning that idea into a powerful digital solution takes the right technology partner.\nAt Coovix, we create modern websites, mobile applications, custom software, and scalable digital solutions that help businesses grow, innovate, and stay ahead.',
            'From strategy and design to development and long-term support, we’re committed to building technology that delivers real business value.\nWe’re not just building software.',
            'We’re building the future of your business.\nReady to transform your business? Contact Coovix today.',
        ],
    },
    {
        'title': 'Why Businesses Choose Coovix',
        'ku': [
            'تەکنەلۆژیی باش بە تێگەیشتن لە بزنسەکەت دەست پێ دەکات.',
            'هەر بزنسێک جیاوازە، بۆیە چارەسەری دیجیتاڵیش دەبێت جیاواز بێت.\nلە Coovix پێش هەر شتێک پێداویستی و ئامانجەکانت دەناسین، دواتر چارەسەرێک دروست دەکەین کە تایبەت بە بزنسەکەت بێت.',
            'پارێزراو، گەشەپێدراو و ئامادە بۆ داهاتوو.\nچونکە سەرکەوتنی نەرمامێر بە ژمارەی تایبەتمەندییەکان ناپێوردرێت.',
            'بەڵکو بە بەهایەک کە بۆ بزنسەکەت دروست دەکات.\nبا پێکەوە شتێکی گەورە دروست بکەین.',
        ],
        'ar': [
            'التقنية الناجحة تبدأ بفهم أعمالك.',
            'لكل شركة أهدافها وتحدياتها الخاصة، لذلك لا يمكن أن تكون جميع الحلول متشابهة.\nفي Coovix نبدأ بفهم احتياجات عملائنا قبل البدء بالتطوير، لنقدم حلولاً مصممة خصيصاً لتحقيق أهدافهم.',
            'حلول آمنة، مرنة، وقابلة للنمو مع تطور أعمالك.\nلأن قيمة البرمجيات لا تقاس بعدد المزايا…',
            'بل بالأثر الذي تصنعه.\nلنبدأ رحلتك الرقمية معاً.',
        ],
        'en': [
            'Great technology starts with understanding your business.',
            'Every business is unique. Your digital solution should be too.\nAt Coovix, we don’t believe in one-size-fits-all software. We take the time to understand your goals, challenges, and vision before writing a single line of code.',
            'The result is technology designed specifically for your business—secure, scalable, and built to grow with you.\nBecause successful software isn’t measured by features.',
            'It’s measured by the value it creates.\nLet’s build something meaningful together.',
        ],
    },
]


def sha256(path: Path) -> str:
    h = hashlib.sha256()
    with path.open('rb') as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def para_text(p: etree._Element) -> str:
    return ''.join(p.xpath('.//w:t/text()', namespaces=NS))


def replace_para_text(p: etree._Element, text: str) -> None:
    runs = p.xpath('./w:r', namespaces=NS)
    rpr = None
    if runs:
        old_rpr = runs[0].find(qn('rPr'))
        if old_rpr is not None:
            rpr = deepcopy(old_rpr)
    for child in list(p):
        if child.tag in {qn('r'), qn('hyperlink'), qn('fldSimple')}:
            p.remove(child)
    r = etree.Element(qn('r'))
    if rpr is not None:
        r.append(rpr)
    for i, part in enumerate(text.split('\n')):
        t = etree.Element(qn('t'))
        if part.startswith(' ') or part.endswith(' '):
            t.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
        t.text = part
        r.append(t)
        if i < text.count('\n'):
            r.append(etree.Element(qn('br')))
    p.append(r)


def set_page_break_before(p: etree._Element) -> None:
    ppr = p.find(qn('pPr'))
    if ppr is None:
        ppr = etree.Element(qn('pPr'))
        p.insert(0, ppr)
    if ppr.find(qn('pageBreakBefore')) is None:
        ppr.append(etree.Element(qn('pageBreakBefore')))


def clear_page_break_before(p: etree._Element) -> None:
    ppr = p.find(qn('pPr'))
    if ppr is None:
        return
    for node in ppr.findall(qn('pageBreakBefore')):
        ppr.remove(node)


def set_after_spacing(p: etree._Element, twips: int) -> None:
    ppr = p.find(qn('pPr'))
    if ppr is None:
        ppr = etree.Element(qn('pPr'))
        p.insert(0, ppr)
    spacing = ppr.find(qn('spacing'))
    if spacing is None:
        spacing = etree.Element(qn('spacing'))
        ppr.append(spacing)
    spacing.set(qn('after'), str(twips))


def update_core_xml(data: bytes) -> bytes:
    root = etree.fromstring(data)
    ns = {'dc': 'http://purl.org/dc/elements/1.1/'}
    title = root.find('dc:title', ns)
    if title is not None:
        title.text = 'COOVIX — 17 Social Media Posts'
    return etree.tostring(root, xml_declaration=True, encoding='UTF-8', standalone='yes')


def main() -> None:
    if sha256(REFERENCE) != EXPECTED_SHA:
        raise RuntimeError('Reference changed after template distillation.')

    with zipfile.ZipFile(REFERENCE, 'r') as zin:
        root = etree.fromstring(zin.read('word/document.xml'))
        body = root.find('w:body', NS)
        direct_paras = body.findall('w:p', NS)

        replace_para_text(direct_paras[1], '17 Social Media Posts — Kurdish • Arabic • English')

        title_paras = [p for p in direct_paras if re.match(r'^POST \d+ — ', para_text(p))]
        if len(title_paras) != 15:
            raise RuntimeError(f'Expected 15 existing post titles; found {len(title_paras)}.')
        for p in title_paras:
            old = para_text(p)
            m = re.match(r'^POST (\d+) — (.+)$', old)
            replace_para_text(p, f'POST {int(m.group(1)) + 2} — {m.group(2)}')

        # Source-derived page component: title, three language blocks, two dividers.
        template = [deepcopy(p) for p in direct_paras[3:24]]
        if len(template) != 21:
            raise RuntimeError('Recurring post-page template was not found.')

        first_existing_title = title_paras[0]
        set_page_break_before(first_existing_title)
        insert_at = body.index(direct_paras[3])

        for post_number, post in enumerate(POSTS, start=1):
            block = [deepcopy(p) for p in template]
            replace_para_text(block[0], f'POST {post_number} — {post["title"]}')
            if post_number == 1:
                clear_page_break_before(block[0])
            else:
                set_page_break_before(block[0])

            values = {
                1: 'کوردی', 6: 'COOVIX\nTechnology Solutions', 7: '────────────────────────',
                8: 'العربية', 13: 'COOVIX\nTechnology Solutions', 14: '────────────────────────',
                15: 'English', 20: 'COOVIX\nTechnology Solutions',
            }
            for i, value in enumerate(post['ku'], start=2): values[i] = value
            for i, value in enumerate(post['ar'], start=9): values[i] = value
            for i, value in enumerate(post['en'], start=16): values[i] = value
            for idx, value in values.items():
                replace_para_text(block[idx], value)

            # Long supplied copy: retain the template font and tighten only
            # the local paragraph rhythm, keeping each post on one page.
            for idx in list(range(2, 7)) + list(range(9, 14)) + list(range(16, 21)):
                set_after_spacing(block[idx], 40)

            for node in block:
                body.insert(insert_at, node)
                insert_at += 1

        new_document_xml = etree.tostring(
            root, xml_declaration=True, encoding='UTF-8', standalone='yes'
        )

        with tempfile.NamedTemporaryFile(dir=OUTPUT.parent, suffix='.docx', delete=False) as tmp:
            tmp_path = Path(tmp.name)
        try:
            with zipfile.ZipFile(tmp_path, 'w') as zout:
                for info in zin.infolist():
                    data = zin.read(info.filename)
                    if info.filename == 'word/document.xml':
                        data = new_document_xml
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
