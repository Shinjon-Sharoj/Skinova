/**
 * Checkout page: highlight selected address/payment labels
 */
(function () {
    'use strict';

    function highlightChecked(radioName) {
        document.querySelectorAll(input[name="${radioName}"]).forEach(radio => {
            const label = radio.closest('label');
            if (!label) return;
            if (radio.checked) {
                label.classList.add('border-primary', 'bg-light');
            } else {
                label.classList.remove('border-primary', 'bg-light');
            }
            radio.addEventListener('change', () => highlightChecked(radioName));
        });
    }

    highlightChecked('address_id');
    highlightChecked('payment_method');
})();