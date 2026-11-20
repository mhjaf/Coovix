from pathlib import Path
from pptx import Presentation
from pptx.util import Inches
from pptx.util import Pt

ROOT = Path('/Applications/MAMP/htdocs/coovix')
TMP = ROOT / '.tmp_erbil_proposal'
source = TMP / 'Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_v7.pptx'
target = TMP / 'Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_v7_fixed.pptx'

prs = Presentation(source)
for slide_number, artwork in (
    (7, TMP / 'kurdish-mobile-fixed.png'),
    (8, TMP / 'kurdish-booking-fixed.png'),
):
    slide = prs.slides[slide_number - 1]
    title = slide.shapes[1]
    title.top = Inches(0.72)
    title.height = Inches(0.58)
    title.text_frame.word_wrap = False
    for paragraph in title.text_frame.paragraphs:
        paragraph.line_spacing = 1.0
        for run in paragraph.runs:
            run.font.size = Pt(21.5)
    intro = slide.shapes[4]
    intro.top = Inches(1.48)
    intro.height = Inches(0.34)
    for paragraph in intro.text_frame.paragraphs:
        paragraph.line_spacing = 1.0
        for run in paragraph.runs:
            run.font.size = Pt(11.5)
    pic = slide.shapes.add_picture(str(artwork), Inches(0.45), Inches(2.02), width=Inches(12.35))
    pic.name = f'Corrected Kurdish production mockup - slide {slide_number}'

prs.save(target)
print(target)
