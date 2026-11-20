from pathlib import Path
from copy import copy
from zipfile import ZipFile, ZIP_DEFLATED
from PIL import Image
from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path('/Applications/MAMP/htdocs/coovix')
TMP = ROOT / '.tmp_erbil_proposal'
SOURCE = ROOT / 'deliverables/Erbil_Sports_Club_Football_Digital_Platform_Proposal_English_v7.docx'
OUT = ROOT / 'deliverables/Erbil_Sports_Club_Football_Digital_Platform_Proposal_Kurdish_v7.docx'

T = {
'BUSINESS PROPOSAL | FOOTBALL':'پێشنیاری بازرگانی | تۆپی پێ','Erbil Sports Club\nDigital Football Platform':'پلاتفۆرمی دیجیتاڵی تۆپی پێ\nیانەی وەرزشی هەولێر','Website, Mobile Application & Smart Football Facility Booking':'ماڵپەڕ، ئەپی مۆبایل و حجزکردنی زیرەکانەی یاریگا','Prepared by: Coovix':'ئامادەکراوە لەلایەن: Coovix','September 2026 | Erbil, Kurdistan Region of Iraq':'ئەیلوولی ٢٠٢٦ | هەولێر، هەرێمی کوردستانی عێراق',
'EXECUTIVE SUMMARY':'پوختەی بەڕێوەبەرایەتی',"One platform can become the club's digital front door":'یەک پلاتفۆرم دەبێتە دەروازەی دیجیتاڵی یانە','Phase 1 connects customers, football facility booking, payments and club operations through one consistent experience.':'قۆناغی ١ کڕیاران، حجزی یاریگا، پارەدان و کارگێڕیی یانە لە یەک ئەزموونی یەکگرتوودا پەیوەست دەکات.','Business outcomes':'ئەنجامە بازرگانییەکان','Customers: See live availability, pay online and receive a verified booking confirmation.':'کڕیاران: بەردەستبوونی ڕاستەوخۆ ببینن، ئۆنلاین پارە بدەن و پشتڕاستکردنەوەی حجز وەربگرن.','Operations: Manage bookings, schedules, facilities, customers and pricing from one dashboard.':'کارگێڕی: حجز، خشتەکات، شوێنەکان، کڕیاران و نرخ لە یەک داشبۆرد بەڕێوەببەن.','Management: Monitor booking volume, payment status and basic revenue performance.':'بەڕێوەبردن: ژمارەی حجز، دۆخی پارەدان و ئاستی سەرەتایی داهات چاودێری بکەن.',
'CURRENT OPPORTUNITIES':'دەرفەتەکانی ئێستا',"Turn today's service gaps into a stronger customer experience":'کەلێنەکانی خزمەتگوزاریی ئەمڕۆ بکەنە ئەزموونێکی باشتر بۆ کڕیار','A connected platform can reduce manual coordination while making club services easier to discover, book and manage.':'پلاتفۆرمێکی پەیوەست هەماهەنگیی دەستی کەم دەکاتەوە و دۆزینەوە، حجز و بەڕێوەبردنی خزمەتگوزارییەکان ئاسانتر دەکات.','Availability: Let customers check live pitch and facility time slots without calling.':'بەردەستبوون: کڕیاران بتوانن بەبێ تەلەفۆن کاتە بەردەستەکانی یاریگا و شوێنەکان بپشکنن.','Reservations: Reduce staff effort around manual booking, changes and cancellations.':'حجزەکان: کاری ستاف بۆ حجزی دەستی، گۆڕانکاری و هەڵوەشاندنەوە کەم بکاتەوە.','Accuracy: Synchronize schedules to reduce conflicts and duplicate reservations.':'وردی: خشتەکاتەکان هاوتا بکات بۆ کەمکردنەوەی ناکۆکی و حجزی دووبارە.','Communication: Deliver booking updates and reminders consistently through push notifications.':'پەیوەندی: نوێکاری و بیرخستنەوەی حجز بە ئاگادارکردنەوەی Push بەردەوام بگەیەنێت.','Payments: Provide a clear online payment, confirmation and receipt journey.':'پارەدان: ڕێڕەوێکی ڕوونی پارەدانی ئۆنلاین، پشتڕاستکردنەوە و پسوڵە دابین بکات.','Reporting: Centralize booking and payment information for better operating visibility.':'ڕاپۆرت: زانیاریی حجز و پارەدان یەکخات بۆ ڕوونیی باشتر لە کارگێڕیدا.',
'PROPOSED SOLUTION':'چارەسەری پێشنیارکراو','One connected ecosystem keeps every channel in sync':'یەک سیستەمی پەیوەست هەموو کەناڵەکان هاوتا دەکات','Customers receive one seamless experience while staff manage one reliable source of booking and payment information.':'کڕیاران یەک ئەزموونی یەکگرتوو وەردەگرن و ستاف یەک سەرچاوەی باوەڕپێکراوی حجز و پارەدان بەڕێوەدەبات.','Responsive website':'ماڵپەڕی وەڵامدەرەوە','Club content, football information and a production-ready facility booking journey.':'ناوەڕۆکی یانە، زانیاریی تۆپی پێ و ڕێڕەوی ئامادەی حجزی شوێن.','Android and iOS application':'ئەپی Android و iOS','Account access, live availability, reservations, payments, notifications and booking history.':'دەستگەیشتن بە هەژمار، بەردەستبوونی ڕاستەوخۆ، حجز، پارەدان، ئاگادارکردنەوە و مێژووی حجز.','Admin dashboard':'داشبۆردی بەڕێوەبەر','Booking, facility, schedule, customer, pricing and payment-status management.':'بەڕێوەبردنی حجز، شوێن، خشتەکات، کڕیار، نرخ و دۆخی پارەدان.','Connected platform services':'خزمەتگوزارییە پەیوەستەکانی پلاتفۆرم','Backend APIs, online payment integration, QR confirmations, reports and notifications.':'API ـەکانی پاشبنەما، پەیوەستکردنی پارەدانی ئۆنلاین، پشتڕاستکردنەوەی QR، ڕاپۆرت و ئاگادارکردنەوە.',
'PROJECT PHASES':'قۆناغەکانی پڕۆژە','Launch the core platform first, then expand with confidence':'سەرەتا پلاتفۆرمی سەرەکی دەستپێبکات، پاشان بە دڵنیایی فراوان بکرێت','The roadmap separates the current paid scope from future optional modules so investment and delivery remain clear.':'نەخشەڕێگاکە مەودای پارەدراوی ئێستا لە مۆدیولە هەڵبژاردەکانی داهاتوو جیا دەکاتەوە تا وەبەرهێنان و گەیاندن ڕوون بن.','Phase 1 - Core Digital Platform':'قۆناغی ١ - پلاتفۆرمی دیجیتاڵی سەرەکی','Current paid scope: website, mobile apps, booking, payments, QR confirmation, admin dashboard, backend APIs, deployment and staff training.':'مەودای پارەدراوی ئێستا: ماڵپەڕ، ئەپی مۆبایل، حجز، پارەدان، پشتڕاستکردنەوەی QR، داشبۆرد، API، بڵاوکردنەوە و ڕاهێنانی ستاف.','Phase 2 - Membership & Growth':'قۆناغی ٢ - ئەندامێتی و گەشە','Future optional services: digital membership, subscriptions, membership card, loyalty, promotions and advanced analytics.':'خزمەتگوزاریی هەڵبژاردەی داهاتوو: ئەندامێتی دیجیتاڵی، بەشداریکردن، کارت، دڵسۆزی، ئۆفەر و شیکاریی پێشکەوتوو.','Phase 3 - Smart Club Services':'قۆناغی ٣ - خزمەتگوزاریی زیرەکی یانە','Future optional services: academy, coach booking, ticketing, restaurant ordering, access control, parking and additional club services.':'خزمەتگوزاریی هەڵبژاردەی داهاتوو: ئەکادیمی، حجزی ڕاهێنەر، بلیت، داواکاریی چێشتخانە، کۆنترۆڵی چوونەژوورەوە، پارکینگ و خزمەتگوزاریی زیاتر.',
'WEBSITE':'ماڵپەڕ','The digital home of Erbil football':'ماڵی دیجیتاڵی تۆپی پێی هەولێر','A responsive public website presents the club professionally and gives customers a clear path from discovery to confirmed facility booking.':'ماڵپەڕێکی گشتیی وەڵامدەرەوە یانە بە شێوەیەکی پیشەیی پیشان دەدات و ڕێگایەکی ڕوون لە دۆزینەوە تا پشتڕاستکردنەوەی حجز دابین دەکات.','Homepage and booking page concepts using the existing Erbil yellow-and-black product language.':'کۆنسێپتی پەڕەی سەرەکی و حجز بە زمانی ڕەنگی زەرد و ڕەشی هەولێر.','Responsive across desktop, tablet and mobile browsers.':'وەڵامدەرەوە لە کۆمپیوتەر، تابلێت و وێبگەڕی مۆبایل.','Live availability, facility details, date and time selection, and booking review.':'بەردەستبوونی ڕاستەوخۆ، وردەکاریی شوێن، هەڵبژاردنی بەروار و کات و پێداچوونەوەی حجز.',
'MOBILE APPLICATION':'ئەپی مۆبایل','A focused experience for everyday club services':'ئەزموونێکی سادە بۆ خزمەتگوزارییە ڕۆژانەکانی یانە','The Android and iOS applications prioritize account access, pitch selection and booking a live time slot.':'ئەپی Android و iOS دەستگەیشتن بە هەژمار، هەڵبژاردنی یاریگا و حجزی کاتی بەردەست دەخاتە پێشەوە.','Mobile concepts: app home, Training Pitch 1 selection and Friday booking calendar.':'کۆنسێپتی مۆبایل: سەرەکیی ئەپ، هەڵبژاردنی یاریگای ڕاهێنان ١ و ڕۆژژمێری حجزی هەینی.','Registration, login and OTP verification with customer profile access.':'تۆمارکردن، چوونەژوورەوە و پشتڕاستکردنەوەی OTP لەگەڵ پرۆفایلی کڕیار.','Live availability, booking history, cancellation and push notifications.':'بەردەستبوونی ڕاستەوخۆ، مێژووی حجز، هەڵوەشاندنەوە و ئاگادارکردنەوەی Push.',
'BOOKING & PAYMENT FLOW':'ڕێڕەوی حجز و پارەدان','From selection to verified entry in minutes':'لە هەڵبژاردنەوە تا چوونەژوورەوەی پشتڕاستکراو لە چەند خولەکێکدا','The flow makes price, payment status and booking details clear at every step.':'ڕێڕەوەکە نرخ، دۆخی پارەدان و وردەکاریی حجز لە هەموو هەنگاوێکدا ڕوون دەکات.','Training Pitch 1 | Friday 18:30-20:00 | 25,000 IQD | Booking ID ESC-18472 | Paid and Confirmed.':'یاریگای ڕاهێنان ١ | هەینی ١٨:٣٠-٢٠:٠٠ | ٢٥,٠٠٠ دینار | ناسنامەی حجز ESC-18472 | پارەدراو و پشتڕاستکراو.','Online payment integration, payment confirmation and a digital receipt are included in Phase 1.':'پەیوەستکردنی پارەدانی ئۆنلاین، پشتڕاستکردنەوەی پارەدان و پسوڵەی دیجیتاڵی لە قۆناغی ١دا لەخۆگیراون.','A real scannable QR design supports reception or entry validation.':'دیزاینێکی ڕاستەقینە و سکانکراوی QR پشتگیری لە پشتڕاستکردنەوە لە پێشوازی یان چوونەژوورەوە دەکات.',
'ADMIN DASHBOARD':'داشبۆردی بەڕێوەبەر','Daily activity becomes operational control':'چالاکیی ڕۆژانە دەبێتە کۆنترۆڵی کارگێڕی','Authorized staff receive a clear workspace for bookings, facilities, customers, pricing and payment status.':'ستافی مۆڵەتپێدراو شوێنێکی کاری ڕوون بۆ حجز، شوێنەکان، کڕیاران، نرخ و دۆخی پارەدان وەردەگرێت.','Dashboard example: booking ESC-18472, Training Pitch 1, 25,000 IQD, Paid and Confirmed.':'نموونەی داشبۆرد: حجزی ESC-18472، یاریگای ڕاهێنان ١، ٢٥,٠٠٠ دینار، پارەدراو و پشتڕاستکراو.','Booking, facility, schedule, customer and pricing management.':'بەڕێوەبردنی حجز، شوێن، خشتەکات، کڕیار و نرخ.','Payment status plus basic booking and revenue reports.':'دۆخی پارەدان لەگەڵ ڕاپۆرتی سەرەتایی حجز و داهات.',
'BUSINESS BENEFITS':'سوودە بازرگانییەکان','Customer convenience translates into stronger decisions':'ئاسانیی کڕیار دەبێتە بڕیاری بەهێزتر','The platform improves both the customer-facing experience and the internal operating model.':'پلاتفۆرمەکە هەم ئەزموونی کڕیار و هەم مۆدێلی کارگێڕیی ناوخۆ باشتر دەکات.',
'PHASE 1 TIMELINE':'خشتەکاتی قۆناغی ١','A focused delivery plan targets launch in 10-12 weeks':'پلانی گەیاندنی چڕ ئامانجی دەستپێکردن لە ١٠-١٢ هەفتەدا دادەنێت','Workstreams overlap where practical, while review points keep management aligned on scope and experience.':'کارەکان لە شوێنی گونجاودا هاوکات دەچن و خاڵەکانی پێداچوونەوە بەڕێوەبردن لەگەڵ مەودا و ئەزموون هاوتا دەهێڵن.',
'PHASE 1 COMMERCIAL PROPOSAL':'پێشنیاری بازرگانیی قۆناغی ١','A complete core football platform for $6,900 USD':'پلاتفۆرمێکی تەواوی سەرەکیی تۆپی پێ بە ٦,٩٠٠ دۆلار','Payment terms':'مەرجەکانی پارەدان','30% on approval | 40% after core system demo | 30% after final deployment':'٣٠٪ لە پەسەندکردن | ٤٠٪ دوای دیمۆی سیستەمی سەرەکی | ٣٠٪ دوای بڵاوکردنەوەی کۆتایی','Third-party fees excluded':'تێچووی لایەنی سێیەم لەخۆناگرێت','Payment gateway transaction fees | Bank/provider fees | SMS/OTP usage fees | Apple Developer fees | Google Play fees | Hosting renewal | Domain renewal':'کرێی مامەڵەی دەروازەی پارەدان | کرێی بانک/دابینکەر | کرێی SMS/OTP | کرێی Apple Developer | کرێی Google Play | نوێکردنەوەی هۆستینگ | نوێکردنەوەی دۆمەین',
'FUTURE OPTIONAL PHASES':'قۆناغە هەڵبژاردەکانی داهاتوو','Extend the platform as club priorities grow':'پلاتفۆرمەکە لەگەڵ گەشەی پێشەنگییەکانی یانە فراوان بکەن','The Phase 1 foundation can support later modules without placing them inside the current paid scope.':'بناغەی قۆناغی ١ دەتوانێت مۆدیولەکانی دواتر پشتیوانی بکات بەبێ دانانیان لە مەودای پارەدراوی ئێستادا.',
'CONCLUSION':'کۆتایی','A practical first phase with room to grow':'قۆناغێکی یەکەمی کردەیی لەگەڵ بوار بۆ گەشە','Phase 1 gives Erbil Sports Club a complete digital foundation for football facility discovery, booking, payment and daily management.':'قۆناغی ١ بناغەیەکی دیجیتاڵی تەواو بۆ دۆزینەوەی شوێنی تۆپی پێ، حجز، پارەدان و بەڕێوەبردنی ڕۆژانەی یانەی وەرزشی هەولێر دابین دەکات.','Professional responsive website and Android/iOS applications.':'ماڵپەڕێکی وەڵامدەرەوەی پیشەیی و ئەپی Android/iOS.','Clear booking, payment, digital receipt and QR confirmation experience.':'ئەزموونێکی ڕوونی حجز، پارەدان، پسوڵەی دیجیتاڵی و پشتڕاستکردنەوەی QR.','Central administration for facilities, schedules, customers, pricing and payments.':'بەڕێوەبردنی ناوەندی بۆ شوێنەکان، خشتەکات، کڕیاران، نرخ و پارەدان.','Target delivery within 10-12 weeks, including deployment and staff training.':'گەیاندنی ئامانج لە ١٠-١٢ هەفتەدا، لەگەڵ بڵاوکردنەوە و ڕاهێنانی ستاف.','Erbil Sports Club Digital Football Platform':'پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر',
'PROPOSAL OBJECTIVE':'ئامانجی پێشنیار','A connected football experience for supporters and members, with clearer operating control for management.':'ئەزموونێکی پەیوەستی تۆپی پێ بۆ هاندەران و ئەندامان، لەگەڵ کۆنترۆڵێکی ڕوونتر بۆ بەڕێوەبردن.','PHASE 1 OBJECTIVE':'ئامانجی قۆناغی ١','Launch a dependable website, mobile application and administration platform within 10-12 weeks.':'ماڵپەڕ، ئەپی مۆبایل و پلاتفۆرمی بەڕێوەبردنی باوەڕپێکراو لە ١٠-١٢ هەفتەدا دەستپێبکات.','OPERATING PRINCIPLE':'بنەمای کارگێڕی','One customer experience. One booking process. One management view.':'یەک ئەزموونی کڕیار. یەک پرۆسەی حجز. یەک دیدی بەڕێوەبردن.','SCOPE BOUNDARY':'سنووری مەودا','Phase 2 and Phase 3 are not included in the Phase 1 price. They will be scoped and priced separately.':'قۆناغی ٢ و ٣ لە نرخی قۆناغی ١دا نیین. مەودا و نرخەکانیان بە جیا دیاری دەکرێت.',
'Customer value':'بەهای کڕیار','Club and management value':'بەهای یانە و بەڕێوەبردن','Fast self-service booking with clear confirmation.':'حجزی خۆخزمەتگوزاریی خێرا لەگەڵ پشتڕاستکردنەوەی ڕوون.','Fewer calls, repetitive tasks and manual checks.':'پەیوەندی، کاری دووبارە و پشکنینی دەستی کەمتر.','Consistent access to football and facility services.':'دەستگەیشتنی بەردەوام بە خزمەتگوزاریی تۆپی پێ و شوێنەکان.','Centralized booking, customer and pricing information.':'زانیاریی یەکخراوی حجز، کڕیار و نرخ.','Clear availability, reminders and booking history.':'بەردەستبوون، بیرخستنەوە و مێژووی حجزی ڕوون.','Visibility into revenue, utilization and peak hours.':'ڕوونی لە داهات، بەکارهێنان و کاتە پڕکارەکان.','A modern experience aligned with the club brand.':'ئەزموونێکی مۆدێرن و هاوتا لەگەڵ براندی یانە.','A scalable foundation for future club services.':'بناغەیەکی گەشەپێدراو بۆ خزمەتگوزارییەکانی داهاتوو.','MANAGEMENT VALUE':'بەهای بەڕێوەبردن','Clearer booking and payment data supports better scheduling, pricing and service decisions.':'داتای ڕوونتری حجز و پارەدان پشتگیری لە بڕیاری باشتر بۆ خشتەکات، نرخ و خزمەتگوزاری دەکات.',
'Step':'هەنگاو','Workstream':'کار','Target':'ئامانج','Discovery and solution planning':'دۆزینەوە و پلاندانانی چارەسەر','Week 1':'هەفتەی ١','Experience design and approval':'دیزاینی ئەزموون و پەسەندکردن','Weeks 2-3':'هەفتەی ٢-٣','Core platform, website and administration':'پلاتفۆرمی سەرەکی، ماڵپەڕ و بەڕێوەبردن','Weeks 3-7':'هەفتەی ٣-٧','Android and iOS applications':'ئەپی Android و iOS','Weeks 5-9':'هەفتەی ٥-٩','Payment, QR, testing and acceptance':'پارەدان، QR، تاقیکردنەوە و وەرگرتن','Weeks 9-11':'هەفتەی ٩-١١','Deployment and staff training':'بڵاوکردنەوە و ڕاهێنانی ستاف','Weeks 11-12':'هەفتەی ١١-١٢','PLANNING ASSUMPTION':'گریمانەی پلاندانان','The target assumes timely approvals, content delivery and third-party account readiness.':'ئامانجەکە پەسەندکردنی بەکات، گەیاندنی ناوەڕۆک و ئامادەبوونی هەژمارەکانی لایەنی سێیەم وەک بنەما دەگرێت.',
'Phase 1 scope':'مەودای قۆناغی ١','Status':'دۆخ','Responsive website and Android/iOS apps':'ماڵپەڕی وەڵامدەرەوە و ئەپی Android/iOS','Included':'لەخۆگیراوە','Booking, live availability, history and cancellation':'حجز، بەردەستبوونی ڕاستەوخۆ، مێژوو و هەڵوەشاندنەوە','Online payment, receipt and QR confirmation':'پارەدانی ئۆنلاین، پسوڵە و پشتڕاستکردنەوەی QR','Admin dashboard and basic reports':'داشبۆرد و ڕاپۆرتە سەرەتاییەکان','Backend APIs, deployment and staff training':'API ـەکانی پاشبنەما، بڵاوکردنەوە و ڕاهێنانی ستاف','Phase 1 investment':'وەبەرهێنانی قۆناغی ١','$6,900 USD':'٦,٩٠٠ دۆلار','SCOPE NOTE':'تێبینیی مەودا','Payment development and integration are included. Phase 2 and Phase 3 are not included in the Phase 1 price.':'گەشەپێدان و پەیوەستکردنی پارەدان لەخۆگیراوە. قۆناغی ٢ و ٣ لە نرخی قۆناغی ١دا نیین.',
'Digital membership':'ئەندامێتی دیجیتاڵی','Membership subscriptions':'بەشداریکردنی ئەندامێتی','Digital membership card':'کارتی ئەندامێتی دیجیتاڵی','Loyalty points and rewards':'خاڵ و خەڵاتی دڵسۆزی','Promotions and offers':'بانگەشە و ئۆفەر','Advanced reports and analytics':'ڕاپۆرت و شیکاریی پێشکەوتوو','Corporate and family memberships':'ئەندامێتی کۆمپانیا و خێزان','Referral and invitation program':'بەرنامەی ناساندن و بانگهێشت','Member surveys and feedback':'ڕاپرسی و فیدباکی ئەندامان','Automated renewal reminders':'بیرخستنەوەی ئۆتۆماتیکی نوێکردنەوە','Sponsor benefits and partner offers':'سوودی سپۆنسەر و ئۆفەری هاوبەش','Academy management':'بەڕێوەبردنی ئەکادیمی','Coach booking':'حجزکردنی ڕاهێنەر','Event ticketing':'بلیتی بۆنەکان','Restaurant ordering':'داواکاریی چێشتخانە','Smart access control':'کۆنترۆڵی زیرەکی چوونەژوورەوە','Parking management':'بەڕێوەبردنی پارکینگ','Club merchandise store':'فرۆشگای بەرهەمەکانی یانە','Digital wallet and cashless payments':'جزدانی دیجیتاڵی و پارەدانی بێ کاش','Digital guest passes':'مۆڵەتی دیجیتاڵی میوان','Maintenance request management':'بەڕێوەبردنی داواکاریی چاککردنەوە','Live match center and club media':'ناوەندی ڕاستەوخۆی یاری و میدیای یانە','Additional club services':'خزمەتگوزاریی زیاتری یانە','COMMERCIAL NOTE':'تێبینیی بازرگانی','Phase 2 and Phase 3 are optional future modules outside the current Phase 1 scope. No pricing is presented; each phase will be scoped and priced separately when Erbil Sports Club chooses to proceed.':'قۆناغی ٢ و ٣ مۆدیولی هەڵبژاردەی داهاتوون و لە مەودای قۆناغی ١دا نیین. هیچ نرخێک پیشان نەدراوە؛ هەر قۆناغێک کاتێک یانە بڕیاری جێبەجێکردن دەدات بە جیا مەودا و نرخ دەکرێت.','PHASE 1 INVESTMENT':'وەبەرهێنانی قۆناغی ١','$6,900 USD | 30% approval | 40% core demo | 30% final deployment':'٦,٩٠٠ دۆلار | ٣٠٪ پەسەندکردن | ٤٠٪ دیمۆی سەرەکی | ٣٠٪ بڵاوکردنەوەی کۆتایی','ERBIL SPORTS CLUB DIGITAL PLATFORM                                               COOVIX':'COOVIX                                               پلاتفۆرمی دیجیتاڵی یانەی وەرزشی هەولێر','Prepared by Coovix  |  ':'ئامادەکراوە لەلایەن Coovix | '
}

def copy_run_format(src, dst):
    if src is None:
        return
    dst.bold = src.bold; dst.italic = src.italic; dst.underline = src.underline
    dst.font.name = 'Arial'
    dst.font.size = src.font.size
    if src.font.color and src.font.color.type is not None:
        dst.font.color.rgb = src.font.color.rgb
    rpr = dst._element.get_or_add_rPr()
    fonts = rpr.rFonts
    fonts.set(qn('w:ascii'), 'Arial'); fonts.set(qn('w:hAnsi'), 'Arial'); fonts.set(qn('w:cs'), 'Arial')
    rtl = OxmlElement('w:rtl'); rtl.set(qn('w:val'), '1'); rpr.append(rtl)

def set_rtl(paragraph):
    ppr = paragraph._p.get_or_add_pPr()
    if ppr.find(qn('w:bidi')) is None:
        bidi = OxmlElement('w:bidi'); bidi.set(qn('w:val'), '1'); ppr.append(bidi)
    if paragraph.alignment not in (WD_ALIGN_PARAGRAPH.CENTER,):
        paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    for run in paragraph.runs:
        rpr = run._element.get_or_add_rPr()
        if rpr.find(qn('w:rtl')) is None:
            rtl = OxmlElement('w:rtl'); rtl.set(qn('w:val'), '1'); rpr.append(rtl)
        run.font.name = 'Arial'
        rpr.rFonts.set(qn('w:ascii'), 'Arial'); rpr.rFonts.set(qn('w:hAnsi'), 'Arial'); rpr.rFonts.set(qn('w:cs'), 'Arial')

def replace_paragraph(paragraph):
    old = paragraph.text
    if old in T:
        has_field = paragraph._p.find(qn('w:fldSimple')) is not None
        if has_field:
            for run in paragraph.runs:
                if run.text in T:
                    run.text = T[run.text]
                    copy_run_format(run, run)
        else:
            source_run = paragraph.runs[0] if paragraph.runs else None
            for run in list(paragraph.runs):
                paragraph._p.remove(run._r)
            new_run = paragraph.add_run(T[old])
            copy_run_format(source_run, new_run)
    set_rtl(paragraph)

def set_table_rtl(table, reverse_visual=True):
    if reverse_visual:
        tblpr = table._tbl.tblPr
        if tblpr.find(qn('w:bidiVisual')) is None:
            bidi = OxmlElement('w:bidiVisual'); bidi.set(qn('w:val'), '1'); tblpr.append(bidi)
    for row in table.rows:
        for cell in row.cells:
            for paragraph in cell.paragraphs:
                replace_paragraph(paragraph)
            for nested in cell.tables:
                set_table_rtl(nested)

doc = Document(SOURCE)
for paragraph in doc.paragraphs:
    replace_paragraph(paragraph)
for index, table in enumerate(doc.tables):
    set_table_rtl(table, reverse_visual=index != 0)
for section in doc.sections:
    for part in (section.header, section.footer, section.first_page_header, section.first_page_footer):
        for paragraph in part.paragraphs:
            replace_paragraph(paragraph)
        for table in part.tables:
            set_table_rtl(table)

doc.core_properties.title = 'پێشنیاری پلاتفۆرمی دیجیتاڵی تۆپی پێی یانەی وەرزشی هەولێر'
doc.core_properties.subject = 'قۆناغی ١ - ماڵپەڕ، ئەپی مۆبایل، حجز و پارەدان'
doc.core_properties.author = 'Coovix'
doc.save(OUT)

# Replace the four English mockup images with Kurdish production-ready artwork.
asset_dir = TMP / 'docx_assets_kurdish_v7'
asset_dir.mkdir(parents=True, exist_ok=True)
with Image.open(TMP / 'previews-kurdish-v7/slide-06.png') as image:
    image.crop((55, 190, 1225, 650)).save(asset_dir / 'website.png')
with Image.open(TMP / 'kurdish-mobile-fixed.png') as image:
    image.resize((1150, 460), Image.Resampling.LANCZOS).save(asset_dir / 'mobile.png')
with Image.open(TMP / 'kurdish-booking-fixed.png') as image:
    image.resize((1170, 450), Image.Resampling.LANCZOS).save(asset_dir / 'journey.png')
with Image.open(TMP / 'previews-kurdish-v7/slide-09.png') as image:
    image.crop((55, 165, 1225, 650)).save(asset_dir / 'admin.png')

replacements = {
    'word/media/image3.png': asset_dir / 'website.png',
    'word/media/image4.png': asset_dir / 'mobile.png',
    'word/media/image5.png': asset_dir / 'journey.png',
    'word/media/image6.png': asset_dir / 'admin.png',
}
temp_zip = OUT.with_suffix('.tmp.docx')
with ZipFile(OUT, 'r') as zin, ZipFile(temp_zip, 'w', ZIP_DEFLATED) as zout:
    for item in zin.infolist():
        data = replacements[item.filename].read_bytes() if item.filename in replacements else zin.read(item.filename)
        zout.writestr(item, data)
temp_zip.replace(OUT)

missing = []
for paragraph in doc.paragraphs:
    if paragraph.text in T:
        missing.append(paragraph.text)
print(f'Wrote {OUT}; untranslated exact matches remaining: {len(missing)}')
