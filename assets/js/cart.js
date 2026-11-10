/**
 * Cart page JS: qty updates, coupon, loyalty, remove — all via api/cart.php
 */
(function () {
    'use strict';
    if (!document.getElementById('cartSummary')) return;

    const api = (action, data) => SKINOVA.api('cart.php', 'POST', Object.assign({ action }, data));

    function reloadSummary() { window.location.reload(); }

    // Qty +/- buttons
    document.querySelectorAll('.cart-qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const pid   = btn.dataset.productId;
            const input = document.querySelector(.cart-qty-input[data-product-id="${pid}"]);
            const delta = parseInt(btn.dataset.delta);
            const newQty = Math.max(1, parseInt(input.value) + delta);
            input.value = newQty;
            api('set_quantity', { product_id: pid, quantity: newQty })
                .then(res => { if (res.ok) reloadSummary(); });
        });
    });

    // Qty direct input (debounced)
    let qtyTimer;
    document.querySelectorAll('.cart-qty-input').forEach(input => {
        input.addEventListener('change', () => {
            clearTimeout(qtyTimer);
            qtyTimer = setTimeout(() => {
                api('set_quantity', { product_id: input.dataset.productId, quantity: parseInt(input.value) })
                    .then(res => { if (res.ok) reloadSummary(); });
            }, 500);
        });
    });

    // Remove item
    document.querySelectorAll('.cart-remove-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            api('remove', { product_id: btn.dataset.productId })
                .then(res => { if (res.ok) reloadSummary(); });
        });
    });

    // Coupon form
    document.getElementById('couponForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const code = document.getElementById('couponInput').value.trim();
        api('set_coupon', { code })
            .then(res => {
                if (res.ok) reloadSummary();
                else SKINOVA.toast(res.message || 'Invalid coupon.', 'danger');
            });
    });

    document.getElementById('removeCoupon')?.addEventListener('click', () => {
        api('set_coupon', { code: '' }).then(() => reloadSummary());
    });

    // Loyalty
    document.getElementById('applyLoyalty')?.addEventListener('click', () => {
        const pts = parseInt(document.getElementById('loyaltyInput').value) || 0;
        api('set_loyalty', { points: pts }).then(() => reloadSummary());
    });
})();