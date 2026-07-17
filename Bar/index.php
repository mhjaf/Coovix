<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Classic Barber - Premium Men's Grooming</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <h2>Coovix BARBER</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#barbers">Barbers</a></li>
                    <li><a href="#gallery">Gallery</a></li>
                    <li><a href="#location">Location</a></li>
                    <!-- Mobile Only Items -->
                    <li class="mobile-nav-item">
                        <div class="mobile-language-dropdown">
                            <button class="mobile-lang-toggle" id="mobileLangToggle">
                                <span>English</span>
                                <span class="mobile-dropdown-arrow">▼</span>
                            </button>
                            <div class="mobile-lang-menu" id="mobileLangMenu">
                                <button class="mobile-lang-option active" data-lang="en">English</button>
                                <button class="mobile-lang-option" data-lang="ku" onclick="window.location.href='kurdish.php'">کوردی</button>
                                <button class="mobile-lang-option" data-lang="ar" onclick="window.location.href='arabic.php'">العربية</button>
                            </div>
                        </div>
                    </li>
                    <li class="mobile-nav-item mobile-book-btn">
                        <button class="btn-primary" data-action="book">Book Now</button>
                    </li>
                </ul>
                <div class="nav-actions">
                    <div class="language-dropdown">
                        <button class="language-btn" id="languageBtn">
                            <span class="current-lang">EN</span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="language-menu" id="languageMenu">
                            <button class="language-option active" data-lang="en">
                                <span>English</span>
                            </button>
                            <button class="language-option" data-lang="ku" onclick="window.location.href='kurdish.php'">
                                <span>کوردی</span>
                            </button>
                            <button class="language-option" data-lang="ar" onclick="window.location.href='arabic.php'">
                                <span>العربية</span>
                            </button>
                        </div>
                    </div>
                    <button class="btn-primary" data-action="book">Book Now</button>
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
                <h1 class="hero-title">Premium Men's Grooming</h1>
                <p class="hero-subtitle">Experience the art of traditional barbering with a modern touch</p>
                <div class="hero-buttons">
                    <button class="btn-primary" data-action="book">Book Appointment</button>
                    <button class="btn-secondary">View Services</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>Professional grooming services tailored to your style</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">✂️</div>
                    <h3>Classic Haircut</h3>
                    <p>Traditional and modern cuts styled to perfection</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🪒</div>
                    <h3>Hot Towel Shave</h3>
                    <p>Luxurious straight razor shave with hot towel treatment</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">💈</div>
                    <h3>Beard Trim</h3>
                    <p>Precise beard shaping and grooming</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">✨</div>
                    <h3>Hair & Beard Combo</h3>
                    <p>Complete grooming package for the perfect look</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">👔</div>
                    <h3>Deluxe Package</h3>
                    <p>Haircut, shave, beard trim, and scalp massage</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🎨</div>
                    <h3>Hair Coloring</h3>
                    <p>Professional hair coloring and grey coverage</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <h2>About The Classic Barber</h2>
                    <p class="about-text">
                        With over 20 years of experience, we've mastered the art of classic barbering while embracing modern techniques and styles. Our skilled barbers are dedicated to providing you with an exceptional grooming experience in a sophisticated, comfortable environment.
                    </p>
                    <p class="about-text">
                        We believe that a great haircut is more than just a service—it's an experience. From the moment you walk in, you'll be treated to personalized attention and expert craftsmanship that will leave you looking and feeling your absolute best.
                    </p>
                    <div class="about-features">
                        <div class="feature">
                            <h4>Expert Barbers</h4>
                            <p>Certified professionals with years of experience</p>
                        </div>
                        <div class="feature">
                            <h4>Premium Products</h4>
                            <p>High-quality grooming products and tools</p>
                        </div>
                        <div class="feature">
                            <h4>Relaxing Atmosphere</h4>
                            <p>Comfortable environment designed for your comfort</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="image/about.jpg" alt="Classic Barber Shop">
                </div>
            </div>
        </div>
    </section>

    <!-- Barbers Section -->
    <section id="barbers" class="barbers">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our Barbers</h2>
                <p>Expert professionals dedicated to your style</p>
            </div>
            <div class="barbers-grid" id="barbersGrid">
                <?php include 'get-barbers.php'; ?>
            </div>
            <div class="barbers-dots" id="barbersDots"></div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="gallery">
        <div class="container-full">
            <div class="section-header">
                <h2>Our Work</h2>
                <p>See the transformations we create every day</p>
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
                            <img src="image/Before_After.jpg" alt="Classic Haircut">
                            <div class="slide-overlay">
                                <h3 class="slide-name">Classic Haircut</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="1">
                            <img src="image/Before_After.jpg" alt="Beard Styling">
                            <div class="slide-overlay">
                                <h3 class="slide-name">Beard Styling</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="2">
                            <img src="image/Before_After.jpg" alt="Premium Cut">
                            <div class="slide-overlay">
                                <h3 class="slide-name">Premium Cut</h3>
                            </div>
                        </div>
                        <div class="slide" data-index="3">
                            <img src="image/Before_After.jpg" alt="Fade Master">
                            <div class="slide-overlay">
                                <h3 class="slide-name">Fade Master</h3>
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

    <!-- Hours & Location Section - NEW -->
    <section id="location" class="location-section">
        <div class="container">
            <div class="section-header">
                <h2>Visit Us</h2>
                <p>Find us and plan your visit</p>
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
                            <h3>Opening Hours</h3>
                            <span class="hours-subtitle">We're here when you need us</span>
                        </div>
                    </div>
                    
                    <div class="hours-grid">
                        <div class="hours-day-card">
                            <div class="day-label">SAT - THU</div>
                            <div class="time-label">9:00 AM - 8:00 PM</div>
                        </div>
                        
                        <div class="hours-day-card">
                            <div class="day-label">FRIDAY</div>
                            <div class="time-label">Close</div>
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
                    <h3>THE CLASSIC BARBER</h3>
                    <p>Premium men's grooming services since 2004</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#location">Location</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="contact-info-footer">
                        <p><i class="fas fa-phone"></i> +964 750 765 0999</p>
                        <p><i class="fas fa-envelope"></i> info@coovix.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> Erbil, Kurdistan Region</p>
                    </div>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 The Classic Barber. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <h2 class="modal-title">Book Your Appointment</h2>
            <p class="modal-subtitle">Fill out the form below and we'll contact you shortly</p>
            <form class="booking-form" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" placeholder="750 123 4567" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Service *</label>
                        <select id="serviceSelect" required>
                            <option value="">Choose a service</option>
                            <option value="haircut">Classic Haircut</option>
                            <option value="shave">Hot Towel Shave</option>
                            <option value="beard">Beard Trim</option>
                            <option value="combo">Hair & Beard Combo</option>
                            <option value="deluxe">Deluxe Package</option>
                            <option value="coloring">Hair Coloring</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Barber *</label>
                        <select id="barberSelect" required>
                            <option value="">Loading barbers...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Preferred Date * <span id="selectedDay" style="color: #FF6B35; font-weight: 600; margin-left: 10px;"></span></label>
                        <input type="date" id="dateSelect" required>
                    </div>
                    <div class="form-group">
                        <label>Preferred Time *</label>
                        <select id="timeSelect" required disabled>
                            <option value="">Select barber and date first</option>
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
                    
                    // Function to update day name
                    function updateDayName(dateValue) {
                        if (dateValue) {
                            const date = new Date(dateValue + 'T00:00:00');
                            const dayName = date.toLocaleDateString('en-US', { weekday: 'long', timeZone: 'Asia/Baghdad' });
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
                    <label>Additional Notes</label>
                    <textarea placeholder="Any special requests or preferences..." rows="4"></textarea>
                </div>
                <button type="submit" class="btn-primary btn-full">Confirm Booking</button>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal-overlay" id="successModal">
        <div class="success-modal-content">
            <div class="success-animation">
                <div class="success-icon">
                    <svg width="100" height="100" viewBox="0 0 100 100">
                        <circle class="success-circle" cx="50" cy="50" r="45" fill="none" stroke="#4CAF50" stroke-width="3"/>
                        <path class="success-check" d="M30 50 L45 65 L70 35" fill="none" stroke="#4CAF50" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="success-sparkles">
                    <span class="sparkle" style="--x: -60px; --y: -60px; --delay: 0.3s;">&#10024;</span>
                    <span class="sparkle" style="--x: 60px; --y: -40px; --delay: 0.5s;">&#10024;</span>
                    <span class="sparkle" style="--x: -40px; --y: 50px; --delay: 0.4s;">&#10024;</span>
                    <span class="sparkle" style="--x: 50px; --y: 60px; --delay: 0.6s;">&#10024;</span>
                    <span class="sparkle" style="--x: 0px; --y: -70px; --delay: 0.35s;">&#10024;</span>
                </div>
            </div>
            <h2 class="success-title">Congratulations!</h2>
            <p class="success-subtitle">Your appointment has been booked successfully</p>
            <div class="success-message">
                <p>&#10003; We will contact you shortly to confirm your appointment.</p>
            </div>
            <div class="success-details" id="successDetails">
                <!-- Filled dynamically by JS -->
            </div>
            <button class="btn-success-ok" id="successOkBtn">
                <span>Done</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <a href="https://wa.me/9647507650999" target="_blank" class="whatsapp-float" aria-label="WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
    <button class="scroll-to-top" id="scrollToTop" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>