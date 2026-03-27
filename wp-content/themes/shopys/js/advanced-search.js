/**
 * Advanced Product Search - Live AJAX Search
 */
(function () {
    'use strict';

    var searchInput, resultsBox, resultsInner, overlay, timer;
    var ajaxUrl = (typeof shopys_search_vars !== 'undefined') ? shopys_search_vars.ajax_url : '/custom-template/wp-admin/admin-ajax.php';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        searchInput = document.getElementById('aps-search-input');
        resultsBox = document.getElementById('aps-results');
        resultsInner = document.getElementById('aps-results-inner');
        overlay = document.getElementById('aps-overlay');

        if (!searchInput || !resultsBox) return;

        // Live search on typing
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            var q = this.value.trim();

            if (q.length < 2) {
                hideResults();
                return;
            }

            showLoading();
            timer = setTimeout(function () {
                doSearch(q);
            }, 300);
        });

        // Focus shows results if input has value
        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 2 && resultsInner.innerHTML !== '') {
                showResults();
            }
        });

        // Close on overlay click
        if (overlay) {
            overlay.addEventListener('click', hideResults);
        }

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideResults();
        });
    }

    function doSearch(query) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', ajaxUrl + '?action=shopys_product_search&q=' + encodeURIComponent(query), true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    renderResults(data, query);
                } catch (e) {
                    renderError();
                }
            } else {
                renderError();
            }
        };
        xhr.onerror = function () {
            renderError();
        };
        xhr.send();
    }

    function renderResults(data, query) {
        var html = '';

        if (!data.results || data.results.length === 0) {
            html = '<div class="aps-no-results">';
            html += '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m8 8 6 6"/><path d="m14 8-6 6"/></svg>';
            html += '<p>No products found for "<strong>' + escHtml(query) + '</strong>"</p>';
            html += '</div>';
        } else {
            for (var i = 0; i < data.results.length; i++) {
                var p = data.results[i];
                html += renderItem(p, query);
            }

            if (data.total > data.results.length) {
                html += '<a href="' + escHtml(data.search_url) + '" class="aps-view-all">';
                html += 'View all ' + data.total + ' results &rarr;';
                html += '</a>';
            }
        }

        resultsInner.innerHTML = html;
        showResults();
    }

    function renderItem(product, query) {
        var html = '<a href="' + escHtml(product.url) + '" class="aps-result-item">';

        // Image
        if (product.image) {
            html += '<img class="aps-result-img" src="' + escHtml(product.image) + '" alt="' + escHtml(product.title) + '" loading="lazy" />';
        } else {
            html += '<div class="aps-result-no-img"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
        }

        // Info
        html += '<div class="aps-result-info">';
        html += '<div class="aps-result-title">' + highlightText(product.title, query) + '</div>';
        html += '<div class="aps-result-meta">';

        if (product.category) {
            html += '<span class="aps-result-category">' + escHtml(product.category) + '</span>';
        }
        if (product.sku) {
            html += '<span class="aps-result-sku">SKU: ' + highlightText(product.sku, query) + '</span>';
        }
        if (product.stock_status === 'outofstock') {
            html += '<span class="aps-out-of-stock">Out of Stock</span>';
        }

        html += '</div></div>';

        // Price
        if (product.price_html) {
            html += '<div class="aps-result-price">' + product.price_html + '</div>';
        }

        html += '</a>';
        return html;
    }

    function showLoading() {
        resultsInner.innerHTML = '<div class="aps-loading"><div class="aps-spinner"></div> Searching products...</div>';
        showResults();
    }

    function renderError() {
        resultsInner.innerHTML = '<div class="aps-no-results"><p>Something went wrong. Please try again.</p></div>';
        showResults();
    }

    function showResults() {
        resultsBox.style.display = 'block';
        if (overlay) overlay.style.display = 'block';
    }

    function hideResults() {
        if (resultsBox) resultsBox.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function highlightText(text, query) {
        if (!text || !query) return escHtml(text);
        var safe = escHtml(text);
        var regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return safe.replace(regex, '<span class="aps-highlight">$1</span>');
    }
})();
