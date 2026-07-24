/**
 * PHP Feedback Handler for Champions Restaurant
 * Handles feedback submission using PHP backend only
 */

/**
 * Submit feedback using PHP backend
 */
async function submitFeedbackPHP() {
    // Check if all categories have been rated
    const allRated = Object.values(feedbackData).every(rating => rating > 0);
    
    if (!allRated) {
        // Show alert based on language
        const language = document.documentElement.lang || 'en';
        let message;
        
        switch (language) {
            case 'ar':
                message = 'يرجى تقييم جميع الفئات قبل الإرسال.';
                break;
            case 'ku':
                message = 'تکایە هەموو بەشەکان هەڵبسەنگێنن پێش ناردن.';
                break;
            default:
                message = 'Please rate all categories before submitting.';
        }
        
        if (typeof showCustomAlert === 'function') {
            showCustomAlert(message);
        } else {
            alert(message);
        }
        return;
    }

    // Show loading state
    const sendBtn = document.getElementById('submitBtn');
    if (!sendBtn) return;
    
    const originalText = sendBtn.innerHTML;
    
    // Set loading text based on language
    const language = document.documentElement.lang || 'en';
    let loadingText;
    
    switch (language) {
        case 'ar':
            loadingText = '<i class="fas fa-spinner fa-spin"></i> <span class="loading-dots">جاري الإرسال</span>';
            break;
        case 'ku':
            loadingText = '<i class="fas fa-spinner fa-spin"></i> <span class="loading-dots">ناردن</span>';
            break;
        default:
            loadingText = '<i class="fas fa-spinner fa-spin"></i> <span class="loading-dots">Sending</span>';
    }
    
    sendBtn.innerHTML = loadingText;
    sendBtn.disabled = true;

    try {
        // Get notes from textarea
        const notesElement = document.getElementById('feedbackNotes');
        const customerNotes = notesElement ? notesElement.value.trim() : '';

        // Prepare data for PHP
        const payload = {
            staff_rating: feedbackData.staff,
            service_rating: feedbackData.service,
            hygiene_rating: feedbackData.hygiene,
            customer_notes: customerNotes,
            user_language: language === 'ar' ? 'Arabic' : language === 'ku' ? 'Kurdish' : 'English',
            submission_time: new Date().toISOString()
        };

        // Send to PHP backend
        const response = await fetch('./send_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Failed to submit feedback');
        }

        // Show success animation
        sendBtn.classList.add('success-animation');
        sendBtn.innerHTML = '<i class="fas fa-check"></i> ' + 
            (language === 'ar' ? 'شكراً!' : language === 'ku' ? 'سپاس!' : 'Thank You!');
        sendBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';

        // Show success message
        let successMessage;
        switch (language) {
            case 'ar':
                successMessage = 'شكراً لكم على ملاحظاتكم! نقدر وقتكم.';
                break;
            case 'ku':
                successMessage = 'سپاس بۆ بیرۆکەکانتان! ئێمە بایەخ بە کاتتان دەدەین.';
                break;
            default:
                successMessage = 'Thank you for your feedback! We appreciate your time.';
        }
        
        if (typeof showCustomAlert === 'function') {
            showCustomAlert(successMessage, 'success');
        }

        // Reset form after 3 seconds
        setTimeout(() => {
            resetForm(originalText);
        }, 3000);

    } catch (error) {
        console.error('Failed to send feedback:', error);
        
        // Show error message
        let errorText, errorMessage;
        
        switch (language) {
            case 'ar':
                errorText = '<i class="fas fa-exclamation-triangle"></i> خطأ - حاول مرة أخرى';
                errorMessage = 'فشل في إرسال التقييم. يرجى التحقق من اتصال الإنترنت والمحاولة مرة أخرى.';
                break;
            case 'ku':
                errorText = '<i class="fas fa-exclamation-triangle"></i> هەڵە - دووبارە هەوڵ بدەنەوە';
                errorMessage = 'شکستی ناردنی بیرۆکە. تکایە پشکنینی بەستراوی ئینتەرنێتتان بکەن و دووبارە هەوڵ بدەنەوە.';
                break;
            default:
                errorText = '<i class="fas fa-exclamation-triangle"></i> Error - Try Again';
                errorMessage = 'Failed to send feedback. Please check your internet connection and try again.';
        }
        
        sendBtn.innerHTML = errorText;
        sendBtn.style.background = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
        
        // Reset button after 3 seconds
        setTimeout(() => {
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
            sendBtn.style.background = '';
        }, 3000);
        
        if (typeof showCustomAlert === 'function') {
            showCustomAlert(errorMessage, 'error');
        }
    }
}

/**
 * Reset the feedback form
 */
function resetForm(originalButtonText) {
    const sendBtn = document.getElementById('submitBtn');
    if (sendBtn) {
        sendBtn.innerHTML = originalButtonText;
        sendBtn.disabled = false;
        sendBtn.classList.remove('success-animation');
        sendBtn.style.background = '';
    }

    // Reset all ratings
    if (typeof feedbackData !== 'undefined') {
        Object.keys(feedbackData).forEach(key => {
            feedbackData[key] = 0;
        });
    }
    
    // Reset star displays
    document.querySelectorAll('.star-rating').forEach(rating => {
        if (typeof updateStarDisplay === 'function') {
            updateStarDisplay(rating, 0);
        }
    });

    // Reset labels
    if (typeof ratingLabels !== 'undefined' && typeof updateRatingLabel === 'function') {
        Object.keys(ratingLabels).forEach(category => {
            updateRatingLabel(category, 0);
        });
    }

    // Reset notes
    const notesElement = document.getElementById('feedbackNotes');
    if (notesElement) {
        notesElement.value = '';
    }

    // Update progress
    if (typeof updateProgress === 'function') {
        updateProgress();
    }

    // Clear session storage
    sessionStorage.removeItem('feedbackData');
}