# COOVIX reels extension contract

- Reference: `/Users/mh/Downloads/COOVIX_Reels_Final.docx`
- Reference SHA-256: `bc1029206949da6d0ab9c278d853549127386ffe400276d6eeafe16d6f5524a9`
- Reference pages: 6; sections: 1
- Reference render: `/private/tmp/coovix_reels/reference`
- Style evidence: `/private/tmp/coovix_reels/reference-style.json`

## Page system and recurring components

- US Letter portrait; margins L/R 0.80 in and T/B 0.65 in; one section; no visible header/footer.
- Cover uses centered COOVIX (24 pt bold), centered subtitle (14 pt bold), then a centered divider.
- A reel begins with an 16 pt bold title and centered divider.
- Language order is Kurdish, Arabic, English. Kurdish/Arabic labels and copy align right; English aligns left.
- Language labels are 16 pt bold. Copy and COOVIX lockups are 12.5 pt with 6 pt paragraph-after; lockups are bold.
- A centered text divider separates language blocks. The next reel begins on a new page.

## Slot map

- Preserve all three existing reel blocks and their text.
- Append five reel components after the final existing lockup, numbered 04–08 in the order supplied: Meet Coovix; From Idea to Product; Your Website Never Sleeps; Mobile Apps; Digital Transformation.
- Each appended component has a reel title, divider, Kurdish label and content, lockup, divider, Arabic label and content, lockup, divider, English label and content, and lockup.
- Scene labels in Reel 04 remain attached to their scene copy and are bold for rapid designer scanning.
- Source elements in `word/document.xml`: title template paragraph 27; divider 28; Kurdish label/body/lockup 29/30/35; Arabic 37/38/43; English 45/46/51; final section properties remain last.

## Package preservation and fidelity gates

- Preserve every package part byte-for-byte except `word/document.xml` and `docProps/core.xml`.
- Preserve styles, settings, theme, font table, numbering, relationships, custom XML, and thumbnail.
- Reference file must remain unchanged at its recorded hash.
- Final remains one section with unchanged page geometry and visibly source-derived formatting.
- Render and inspect every final page; no clipped text, overlaps, missing glyphs, accidental blank pages, or broken language blocks.
