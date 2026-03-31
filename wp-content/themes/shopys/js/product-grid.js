/**
 * Premium Product Grid - Filter, Infinite Scroll & AJAX Pagination
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initCategoryFilter();
        initInfiniteScroll();
        initAjaxPagination();
        initQuickView();
    });

    /* ── AJAX Pagination (normal mode, no page reload) ───────────── */
    function initAjaxPagination() {
        // Use event delegation so it works after grid replacement
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.ppg-pagination-links a.page-numbers');
            if (!link) return;

            var container = link.closest('.ppg-container');
            if (!container || container.dataset.paginationType === 'infinite') return;

            e.preventDefault();

            // Extract page number from the href
            var href = link.getAttribute('href') || '';
            var match = href.match(/\/page\/(\d+)/);
            var page = match ? parseInt(match[1], 10) : 1;

            // Handle ?paged= style URLs too
            if (!match) {
                var pagedMatch = href.match(/[?&]paged=(\d+)/);
                page = pagedMatch ? parseInt(pagedMatch[1], 10) : 1;
            }

            // "Prev" without page number → page 1
            if (!match && !href.match(/[?&]paged=/)) {
                if (link.classList.contains('prev')) page = Math.max(1, (container._currentPage || 1) - 1);
                if (link.classList.contains('next')) page = (container._currentPage || 1) + 1;
            }

            loadPage(container, page);
        });
    }

    function loadPage(container, page) {
        var d = container.dataset;
        var isTable = d.listingType === 'table';
        var tableWrap = container.querySelector('.ppg-table-wrap');
        var grid = container.querySelector('.ppg-grid');
        var paginationWrap = container.querySelector('.ppg-pagination');

        // Dim whichever element is the current listing
        var current = isTable ? tableWrap : grid;
        if (current) {
            current.style.transition = 'opacity 0.2s';
            current.style.opacity = '0.35';
            current.style.pointerEvents = 'none';
        }

        var fd = new FormData();
        var ajaxAction = 'ppg_ajax_paginate';
        if (d.shortcode === 'featured_products') ajaxAction = 'fpg_ajax_paginate';
        if (d.shortcode === 'latest_products')   ajaxAction = 'lpg_ajax_paginate';
        fd.append('action', ajaxAction);
        fd.append('nonce', ppgParams.nonce);
        fd.append('page', page);
        fd.append('limit', d.limit || 12);
        fd.append('columns', d.columns || 4);
        fd.append('category', d.category || '');
        fd.append('filter_tabs', d.filterTabs || '');
        fd.append('orderby', d.orderby || 'date');
        fd.append('order', d.order || 'DESC');
        fd.append('filter', d.filter || 'false');
        fd.append('cart', d.cart || 'true');
        fd.append('show_description', d.showDescription || 'true');
        fd.append('pagination_type', d.paginationType || 'normal');
        fd.append('listing_type', d.listingType || 'grid');   // ← key fix

        fetch(ppgParams.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                var tmp = document.createElement('div');
                tmp.innerHTML = data.data.html;

                if (isTable) {
                    // ── Table mode: replace .ppg-table-wrap ──
                    var newTableWrap = tmp.querySelector('.ppg-table-wrap');
                    if (newTableWrap && tableWrap) {
                        container.replaceChild(newTableWrap, tableWrap);
                    } else if (newTableWrap) {
                        container.insertBefore(newTableWrap, container.querySelector('.ppg-pagination'));
                    }

                    // Re-apply category filter to new rows
                    var activeBtn = container.querySelector('.ppg-filter-btn.active');
                    if (activeBtn && activeBtn.dataset.category !== 'all') {
                        var cat = activeBtn.dataset.category;
                        (newTableWrap || container).querySelectorAll('.ppg-lt-row').forEach(function (row) {
                            row.classList.toggle('ppg-hidden', (row.dataset.categories || '').indexOf(cat) === -1);
                        });
                    }
                } else {
                    // ── Grid mode: replace .ppg-grid ──
                    var newGrid = tmp.querySelector('.ppg-grid');
                    if (newGrid && grid) {
                        container.replaceChild(newGrid, grid);
                    }

                    // Staggered fade-in for cards
                    if (newGrid) {
                        newGrid.querySelectorAll('.ppg-card').forEach(function (card, i) {
                            card.style.animation = 'none';
                            card.offsetHeight;
                            card.style.animation = 'ppgFadeIn 0.35s ease-out ' + (i * 0.04) + 's both';
                        });
                    }

                    // Re-apply category filter
                    var activeBtn2 = container.querySelector('.ppg-filter-btn.active');
                    if (activeBtn2 && activeBtn2.dataset.category !== 'all') {
                        var cat2 = activeBtn2.dataset.category;
                        (newGrid || container).querySelectorAll('.ppg-card').forEach(function (card) {
                            card.classList.toggle('ppg-hidden', (card.dataset.categories || '').indexOf(cat2) === -1);
                        });
                    }
                }

                // Replace pagination bar
                if (paginationWrap) paginationWrap.remove();
                var newPagination = tmp.querySelector('.ppg-pagination');
                if (newPagination) container.appendChild(newPagination);

                container._currentPage = data.data.page;

                // Re-init WooCommerce add-to-cart on new elements
                if (typeof jQuery !== 'undefined') {
                    jQuery(document.body).trigger('wc_fragment_refresh');
                    jQuery(document).trigger('wc-product-added');
                }

                // Smooth scroll back to top of grid/table
                var top = container.getBoundingClientRect().top + window.scrollY - 80;
                window.scrollTo({ top: top, behavior: 'smooth' });

                // Restore opacity on fallback (grid already replaced, so this handles if replace failed)
                var restored = container.querySelector('.ppg-table-wrap, .ppg-grid');
                if (restored) { restored.style.opacity = '1'; restored.style.pointerEvents = ''; }
            })
            .catch(function (err) {
                console.error('PPG AJAX pagination error:', err);
                if (current) { current.style.opacity = '1'; current.style.pointerEvents = ''; }
            });
    }

    /* ── Infinite Scroll ─────────────────────────────────────────── */
    function initInfiniteScroll() {
        const containers = document.querySelectorAll('.ppg-container');

        containers.forEach(function (container) {
            const pagination = container.querySelector('.ppg-pagination[data-type="infinite"]');
            if (!pagination) return;

            const isTable = container.dataset.listingType === 'table';
            const grid = container.querySelector('.ppg-grid');
            const tableBody = container.querySelector('.ppg-list-table tbody');
            const loader = pagination.querySelector('.ppg-infinite-loader');
            const linksContainer = pagination.querySelector('.ppg-pagination-links');
            let isFetching = false;

            const observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting && !isFetching) {
                    loadNextPage();
                }
            }, { rootMargin: '0px 0px 300px 0px' });

            if (loader) observer.observe(loader);

            function loadNextPage() {
                const nextLink = linksContainer.querySelector('.next.page-numbers');
                if (!nextLink) {
                    loader.innerHTML = '<span style="color:#aaa;margin-top:10px;display:block;">No more products.</span>';
                    observer.disconnect();
                    return;
                }

                const nextUrl = nextLink.getAttribute('href');
                isFetching = true;

                fetch(nextUrl)
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        if (isTable && tableBody) {
                            // ── Table mode: append new rows ──
                            const newRows = doc.querySelectorAll('.ppg-lt-row');
                            newRows.forEach(function (row) { tableBody.appendChild(row); });

                            // Re-apply category filter
                            const activeFilter = container.querySelector('.ppg-filter-btn.active');
                            if (activeFilter && activeFilter.dataset.category !== 'all') {
                                const cat = activeFilter.dataset.category;
                                newRows.forEach(function (row) {
                                    if ((row.dataset.categories || '').indexOf(cat) === -1) {
                                        row.classList.add('ppg-hidden');
                                    }
                                });
                            }
                        } else if (grid) {
                            // ── Grid mode: append new cards ──
                            const newCards = doc.querySelectorAll('.ppg-card');
                            let visibleIndex = 0;
                            newCards.forEach(function (card) {
                                grid.appendChild(card);
                                card.style.animation = 'none';
                                card.offsetHeight;
                                card.style.animation = 'ppgFadeIn 0.4s ease-out ' + (visibleIndex * 0.05) + 's both';
                                visibleIndex++;
                            });

                            // Re-apply category filter
                            const activeFilter = container.querySelector('.ppg-filter-btn.active');
                            if (activeFilter && activeFilter.getAttribute('data-category') !== 'all') {
                                const cat = activeFilter.getAttribute('data-category');
                                newCards.forEach(function (card) {
                                    const cardCats = card.getAttribute('data-categories') || '';
                                    if (cardCats.indexOf(cat) === -1) card.classList.add('ppg-hidden');
                                });
                            }
                        }

                        // Update next link
                        const newLinksContainer = doc.querySelector('.ppg-pagination-links');
                        if (newLinksContainer) {
                            linksContainer.innerHTML = newLinksContainer.innerHTML;
                        } else {
                            linksContainer.innerHTML = '';
                            loadNextPage();
                        }

                        isFetching = false;
                    })
                    .catch(function (error) {
                        console.error('PPG Infinite Scroll Error:', error);
                        isFetching = false;
                    });
            }
        });
    }

    /* ── Category Filter ─────────────────────────────────────────── */
    function initCategoryFilter() {
        const filterBtns = document.querySelectorAll('.ppg-filter-btn');
        if (!filterBtns.length) return;

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const category = this.getAttribute('data-category');
                const container = this.closest('.ppg-container');
                // Support both grid cards and table rows
                const items = container.querySelectorAll('.ppg-card, .ppg-lt-row');

                container.querySelectorAll('.ppg-filter-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');

                let visibleIndex = 0;
                items.forEach(function (item) {
                    const itemCats = item.getAttribute('data-categories') || '';
                    if (category === 'all' || itemCats.indexOf(category) !== -1) {
                        item.classList.remove('ppg-hidden');
                        if (item.classList.contains('ppg-card')) {
                            item.style.animation = 'none';
                            item.offsetHeight;
                            item.style.animation = 'ppgFadeIn 0.4s ease-out ' + (visibleIndex * 0.05) + 's both';
                        }
                        visibleIndex++;
                    } else {
                        item.classList.add('ppg-hidden');
                    }
                });
            });
        });
    }

    /* Quick View Modal */
    function initQuickView() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.ppg-qv-btn');
            if (!btn) return;
            var row = btn.closest('.ppg-lt-row');
            if (!row) return;
            var modal = document.getElementById('ppg-qv-modal');
            if (!modal) return;
            modal.querySelector('.ppg-qv-image').src        = row.dataset.qvImage || '';
            modal.querySelector('.ppg-qv-image').alt        = row.dataset.qvName  || '';
            modal.querySelector('.ppg-qv-name').textContent = row.dataset.qvName  || '';
            modal.querySelector('.ppg-qv-price').innerHTML  = row.dataset.qvPrice || '';
            modal.querySelector('.ppg-qv-desc').textContent = row.dataset.qvDesc  || '';
            modal.querySelector('.ppg-qv-link').href        = row.dataset.qvUrl   || '#';
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        });
        document.addEventListener('click', function (e) {
            if (e.target.closest('.ppg-qv-close') || e.target.classList.contains('ppg-qv-overlay')) {
                var modal = document.getElementById('ppg-qv-modal');
                if (modal) { modal.hidden = true; document.body.style.overflow = ''; }
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var modal = document.getElementById('ppg-qv-modal');
                if (modal) { modal.hidden = true; document.body.style.overflow = ''; }
            }
        });
    }

})();

