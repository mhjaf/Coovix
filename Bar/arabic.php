<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الحلاق الكلاسيكي - العناية الفاخرة بالرجال</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="rtl.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Arabic Font Override */
        body, .nav-menu a, .btn-primary, .btn-secondary, p, h1, h2, h3, h4, label, input, select, textarea, button {
            font-family: 'Tajawal', 'Poppins', sans-serif;
        }
        .hero-title, .section-header h2, .modal-title, .about-content h2, .contact-info h2, .footer-section h3 {
            font-family: 'Tajawal', 'Playfair Display', serif;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <h2>الحلاق کودیفا</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="#home">الرئيسية</a></li>
                    <li><a href="#services">الخدمات</a></li>
                    <li><a href="#about">من نحن</a></li>
                    <li><a href="#barbers">الحلاقون</a></li>
                    <li><a href="#gallery">المعرض</a></li>
                    <li><a href="#location">الموقع</a></li>
                    <!-- Mobile Only Items -->
                    <li class="mobile-nav-item">
                        <div class="mobile-language-dropdown">
                            <button class="mobile-lang-toggle" id="mobileLangToggle">
                                <span>العربية</span>
                                <span class="mobile-dropdown-arrow">▼</span>
                            </button>
                            <div class="mobile-lang-menu" id="mobileLangMenu">
                                <button class="mobile-lang-option" data-lang="en" onclick="window.location.href='index.php'">English</button>
                                <button class="mobile-lang-option" data-lang="ku" onclick="window.location.href='kurdish.php'">کوردی</button>
                                <button class="mobile-lang-option active" data-lang="ar">العربية</button>
                            </div>
                        </div>
                    </li>
                    <li class="mobile-nav-item mobile-book-btn">
                        <button class="btn-primary" data-action="book">احجز الآن</button>
                    </li>
                </ul>
                <div class="nav-actions">
                    <div class="language-dropdown">
                        <button class="language-btn" id="languageBtn">
                            <span class="current-lang">AR</span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="language-menu" id="languageMenu">
                            <button class="language-option" data-lang="en" onclick="window.location.href='index.php'">
                                <span>English</span>
                            </button>
                            <button class="language-option" data-lang="ku" onclick="window.location.href='kurdish.php'">
                                <span>کوردی</span>
                            </button>
                            <button class="language-option active" data-lang="ar">
                                <span>العربية</span>
                            </button>
                        </div>
                    </div>
                    <button class="btn-primary" data-action="book">احجز الآن</button>
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">العناية الفاخرة بالرجال</h1>
                <p class="hero-subtitle">اختبر فن الحلاقة التقليدية بلمسة عصرية</p>
                <div class="hero-buttons">
                    <button class="btn-primary" data-action="book">احجز موعداً</button>
                    <button class="btn-secondary">عرض الخدمات</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>خدماتنا</h2>
                <p>خدمات العناية الاحترافية المصممة حسب أسلوبك</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">✂️</div>
                    <h3>قصة الشعر الكلاسيكية</h3>
                    <p>قصات تقليدية وحديثة منسقة بإتقان</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🪒</div>
                    <h3>حلاقة بالمنشفة الساخنة</h3>
                    <p>حلاقة فاخرة بالموس مع علاج المنشفة الساخنة</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">💈</div>
                    <h3>تشذيب اللحية</h3>
                    <p>تشكيل وتهذيب دقيق للحية</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">✨</div>
                    <h3>الشعر واللحية معاً</h3>
                    <p>باقة العناية الكاملة للمظهر المثالي</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">👔</div>
                    <h3>الباقة الفاخرة</h3>
                    <p>قص الشعر، الحلاقة، تشذيب اللحية، وتدليك فروة الرأس</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🎨</div>
                    <h3>صبغ الشعر</h3>
                    <p>صبغ احترافي للشعر وتغطية الشيب</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <h2>عن الحلاق الكلاسيكي</h2>
                    <p class="about-text">
                        مع أكثر من 20 عاماً من الخبرة، أتقنا فن الحلاقة الكلاسيكية مع تبني التقنيات والأساليب الحديثة. حلاقونا المهرة ملتزمون بتقديم تجربة عناية استثنائية في بيئة راقية ومريحة.
                    </p>
                    <p class="about-text">
                        نؤمن بأن قصة الشعر الرائعة هي أكثر من مجرد خدمة - إنها تجربة. من لحظة دخولك، ستحظى باهتمام شخصي وحرفية خبيرة ستجعلك تبدو وتشعر بأفضل حالاتك.
                    </p>
                    <div class="about-features">
                        <div class="feature">
                            <h4>حلاقون خبراء</h4>
                            <p>محترفون معتمدون بسنوات من الخبرة</p>
                        </div>
                        <div class="feature">
                            <h4>منتجات فاخرة</h4>
                            <p>منتجات وأدوات عناية عالية الجودة</p>
                        </div>
                        <div class="feature">
                            <h4>أجواء مريحة</h4>
                            <p>بيئة مريحة مصممة لراحتك</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="image/about.jpg" alt="صالون الحلاقة الكلاسيكي">
                </div>
            </div>
        </div>
    </section>

    <!-- Barbers Section -->
    <section id="barbers" class="barbers">
        <div class="container">
            <div class="section-header">
                <h2>تعرف على حلاقينا</h2>
                <p>محترفون خبراء مكرسون لأسلوبك</p>
            </div>
            <div class="barbers-grid" id="barbersGrid">
                <?php $_GET['lang'] = 'ar'; include 'get-barbers.php'; ?>
            </div>
            <div class="barbers-dots" id="barbersDots"></div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="gallery">
        <div class="container-full">
            <div class="section-header">
                <h2>أعمالنا</h2>
                <p>شاهد التحولات التي نصنعها كل يوم</p>
            </div>

            <div class="slider-wrapper">
                <button class="slider-arrow slider-arrow-left" onclick="moveSlider(-1)">
                    <svg width="50" height="50" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="25" fill="#FF6B35"/>
                        <path d="M30 15L20 25L30 35" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div class="slider-container">
                    <div class="slider-track" id="sliderTrack">
                        <div class="slide" data-index="0">
                            <img src="image/Before_After.jpg" alt="قصة كلاسيكية">
                            <div class="slide-overlay">
                                <h3 class="slide-name">قصة كلاسيكية</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="1">
                            <img src="image/Before_After.jpg" alt="تصفيف اللحية">
                            <div class="slide-overlay">
                                <h3 class="slide-name">تصفيف اللحية</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="2">
                            <img src="image/Before_After.jpg" alt="قصة فاخرة">
                            <div class="slide-overlay">
                                <h3 class="slide-name">قصة فاخرة</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="3">
                            <img src="image/Before_After.jpg" alt="الفيد المتقن">
                            <div class="slide-overlay">
                                <h3 class="slide-name">الفيد المتقن</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="slider-arrow slider-arrow-right" onclick="moveSlider(1)">
                    <svg width="50" height="50" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="25" fill="#FF6B35"/>
                        <path d="M20 15L30 25L20 35" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- Hours & Location Section -->
    <section id="location" class="location-section">
        <div class="container">
            <div class="section-header">
                <h2>زورونا</h2>
                <p>اعثر علينا وخطط لزيارتك</p>
            </div>
            <!-- Map Section -->
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1563716.4!2d44.3871953!3d36.4103395!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40090675168d57f5%3A0x18d1cbbe61767ce8!2sKurdistan%20Region!5e0!3m2!1sen!2s"
                    width="100%"
                    height="450"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

            <div class="location-grid">
                <!-- Hours Card - New Design -->
                <div class="hours-card-new">
                    <div class="hours-header">
                        <div class="hours-icon">⏰</div>
                        <div class="hours-title-group">
                            <h3>ساعات العمل</h3>
                            <span class="hours-subtitle">نحن هنا عندما تحتاجنا</span>
                        </div>
                    </div>

                    <div class="hours-grid">
                        <div class="hours-day-card">
                            <div class="day-label">السبت - الخميس</div>
                            <div class="time-label">9:00 ص - 8:00 م</div>
                        </div>

                        <div class="hours-day-card">
                            <div class="day-label">الجمعة</div>
                            <div class="time-label">مغلق</div>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>الحلاق الكلاسيكي</h3>
                    <p>خدمات العناية الفاخرة بالرجال منذ 2004</p>
                </div>
                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="#home">الرئيسية</a></li>
                        <li><a href="#services">الخدمات</a></li>
                        <li><a href="#about">من نحن</a></li>
                        <li><a href="#location">الموقع</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>تابعنا</h4>
                    <div class="contact-info-footer">
                        <p><i class="fas fa-phone"></i> <span class="ltr-text">+964 750 765 0999</span></p>
                        <p><i class="fas fa-envelope"></i> info@coovix.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> أربيل، إقليم كردستان</p>
                    </div>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 الحلاق الكلاسيكي. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <h2 class="modal-title">احجز موعدك</h2>
            <p class="modal-subtitle">املأ النموذج أدناه وسنتواصل معك قريباً</p>
            <form class="booking-form" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>اسمك *</label>
                        <input type="text" placeholder="أحمد محمد" required>
                    </div>
                    <div class="form-group">
                        <label>رقم الهاتف *</label>
                        <input type="tel" placeholder="750 123 4567" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>اختر الخدمة *</label>
                        <select id="serviceSelect" required>
                            <option value="">اختر خدمة</option>
                            <option value="haircut">قصة الشعر الكلاسيكية</option>
                            <option value="shave">حلاقة بالمنشفة الساخنة</option>
                            <option value="beard">تشذيب اللحية</option>
                            <option value="combo">الشعر واللحية معاً</option>
                            <option value="deluxe">الباقة الفاخرة</option>
                            <option value="coloring">صبغ الشعر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>اختر الحلاق *</label>
                        <select id="barberSelect" required>
                            <option value="">جاري تحميل الحلاقين...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>التاريخ المفضل * <span id="selectedDay" style="color: #FF6B35; font-weight: 600; margin-right: 10px;"></span></label>
                        <input type="date" id="dateSelect" required>
                    </div>
                    <div class="form-group">
                        <label>الوقت المفضل *</label>
                        <select id="timeSelect" required disabled>
                            <option value="">اختر الحلاق والتاريخ أولاً</option>
                        </select>
                        <small id="scheduleInfo" style="color: #888; font-size: 0.8rem; margin-top: 5px; display: block;"></small>
                    </div>
                </div>
                <script>
                    // Set today's date as default and minimum date (Baghdad timezone)
                    const dateInput = document.getElementById('dateSelect');
                    const selectedDayDisplay = document.getElementById('selectedDay');
                    const baghdadTime = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Baghdad"}));
                    const today = baghdadTime.toISOString().split('T')[0];
                    dateInput.value = today;
                    dateInput.min = today;

                    // Function to update day name in Arabic
                    function updateDayName(dateValue) {
                        if (dateValue) {
                            const date = new Date(dateValue + 'T00:00:00');
                            const dayName = date.toLocaleDateString('ar-IQ', { weekday: 'long', timeZone: 'Asia/Baghdad' });
                            selectedDayDisplay.textContent = `(${dayName})`;
                        }
                    }

                    // Show today's day name
                    updateDayName(today);

                    // Update day name when date changes
                    dateInput.addEventListener('change', function() {
                        updateDayName(this.value);
                    });
                </script>
                <div class="form-group">
                    <label>ملاحظات إضافية</label>
                    <textarea placeholder="أي طلبات أو تفضيلات خاصة..." rows="4"></textarea>
                </div>
                <button type="submit" class="btn-primary btn-full">تأكيد الحجز</button>
            </form>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <a href="https://wa.me/9647507650999" target="_blank" class="whatsapp-float" aria-label="واتساب">
        <i class="fab fa-whatsapp"></i>
    </a>
    <button class="scroll-to-top" id="scrollToTop" aria-label="العودة للأعلى">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
