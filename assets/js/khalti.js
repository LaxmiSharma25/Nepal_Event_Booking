/**
 * Khalti Payment Integration
 * This file contains JavaScript functions to handle Khalti payment process.
 */

/**
 * Initialize and display Khalti payment information.
 * This function is called when the Khalti payment method is selected.
 */
function initKhaltiPayment() {
    // Add Khalti payment information or additional instructions
    const khaltiInfo = document.getElementById('khalti-info');
    if (khaltiInfo) {
        khaltiInfo.style.display = 'block';
    }
}

/**
 * Hide Khalti payment information.
 * This function is called when another payment method is selected.
 */
function hideKhaltiPayment() {
    // Hide Khalti payment information
    const khaltiInfo = document.getElementById('khalti-info');
    if (khaltiInfo) {
        khaltiInfo.style.display = 'none';
    }
}

/**
 * Setup the payment method radio buttons event listeners.
 * This function is called when the DOM is loaded.
 */
document.addEventListener('DOMContentLoaded', function() {
    const khaltiRadio = document.getElementById('khalti');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    if (paymentMethods && paymentMethods.length > 0) {
        paymentMethods.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (khaltiRadio && khaltiRadio.checked) {
                    initKhaltiPayment();
                } else {
                    hideKhaltiPayment();
                }
            });
        });
        
        // Initial check
        if (khaltiRadio && khaltiRadio.checked) {
            initKhaltiPayment();
        }
    }
    
    // Set up checkout form submission for Khalti
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(event) {
            // If Khalti is selected, we'll handle it in PHP
            // No need to prevent form submission
        });
    }
}); 