// Resolve shared assets and pages from the directory that contains this script.
// This keeps the site working both at coovix.com/ and in MAMP's /coovix/ folder.
const coovixRootUrl = document.currentScript?.src
    ? new URL('.', document.currentScript.src)
    : new URL('./', window.location.href);

function coovixUrl(path) {
    return new URL(String(path).replace(/^\/+/, ''), coovixRootUrl).href;
}

// Loading Screen Animation
window.addEventListener('load', function() {
    setTimeout(function() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.add('hide');
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        }
    }, 1500);
});

// Language Dropdown Functionality
function initLanguageDropdown() {
    const languageDropdown = document.querySelector('.language-dropdown');
    const languageToggle = document.querySelector('.language-toggle');
    const languageOptions = document.querySelector('.language-options');
    
    if (!languageDropdown || !languageToggle || !languageOptions) {
        console.log('Language dropdown elements not found');
        return;
    }
    
    console.log('Language dropdown initialized');
    
    // Toggle dropdown on click
    languageToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isOpen = languageDropdown.classList.contains('open');
        
        if (isOpen) {
            languageDropdown.classList.remove('open');
            languageToggle.classList.remove('active');
        } else {
            languageDropdown.classList.add('open');
            languageToggle.classList.add('active');
        }
        
        console.log('Dropdown toggled:', !isOpen);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!languageDropdown.contains(e.target)) {
            languageDropdown.classList.remove('open');
            languageToggle.classList.remove('active');
        }
    });
    
    // Handle option selection
    const langOptions = document.querySelectorAll('.lang-option');
    langOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the selected language
            const selectedLang = this.querySelector('.lang-name').textContent;
            const currentLangElement = document.querySelector('.current-lang');
            
            // Update current language display
            if (currentLangElement) {
                currentLangElement.textContent = selectedLang;
            }
            
            // Remove active class from all options and add to current
            langOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Close dropdown
            languageDropdown.classList.remove('open');
            languageToggle.classList.remove('active');
            
            // Navigate to the page after a short delay
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            }
        });
    });
    
    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && languageDropdown.classList.contains('open')) {
            languageDropdown.classList.remove('open');
            languageToggle.classList.remove('active');
        }
    });
    
    // Handle keyboard navigation
    languageToggle.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            languageDropdown.classList.toggle('open');
            languageToggle.classList.toggle('active');
        }
    });
}

// Custom Cursor code removed - using normal cursor now

// Add event listeners for interactive elements (kept for compatibility)
function updateInteractiveElements() {
    // This function now does nothing but is kept for compatibility
    // Normal cursor will handle hover states through CSS
}

// Call this function after DOM is loaded
document.addEventListener('DOMContentLoaded', updateInteractiveElements);

// Particle Background
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    const particleCount = 50;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        particlesContainer.appendChild(particle);
    }
}

// Scroll Indicator
function updateScrollIndicator() {
    const scrollIndicator = document.getElementById('scrollIndicator');
    if (!scrollIndicator) return;
    
    const scrollTop = window.pageYOffset;
    const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
    const scrollProgress = (scrollTop / documentHeight) * 100;
    scrollIndicator.style.width = scrollProgress + '%';
}

// Back to Top Button
const backToTopBtn = document.getElementById('backToTop');

function toggleBackToTop() {
    if (!backToTopBtn) return;
    
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
}

if (backToTopBtn) {
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Enhanced Scroll Animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
            revealObserver.unobserve(entry.target);
        }
    });
}, observerOptions);

// Staggered Animation Observer
const staggerObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const items = entry.target.querySelectorAll('.stagger-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('active');
                    if (item.classList.contains('service-card') || 
                        item.classList.contains('portfolio-item') || 
                        item.classList.contains('tech-item') || 
                        item.classList.contains('pricing-card') || 
                        item.classList.contains('tech-card') || 
                        item.classList.contains('tip-card') || 
                        item.classList.contains('creation-step') ||
                        item.classList.contains('menu-step-card') ||
                        item.classList.contains('menu-type-card')) {
                        item.classList.add('animate');
                    }
                }, index * 200);
            });
            staggerObserver.unobserve(entry.target);
        }
    });
}, observerOptions);

// Apply observers
document.querySelectorAll('.reveal').forEach(el => {
    revealObserver.observe(el);
});

document.querySelectorAll('.services-grid, .tech-list, .pricing-grid, .tech-grid, .tips-grid, .step-timeline, .menu-steps-grid, .menu-types-grid, .values-grid').forEach(el => {
    staggerObserver.observe(el);
});

// Stats Counter Animation
function animateStats() {
    const stats = document.querySelectorAll('.stat-number[data-target]');
    
    stats.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target'));
        const suffix = stat.textContent.replace(/[0-9]/g, '');
        let current = 0;
        const increment = target / 100;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            stat.textContent = Math.floor(current) + suffix;
        }, 20);
    });
}

// Stats Observer
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateStats();
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const statsSection = document.querySelector('.stats');
if (statsSection) {
    statsObserver.observe(statsSection);
}


// Enhanced About Section Animations
function initAboutAnimations() {
    // Animate About Stats
    const aboutStats = document.querySelectorAll('.about-stat .stat-number');
    const aboutStatsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumber = entry.target;
                const finalValue = statNumber.textContent;
                const isNumeric = /^\d+/.test(finalValue);
                
                if (isNumeric) {
                    const targetValue = parseInt(finalValue);
                    let currentValue = 0;
                    const increment = Math.ceil(targetValue / 50);
                    
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= targetValue) {
                            currentValue = targetValue;
                            clearInterval(timer);
                        }
                        statNumber.textContent = currentValue + (finalValue.includes('+') ? '+' : '');
                    }, 30);
                }
                
                aboutStatsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.7 });
    
    aboutStats.forEach(stat => {
        aboutStatsObserver.observe(stat);
    });
    
    // Code Demo Typing Effect
    const codeDemoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const codeLines = entry.target.querySelectorAll('.code-line');
                codeLines.forEach((line, index) => {
                    setTimeout(() => {
                        line.style.opacity = '0.3';
                        setTimeout(() => {
                            line.style.opacity = '1';
                            line.style.transform = 'translateX(5px)';
                            setTimeout(() => {
                                line.style.transform = 'translateX(0)';
                            }, 200);
                        }, 100);
                    }, index * 300);
                });
                codeDemoObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.6 });
    
    const codeDemo = document.querySelector('.code-snippet-demo');
    if (codeDemo) {
        codeDemoObserver.observe(codeDemo);
    }
}


// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const target = document.querySelector(targetId);
        if (target) {
            window.scrollTo({
                top: target.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Enhanced Header Scroll Effect
let lastScrollTop = 0;
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (!header) return;
    
    const scrollTop = window.pageYOffset;
    
    if (scrollTop > 80) {
        header.style.background = 'rgba(255, 255, 255, 0.98)';
        header.style.boxShadow = '0 4px 25px rgba(0, 0, 0, 0.12)';
    } else {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
        header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    }
    
    // Hide header on scroll down, show on scroll up
    if (scrollTop > lastScrollTop && scrollTop > 200) {
        header.style.transform = 'translateY(-100%)';
    } else {
        header.style.transform = 'translateY(0)';
    }
    lastScrollTop = scrollTop;
});

// Pricing Cards Functionality
function initPricingCards() {
    const pricingButtons = document.querySelectorAll('.pricing-button');
    
    pricingButtons.forEach(button => {
        button.addEventListener('click', function() {
            const card = this.closest('.pricing-card');
            const planTitle = card.querySelector('.pricing-title').textContent;
            
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            alert(`Thanks for choosing the ${planTitle}! We'll contact you soon.`);
        });
    });
}

// Form Enhancement
function initFormValidation() {
    const contactForm = document.querySelector('.contact-form');
    
    if (contactForm) {
        // Phone number input restriction - only numbers
        const phoneInput = contactForm.querySelector('#phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                // Remove any non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            phoneInput.addEventListener('keypress', function(e) {
                // Prevent non-numeric characters from being typed
                if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }
        
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            let isValid = true;
            
            // Validate required fields
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#ff4757';
                    input.style.animation = 'shake 0.5s ease-in-out';
                } else {
                    input.style.borderColor = '#5ac8fa';
                    input.style.animation = '';
                }
            });
            
            // Validate email format
            const emailInput = this.querySelector('#email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value && !emailRegex.test(emailInput.value)) {
                isValid = false;
                emailInput.style.borderColor = '#ff4757';
                emailInput.style.animation = 'shake 0.5s ease-in-out';
                showNotification('Please enter a valid email address', 'error');
            }
            
            // Validate phone number
            const phoneInput = this.querySelector('#phone');
            const phoneRegex = /^[0-9\s\+\-\(\)]+$/;
            if (phoneInput.value && !phoneRegex.test(phoneInput.value)) {
                isValid = false;
                phoneInput.style.borderColor = '#ff4757';
                phoneInput.style.animation = 'shake 0.5s ease-in-out';
                showNotification('Please enter a valid phone number', 'error');
            }
            
            if (isValid) {
                const button = this.querySelector('button[type="submit"]');
                if (!button) {
                    console.error('Submit button not found');
                    return;
                }
                const originalText = button.innerHTML;
                
                // Show loading state
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                button.disabled = true;
                button.style.background = '#6c757d';
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Send email via PHP
                fetch('send_email_smtp.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type') || '';

                    if (!contentType.includes('application/json')) {
                        throw new Error(`Contact endpoint returned HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || `Contact request failed with HTTP ${response.status}`);
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        button.innerHTML = '<i class="fas fa-check"></i> Sent!';
                        button.style.background = '#28a745';
                        showNotification(data.message, 'success');
                        
                        // Reset form after success
                        setTimeout(() => {
                            this.reset();
                            inputs.forEach(input => input.style.borderColor = '');
                            button.innerHTML = originalText;
                            button.style.background = '';
                            button.disabled = false;
                        }, 3000);
                    } else {
                        button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        button.style.background = '#dc3545';
                        showNotification(data.message, 'error');
                        
                        // Reset button after error
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.style.background = '';
                            button.disabled = false;
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Contact form submission failed:', error);
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    button.style.background = '#dc3545';
                    showNotification('The contact form is temporarily unavailable. Please email info@coovix.com directly.', 'error');
                    
                    // Reset button after error
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        button.disabled = false;
                    }, 3000);
                });
            } else {
                showNotification('Please fill in all required fields correctly', 'error');
            }
        });
        
        // Real-time validation
        const inputs = contactForm.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.style.borderColor = '#5ac8fa';
                } else {
                    this.style.borderColor = '#5ac8fa';
                }
            });
            
            input.addEventListener('focus', function() {
                this.style.borderColor = '#0088cc';
            });
        });
    }
}

// Parallax Effect for Floating Elements
function parallaxFloatingElements() {
    const floatingElements = document.querySelectorAll('.floating-element');
    const scrolled = window.pageYOffset;
    
    floatingElements.forEach((element, index) => {
        const rate = scrolled * -0.2 * (index + 0.5);
        const rotation = scrolled * 0.01 * (index % 2 === 0 ? 1 : -1);
        element.style.transform = `translateY(${rate}px) rotate(${rotation}deg)`;
    });
}

// Enhanced Code Typing Animation
function startTypingAnimation() {
    const typingElements = document.querySelectorAll('.typing-effect');
    typingElements.forEach(element => {
        element.style.animation = 'none';
        setTimeout(() => {
            element.style.animation = 'typing 3s steps(30) infinite, blink 1s infinite';
        }, 100);
    });
}

// Restaurant Menu Interactive Features
function initRestaurantMenuFeatures() {
    // Interactive menu item hover effects
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.backgroundColor = '#f8f9fa';
            this.style.transition = 'all 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.backgroundColor = 'transparent';
        });
    });

    // Menu step cards enhanced interaction
    const menuStepCards = document.querySelectorAll('.menu-step-card');
    
    menuStepCards.forEach((card, index) => {
        // Add entrance animation delay
        card.style.animationDelay = `${0.5 + (index * 0.2)}s`;
        
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            const stepIcon = this.querySelector('.step-icon');
            if (stepIcon) {
                stepIcon.style.transform = 'scale(1.1) rotate(5deg)';
                stepIcon.style.transition = 'transform 0.3s ease';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const stepIcon = this.querySelector('.step-icon');
            if (stepIcon) {
                stepIcon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });

    // Menu type cards interaction
    const menuTypeCards = document.querySelectorAll('.menu-type-card');
    
    menuTypeCards.forEach((card, index) => {
        card.style.animationDelay = `${1.7 + (index * 0.2)}s`;
        
        card.addEventListener('click', function() {
            const cardTitle = this.querySelector('h4').textContent;
            
            // Add click effect
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
            // Show info alert
            const messages = {
                'QR Code Menu': 'QR Code menus are perfect for contactless dining. Customers simply scan and view your menu instantly!',
                'Web Menu': 'Web-based menus offer full functionality with online ordering, payments, and delivery integration.',
                'Mobile App': 'Dedicated mobile apps provide the best user experience with push notifications and loyalty programs.'
            };
            
            if (messages[cardTitle]) {
                showNotification(messages[cardTitle], 'info');
            }
        });
    });

    // Menu preview mockup interaction
    const previewMockup = document.querySelector('.preview-mockup');
    if (previewMockup) {
        previewMockup.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05) rotateY(5deg)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        previewMockup.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotateY(0deg)';
        });
    }
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Set notification colors based on type
    let borderColor = 'var(--primary-color)';
    let iconClass = 'fas fa-info-circle';
    let bgColor = 'white';
    
    if (type === 'success') {
        borderColor = '#28a745';
        iconClass = 'fas fa-check-circle';
        bgColor = '#f8fff9';
    } else if (type === 'error') {
        borderColor = '#dc3545';
        iconClass = 'fas fa-exclamation-triangle';
        bgColor = '#fff8f8';
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="${iconClass}"></i>
            <span>${message}</span>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${bgColor};
        border-left: 4px solid ${borderColor};
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 1rem 1.5rem;
        max-width: 400px;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;

    // Add to body
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    });

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 5000);
}

// Menu CTA Buttons Enhanced Interaction
function initMenuCTAButtons() {
    const menuCTAButtons = document.querySelectorAll('.menu-cta .btn-primary, .menu-cta .btn-secondary');
    
    menuCTAButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const isContactButton = this.getAttribute('href') === '#contact';
            const isServicesButton = this.getAttribute('href') === '#services';
            
            if (isContactButton) {
                e.preventDefault();
                showNotification('Great! We\'d love to help you create an amazing digital menu for your restaurant. Please fill out the contact form below.', 'info');
                
                setTimeout(() => {
                    const contactSection = document.querySelector('#contact');
                    if (contactSection) {
                        contactSection.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 1000);
            } else if (isServicesButton) {
                e.preventDefault();
                showNotification('Check out our comprehensive services! We offer everything from basic menus to advanced restaurant management systems.', 'info');
                
                setTimeout(() => {
                    const servicesSection = document.querySelector('#services');
                    if (servicesSection) {
                        servicesSection.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 1000);
            }
        });
    });
}

// Progressive Menu Item Loading Animation
function animateMenuItemsSequentially() {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 3000 + (index * 200)); // Start after preview loads
    });
}

// Mobile Menu Toggle
function initMobileMenu() {
    const navContainer = document.querySelector('.nav-container');
    const navMenu = document.querySelector('.nav-menu');
    
    if (window.innerWidth <= 992 && navContainer && navMenu) {
        let hamburger = document.querySelector('.hamburger');
        if (!hamburger) {
            hamburger = document.createElement('button');
            hamburger.className = 'hamburger';
            hamburger.innerHTML = '<i class="fas fa-bars"></i>';
            hamburger.setAttribute('aria-label', 'Toggle navigation menu');
            hamburger.style.cssText = `
                background: none;
                border: none;
                font-size: 1.5rem;
                color: var(--primary-color);
                cursor: pointer;
                display: block;
                padding: 0.5rem;
                z-index: 1001;
                position: relative;
            `;
            navContainer.appendChild(hamburger);
            
            hamburger.addEventListener('click', () => {
                navMenu.classList.toggle('mobile-active');
                const icon = hamburger.querySelector('i');
                icon.className = navMenu.classList.contains('mobile-active') ? 'fas fa-times' : 'fas fa-bars';
                
                // Prevent body scroll when menu is open
                document.body.style.overflow = navMenu.classList.contains('mobile-active') ? 'hidden' : '';
            });

            // Close menu when clicking on links
            navMenu.addEventListener('click', (e) => {
                if (e.target.classList.contains('nav-link')) {
                    navMenu.classList.remove('mobile-active');
                    hamburger.querySelector('i').className = 'fas fa-bars';
                    document.body.style.overflow = '';
                }
            });
        }
    }
}

// Responsive behavior
function handleResize() {
    if (window.innerWidth > 992) {
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        if (hamburger) hamburger.remove();
        if (navMenu) {
            navMenu.classList.remove('mobile-active');
            document.body.style.overflow = '';
        }
    } else {
        initMobileMenu();
    }
}

// Advanced Scroll Effects for Menu Section
function initMenuScrollEffects() {
    const menuSection = document.querySelector('.restaurant-menu');
    if (!menuSection) return;

    const menuObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trigger menu-specific animations
                animateMenuItemsSequentially();
                
                // Add floating animation to menu icons
                const menuIcons = entry.target.querySelectorAll('.step-icon, .menu-type-icon');
                menuIcons.forEach((icon, index) => {
                    setTimeout(() => {
                        icon.style.animation = 'iconFloat 3s ease-in-out infinite';
                        icon.style.animationDelay = `${index * 0.2}s`;
                    }, 1000);
                });
                
                menuObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    menuObserver.observe(menuSection);
}

// Performance optimization
let ticking = false;
function requestTick() {
    if (!ticking) {
        requestAnimationFrame(() => {
            updateScrollIndicator();
            parallaxFloatingElements();
            toggleBackToTop();
            ticking = false;
        });
        ticking = true;
    }
}

// Enhanced Error Handling
function handleErrors() {
    window.addEventListener('error', function(e) {
        // Gracefully handle errors without breaking the user experience
    });

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        e.preventDefault();
    });
}

// Feature Detection and Graceful Degradation
function checkFeatureSupport() {
    // Check for Intersection Observer support
    if (!('IntersectionObserver' in window)) {
        // Fallback: immediately show all elements
        document.querySelectorAll('.reveal, .stagger-item').forEach(el => {
            el.classList.add('active');
        });
    }

    // Check for modern JavaScript features
    if (!window.requestAnimationFrame) {
        // Fallback for older browsers
        window.requestAnimationFrame = function(callback) {
            return setTimeout(callback, 16);
        };
    }
}

// Initialize all animations and features
document.addEventListener('DOMContentLoaded', function() {
    // Check for feature support first
    checkFeatureSupport();
    
    // Handle errors gracefully
    handleErrors();
    
    // Initialize core features
    createParticles();
    initFormValidation();
    initPricingCards();
    startTypingAnimation();
    handleResize();
    updateInteractiveElements();
    initLanguageDropdown();
    
    // Initialize restaurant menu features
    initRestaurantMenuFeatures();
    initMenuCTAButtons();
    initMenuScrollEffects();
    
    // Initialize enhanced About section features
    initAboutAnimations();
    
    // Add CSS for additional mobile styles and animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .mobile-active {
            display: flex !important;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            flex-direction: column;
            padding: 2rem;
            z-index: 999;
            overflow-y: auto;
        }
        
        .mobile-active .nav-link {
            padding: 0.1rem 0;
            border-bottom: 1px solid #5ac8fa;
            font-size: 1.2rem;
            text-align: left;
        }
        
        .mobile-active .nav-link:last-child {
            border-bottom: none;
        }
        
        .pricing-card.animate,
        .tech-card.animate,
        .tip-card.animate,
        .creation-step.animate,
        .menu-step-card.animate,
        .menu-type-card.animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--gray-dark);
            cursor: pointer;
            margin-left: auto;
            padding: 0.2rem;
            border-radius: 50%;
            transition: background 0.2s ease;
        }
        
        .notification-close:hover {
            background: var(--gray-light);
        }
        
        .menu-item {
            cursor: pointer;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .menu-step-card .step-number {
            animation: pulse 2s ease-in-out infinite;
        }
        
        .menu-type-card {
            cursor: pointer;
        }
        
        .menu-type-card:active {
            transform: scale(0.98);
        }
        
        @media (max-width: 992px) {
            .mobile-active {
                top: 60px;
                padding: 1.5rem;
            }
            
            .mobile-active .nav-link {
                padding: 0.1rem 0;
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            .notification {
                right: 10px !important;
                left: 10px !important;
                max-width: none !important;
                top: 80px !important;
            }
            
            .notification-content {
                font-size: 0.9rem;
            }
        }
    `;
    document.head.appendChild(style);
});

// Event Listeners
window.addEventListener('resize', handleResize);
window.addEventListener('scroll', requestTick);

// Advanced Menu Interaction Features
function initAdvancedMenuFeatures() {
    // Menu item click to order simulation
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const itemName = this.querySelector('h5').textContent;
            const itemPrice = this.querySelector('.item-price').textContent;
            
            // Add to cart animation
            this.style.transform = 'scale(1.05)';
            this.style.backgroundColor = 'var(--light-primary)';
            
            setTimeout(() => {
                this.style.transform = 'scale(1)';
                this.style.backgroundColor = '';
                showNotification(`Added "${itemName}" (${itemPrice}) to cart!`, 'success');
            }, 200);
        });
    });

    // Menu step cards progress indication
    const stepCards = document.querySelectorAll('.menu-step-card');
    stepCards.forEach((card, index) => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            stepCards.forEach(c => c.classList.remove('step-active'));
            
            // Add active class to clicked card and all previous cards
            for (let i = 0; i <= index; i++) {
                stepCards[i].classList.add('step-active');
            }
            
            const stepTitle = this.querySelector('h3').textContent;
            showNotification(`Step ${index + 1}: ${stepTitle} - Click for more details!`, 'info');
        });
    });
}

// Call advanced features after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initAdvancedMenuFeatures, 1000);

    // Keep the OnZone case study aligned with the shared project header and polished app artwork.
    if (document.body.classList.contains('onzone-detail-page')) {
        const customerApp = document.querySelector('img[src$="onzone-customer-app.png"]');
        const storeApp = document.querySelector('img[src$="onzone-store-app.png"]');
        if (customerApp) customerApp.src = coovixUrl('Projects/onzone/onzone-customer-app-showcase-v2.png');
        if (storeApp) storeApp.src = coovixUrl('Projects/onzone/onzone-store-app-showcase-v2.png');
        document.querySelectorAll('.fa-mobile-screen').forEach((icon) => {
            icon.classList.replace('fa-mobile-screen', 'fa-mobile-screen-button');
        });

        const nav = document.querySelector('.header .nav-menu');
        const contact = nav?.querySelector('a[href$="#contact"]');
        if (nav && contact && !nav.querySelector('a[href$="#website-creation"]')) {
            const language = document.documentElement.lang;
            const home = coovixUrl(language === 'ar' ? 'ar' : language === 'ku' ? 'kr' : 'index');
            const websiteLabel = language === 'ar' ? 'موقع إلكتروني' : language === 'ku' ? 'وێبسایت' : 'Website';
            const mobileLabel = language === 'ar' ? 'تطبيق الهاتف' : language === 'ku' ? 'ئەپی مۆبایل' : 'Mobile App';
            const website = document.createElement('a');
            website.href = `${home}#website-creation`;
            website.className = 'nav-link';
            website.textContent = websiteLabel;
            const mobile = document.createElement('a');
            mobile.href = `${home}#mobile-app`;
            mobile.className = 'nav-link';
            mobile.textContent = mobileLabel;
            const separatorOne = document.createElement('span');
            separatorOne.className = 'nav-separator';
            separatorOne.textContent = '|';
            const separatorTwo = separatorOne.cloneNode(true);
            contact.before(website, separatorOne, mobile, separatorTwo);
        }

        const footer = document.querySelector('footer.footer');
        if (footer) {
            const language = document.documentElement.lang;
            const isArabic = language === 'ar';
            const isKurdish = language === 'ku';
            const home = coovixUrl(isArabic ? 'ar' : isKurdish ? 'kr' : 'index');
            const projects = coovixUrl(isArabic ? 'Projects/ar-projects-index' : isKurdish ? 'Projects/kr-projects-index' : 'Projects/projects-index');
            const privacy = coovixUrl(isArabic ? 'privacy/ar-privacy-policy' : isKurdish ? 'privacy/ku-privacy-policy' : 'privacy/privacy-policy');
            const terms = coovixUrl(isArabic ? 'terms/ar-terms' : isKurdish ? 'terms/ku-terms' : 'terms/terms-of-service');
            const labels = isArabic ? {
                services: 'الخدمات', web: 'تطوير المواقع', mobile: 'تطبيقات الهاتف', backend: 'أنظمة Backend', menu: 'القائمة الرقمية',
                company: 'الشركة', about: 'من نحن', projects: 'مشاريعنا', privacy: 'سياسة الخصوصية', terms: 'شروط الخدمة',
                follow: 'تابعنا', copyright: 'جميع الحقوق محفوظة.'
            } : isKurdish ? {
                services: 'خزمەتگوزاریەکان', web: 'گەشەپێدانی وێب', mobile: 'ئەپی مۆبایل', backend: 'سیستەمی Backend', menu: 'مێنووی دیجیتاڵ',
                company: 'کۆمپانیا', about: 'دەربارەمان', projects: 'پڕۆژەکانمان', privacy: 'سیاسەتی تایبەتی', terms: 'مەرجەکانی خزمەتگوزاری',
                follow: 'شوێنمان بکەن', copyright: 'هەموو مافەکان پارێزراون.'
            } : {
                services: 'Services', web: 'Web Development', mobile: 'Mobile Applications', backend: 'Backend Systems', menu: 'Digital Menu',
                company: 'Company', about: 'About Us', projects: 'Our Projects', privacy: 'Privacy Policy', terms: 'Terms of Service',
                follow: 'Follow Us', copyright: 'All rights reserved.'
            };
            footer.innerHTML = `<div class="footer-container"><div class="footer-section"><h3>${labels.services}</h3><ul><li><a href="${home}#website-creation">${labels.web}</a></li><li><a href="${home}#mobile-app">${labels.mobile}</a></li><li><a href="${home}#services">${labels.backend}</a></li><li><a href="${projects}">${labels.menu}</a></li></ul></div><div class="footer-section"><h3>${labels.company}</h3><ul><li><a href="${home}#about">${labels.about}</a></li><li><a href="${projects}">${labels.projects}</a></li><li><a href="${privacy}">${labels.privacy}</a></li><li><a href="${terms}">${labels.terms}</a></li></ul></div><div class="footer-section"><h3>${labels.follow}</h3><div class="contact-info"><p><i class="fas fa-phone" aria-hidden="true"></i> <a href="tel:+9647507650999">+964 750 765 0999</a></p><p><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:info@coovix.com">info@coovix.com</a></p></div><div class="social-links"><a href="https://www.linkedin.com/company/coovix/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><a href="https://www.facebook.com/share/188UQedKkT/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook"></i></a><a href="https://www.instagram.com/coovix.tech" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a></div></div></div><div class="footer-bottom"><p>Copyright © 2026 Coovix. ${labels.copyright}</p></div>`;
        }
    }

    // Use local portfolio assets so Powerful Energy visuals never depend on a third-party server.
    document.querySelectorAll('.powerful-visual > img, .powerful-hero-showcase > img, .powerful-detail-hero .bv2-product-image > img').forEach((image) => {
        image.src = coovixUrl('Projects/powerful/powerful-solar-platform.webp');
    });
    document.querySelectorAll('.powerful-brand img, .powerful-hero-logo img').forEach((image) => {
        image.src = coovixUrl('Projects/powerful/powerful-logo.webp');
    });

    // Present OnZone as a live fashion marketplace instead of a coming-soon concept.
    document.querySelectorAll('.project-card').forEach((card) => {
        if (card.querySelector('h2')?.textContent.trim() !== 'OnZone') return;
        const language = document.documentElement.lang;
        const isArabic = language === 'ar';
        const isKurdish = language === 'ku';
        const liveUrl = 'https://onzone.co/shop/men';
        const detailUrl = coovixUrl(isArabic
            ? 'Projects/onzone/ar-onzone-detail'
            : isKurdish
                ? 'Projects/onzone/kr-onzone-detail'
                : 'Projects/onzone/onzone-detail');
        const oldVisual = card.querySelector('.onzone-visual');
        if (oldVisual) {
            const visual = document.createElement('a');
            visual.href = detailUrl;
            visual.className = oldVisual.className;
            visual.setAttribute('aria-label', isArabic ? 'فتح متجر OnZone' : isKurdish ? 'کردنەوەی فرۆشگای OnZone' : 'Open the OnZone marketplace');
            visual.innerHTML = `<span class="project-index">03</span><span class="project-view"><i class="fas ${language === 'en' ? 'fa-arrow-right' : 'fa-arrow-left'}"></i></span>`;
            oldVisual.replaceWith(visual);
        }
        const description = card.querySelector('.project-card-copy > p');
        if (description) description.textContent = isArabic
            ? 'سوق أزياء رقمي حديث يربط المتسوقين بالمجموعات والفئات والمتاجر في تجربة تسوق سهلة ومتجاوبة.'
            : isKurdish
                ? 'بازاڕێکی مۆدێرنی دیجیتاڵ بۆ بەستنەوەی کڕیاران بە کۆمەڵە و پۆل و فرۆشگاکان لە ئەزموونێکی ئاسان و وەڵامدەرەوەدا.'
                : 'A modern fashion marketplace connecting shoppers with collections, categories, and stores through a seamless responsive experience.';
        const platforms = card.querySelector('.platform-actions');
        if (platforms) {
            platforms.classList.add('onzone-platforms');
            platforms.innerHTML = `<span class="platform-status"><i class="fas fa-globe"></i><span><small>${isArabic ? 'قيد الاختبار' : isKurdish ? 'لە تاقیکردنەوەدایە' : 'In testing'}</small><strong>${isArabic ? 'الموقع' : isKurdish ? 'وێبسایت' : 'Website'}</strong></span></span><span class="platform-status"><i class="fas fa-gauge-high"></i><span><small>${isArabic ? 'يُدار بواسطة' : isKurdish ? 'بەڕێوەدەبرێت بە' : 'Managed with'}</small><strong>${isArabic ? 'لوحة المتجر' : isKurdish ? 'داشبۆردی فرۆشگا' : 'Store Dashboard'}</strong></span></span><span class="platform-status"><i class="fab fa-apple"></i><span><small>${isArabic ? 'متاح على iOS' : isKurdish ? 'لە iOS بەردەستە' : 'Available on iOS'}</small><strong>OnZone App</strong></span></span><span class="platform-status"><i class="fab fa-apple"></i><span><small>${isArabic ? 'متاح على iOS' : isKurdish ? 'لە iOS بەردەستە' : 'Available on iOS'}</small><strong>${isArabic ? 'تطبيق المتجر' : isKurdish ? 'ئەپی فرۆشگا' : 'Store App'}</strong></span></span><span class="platform-status"><i class="fab fa-google-play"></i><span><small>${isArabic ? 'متاح على Android' : isKurdish ? 'لە Android بەردەستە' : 'Available on Android'}</small><strong>OnZone App</strong></span></span><span class="platform-status"><i class="fab fa-google-play"></i><span><small>${isArabic ? 'متاح على Android' : isKurdish ? 'لە Android بەردەستە' : 'Available on Android'}</small><strong>${isArabic ? 'تطبيق المتجر' : isKurdish ? 'ئەپی فرۆشگا' : 'Store App'}</strong></span></span>`;
        }
        let projectLink = card.querySelector('.project-link');
        if (!projectLink) {
            projectLink = document.createElement('a');
            projectLink.className = 'project-link';
            card.querySelector('.project-card-copy')?.appendChild(projectLink);
        }
        projectLink.href = detailUrl;
        projectLink.removeAttribute('target');
        projectLink.removeAttribute('rel');
        projectLink.innerHTML = `${isArabic ? 'عرض المشروع' : isKurdish ? 'بینینی پڕۆژە' : 'View project'} <i class="fas ${language === 'en' ? 'fa-arrow-right' : 'fa-arrow-left'}"></i>`;
    });

    // Keep Powerful project-card navigation in the visitor's selected language.
    document.querySelectorAll('.project-card').forEach((card) => {
        if (card.querySelector('h2')?.textContent.trim() !== 'Powerful Energy Company') return;
        const language = document.documentElement.lang;
        const detailUrl = coovixUrl(language === 'ar'
            ? 'Projects/powerful/ar-powerful-detail'
            : language === 'ku'
                ? 'Projects/powerful/kr-powerful-detail'
                : 'Projects/powerful/powerful-detail');
        card.querySelectorAll('a[href*="Projects/powerful/"][href$="powerful-detail"]').forEach((link) => {
            link.href = detailUrl;
        });
    });

    // Keep the Fkrah Android download connected in every language version.
    const fkrahGooglePlayUrl = 'https://play.google.com/store/apps/details?id=com.coovix.fkrah&pcampaignid=web_share';
    document.querySelectorAll('.project-card').forEach((card) => {
        if (card.querySelector('h2')?.textContent.trim() !== 'Fkrah') return;
        if (document.documentElement.lang === 'ar') {
            card.querySelectorAll('a[href$="Projects/fkrah/fkrah-detail"]').forEach((link) => {
                link.href = coovixUrl('Projects/fkrah/ar-fkrah-detail');
            });
        }
        const androidIcon = card.querySelector('.platform-status .fa-android');
        const status = androidIcon?.closest('.platform-status');
        if (!status || status.tagName === 'A') return;
        const link = document.createElement('a');
        link.href = fkrahGooglePlayUrl;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.setAttribute('aria-label', 'Download Fkrah on Google Play');
        while (status.firstChild) link.appendChild(status.firstChild);
        const icon = link.querySelector('.fa-android');
        if (icon) icon.className = 'fab fa-google-play';
        const label = link.querySelector('small');
        const title = link.querySelector('strong');
        if (label) label.textContent = document.documentElement.lang === 'ar' ? 'حمّل من' : document.documentElement.lang === 'ku' ? 'داگرتن لە' : 'Get it on';
        if (title) title.textContent = 'Google Play';
        status.replaceWith(link);
    });

    const androidPlatform = document.querySelector('.fkrah-detail-page .fk-platform-strip .fa-android')?.parentElement;
    if (androidPlatform && androidPlatform.tagName === 'A') {
        const status = document.createElement('span');
        while (androidPlatform.firstChild) status.appendChild(androidPlatform.firstChild);
        androidPlatform.replaceWith(status);
    }

    const fkrahActions = document.querySelector('.fkrah-detail-page .fk-actions');
    if (fkrahActions && !fkrahActions.querySelector('a[href*="play.google.com"]')) {
        const isKurdish = document.documentElement.lang === 'ku';
        const googlePlay = document.createElement('a');
        googlePlay.href = fkrahGooglePlayUrl;
        googlePlay.target = '_blank';
        googlePlay.rel = 'noopener noreferrer';
        googlePlay.className = 'fk-google';
        googlePlay.innerHTML = `<i class="fab fa-google-play"></i><span><small>${isKurdish ? 'داگرتن لە' : 'Get it on'}</small><strong>Google Play</strong></span>`;
        fkrahActions.appendChild(googlePlay);
    }

    if (document.body.classList.contains('fkrah-detail-page')) {
        document.querySelectorAll('.language-options a').forEach((link) => {
            const flag = link.querySelector('img')?.getAttribute('alt');
            if (flag === 'العربية') link.href = coovixUrl('Projects/fkrah/ar-fkrah-detail');
            if (flag === 'كوردى') link.href = coovixUrl('Projects/fkrah/kr-fkrah-detail');
            if (flag === 'English') link.href = coovixUrl('Projects/fkrah/fkrah-detail');
        });
    }
});

// Export functions for external use (if needed)
window.CODIVA = {
    showNotification,
    updateInteractiveElements,
    initRestaurantMenuFeatures,
    handleResize
};
