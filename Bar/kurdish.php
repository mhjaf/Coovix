<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەلاکی کلاسیک - خزمەتگوزاری پیاوان</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="rtl.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Kurdish Font Override */
        body, .nav-menu a, .btn-primary, .btn-secondary, p, h1, h2, h3, h4, label, input, select, textarea, button {
            font-family: 'Noto Sans Arabic', 'Poppins', sans-serif;
        }
        .hero-title, .section-header h2, .modal-title, .about-content h2, .contact-info h2, .footer-section h3 {
            font-family: 'Noto Sans Arabic', 'Playfair Display', serif;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <h2>دەلاکی کۆدیڤا</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="#home">سەرەتا</a></li>
                    <li><a href="#services">خزمەتگوزاریەکان</a></li>
                    <li><a href="#about">دەربارەی ئێمە</a></li>
                    <li><a href="#barbers">دەلاکەکان</a></li>
                    <li><a href="#gallery">گەلەری</a></li>
                    <li><a href="#location">شوێن</a></li>
                    <!-- Mobile Only Items -->
                    <li class="mobile-nav-item">
                        <div class="mobile-language-dropdown">
                            <button class="mobile-lang-toggle" id="mobileLangToggle">
                                <span>کوردی</span>
                                <span class="mobile-dropdown-arrow">▼</span>
                            </button>
                            <div class="mobile-lang-menu" id="mobileLangMenu">
                                <button class="mobile-lang-option" data-lang="en" onclick="window.location.href='index.php'">English</button>
                                <button class="mobile-lang-option active" data-lang="ku">کوردی</button>
                                <button class="mobile-lang-option" data-lang="ar" onclick="window.location.href='arabic.php'">العربية</button>
                            </div>
                        </div>
                    </li>
                    <li class="mobile-nav-item mobile-book-btn">
                        <button class="btn-primary" data-action="book">نۆرەکردن</button>
                    </li>
                </ul>
                <div class="nav-actions">
                    <div class="language-dropdown">
                        <button class="language-btn" id="languageBtn">
                            <span class="current-lang">KU</span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="language-menu" id="languageMenu">
                            <button class="language-option" data-lang="en" onclick="window.location.href='index.php'">
                                <span>English</span>
                            </button>
                            <button class="language-option active" data-lang="ku">
                                <span>کوردی</span>
                            </button>
                            <button class="language-option" data-lang="ar" onclick="window.location.href='arabic.php'">
                                <span>العربية</span>
                            </button>
                        </div>
                    </div>
                    <button class="btn-primary" data-action="book">نۆرەکردن</button>
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
                <h1 class="hero-title">خزمەتگوزاری بەرزی پیاوان</h1>
                <p class="hero-subtitle">ئەزموونی هونەری دەلاکی کۆنەپەرست بە شێوازێکی نوێ</p>
                <div class="hero-buttons">
                    <button class="btn-primary" data-action="book">نۆرە بکە</button>
                    <button class="btn-secondary">خزمەتگوزاریەکان ببینە</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>خزمەتگوزاریەکانمان</h2>
                <p>خزمەتگوزاری پیشەیی بۆ ستایلی تۆ</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">✂️</div>
                    <h3>بڕینی قژی کلاسیک</h3>
                    <p>بڕینی قژی کۆن و نوێ بە شێوازی تەواو</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🪒</div>
                    <h3>تاشینی ڕیش بە تاوێڵی گەرم</h3>
                    <p>تاشینی ڕیش بە موس و تاوێڵی گەرم</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">💈</div>
                    <h3>ڕێکخستنی ڕیش</h3>
                    <p>شێوەدانی وردی ڕیش</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">✨</div>
                    <h3>قژ و ڕیش پێکەوە</h3>
                    <p>پاکەتی تەواو بۆ دیمەنی باش</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">👔</div>
                    <h3>پاکەتی لوکس</h3>
                    <p>بڕینی قژ، تاشین، ڕێکخستنی ڕیش، و ماساژی سەر</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🎨</div>
                    <h3>ڕەنگکردنی قژ</h3>
                    <p>ڕەنگکردنی پیشەیی قژ و داپۆشینی سپی</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <h2>دەربارەی دەلاکی کلاسیک</h2>
                    <p class="about-text">
                        بە زیاتر لە ٢٠ ساڵ ئەزموون، هونەری دەلاکی کۆنەپەرستمان تەواو کردووە لەگەڵ پەیڕەوکردنی تەکنیک و شێوازی نوێ. دەلاکە شارەزاکانمان دەستبەکارن بۆ پێشکەشکردنی ئەزموونی چاودێری تایبەت لە ژینگەیەکی ڕازیکەر و ئارام.
                    </p>
                    <p class="about-text">
                        باوەڕمان وایە کە بڕینی قژی باش زیاتر لە خزمەتگوزارییەکە - ئەزموونێکە. لە ساتی هاتنتەوە، چاودێری تایبەت و پیشەسازی شارەزات وەردەگریت کە وات دەکات باشترین دیمەن و هەست بکەیت.
                    </p>
                    <div class="about-features">
                        <div class="feature">
                            <h4>دەلاکە شارەزاکان</h4>
                            <p>پیشەگەرانی بڕوانامەدار بە ساڵانی ئەزموون</p>
                        </div>
                        <div class="feature">
                            <h4>بەرهەمی بەرز</h4>
                            <p>بەرهەم و ئامرازی چاودێری کوالیتی بەرز</p>
                        </div>
                        <div class="feature">
                            <h4>ژینگەی ئارام</h4>
                            <p>ژینگەیەکی ڕازیکەر دیزاین کراوە بۆ ئارامیت</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="image/about.jpg" alt="دەلاکی کلاسیک">
                </div>
            </div>
        </div>
    </section>

    <!-- Barbers Section -->
    <section id="barbers" class="barbers">
        <div class="container">
            <div class="section-header">
                <h2>دەلاکەکانمان بناسە</h2>
                <p>پیشەگەرانی شارەزا بۆ ستایلی تۆ</p>
            </div>
            <div class="barbers-grid" id="barbersGrid">
                <?php $_GET['lang'] = 'ku'; include 'get-barbers.php'; ?>
            </div>
            <div class="barbers-dots" id="barbersDots"></div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="gallery">
        <div class="container-full">
            <div class="section-header">
                <h2>کارەکانمان</h2>
                <p>ئەو گۆڕانکاریانە ببینە کە هەموو ڕۆژێک دروستیان دەکەین</p>
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
                            <img src="image/Before_After.jpg" alt="بڕینی کلاسیک">
                            <div class="slide-overlay">
                                <h3 class="slide-name">بڕینی کلاسیک</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="1">
                            <img src="image/Before_After.jpg" alt="شێوەدانی ڕیش">
                            <div class="slide-overlay">
                                <h3 class="slide-name">شێوەدانی ڕیش</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="2">
                            <img src="image/Before_After.jpg" alt="بڕینی لوکس">
                            <div class="slide-overlay">
                                <h3 class="slide-name">بڕینی لوکس</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="3">
                            <img src="image/Before_After.jpg" alt="فەید ماستەر">
                            <div class="slide-overlay">
                                <h3 class="slide-name">فەید ماستەر</h3>
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
                <h2>سەردانمان بکە</h2>
                <p>بماندۆزەوە و سەردانت پلان بکە</p>
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
                            <h3>کاتەکانی کارکردن</h3>
                            <span class="hours-subtitle">ئێمە لێرەین کاتێک پێویستت پێمانە</span>
                        </div>
                    </div>

                    <div class="hours-grid">
                        <div class="hours-day-card">
                            <div class="day-label">شەممە - پێنجشەممە</div>
                            <div class="time-label">٩:٠٠ بەیانی - ٨:٠٠ ئێوارە</div>
                        </div>

                        <div class="hours-day-card">
                            <div class="day-label">هەینی</div>
                            <div class="time-label">داخراوە</div>
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
                    <h3>دەلاکی کلاسیک</h3>
                    <p>خزمەتگوزاری بەرزی پیاوان لە ٢٠٠٤ەوە</p>
                </div>
                <div class="footer-section">
                    <h4>لینکە خێراکان</h4>
                    <ul>
                        <li><a href="#home">سەرەتا</a></li>
                        <li><a href="#services">خزمەتگوزاریەکان</a></li>
                        <li><a href="#about">دەربارەی ئێمە</a></li>
                        <li><a href="#location">شوێن</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>بمان بەدوادا بکەوە</h4>
                    <div class="contact-info-footer">
                        <p><i class="fas fa-phone"></i> <span class="ltr-text">+964 750 765 0999</span></p>
                        <p><i class="fas fa-envelope"></i> info@coovix.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> هەولێر، هەرێمی کوردستان</p>
                    </div>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; ٢٠٢٦ دەلاکی کلاسیک. هەموو مافەکان پارێزراون.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <h2 class="modal-title">نۆرەکەت تۆمار بکە</h2>
            <p class="modal-subtitle">فۆڕمەکەی خوارەوە پڕ بکەوە و بەم زووانە پەیوەندیت پێوە دەکەین</p>
            <form class="booking-form" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>ناوت *</label>
                        <input type="text" placeholder="ئەحمەد محەمەد" required>
                    </div>
                    <div class="form-group">
                        <label>ژمارەی تەلەفۆن *</label>
                        <input type="tel" placeholder="750 123 4567" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>خزمەتگوزاری هەڵبژێرە *</label>
                        <select id="serviceSelect" required>
                            <option value="">خزمەتگوزارییەک هەڵبژێرە</option>
                            <option value="haircut">بڕینی قژی کلاسیک</option>
                            <option value="shave">تاشینی ڕیش بە تاوێڵی گەرم</option>
                            <option value="beard">ڕێکخستنی ڕیش</option>
                            <option value="combo">قژ و ڕیش پێکەوە</option>
                            <option value="deluxe">پاکەتی لوکس</option>
                            <option value="coloring">ڕەنگکردنی قژ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>دەلاک هەڵبژێرە *</label>
                        <select id="barberSelect" required>
                            <option value="">دەلاکەکان باردەکرێن...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>بەرواری خوازراو * <span id="selectedDay" style="color: #FF6B35; font-weight: 600; margin-right: 10px;"></span></label>
                        <input type="date" id="dateSelect" required>
                    </div>
                    <div class="form-group">
                        <label>کاتی خوازراو *</label>
                        <select id="timeSelect" required disabled>
                            <option value="">سەرەتا دەلاک و بەروار هەڵبژێرە</option>
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

                    // Function to update day name in Kurdish
                    function updateDayName(dateValue) {
                        if (dateValue) {
                            const date = new Date(dateValue + 'T00:00:00');
                            const dayName = date.toLocaleDateString('ku', { weekday: 'long', timeZone: 'Asia/Baghdad' });
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
                    <label>تێبینی زیادە</label>
                    <textarea placeholder="هەر داواکاری یان خوازیاری تایبەت..." rows="4"></textarea>
                </div>
                <button type="submit" class="btn-primary btn-full">نۆرەکە پشتڕاست بکەوە</button>
            </form>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <a href="https://wa.me/9647507650999" target="_blank" class="whatsapp-float" aria-label="واتساپ">
        <i class="fab fa-whatsapp"></i>
    </a>
    <button class="scroll-to-top" id="scrollToTop" aria-label="گەڕانەوە بۆ سەرەوە">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
