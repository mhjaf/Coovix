from __future__ import annotations

import hashlib
import shutil
import tempfile
import zipfile
from copy import deepcopy
from pathlib import Path

from docx import Document
from lxml import etree

REFERENCE = Path('/Users/mh/Downloads/COOVIX_6_Social_Media_Posts_Final.docx')
SOURCE = Path('/Users/mh/Desktop/coovix.docx')
OUTPUT = Path('/Applications/MAMP/htdocs/coovix/COOVIX_15_Social_Media_Posts_Final.docx')
EXPECTED_SHA = '041b24976e70ed3c22d5c965fff460e3cc1975b236da640b59f1feda2108d273'

W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
NS = {'w': W}
qn = lambda tag: f'{{{W}}}{tag}'


def sha256(path: Path) -> str:
    h = hashlib.sha256()
    with path.open('rb') as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def is_separator(text: str) -> bool:
    compact = ''.join(text.split())
    return bool(compact) and all(ch in '─━—–-=⸻_' for ch in compact)


def normalize_content(items: list[str]) -> list[str]:
    cleaned = []
    for text in items:
        text = text.strip()
        if not text or is_separator(text) or text in {'COOVIX', 'Technology Solutions'}:
            continue
        cleaned.append(text)
    if len(cleaned) <= 4:
        return cleaned + [''] * (4 - len(cleaned))

    # Preserve every sentence while fitting the compact source page pattern.
    first, rest = cleaned[0], cleaned[1:]
    groups = []
    for i in range(3):
        start = round(i * len(rest) / 3)
        end = round((i + 1) * len(rest) / 3)
        groups.append('\n'.join(rest[start:end]))
    return [first] + groups


def parse_source_posts() -> list[dict]:
    doc = Document(SOURCE)
    paras = [p.text.strip() for p in doc.paragraphs]
    starts = [i for i, text in enumerate(paras) if text.startswith('POST ') and '—' in text]
    posts = []
    language_aliases = {'English': 'en', 'العربية': 'ar', 'Kurdish': 'ku', 'کوردی': 'ku'}
    for pos, start in enumerate(starts):
        end = starts[pos + 1] if pos + 1 < len(starts) else len(paras)
        block = paras[start + 1:end]
        data = {'title': paras[start], 'en': [], 'ar': [], 'ku': []}
        current = None
        for text in block:
            if text in language_aliases:
                current = language_aliases[text]
                continue
            if current:
                data[current].append(text)
        for lang in ('en', 'ar', 'ku'):
            data[lang] = normalize_content(data[lang])
            if not any(data[lang]):
                raise RuntimeError(f'Missing {lang} content in {data["title"]}')
        posts.append(data)
    return posts


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
    parts = text.split('\n')
    for i, part in enumerate(parts):
        t = etree.Element(qn('t'))
        if part.startswith(' ') or part.endswith(' '):
            t.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
        t.text = part
        r.append(t)
        if i < len(parts) - 1:
            r.append(etree.Element(qn('br')))
    p.append(r)


def update_core_xml(data: bytes) -> bytes:
    root = etree.fromstring(data)
    ns = {
        'dc': 'http://purl.org/dc/elements/1.1/',
        'cp': 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties',
    }
    title = root.find('dc:title', ns)
    if title is not None:
        title.text = 'COOVIX — 15 Social Media Posts'
    return etree.tostring(root, xml_declaration=True, encoding='UTF-8', standalone='yes')


def main() -> None:
    if sha256(REFERENCE) != EXPECTED_SHA:
        raise RuntimeError('Reference file changed after template distillation.')
    source_posts = parse_source_posts()
    if len(source_posts) != 9:
        raise RuntimeError(f'Expected 9 source posts, found {len(source_posts)}')

    titles = [
        'Meet Coovix',
        'Professional Web Development',
        'Mobile Application Development',
        'Custom Software Development',
        'UI / UX Design',
        'Cloud Solutions',
        'UI / UX Design — Effortless Experience',
        'DevOps',
        'Long-Term Technology Partner',
    ]

    with zipfile.ZipFile(REFERENCE, 'r') as zin:
        document_xml = zin.read('word/document.xml')
        root = etree.fromstring(document_xml)
        body = root.find('w:body', NS)
        paragraphs = body.findall('w:p', NS)

        # The final reference page is the exact recurring post-page pattern.
        template_block = [deepcopy(p) for p in paragraphs[112:137]]
        if len(template_block) != 25:
            raise RuntimeError('Reference post template block was not found.')

        # Cover subtitle: preserve its run and paragraph formatting.
        replace_para_text(paragraphs[1], '15 Social Media Posts — Kurdish • Arabic • English')

        sect_pr = body.find('w:sectPr', NS)
        insert_at = body.index(sect_pr)
        for offset, (post, topic) in enumerate(zip(source_posts, titles), start=7):
            block = [deepcopy(p) for p in template_block]
            values = {
                1: f'POST {offset} — {topic}',
                2: 'کوردی',
                8: 'COOVIX\nTechnology Solutions',
                9: '────────────────────────',
                10: 'العربية',
                16: 'COOVIX\nTechnology Solutions',
                17: '────────────────────────',
                18: 'English',
                24: 'COOVIX\nTechnology Solutions',
            }
            for i, text in enumerate(post['ku'], start=3): values[i] = text
            for i, text in enumerate(post['ar'], start=11): values[i] = text
            for i, text in enumerate(post['en'], start=19): values[i] = text
            for idx, text in values.items():
                replace_para_text(block[idx], text)

            # Four body paragraphs per language match the compact reference
            # pages and keep every added post to a single page.
            for idx in (23, 15, 7):
                del block[idx]

            # A page-break-before setting avoids a trailing blank page when
            # the preceding post finishes close to the bottom margin.
            title_p = block[1]
            ppr = title_p.find(qn('pPr'))
            if ppr is None:
                ppr = etree.Element(qn('pPr'))
                title_p.insert(0, ppr)
            ppr.append(etree.Element(qn('pageBreakBefore')))

            # The introductory source post carries more copy than the other
            # pages. Tighten only its existing paragraph-after rhythm enough
            # to keep the complete trilingual block on one page.
            if offset == 7:
                for para in block[1:]:
                    spacing = para.find('./w:pPr/w:spacing', NS)
                    if spacing is not None and spacing.get(qn('after')) == '140':
                        spacing.set(qn('after'), '80')

            for node in block[1:]:
                body.insert(insert_at, node)
                insert_at += 1

        new_document_xml = etree.tostring(root, xml_declaration=True, encoding='UTF-8', standalone='yes')

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
