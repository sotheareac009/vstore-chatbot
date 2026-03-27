/**
 * Cart Summary — AJAX remove + auto-refresh on add-to-cart
 */
(function () {
    'use strict';

    /* ── Helpers ─────────────────────────────────────────────────── */

    function getAtts(wrapper) {
        return {
            show_checkout: wrapper.dataset.showCheckout || 'true',
            show_print: wrapper.dataset.showPrint || 'true',
            title: wrapper.dataset.title || '',
        };
    }

    /** Fade old widget out, insert new HTML, fade in */
    function replaceWidget(wrapper, newHtml) {
        var tmp = document.createElement('div');
        tmp.innerHTML = newHtml;
        var newWidget = tmp.firstElementChild;
        if (!newWidget) return;

        wrapper.style.transition = 'opacity 0.2s';
        wrapper.style.opacity = '0';

        setTimeout(function () {
            if (wrapper.parentNode) {
                wrapper.parentNode.insertBefore(newWidget, wrapper);
                wrapper.parentNode.removeChild(wrapper);
            }
            newWidget.style.opacity = '0';
            newWidget.style.transition = 'opacity 0.2s';
            requestAnimationFrame(function () {
                newWidget.style.opacity = '1';
            });
        }, 220);
    }

    /** Build FormData from an action + extras object */
    function buildForm(action, extras) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', shopysCsParams.nonce);
        Object.keys(extras).forEach(function (k) { fd.append(k, extras[k]); });
        return fd;
    }

    /** Refresh every .shopys-cs widget on the page */
    function refreshAll() {
        document.querySelectorAll('.shopys-cs').forEach(function (wrapper) {
            var atts = getAtts(wrapper);
            fetch(shopysCsParams.ajaxUrl, {
                method: 'POST',
                body: buildForm('shopys_refresh_cart_summary', atts),
                credentials: 'same-origin',
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        replaceWidget(wrapper, data.data.html);
                        updateHeaderCount(data.data.count);
                    }
                })
                .catch(function (err) { console.warn('Cart refresh error:', err); });
        });
    }

    /** Update WC mini-cart count badges in the theme header */
    function updateHeaderCount(count) {
        if (typeof count === 'undefined') return;
        document.querySelectorAll(
            '.cart-contents-count, .wc-block-mini-cart__badge'
        ).forEach(function (el) {
            el.textContent = count;
        });
    }

    /* ── Listen: WooCommerce added_to_cart (jQuery event) ───────── */
    // WooCommerce fires this on document.body via jQuery after AJAX add-to-cart.
    // We bridge it to a native CustomEvent so we don't need jQuery ourselves.
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('added_to_cart', function () {
            refreshAll();
        });
    } else {
        // Fallback: poll for jQuery readiness (rare edge case)
        var _jqWait = setInterval(function () {
            if (typeof jQuery !== 'undefined') {
                clearInterval(_jqWait);
                jQuery(document.body).on('added_to_cart', function () {
                    refreshAll();
                });
            }
        }, 300);
    }

    /* ── Listen: Remove button click ────────────────────────────── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.shopys-cs__remove-btn');
        if (!btn) return;
        e.preventDefault();

        var wrapper = btn.closest('.shopys-cs');
        var cartKey = btn.dataset.cartKey;
        var atts = getAtts(wrapper);

        if (!cartKey) return;

        // Visual: dim the row
        var row = btn.closest('.shopys-cs__row--item');
        if (row) {
            row.style.transition = 'opacity 0.15s';
            row.style.opacity = '0.3';
            row.style.pointerEvents = 'none';
        }
        btn.disabled = true;

        var extras = Object.assign({ cart_item_key: cartKey }, atts);

        fetch(shopysCsParams.ajaxUrl, {
            method: 'POST',
            body: buildForm('shopys_remove_cart_item', extras),
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    replaceWidget(wrapper, data.data.html);
                    updateHeaderCount(data.data.count);
                } else {
                    // Restore on failure
                    if (row) { row.style.opacity = '1'; row.style.pointerEvents = ''; }
                    btn.disabled = false;
                    console.warn('Cart remove failed:', data.data);
                }
            })
            .catch(function (err) {
                console.error('Cart remove error:', err);
                if (row) { row.style.opacity = '1'; row.style.pointerEvents = ''; }
                btn.disabled = false;
            });
    });
})();
