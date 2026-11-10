/**
 * Skinova global JavaScript.
 *  - Skinova.api(): fetch wrapper (JSON, CSRF header, error handling)
 *  - Skinova.toast(): Bootstrap toasts
 *  - Global handlers: add-to-cart, toggle-wishlist, data-confirm, form loading states,
 *    Bootstrap validation, password visibility, image preview, badge updates.
 */
(function () {
    'use strict';

    var cfg = window.SKINOVA || { baseUrl: '', csrf: '', loggedIn: false, currency: '$' };

    function url(path) {
        return cfg.baseUrl + '/' + String(path).replace(/^\/+/, '');
    }

    /**
     * Call a JSON API endpoint.
     * @param {string} path  e.g. 'api/cart.php'
     * @param {object} opts  {method:'POST', data:{...}, formData: FormData}
     * @returns {Promise<{success:boolean,message:string,data:any,errors?:object,status:number}>}
     */
    function api(path, opts) {
        opts = opts || {};
        var method = (opts.method || (opts.data || opts.formData ? 'POST' : 'GET')).toUpperCase();
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': cfg.csrf };
        var init = { method: method, headers: headers, credentials: 'same-origin' };
        var target = url(path);
        if (opts.formData) {
            init.body = opts.formData;
        } else if (opts.data && method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.data);
        } else if (opts.data) {
            target += (target.indexOf('?') > -1 ? '&' : '?') + new URLSearchParams(opts.data).toString();
        }
        return fetch(target, init).then(function (res) {
            return res.json().catch(function () { return { success: false, message: 'Unexpected server response.' }; })
                .then(function (body) { body.status = res.status; return body; });
        }).catch(function () {
            return { success: false, message: 'Network error. Please check your connection and try again.', status: 0 };
        });
    }

    function toast(message, type) {
        type = type || 'success';
        var container = document.getElementById('toastContainer');
        if (!container || !window.bootstrap) { return; }
        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + type + ' border-0';
        el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        var inner = document.createElement('div');
        inner.className = 'd-flex';
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Close');
        inner.appendChild(body);
        inner.appendChild(close);
        el.appendChild(inner);
        container.appendChild(el);
        var t = new bootstrap.Toast(el, { delay: 3500 });
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        t.show();
    }

    function setBadge(name, count) {
        document.querySelectorAll('[data-badge="' + name + '"]').forEach(function (b) {
            b.textContent = count;
            b.classList.toggle('d-none', !count);
        });
    }

    function formatMoney(n) {
        return cfg.currency + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function loginRedirect() {
        var next = window.location.pathname.replace(cfg.baseUrl, '').replace(/^\//, '') + window.location.search;
        window.location.href = url('login.php?next=' + encodeURIComponent(next));
    }

    function setLoading(btn, loading, label) {
        if (!btn) { return; }
        if (loading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + (label || 'Please wait...');
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) { btn.innerHTML = btn.dataset.originalHtml; }
        }
    }

    // ---- Global click actions -------------------------------------------------
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-action]');
        if (!btn) { return; }
        var action = btn.getAttribute('data-action');

        if (action === 'add-to-cart') {
            ev.preventDefault();
            if (!cfg.loggedIn) { return loginRedirect(); }
            var qtyInput = btn.getAttribute('data-qty-input') ? document.querySelector(btn.getAttribute('data-qty-input')) : null;
            var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : parseInt(btn.getAttribute('data-qty') || '1', 10);
            setLoading(btn, true, 'Adding...');
            api('api/cart.php', { data: { action: 'add', product_id: parseInt(btn.getAttribute('data-product-id'), 10), quantity: qty } })
                .then(function (res) {
                    setLoading(btn, false);
                    if (res.status === 401) { return loginRedirect(); }
                    toast(res.message, res.success ? 'success' : 'danger');
                    if (res.success && res.data && typeof res.data.cart_count !== 'undefined') { setBadge('cart', res.data.cart_count); }
                });
        }

        if (action === 'toggle-wishlist') {
            ev.preventDefault();
            if (!cfg.loggedIn) { return loginRedirect(); }
            api('api/wishlist.php', { data: { action: 'toggle', product_id: parseInt(btn.getAttribute('data-product-id'), 10) } })
                .then(function (res) {
                    if (res.status === 401) { return loginRedirect(); }
                    toast(res.message, res.success ? 'success' : 'danger');
                    if (res.success && res.data) {
                        var on = !!res.data.in_wishlist;
                        document.querySelectorAll('[data-action="toggle-wishlist"][data-product-id="' + btn.getAttribute('data-product-id') + '"]').forEach(function (b) {
                            b.classList.toggle('active', on);
                            b.setAttribute('aria-pressed', on ? 'true' : 'false');
                            var icon = b.querySelector('i');
                            if (icon) { icon.className = 'bi ' + (on ? 'bi-heart-fill text-danger' : 'bi-heart'); }
                        });
                        if (typeof res.data.wishlist_count !== 'undefined') { setBadge('wishlist', res.data.wishlist_count); }
                    }
                });
        }
    });

    // ---- data-confirm on links/buttons ----------------------------------------------
    document.addEventListener('click', function (ev) {
        var el = ev.target.closest('[data-confirm]');
        if (el && !window.confirm(el.getAttribute('data-confirm'))) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    }, true);

    // ---- Forms: Bootstrap validation + loading state ---------------------------------
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!(form instanceof HTMLFormElement)) { return; }
        if (form.classList.contains('needs-validation') && !form.checkValidity()) {
            ev.preventDefault();
            ev.stopPropagation();
            form.classList.add('was-validated');
            var firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) { firstInvalid.focus(); }
            return;
        }
        if (!ev.defaultPrevented) {
            var submit = form.querySelector('button[type="submit"][data-loading]');
            if (submit) {
                // Defer so the submit button's name/value still gets posted.
                setTimeout(function () { setLoading(submit, true, submit.getAttribute('data-loading') || 'Please wait...'); }, 0);
            }
        }
    });

    // ---- Password visibility toggle: <button data-toggle-password="#inputId"> ------------
    document.addEventListener('click', function (ev) {
        var t = ev.target.closest('[data-toggle-password]');
        if (!t) { return; }
        var input = document.querySelector(t.getAttribute('data-toggle-password'));
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = t.querySelector('i');
        if (icon) { icon.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye'); }
    });

    // ---- Image preview: <input type=file data-preview="#imgId" data-max-mb="5"> -----------
    document.addEventListener('change', function (ev) {
        var input = ev.target;
        if (!input.matches || !input.matches('input[type="file"][data-preview]')) { return; }
        var target = document.querySelector(input.getAttribute('data-preview'));
        if (!target || !input.files || !input.files[0]) { return; }
        var file = input.files[0];
        var maxMb = parseFloat(input.getAttribute('data-max-mb') || '5');
        if (file.size > maxMb * 1024 * 1024) {
            toast('File is too large (max ' + maxMb + ' MB).', 'danger');
            input.value = '';
            return;
        }
        if (/^image\//.test(file.type)) {
            var reader = new FileReader();
            reader.onload = function (e) { target.src = e.target.result; target.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        }
    });

    // ---- Auto-dismiss success alerts -----------------------------------------------------
    setTimeout(function () {
        document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (a) {
            if (window.bootstrap) { bootstrap.Alert.getOrCreateInstance(a).close(); }
        });
    }, 6000);

    // ---- Notification badge refresh (every 60s for logged-in users) ------------------------
    if (cfg.loggedIn) {
        setInterval(function () {
            api('api/notifications.php', { data: { action: 'count' } }).then(function (res) {
                if (res.success && res.data && typeof res.data.unread !== 'undefined') { setBadge('notifications', res.data.unread); }
            });
        }, 60000);
    }

    window.Skinova = {
        url: url, api: api, toast: toast, setBadge: setBadge, formatMoney: formatMoney,
        escapeHtml: escapeHtml, setLoading: setLoading, loginRedirect: loginRedirect, config: cfg
    };
})();