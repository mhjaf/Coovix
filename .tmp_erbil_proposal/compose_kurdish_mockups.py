from pathlib import Path
from PIL import Image

ROOT = Path('/Applications/MAMP/htdocs/coovix')
TMP = ROOT / '.tmp_erbil_proposal'
ASSETS = ROOT / 'deliverables/assets'

logo = Image.open(ASSETS / 'erbil-sports-club-logo.png').convert('RGBA')
qr = Image.open(ASSETS / 'erbil-booking-qr.png').convert('RGBA')

mobile = Image.open(TMP / 'kurdish-mobile-fixed.png').convert('RGBA')
for x in (110, 705, 1300):
    mark = logo.copy()
    mark.thumbnail((55, 62), Image.Resampling.LANCZOS)
    mobile.alpha_composite(mark, (x + (55 - mark.width) // 2, 82))
mobile.convert('RGB').save(TMP / 'kurdish-mobile-fixed.png', quality=96)

booking = Image.open(TMP / 'kurdish-booking-fixed.png').convert('RGBA')
for x in (68, 513, 958, 1403):
    mark = logo.copy()
    mark.thumbnail((48, 55), Image.Resampling.LANCZOS)
    booking.alpha_composite(mark, (x + (48 - mark.width) // 2, 80))
qr_mark = qr.resize((146, 146), Image.Resampling.LANCZOS)
booking.alpha_composite(qr_mark, (1045, 382))
booking.convert('RGB').save(TMP / 'kurdish-booking-fixed.png', quality=96)
