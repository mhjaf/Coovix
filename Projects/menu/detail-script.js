// CODIVA Projects - Detail Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initDemoButtons();
    initScrollAnimations();
    initHeaderEffects();
    initStatCounters();
    initTechTags();
});

// Demo Buttons Interaction
function initDemoButtons() {
    const demoButtons = document.querySelectorAll('.demo-btn');
    
    demoButtons.forEach(btn => {
        // Hover effect
        btn.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.demo-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        btn.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.demo-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });

        // Click event
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const demoNumber = this.getAttribute('data-demo');
            const demoTitle = this.querySelector('h3').textContent;
            const demoDescription = this.querySelector('p').textContent;
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                this.style.transform = '';
                showDemoModal(demoNumber, demoTitle, demoDescription);
            }, 150);
        });
    });
}

// // Show Demo Modal
// function showDemoModal(demoNumber, title, description) {
//     const modal = document.createElement('div');
//     modal.className = 'demo-modal';
//     modal.innerHTML = `
//         <div class="demo-modal-overlay"></div>
//         <div class="demo-modal-content">
//             <div class="demo-modal-header">
//                 <h2>${title} - ${description}</h2>
//                 <button class="demo-modal-close">&times;</button>
//             </div>
//             <div class="demo-modal-body">
//                 <div class="demo-preview">
//                     <i class="fas fa-desktop"></i>
//                     <p>Demo ${demoNumber} Preview</p>
//                     <span>This is where your live demo would be displayed</span>
//                 </div>
//                 <div class="demo-info">
//                     <h3>About This Demo</h3>
//                     <p>This demo showcases our ${description.toLowerCase()} solution with all the key features and functionality. You can interact with the live preview to see how it works.</p>
//                     <div class="demo-features">
//                         <div class="demo-feature">
//                             <i class="fas fa-check-circle"></i>
//                             <span>Fully Functional</span>
//                         </div>
//                         <div class="demo-feature">
//                             <i class="fas fa-check-circle"></i>
//                             <span>Responsive Design</span>
//                         </div>
//                         <div class="demo-feature">
//                             <i class="fas fa-check-circle"></i>
//                             <span>Interactive Elements</span>
//                         </div>
//                     </div>
//                 </div>
//             </div>
//             <div class="demo-modal-footer">
//                 <button class="demo-btn-primary" onclick="window.location.href='../index#contact'">
//                     <i class="fas fa-envelope"></i>
//                     Request Similar Project
//                 </button>
//                 <button class="demo-btn-secondary">Close</button>
//             </div>
//         </div>
//     `;
    
//     // Add modal styles
//     addModalStyles();
    
//     document.body.appendChild(modal);
    
//     // Show modal with animation
//     setTimeout(() => {
//         modal.classList.add('show');
//     }, 10);
    
//     // Close button events
//     modal.querySelector('.demo-modal-close').addEventListener('click', () => closeDemoModal(modal));
//     modal.querySelector('.demo-btn-secondary').addEventListener('click', () => closeDemoModal(modal));
//     modal.querySelector('.demo-modal-overlay').addEventListener('click', () => closeDemoModal(modal));
// }

// Close Demo Modal
function closeDemoModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
        modal.remove();
    }, 300);
}

// Add Modal Styles
function addModalStyles() {
    if (document.querySelector('#demo-modal-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'demo-modal-styles';
    style.textContent = `
        .demo-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .demo-modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .demo-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
        }
        
        .demo-modal-content {
            position: relative;
            background: white;
            border-radius: 25px;
            max-width: 900px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            margin: 3% auto;
            transform: translateY(30px) scale(0.95);
            transition: all 0.3s ease;
        }
        
        .demo-modal.show .demo-modal-content {
            transform: translateY(0) scale(1);
        }
        
        .demo-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 2.5rem;
            border-bottom: 2px solid #f0f0f0;
            background: linear-gradient(135deg, #E6F5FC, #fff);
        }
        
        .demo-modal-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .demo-modal-close {
            background: none;
            border: none;
            font-size: 2.5rem;
            cursor: pointer;
            color: var(--gray-dark);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .demo-modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: rotate(90deg);
        }
        
        .demo-modal-body {
            padding: 2.5rem;
        }
        
        .demo-preview {
            background: linear-gradient(135deg, #E6F5FC, #0099d8);
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }
        
        .demo-preview i {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .demo-preview p {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .demo-preview span {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .demo-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .demo-info p {
            color: var(--gray-dark);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .demo-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .demo-feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem;
            background: var(--gray-light);
            border-radius: 10px;
        }
        
        .demo-feature i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .demo-feature span {
            font-size: 0.9rem;
            color: var(--gray-dark);
            font-weight: 500;
        }
        
        .demo-modal-footer {
            padding: 2rem 2.5rem;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .demo-btn-primary,
        .demo-btn-secondary {
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .demo-btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .demo-btn-primary:hover {
            background: var(--dark-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 153, 216, 0.3);
        }
        
        .demo-btn-secondary {
            background: var(--gray-light);
            color: var(--gray-dark);
        }
        
        .demo-btn-secondary:hover {
            background: var(--gray-medium);
        }
        
        @media (max-width: 768px) {
            .demo-modal-content {
                width: 95%;
                margin: 2% auto;
            }
            
            .demo-modal-header,
            .demo-modal-body,
            .demo-modal-footer {
                padding: 1.5rem;
            }
            
            .demo-modal-header h2 {
                font-size: 1.3rem;
            }
            
            .demo-modal-footer {
                flex-direction: column;
            }
            
            .demo-btn-primary,
            .demo-btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    `;
    document.head.appendChild(style);
}

// Scroll Animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements that need animation
    document.querySelectorAll('.demo-btn, .stat-item, .features-list li').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
}

// Header Effects
function initHeaderEffects() {
    let lastScrollTop = 0;
    const header = document.querySelector('.header');
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset;
        
        if (scrollTop > 80) {
            header.style.background = 'rgba(255, 255, 255, 0.98)';
            header.style.boxShadow = '0 4px 25px rgba(0, 0, 0, 0.12)';
        } else {
            header.style.background = 'rgba(255, 255, 255, 0.95)';
            header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.08)';
        }
        
        if (window.innerWidth <= 768) {
            if (scrollTop > lastScrollTop && scrollTop > 200) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
        }
        
        lastScrollTop = scrollTop;
    });
}

// Animate stat counters
function initStatCounters() {
    const statItems = document.querySelectorAll('.stat-item span');
    
    statItems.forEach(stat => {
        const text = stat.textContent;
        const numbers = text.match(/\d+/g);
        
        if (numbers && numbers.length > 0) {
            const targetNumber = parseInt(numbers[0]);
            animateCounter(stat, targetNumber, text);
        }
    });
}

function animateCounter(element, target, originalText) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        element.textContent = originalText;
                        clearInterval(timer);
                    } else {
                        element.textContent = originalText.replace(/\d+/, Math.floor(current));
                    }
                }, 30);
                observer.unobserve(entry.target);
            }
        });
    });
    
    observer.observe(element);
}

// Tech Tags Animation
function initTechTags() {
    const techTags = document.querySelectorAll('.tech-tag');
    
    techTags.forEach((tag, index) => {
        setTimeout(() => {
            tag.style.opacity = '0';
            tag.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                tag.style.transition = 'all 0.4s ease';
                tag.style.opacity = '1';
                tag.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
}

// Smooth scroll for contact button
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId !== '#') {
            e.preventDefault();
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});