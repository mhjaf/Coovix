// Ensure DOM is fully loaded before running JS
document.addEventListener('DOMContentLoaded', () => {

    // Detect page language
    const pageLanguage = document.documentElement.lang || 'en';
    
    // Translation strings
    const translations = {
        en: {
            selectCategory: 'Select Category',
            selectCarType: 'Select Car Type',
            selectWashType: 'Select Wash Type',
            selectService: 'Select Service'
        },
        ar: {
            selectCategory: 'اختر الفئة',
            selectCarType: 'اختر نوع السيارة',
            selectWashType: 'اختر نوع الغسيل',
            selectService: 'اختر الخدمة'
        },
        ku: {
            selectCategory: 'جۆر هەڵبژێرە',
            selectCarType: 'جۆری ئۆتۆمبێل هەڵبژێرە',
            selectWashType: 'جۆری شۆردن هەڵبژێرە',
            selectService: 'خزمەتگوزاری هەڵبژێرە'
        }
    };
    
    const t = translations[pageLanguage] || translations.en;

    // --- 1. Mobile Menu Toggle ---
    function toggleMobileMenu() {
        const navLinks = document.querySelector('.nav-links');
        const toggleBtn = document.querySelector('.mobile-toggle');
        const toggleIcon = toggleBtn ? toggleBtn.querySelector('i') : null;

        navLinks.classList.toggle('mobile-active');

        // Toggle icon between hamburger and close
        if (toggleIcon) {
            if (navLinks.classList.contains('mobile-active')) {
                toggleIcon.classList.remove('ph-list');
                toggleIcon.classList.add('ph-x');
            } else {
                toggleIcon.classList.remove('ph-x');
                toggleIcon.classList.add('ph-list');
            }
        }
    }

    // Close mobile menu when clicking any link or button inside it
    document.addEventListener('click', (e) => {
        const navLinks = document.querySelector('.nav-links');
        const toggleBtn = document.querySelector('.mobile-toggle');
        const toggleIcon = toggleBtn ? toggleBtn.querySelector('i') : null;

        if (navLinks && navLinks.classList.contains('mobile-active')) {
            // Check if clicked element is a link or Book Now button inside nav-links
            if (e.target.closest('.nav-links a:not(.lang-option)') ||
                (e.target.closest('.nav-links .btn-primary') && !e.target.closest('.language-selector'))) {
                navLinks.classList.remove('mobile-active');

                // Reset icon to hamburger
                if (toggleIcon) {
                    toggleIcon.classList.remove('ph-x');
                    toggleIcon.classList.add('ph-list');
                }
            }
        }
    });

    // --- 2. Modal Functions ---
    const modal = document.getElementById('bookingModal');
    const serviceSelect = document.getElementById('serviceSelect');

    // Set date input min to tomorrow and default to today
    const dateInput = document.getElementById('bookingDate');
    const timeSelect = document.getElementById('bookingTime');
    const bookingForm = document.getElementById('bookingForm');

    // Helper to format date as YYYY-MM-DD in local timezone (avoids toISOString UTC bug)
    function formatLocalDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    if (dateInput) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Function to find next working day
        async function findNextWorkingDay(startDate) {
            let checkDate = new Date(startDate);
            const maxDays = 14; // Check up to 14 days ahead

            for (let i = 0; i < maxDays; i++) {
                const dateStr = formatLocalDate(checkDate);
                
                try {
                    const response = await fetch(`api/check_working_day.php?date=${dateStr}`);
                    const data = await response.json();
                    
                    if (data.success && data.is_working) {
                        return dateStr;
                    }
                } catch (error) {
                    console.log('Error checking working day:', error);
                    return dateStr; // Return current date on error
                }
                
                // Move to next day
                checkDate.setDate(checkDate.getDate() + 1);
            }
            
            // If no working day found in 14 days, return tomorrow
            return formatLocalDate(tomorrow);
        }

        // Initialize date with next working day (start from today for same-day booking)
        async function initializeDate() {
            const nextWorkingDay = await findNextWorkingDay(today);
            dateInput.value = nextWorkingDay;
            dateInput.min = formatLocalDate(today);
            
            // After setting date, check if it's a working day and filter times
            await checkWorkingDay();
            filterTimes();
        }
        
        // Call initialization
        initializeDate();
        
        // Function to filter past times
        function filterTimes() {
            if (!timeSelect) return;
            
            const selectedDate = dateInput.value;
            const todayStr = formatLocalDate(today);
            const currentHour = today.getHours();
            const currentMinute = today.getMinutes();
            const currentTime = currentHour * 60 + currentMinute;
            
            // Reset selection if currently selected time is in the past
            const selectedValue = timeSelect.value;
            let needsReset = false;
            
            // Show/hide time options based on current time if today is selected
            Array.from(timeSelect.options).forEach(option => {
                if (option.value === '') return; // Skip placeholder
                
                const [hours, minutes] = option.value.split(':').map(Number);
                const optionTime = hours * 60 + minutes;
                
                if (selectedDate === todayStr) {
                    // If today is selected, hide past times
                    if (optionTime <= currentTime) {
                        option.disabled = true;
                        option.style.display = 'none';
                        if (option.value === selectedValue) {
                            needsReset = true;
                        }
                    } else {
                        option.disabled = false;
                        option.style.display = '';
                    }
                } else {
                    // For future dates, show all times
                    option.disabled = false;
                    option.style.display = '';
                }
            });
            
            // Reset to placeholder if selected time is now invalid
            if (needsReset) {
                timeSelect.value = '';
            }
        }
        
        // Filter times on page load
        filterTimes();
        
        // Re-filter times when date changes
        dateInput.addEventListener('change', filterTimes);
        
        // Check if selected date is a working day
        async function checkWorkingDay() {
            const selectedDate = dateInput.value;
            const dayOffWarning = document.getElementById('dayOffWarning');
            const submitButton = bookingForm?.querySelector('button[type="submit"]');
            
            if (!selectedDate || !dayOffWarning) return;
            
            try {
                const response = await fetch(`api/check_working_day.php?date=${selectedDate}`);
                const data = await response.json();
                
                if (data.success && !data.is_working) {
                    // Day is off - show warning and disable submit
                    dayOffWarning.style.display = 'flex';
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.style.opacity = '0.5';
                        submitButton.style.cursor = 'not-allowed';
                    }
                } else {
                    // Day is working - hide warning and enable submit
                    dayOffWarning.style.display = 'none';
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.style.opacity = '1';
                        submitButton.style.cursor = 'pointer';
                    }
                }
            } catch (error) {
                console.log('Could not check working day:', error);
                // On error, assume it's a working day
                dayOffWarning.style.display = 'none';
            }
        }
        
        // Check working day on page load
        checkWorkingDay();
        
        // Re-check when date changes
        dateInput.addEventListener('change', checkWorkingDay);
    }

    function openModal(serviceType) {
        modal.classList.add('open');

        // Pre-fill select if button specified a service
        if (serviceType && serviceSelect) {
            const options = serviceSelect.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].text.toLowerCase().includes(serviceType.toLowerCase())) {
                    serviceSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }

    function closeModal() {
        modal.classList.remove('open');
        const messageDiv = document.getElementById('bookingMessage');
        if (messageDiv) {
            messageDiv.innerHTML = '';
            messageDiv.className = 'booking-message';
        }
    }

    // Close modal if clicking outside the box
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Scroll select elements to top when focused on mobile (for dropdown to open downward)
    const modalBox = modal ? modal.querySelector('.modal-box') : null;
    if (modalBox) {
        // Function to scroll select to very top of modal
        function scrollSelectToTop(element) {
            if (window.innerWidth <= 768) {
                // Get element's position relative to the form
                const form = modalBox.querySelector('form');
                if (!form) return;

                // Calculate the element's position from the top of the form
                const formRect = form.getBoundingClientRect();
                const elementRect = element.getBoundingClientRect();
                const elementOffsetInForm = elementRect.top - formRect.top + form.scrollTop;

                // Get the modal header height (h2 + p)
                const modalH2 = modalBox.querySelector('h2');
                const modalP = modalBox.querySelector(':scope > p');
                let headerHeight = 0;
                if (modalH2) headerHeight += modalH2.offsetHeight + 36; // h2 padding-top
                if (modalP) headerHeight += modalP.offsetHeight + 24;

                // Calculate scroll position to put element near top of visible area
                const scrollTarget = element.offsetTop + headerHeight - 20;

                // Scroll modal instantly
                modalBox.scrollTop = Math.max(0, scrollTarget);
            }
        }

        // Apply to all select elements and date/time inputs
        const formElements = modalBox.querySelectorAll('select, input[type="date"], input[type="time"]');
        formElements.forEach(element => {
            // Use click event for more reliable timing
            element.addEventListener('click', function(e) {
                scrollSelectToTop(this);
            });

            element.addEventListener('touchend', function(e) {
                scrollSelectToTop(this);
            }, { passive: true });
        });
    }

    // --- 3. Load Services, Categories and Car Types from API ---
    let allServices = [];
    const categorySelect = document.getElementById('categorySelect');
    const carTypeSelect = document.getElementById('carTypeSelect');
    const washTypeSelect = document.getElementById('washTypeSelect');

    async function loadServices() {
        try {
            const response = await fetch('api/book.php');
            const data = await response.json();

            if (data.success && data.services) {
                allServices = data.services;
            }
        } catch (error) {
            console.log('Could not load services from API, using defaults');
        }
    }

    async function loadWashTypes() {
        try {
            const response = await fetch('api/book.php?action=wash_types');
            const data = await response.json();

            if (data.success && data.wash_types && washTypeSelect) {
                // Clear existing options except the first one
                washTypeSelect.innerHTML = `<option value="" disabled selected>${t.selectWashType}</option>`;

                // Add wash types from database with translated names
                data.wash_types.forEach(washType => {
                    const option = document.createElement('option');
                    option.value = washType.name; // Keep English name as value for database
                    option.dataset.id = washType.id; // Store ID for price matrix lookup
                    option.textContent = getWashTypeName(washType); // Display translated name
                    washTypeSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.log('Could not load wash types from API');
        }
    }

    // Helper function to get category name based on current language
    function getCategoryName(category) {
        if (pageLanguage === 'ku' && category.name_ku) {
            return category.name_ku;
        } else if (pageLanguage === 'ar' && category.name_ar) {
            return category.name_ar;
        }
        return category.name;
    }

    async function loadCategories() {
        try {
            const response = await fetch('api/book.php?action=categories');
            const data = await response.json();

            if (data.success && data.categories && categorySelect) {
                // Clear existing options except the first one
                categorySelect.innerHTML = `<option value="" disabled selected>${t.selectCategory}</option>`;

                // Add categories from database with translated names
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.name; // Keep English name as value for database
                    option.textContent = getCategoryName(category); // Display translated name
                    categorySelect.appendChild(option);
                });
            }
        } catch (error) {
            console.log('Could not load categories from API');
        }
    }

    // Helper function to get car type name based on current language
    function getCarTypeName(carType) {
        if (pageLanguage === 'ku' && carType.name_ku) {
            return carType.name_ku;
        } else if (pageLanguage === 'ar' && carType.name_ar) {
            return carType.name_ar;
        }
        return carType.name;
    }

    // Helper function to get wash type name based on current language
    function getWashTypeName(washType) {
        if (pageLanguage === 'ku' && washType.name_ku) {
            return washType.name_ku;
        } else if (pageLanguage === 'ar' && washType.name_ar) {
            return washType.name_ar;
        }
        return washType.name;
    }

    async function loadCarTypes() {
        try {
            const response = await fetch('api/book.php?action=car_types');
            const data = await response.json();

            if (data.success && data.car_types && carTypeSelect) {
                // Clear existing options except the first one
                carTypeSelect.innerHTML = `<option value="" disabled selected>${t.selectCarType}</option>`;

                // Add car types from database with translated names
                data.car_types.forEach(carType => {
                    const option = document.createElement('option');
                    option.value = carType.name; // Keep English name as value for database
                    option.dataset.id = carType.id; // Store ID for price matrix lookup
                    option.textContent = getCarTypeName(carType); // Display translated name
                    carTypeSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.log('Could not load car types from API');
        }
    }

    // Load services with prices for a specific car type and wash type
    async function loadServicesForCarType(carTypeId, washTypeId = null) {
        try {
            let url = `api/book.php?action=service_prices&car_type_id=${carTypeId}`;
            if (washTypeId) {
                url += `&wash_type_id=${washTypeId}`;
            }
            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.services) {
                allServices = data.services;
            }
        } catch (error) {
            console.log('Could not load service prices for car type');
        }
    }

    // Helper function to get service name based on current language
    function getServiceName(service) {
        if (pageLanguage === 'ku' && service.name_ku) {
            return service.name_ku;
        } else if (pageLanguage === 'ar' && service.name_ar) {
            return service.name_ar;
        }
        return service.name;
    }

    // Filter and display services by category, car type, and wash type
    function filterServices() {
        if (!serviceSelect) return;

        const category = categorySelect ? categorySelect.value : '';
        const carType = carTypeSelect ? carTypeSelect.value : '';
        const washType = washTypeSelect ? washTypeSelect.value : '';

        // Clear existing options
        serviceSelect.innerHTML = `<option value="" disabled selected>${t.selectService}</option>`;

        // Hide price display
        const priceDisplay = document.getElementById('priceDisplay');
        if (priceDisplay) priceDisplay.style.display = 'none';

        // Check if all required fields are filled
        if (!category || !carType) {
            serviceSelect.disabled = true;
            return;
        }

        // For Car Wash category, wash type is required
        if (category === 'Car Wash' && !washType) {
            serviceSelect.disabled = true;
            return;
        }

        // Filter services by selected category, car type, and wash type
        const filteredServices = allServices.filter(service => {
            const matchesCategory = service.category === category;
            const matchesCarType = !service.car_type || service.car_type === 'All Types' || service.car_type === carType;

            // For Car Wash category, also filter by wash type
            if (category === 'Car Wash' && washType) {
                const matchesWashType = service.wash_type === washType;
                return matchesCategory && matchesCarType && matchesWashType;
            }

            return matchesCategory && matchesCarType;
        });

        if (filteredServices.length > 0) {
            serviceSelect.disabled = false;
            filteredServices.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                // Use translated name based on page language
                option.textContent = getServiceName(service);
                option.dataset.price = service.price;
                serviceSelect.appendChild(option);
            });
        } else {
            serviceSelect.disabled = true;
            const option = document.createElement('option');
            option.value = '';
            // Translated "No services available" message
            const noServicesMsg = {
                en: 'No services available for this combination',
                ku: 'هیچ خزمەتگوزاریەک بەردەست نییە بۆ ئەم هەڵبژاردنە',
                ar: 'لا توجد خدمات متاحة لهذا الاختيار'
            };
            option.textContent = noServicesMsg[pageLanguage] || noServicesMsg.en;
            option.disabled = true;
            serviceSelect.appendChild(option);
        }
    }

    // Display price when service is selected
    if (serviceSelect) {
        serviceSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.dataset.price;
            const priceDisplay = document.getElementById('priceDisplay');
            const priceAmount = document.getElementById('priceAmount');

            if (price && priceDisplay && priceAmount) {
                priceAmount.textContent = parseFloat(price).toFixed(0);
                priceDisplay.style.display = 'block';
            }
        });
    }

    // Load services, categories, car types and wash types on page load
    loadServices();
    loadCategories();
    loadCarTypes();
    loadWashTypes();

    // Add category and car type change listeners
    if (categorySelect) {
        // Disable service select initially
        if (serviceSelect) {
            serviceSelect.disabled = true;
        }

        categorySelect.addEventListener('change', () => {
            // Hide wash type when category changes
            if (washTypeSelect) {
                washTypeSelect.style.display = 'none';
                washTypeSelect.value = '';
            }
            filterServices();
        });
    }

    if (carTypeSelect) {
        carTypeSelect.addEventListener('change', async () => {
            const category = categorySelect ? categorySelect.value : '';
            const selectedOption = carTypeSelect.options[carTypeSelect.selectedIndex];
            const carTypeId = selectedOption.dataset.id;

            // Show wash type dropdown only if Car Wash category is selected
            if (washTypeSelect && category === 'Car Wash') {
                washTypeSelect.style.display = 'block';
                washTypeSelect.required = true;
                // Wait for wash type selection before loading services
            } else if (washTypeSelect) {
                washTypeSelect.style.display = 'none';
                washTypeSelect.required = false;
                // For non-car-wash categories, load services immediately
                if (carTypeId) {
                    await loadServicesForCarType(carTypeId);
                }
                filterServices();
            }
        });
    }

    if (washTypeSelect) {
        washTypeSelect.addEventListener('change', async () => {
            const carTypeSelect = document.getElementById('carTypeSelect');
            const selectedCarTypeOption = carTypeSelect ? carTypeSelect.options[carTypeSelect.selectedIndex] : null;
            const carTypeId = selectedCarTypeOption ? selectedCarTypeOption.dataset.id : null;
            
            const selectedWashTypeOption = washTypeSelect.options[washTypeSelect.selectedIndex];
            const washTypeId = selectedWashTypeOption ? selectedWashTypeOption.dataset.id : null;

            // Load services for the selected car type and wash type
            if (carTypeId && washTypeId) {
                await loadServicesForCarType(carTypeId, washTypeId);
            }
            
            filterServices();
        });
    }

    // --- 4. Booking Form Handling ---
    if (bookingForm) {
        const btn = bookingForm.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        const messageDiv = document.getElementById('bookingMessage');

        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Loading State
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-spinner" style="animation: spin 1s linear infinite"></i> Booking...';
            messageDiv.innerHTML = '';
            messageDiv.className = 'booking-message';

            // Collect form data
            const category = bookingForm.querySelector('[name="category"]').value;
            const formData = {
                name: bookingForm.querySelector('[name="name"]').value,
                phone: bookingForm.querySelector('[name="phone"]').value,
                category: category,
                car_type: bookingForm.querySelector('[name="car_type"]').value,
                service_id: bookingForm.querySelector('[name="service_id"]').value,
                date: bookingForm.querySelector('[name="date"]').value,
                time: bookingForm.querySelector('[name="time"]').value,
                note: bookingForm.querySelector('[name="note"]').value
            };
            
            // Only include wash_type for Car Wash category
            if (category === 'Car Wash') {
                formData.wash_type = bookingForm.querySelector('[name="wash_type"]')?.value || '';
            }

            try {
                const response = await fetch('api/book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Booked!';
                    btn.style.backgroundColor = '#10b981';
                    messageDiv.innerHTML = data.message;
                    messageDiv.className = 'booking-message success';

                    // Reset after 3 seconds
                    setTimeout(() => {
                        bookingForm.reset();
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        btn.style.backgroundColor = '';
                        closeModal();
                    }, 3000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    messageDiv.innerHTML = data.message;
                    messageDiv.className = 'booking-message error';
                }
            } catch (error) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                messageDiv.innerHTML = 'Connection error. Please try again.';
                messageDiv.className = 'booking-message error';
            }
        });
    }

    // --- 5. Quote Form Handling (Simulation) ---
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        const btn = quoteForm.querySelector('button');
        const originalText = btn.innerHTML;

        quoteForm.addEventListener('submit', (e) => {
            e.preventDefault();

            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-spinner" style="animation: spin 1s linear infinite"></i> Sending...';

            setTimeout(() => {
                btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Sent!';
                btn.style.backgroundColor = '#10b981';

                setTimeout(() => {
                    quoteForm.reset();
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    btn.style.backgroundColor = '';
                }, 2000);
            }, 1500);
        });
    }

    // Add spinner animation style dynamically
    const styleSheet = document.createElement("style");
    styleSheet.innerText = `
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .booking-message { margin: 10px 0; padding: 10px; border-radius: 8px; text-align: center; }
        .booking-message.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .booking-message.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    `;
    document.head.appendChild(styleSheet);

    // --- 6. Scroll to Top Button ---
    const scrollTopBtn = document.getElementById('scrollTopBtn');

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Show/hide scroll to top button based on scroll position
    function handleScrollTopButton() {
        if (scrollTopBtn) {
            if (window.scrollY > 400) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        }
    }

    // Add scroll event listener
    window.addEventListener('scroll', handleScrollTopButton);

    // Check initial scroll position
    handleScrollTopButton();

    // Make functions global so HTML onclick attributes can find them
    window.toggleMobileMenu = toggleMobileMenu;
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.scrollToTop = scrollToTop;
    window.toggleLanguageMenu = function() {
        const menu = document.getElementById('languageMenu');
        if (menu) {
            menu.classList.toggle('active');
        }
    };

    // Close language menu when clicking outside
    document.addEventListener('click', function(event) {
        const languageSelector = document.querySelector('.language-selector');
        const languageMenu = document.getElementById('languageMenu');
        if (languageMenu && languageSelector && !languageSelector.contains(event.target)) {
            languageMenu.classList.remove('active');
        }
    });
});
