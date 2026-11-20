# COOVIX social posts template contract

- Reference: `/Users/mh/Downloads/COOVIX_6_Social_Media_Posts_Final.docx`
- Reference SHA-256: `041b24976e70ed3c22d5c965fff460e3cc1975b236da640b59f1feda2108d273`
- Reference pages: 7
- Reference sections: 1
- Reference render: `/private/tmp/coovix_merge_review/template`
- Style evidence: `/private/tmp/coovix_merge_review/template-style-evidence.json`

## Page system

- US Letter portrait, 8.5 × 11 in.
- Margins: left/right 0.75 in; top/bottom 0.65 in.
- Header/footer distance: 0.50 in.
- One section. No visible header or footer content.
- Explicit page-break paragraph before each post page.

## Page patterns

1. Cover: centered `COOVIX` at 26 pt bold; centered subtitle at 14 pt bold.
2. Post page: title at 18 pt bold; Kurdish block; centered horizontal text divider; Arabic block; centered horizontal text divider; English block. Each language label is 14 pt bold. Body uses the reference Normal style with 7 pt after each content paragraph. Kurdish and Arabic are right-aligned; English is left-aligned.

## Content slots

- Cover slots: document title and post-count/language subtitle.
- Each cloned post block has these slots: post title; Kurdish label; up to five Kurdish content paragraphs; Kurdish COOVIX lock-up; divider; Arabic label; up to five Arabic content paragraphs; Arabic COOVIX lock-up; divider; English label; up to five English content paragraphs; English COOVIX lock-up.
- Stable source pattern: paragraphs 113–136, preceded by page-break paragraph 112.
- New content must be normalized to Kurdish → Arabic → English, even when the source document uses another language order.
- Preserve all source ideas. Two distinct UI/UX texts remain separate posts.

## Package preservation

- Preserve all reference parts except `word/document.xml` and document metadata changed only if necessary.
- Preserve styles, styles-with-effects, settings, theme, font table, numbering, relationships, custom XML, and thumbnail byte-for-byte.
- No tables, images, fields, content controls, comments, footnotes, headers, or footers require editing.

## Fidelity gates

- Original reference remains unchanged and retains its recorded SHA-256.
- Final uses one section and the same page geometry.
- Existing seven pages remain visually unchanged except the cover subtitle updates from 6 to 15 posts.
- Each appended post uses the same page pattern, typography, language order, dividers, spacing, and COOVIX lock-up.
- No post clips, overlaps, or spills unexpectedly onto another page.
