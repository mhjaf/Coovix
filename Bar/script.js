// Slider functionality - True infinite carousel
let currentSlide = 0;
const sliderTrack = document.getElementById('sliderTrack');
const originalSlides = document.querySelectorAll('.slide');
const totalSlides = originalSlides.length;
let autoPlayInterval;
let isTransitioning = false;
let isHovering = false;

// Clone slides for infinite effect
function setupInfiniteSlider() {
    // Clone all slides and append them to the end
    originalSlides.forEach(slide => {
        const clone = slide.cloneNode(true);
        clone.classList.remove('active');
        sliderTrack.appendChild(clone);
    });
    
    // Clone all slides again and prepend them to the beginning
    const allSlides = Array.from(originalSlides);
    allSlides.reverse().forEach(slide => {
        const clone = slide.cloneNode(true);
        clone.classList.remove('active');
        sliderTrack.insertBefore(clone, sliderTrack.firstChild);
    });
    
    // Start at the first real slide (after prepended clones)
    currentSlide = totalSlides;
    updateSliderPosition(false); // Position without transition
    
    // Add hover listeners to all slides (including clones)
    addHoverListeners();
}

function addHoverListeners() {
    const allSlides = document.querySelectorAll('.slide');
    
    allSlides.forEach((slide, index) => {
        slide.addEventListener('mouseenter', () => {
            if (!isTransitioning) {
                isHovering = true;
                stopAutoPlay();
                centerSlide(index);
            }
        });
        
        slide.addEventListener('mouseleave', () => {
            isHovering = false;
            // Resume auto-play after a short delay
            setTimeout(() => {
                if (!isHovering) {
                    startAutoPlay();
                }
            }, 1000);
        });
    });
}

function centerSlide(slideIndex) {
    if (isTransitioning) return;
    
    currentSlide = slideIndex;
    updateSliderPosition(true);
}

function updateSliderPosition(withTransition = true) {
    const slideWidth = originalSlides[0].offsetWidth;
    const gap = 20;
    const containerWidth = document.querySelector('.slider-container').offsetWidth;
    const centerOffset = (containerWidth / 2) - (slideWidth / 2) - 80; // 80 = 5rem padding
    const translateX = -((slideWidth + gap) * currentSlide) + centerOffset;
    
    if (withTransition) {
        sliderTrack.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
    } else {
        sliderTrack.style.transition = 'none';
    }
    
    sliderTrack.style.transform = `translateX(${translateX}px)`;
    
    // Update active class on visible slides
    const allSlides = document.querySelectorAll('.slide');
    allSlides.forEach((slide, index) => {
        slide.classList.remove('active');
        if (index === currentSlide) {
            slide.classList.add('active');
        }
    });
}

function moveToNextSlide() {
    if (isTransitioning || isHovering) return;
    
    isTransitioning = true;
    currentSlide++;
    updateSliderPosition(true);
    
    // Check if we need to loop
    setTimeout(() => {
        if (currentSlide >= totalSlides * 2) {
            // We've reached the cloned slides at the end
            // Jump back to the real slides without transition
            currentSlide = totalSlides;
            updateSliderPosition(false);
        }
        isTransitioning = false;
    }, 600); // Match the transition duration
}

// Auto-play functionality
function startAutoPlay() {
    stopAutoPlay();
    autoPlayInterval = setInterval(() => {
        moveToNextSlide();
    }, 3000); // Change slide every 3 seconds
}

function stopAutoPlay() {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
    }
}

function moveSlider(direction) {
    if (isTransitioning) return;
    
    isTransitioning = true;
    currentSlide += direction;
    updateSliderPosition(true);
    
    // Check if we need to loop
    setTimeout(() => {
        if (currentSlide >= totalSlides * 2) {
            currentSlide = totalSlides;
            updateSliderPosition(false);
        } else if (currentSlide < totalSlides) {
            currentSlide = totalSlides * 2 - 1;
            updateSliderPosition(false);
        }
        isTransitioning = false;
    }, 600);
}

// Initialize slider on page load
window.addEventListener('load', () => {
    setupInfiniteSlider();
    startAutoPlay();
});

// Update on window resize
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        updateSliderPosition(false);
    }, 100);
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        moveSlider(-1);
    } else if (e.key === 'ArrowRight') {
        moveSlider(1);
    }
});

// Touch/swipe support
let touchStartX = 0;
let touchEndX = 0;

sliderTrack.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, { passive: true });

sliderTrack.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, { passive: true });

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            // Swipe left - next slide
            moveSlider(1);
        } else {
            // Swipe right - previous slide
            moveSlider(-1);
        }
    }
}

// =============================================
// Rest of the original JavaScript functionality
// =============================================

document.addEventListener('DOMContentLoaded', function() {

    // Language Dropdown Functionality
    const languageBtn = document.getElementById('languageBtn');
    const languageMenu = document.getElementById('languageMenu');
    const languageDropdown = document.querySelector('.language-dropdown');
    const languageOptions = document.querySelectorAll('.language-option');
    const currentLangSpan = document.querySelector('.current-lang');

    // Toggle dropdown
    if (languageBtn) {
        languageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            languageDropdown.classList.toggle('active');
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (languageDropdown && !languageDropdown.contains(e.target)) {
            languageDropdown.classList.remove('active');
        }
    });

    // Handle language selection
    languageOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();

            // Get selected language
            const selectedLang = this.getAttribute('data-lang');
            const langText = this.querySelector('span').textContent;

            // Store language preference
            localStorage.setItem('selectedLanguage', selectedLang);

            // Redirect to appropriate language page
            const currentPage = window.location.pathname.split('/').pop();

            if (selectedLang === 'en') {
                if (currentPage !== 'index.php') {
                    window.location.href = 'index.php';
                    return;
                }
            } else if (selectedLang === 'ku') {
                if (currentPage !== 'kurdish.php' && currentPage !== 'kurdish.html') {
                    window.location.href = 'kurdish.php';
                    return;
                }
            } else if (selectedLang === 'ar') {
                if (currentPage !== 'arabic.php' && currentPage !== 'arabic.html') {
                    window.location.href = 'arabic.php';
                    return;
                }
            }

            // If already on the correct page, just update UI
            // Update current language display
            if (selectedLang === 'en') {
                currentLangSpan.textContent = 'EN';
            } else if (selectedLang === 'ku') {
                currentLangSpan.textContent = 'KU';
            } else if (selectedLang === 'ar') {
                currentLangSpan.textContent = 'AR';
            }

            // Remove active class from all options
            languageOptions.forEach(opt => opt.classList.remove('active'));

            // Add active class to selected option
            this.classList.add('active');

            // Close dropdown
            languageDropdown.classList.remove('active');

            // Apply language change

            // For Arabic and Kurdish, set text direction to RTL
            if (selectedLang === 'ar' || selectedLang === 'ku') {
                document.body.setAttribute('dir', 'rtl');
            } else {
                document.body.setAttribute('dir', 'ltr');
            }
        });
    });

    // Load saved language preference on page load
    const savedLang = localStorage.getItem('selectedLanguage') || 'en';
    const savedOption = document.querySelector(`.language-option[data-lang="${savedLang}"]`);

    if (savedOption) {
        savedOption.classList.add('active');

        if (savedLang === 'en') {
            currentLangSpan.textContent = 'EN';
        } else if (savedLang === 'ku') {
            currentLangSpan.textContent = 'KU';
            document.body.setAttribute('dir', 'rtl');
        } else if (savedLang === 'ar') {
            currentLangSpan.textContent = 'AR';
            document.body.setAttribute('dir', 'rtl');
        }
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Booking Modal Functionality
    const bookingModal = document.getElementById('bookingModal');
    const closeModalBtn = document.getElementById('closeModal');
    const bookingForm = document.getElementById('bookingForm');

    if (!bookingModal) {
        return;
    }

    // Get all booking buttons by data-action attribute
    const bookButtons = document.querySelectorAll('[data-action="book"]');

    // Open modal when clicking book buttons
    bookButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            bookingModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal when clicking close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            bookingModal.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close modal when clicking outside the modal content
    bookingModal.addEventListener('click', function(e) {
        if (e.target === bookingModal) {
            bookingModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bookingModal.classList.contains('active')) {
            bookingModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // API base URL
    const API_URL = 'admin/api.php';

    // Load barbers from database
    function loadBarbers() {
        const barberSelect = document.getElementById('barberSelect');
        if (!barberSelect) return;

        fetch(`${API_URL}?action=get_barbers`)
            .then(response => response.json())
            .then(data => {
                if (data.barbers && data.barbers.length > 0) {
                    barberSelect.innerHTML = '<option value="">Choose your barber</option>';
                    data.barbers.forEach(barber => {
                        const option = document.createElement('option');
                        option.value = barber.id;
                        option.textContent = barber.name;
                        barberSelect.appendChild(option);
                    });
                } else {
                    barberSelect.innerHTML = '<option value="">No barbers available</option>';
                }
            })
            .catch(error => {
                barberSelect.innerHTML = '<option value="">Error loading barbers</option>';
            });
    }

    // Load available times based on barber and date
    function loadAvailableTimes() {
        const barberSelect = document.getElementById('barberSelect');
        const dateSelect = document.getElementById('dateSelect');
        const timeSelect = document.getElementById('timeSelect');
        const scheduleInfo = document.getElementById('scheduleInfo');

        if (!barberSelect || !dateSelect || !timeSelect) return;

        const barberId = barberSelect.value;
        const date = dateSelect.value;

        // Reset time select
        timeSelect.innerHTML = '<option value="">Loading times...</option>';
        timeSelect.disabled = true;
        if (scheduleInfo) scheduleInfo.textContent = '';

        if (!barberId || !date) {
            timeSelect.innerHTML = '<option value="">Select barber and date first</option>';
            return;
        }

        fetch(`${API_URL}?action=get_available_times&barber_id=${barberId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    timeSelect.innerHTML = `<option value="">${data.error}</option>`;
                    return;
                }

                if (data.message) {
                    timeSelect.innerHTML = `<option value="">${data.message}</option>`;
                    if (scheduleInfo) scheduleInfo.textContent = 'This barber is not available on the selected day.';
                    return;
                }

                if (data.times && data.times.length > 0) {
                    timeSelect.innerHTML = '<option value="">Choose a time</option>';
                    data.times.forEach(time => {
                        const option = document.createElement('option');
                        option.value = time.value;
                        option.textContent = time.label;
                        timeSelect.appendChild(option);
                    });
                    timeSelect.disabled = false;

                    // Show schedule info
                    if (scheduleInfo && data.schedule) {
                        let info = `Working: ${data.schedule.start} - ${data.schedule.end}`;
                        if (data.schedule.break_start) {
                            info += ` | Break: ${data.schedule.break_start} - ${data.schedule.break_end}`;
                        }
                        scheduleInfo.textContent = info;
                    }
                } else {
                    timeSelect.innerHTML = '<option value="">No available times</option>';
                    if (scheduleInfo) scheduleInfo.textContent = 'All time slots are booked for this day.';
                }
            })
            .catch(error => {
                timeSelect.innerHTML = '<option value="">Error loading times</option>';
            });
    }

    // Set minimum date to today
    function setMinDate() {
        const dateSelect = document.getElementById('dateSelect');
        if (dateSelect) {
            const today = new Date().toISOString().split('T')[0];
            dateSelect.setAttribute('min', today);
        }
    }

    // Event listeners for barber and date change
    const barberSelect = document.getElementById('barberSelect');
    const dateSelect = document.getElementById('dateSelect');

    if (barberSelect) {
        barberSelect.addEventListener('change', loadAvailableTimes);
    }

    if (dateSelect) {
        dateSelect.addEventListener('change', loadAvailableTimes);
    }

    // Load barbers and set min date on page load
    loadBarbers();
    setMinDate();

    // Handle booking form submission
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form values using IDs
            const name = this.querySelector('input[type="text"]').value;
            const phone = this.querySelector('input[type="tel"]').value;
            const serviceSelect = document.getElementById('serviceSelect');
            const barberSelectEl = document.getElementById('barberSelect');
            const dateSelectEl = document.getElementById('dateSelect');
            const timeSelectEl = document.getElementById('timeSelect');
            const notes = this.querySelector('textarea').value;

            const service = serviceSelect ? serviceSelect.value : '';
            const barber = barberSelectEl ? barberSelectEl.value : '';
            const date = dateSelectEl ? dateSelectEl.value : '';
            const time = timeSelectEl ? timeSelectEl.value : '';

            // Validate form
            if (!name || !phone || !service || !barber || !date || !time) {
                alert('Please fill in all required fields');
                return;
            }

            // Get display names for confirmation
            const serviceName = serviceSelect.options[serviceSelect.selectedIndex].text;
            const barberName = barberSelectEl.options[barberSelectEl.selectedIndex].text;
            const timeName = timeSelectEl.options[timeSelectEl.selectedIndex].text;

            // Submit to database
            const formData = new FormData();
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('service', serviceName);
            formData.append('barber', barber);
            formData.append('date', date);
            formData.append('time', time);
            formData.append('notes', notes);

            fetch('admin/book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close booking modal
                    bookingModal.classList.remove('active');

                    // Build success details
                    const successDetails = document.getElementById('successDetails');
                    if (successDetails) {
                        successDetails.innerHTML =
                            '<div class="success-detail-item">' +
                                '<span class="success-detail-label"><i class="fas fa-user"></i> Name</span>' +
                                '<span class="success-detail-value">' + name + '</span>' +
                            '</div>' +
                            '<div class="success-detail-item">' +
                                '<span class="success-detail-label"><i class="fas fa-cut"></i> Service</span>' +
                                '<span class="success-detail-value">' + serviceName + '</span>' +
                            '</div>' +
                            '<div class="success-detail-item">' +
                                '<span class="success-detail-label"><i class="fas fa-user-tie"></i> Barber</span>' +
                                '<span class="success-detail-value">' + barberName + '</span>' +
                            '</div>' +
                            '<div class="success-detail-item">' +
                                '<span class="success-detail-label"><i class="fas fa-calendar"></i> Date</span>' +
                                '<span class="success-detail-value">' + date + '</span>' +
                            '</div>' +
                            '<div class="success-detail-item">' +
                                '<span class="success-detail-label"><i class="fas fa-clock"></i> Time</span>' +
                                '<span class="success-detail-value">' + timeName + '</span>' +
                            '</div>';
                    }

                    // Show success modal
                    const successModal = document.getElementById('successModal');
                    if (successModal) {
                        successModal.classList.add('active');
                    }

                    // Reset form
                    bookingForm.reset();
                } else {
                    // Close booking modal and restore scroll
                    bookingModal.classList.remove('active');
                    document.body.style.overflow = '';
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // Close booking modal and restore scroll
                bookingModal.classList.remove('active');
                document.body.style.overflow = '';
                alert('Error submitting booking. Please try again.');
            });
        });
    }

    // Handle success modal close
    const successOkBtn = document.getElementById('successOkBtn');
    const successModal = document.getElementById('successModal');
    if (successOkBtn && successModal) {
        successOkBtn.addEventListener('click', function() {
            successModal.classList.remove('active');
            document.body.style.overflow = '';
        });
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                successModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Handle "View Services" button
    const viewServicesBtn = document.querySelector('.btn-secondary');
    if (viewServicesBtn) {
        viewServicesBtn.addEventListener('click', function() {
            const servicesSection = document.querySelector('#services');
            if (servicesSection) {
                servicesSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }

    // Handle form submission in contact section
    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form values
            const name = this.querySelector('input[type="text"]').value;
            const email = this.querySelector('input[type="email"]').value;
            const phone = this.querySelector('input[type="tel"]').value;
            const selects = this.querySelectorAll('select');
            const service = selects[0].value;
            const barber = selects[1].value;
            const date = this.querySelector('input[type="date"]').value;
            const time = selects[2].value;
            const notes = this.querySelector('textarea').value;

            // Validate form
            if (!name || !email || !phone || !service || !barber || !date || !time) {
                alert('Please fill in all required fields');
                return;
            }

            // Create confirmation message
            const serviceName = selects[0].options[selects[0].selectedIndex].text;
            const barberName = selects[1].options[selects[1].selectedIndex].text;
            const timeName = selects[2].options[selects[2].selectedIndex].text;

            const confirmationMessage = `
Appointment Booked Successfully!

Name: ${name}
Email: ${email}
Phone: ${phone}
Service: ${serviceName}
Barber: ${barberName}
Date: ${date}
Time: ${timeName}
${notes ? 'Notes: ' + notes : ''}

We'll contact you shortly to confirm your appointment!
            `;

            // Show confirmation
            alert(confirmationMessage.trim());

            // Reset form
            this.reset();

        });
    }

    // Add active state to navigation links based on scroll position
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-menu a');

        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;

            if (window.scrollY >= (sectionTop - 100)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.style.color = '#ffffff';
            if (link.getAttribute('href') === `#${current}`) {
                link.style.color = '#FF6B35';
                link.style.fontWeight = '700';
            } else {
                link.style.fontWeight = '500';
            }
        });
    });
});

// Buy Product function
function buyProduct(productName, price) {
    const formattedPrice = price.toLocaleString();
    alert(`Thank you for your interest in ${productName}!\n\nPrice: ${formattedPrice}\n\nPlease visit our shop or contact us to complete your purchase.`);
}

// Success Modal Functions
function showSuccessModal(bookingData) {
    const successModal = document.getElementById('successModal');
    const successDetails = document.getElementById('successDetails');

    if (!successModal || !successDetails) {
        return;
    }
    
    // Build details HTML with icons
    let detailsHTML = `
        <div class="success-detail-item">
            <span class="success-detail-label">👤 Name</span>
            <span class="success-detail-value">${bookingData.name}</span>
        </div>
        <div class="success-detail-item">
            <span class="success-detail-label">📱 Phone</span>
            <span class="success-detail-value">${bookingData.phone}</span>
        </div>
        <div class="success-detail-item">
            <span class="success-detail-label">✂️ Service</span>
            <span class="success-detail-value">${bookingData.service}</span>
        </div>
        <div class="success-detail-item">
            <span class="success-detail-label">💈 Barber</span>
            <span class="success-detail-value">${bookingData.barber}</span>
        </div>
        <div class="success-detail-item">
            <span class="success-detail-label">📅 Date</span>
            <span class="success-detail-value">${bookingData.date}</span>
        </div>
        <div class="success-detail-item">
            <span class="success-detail-label">🕐 Time</span>
            <span class="success-detail-value">${bookingData.time}</span>
        </div>
    `;
    
    if (bookingData.notes) {
        detailsHTML += `
            <div class="success-detail-item">
                <span class="success-detail-label">📝 Notes</span>
                <span class="success-detail-value">${bookingData.notes}</span>
            </div>
        `;
    }
    
    successDetails.innerHTML = detailsHTML;
    successModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    const successModal = document.getElementById('successModal');
    successModal.classList.remove('active');
    document.body.style.overflow = '';
}

// Close success modal when clicking outside
document.addEventListener('click', function(e) {
    const successModal = document.getElementById('successModal');
    if (e.target === successModal) {
        closeSuccessModal();
    }
});

// Barbers Dots Navigation
document.addEventListener('DOMContentLoaded', function() {
    const barbersGrid = document.getElementById('barbersGrid');
    const barbersDots = document.getElementById('barbersDots');

    if (barbersGrid && barbersDots) {
        const barberCards = barbersGrid.querySelectorAll('.barber-card');

        // Only show dots if there are multiple barber cards
        if (barberCards.length > 1) {
            // Create dots
            barberCards.forEach((card, index) => {
                const dot = document.createElement('button');
                dot.className = 'barbers-dot' + (index === 0 ? ' active' : '');
                dot.setAttribute('aria-label', `Go to barber ${index + 1}`);
                dot.addEventListener('click', () => {
                    // Scroll to the card
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                });
                barbersDots.appendChild(dot);
            });

            // Update active dot on scroll
            let scrollTimeout;
            barbersGrid.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const scrollLeft = barbersGrid.scrollLeft;
                    const cardWidth = barberCards[0].offsetWidth + 25; // 25 is the gap
                    const activeIndex = Math.round(scrollLeft / cardWidth);

                    // Update dots
                    const dots = barbersDots.querySelectorAll('.barbers-dot');
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === activeIndex);
                    });
                }, 50);
            });
        }
    }
});

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.querySelector('.nav-menu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

    function closeMobileMenu() {
        if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
        if (navMenu) navMenu.classList.remove('active');
        if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function openMobileMenu() {
        if (mobileMenuToggle) mobileMenuToggle.classList.add('active');
        if (navMenu) navMenu.classList.add('active');
        if (mobileMenuOverlay) mobileMenuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    if (mobileMenuToggle && navMenu) {
        // Toggle mobile menu
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (navMenu.classList.contains('active')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        // Close menu when clicking a nav link
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Close menu when clicking overlay
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        }

        // Close menu on window resize (if switching to desktop view)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) {
                closeMobileMenu();
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }

    // Mobile Language Dropdown Toggle
    const mobileLangToggle = document.getElementById('mobileLangToggle');
    const mobileLangDropdown = document.querySelector('.mobile-language-dropdown');

    if (mobileLangToggle && mobileLangDropdown) {
        mobileLangToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileLangDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileLangDropdown.contains(e.target)) {
                mobileLangDropdown.classList.remove('active');
            }
        });
    }

    // Scroll to Top Button
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (scrollToTopBtn) {
        // Show/hide button on scroll
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        // Scroll to top when clicked
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});