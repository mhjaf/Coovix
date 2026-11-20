# COOVIX 17-post extension contract

- Reference: `/Applications/MAMP/htdocs/coovix/COOVIX_15_Social_Media_Posts_Final.docx`
- Reference SHA-256: `4ffb4426925a4a2f88febd2f9bd7e2526800e32342258be2174e804026ab0c48`
- Reference pages: 16; sections: 1
- Reference render: `/private/tmp/coovix17/reference`
- Style evidence: `/private/tmp/coovix17/reference-style.json`

## Page system and recurring pattern

- US Letter portrait, 8.5 x 11 in; margins L/R 0.75 in and T/B 0.65 in; header/footer distance 0.50 in.
- One section with no visible header or footer content.
- Cover: centered COOVIX at 26 pt bold and centered 14 pt bold subtitle.
- Post page: 18 pt bold title; Kurdish block; centered text divider; Arabic block; centered text divider; English block.
- Language labels are 14 pt bold. Kurdish and Arabic align right; English aligns left. Each block ends with the COOVIX / Technology Solutions lockup.
- Existing source-derived post pages use four content paragraphs per language; embedded line breaks are allowed inside these slots.

## Edit slots and capacity

- Cover subtitle: replace 15 with 17.
- Insert two complete cloned post-page components immediately after the cover, ordered Kurdish, Arabic, English.
- New Post 1 title: Meet Coovix. New Post 2 title: Why Businesses Choose Coovix.
- Each language uses four body slots: headline; grouped opening copy; grouped supporting copy; closing copy plus CTA. Embedded line breaks preserve every supplied sentence.
- Renumber all existing Post 1–15 title slots to Post 3–17 without changing their topic names or body content.
- Stable package locations: `word/document.xml` body paragraphs; cover paragraphs 0–2; recurring first post component paragraphs 3–23; section properties at the end of the body.

## Package preservation and fidelity gates

- Preserve every package part byte-for-byte except `word/document.xml` and `docProps/core.xml`.
- Preserve styles, settings, theme, fonts, numbering, relationships, custom XML, and thumbnail.
- Keep the reference file unchanged at its recorded SHA-256.
- Final must have 18 pages: one cover plus 17 one-page posts, with one section and unchanged page geometry.
- Inspect all final rendered pages for clipping, overlap, missing glyphs, or unintended page spill.
