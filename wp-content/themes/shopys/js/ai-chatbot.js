/**
 * AI Shopping Assistant – Chat Widget
 * Renders chat UI with product card recommendations
 */
(function () {
    'use strict';

    var cfg, toggle, chatWindow, messagesEl, inputEl, sendBtn, headerName, closeBtn, fullscreenBtn, newChatBtn, modelSelect;
    var history = [];
    var isOpen = false;
    var isSending = false;
    var isFullscreen = false;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        if (typeof shopysAI === 'undefined') return;
        cfg = shopysAI;

        toggle     = document.getElementById('sai-toggle');
        chatWindow = document.getElementById('sai-window');
        messagesEl = document.getElementById('sai-messages');
        inputEl    = document.getElementById('sai-input');
        sendBtn    = document.getElementById('sai-send');
        headerName = document.getElementById('sai-header-name');
        closeBtn      = document.getElementById('sai-close');
        fullscreenBtn = document.getElementById('sai-fullscreen');
        newChatBtn    = document.getElementById('sai-new-chat');
        modelSelect   = document.getElementById('sai-model-select');

        if (!toggle || !chatWindow) return;

        headerName.textContent = cfg.bot_name;

        // Events
        toggle.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', toggleChat);
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        newChatBtn.addEventListener('click', newChat);
        sendBtn.addEventListener('click', sendMessage);

        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        inputEl.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    }

    function newChat() {
        history = [];
        messagesEl.innerHTML = '';
        showWelcome();
        inputEl.focus();
    }

    function toggleFullscreen() {
        isFullscreen = !isFullscreen;
        chatWindow.classList.toggle('sai-fullscreen-mode', isFullscreen);
        scrollBottom();
    }

    function toggleChat() {
        isOpen = !isOpen;
        toggle.classList.toggle('sai-active', isOpen);
        chatWindow.classList.toggle('sai-open', isOpen);

        if (isOpen && messagesEl.children.length === 0) {
            showWelcome();
        }

        if (isOpen) {
            setTimeout(function () { inputEl.focus(); }, 300);
        }
    }

    function showWelcome() {
        var lines = cfg.welcome_msg.split('\n');
        var html = '';
        for (var i = 0; i < lines.length; i++) {
            html += '<p>' + escHtml(lines[i]) + '</p>';
        }
        appendBot(html, []);
    }

    /* ── Send Message ─────────────────────────────────── */

    function sendMessage() {
        if (isSending) return;
        var text = inputEl.value.trim();
        if (!text) return;

        // Show user message
        appendUser(text);
        inputEl.value = '';
        inputEl.style.height = 'auto';

        // Add to history
        history.push({ role: 'user', text: text });

        // Show typing indicator
        var typingId = showTyping();
        isSending = true;
        sendBtn.classList.add('sai-disabled');

        // AJAX request
        var formData = new FormData();
        formData.append('action', 'shopys_ai_chat');
        formData.append('nonce', cfg.nonce);
        formData.append('message', text);
        formData.append('history', JSON.stringify(history.slice(-10)));
        formData.append('model', modelSelect ? modelSelect.value : 'claude-haiku-4-5-20251001');
        formData.append('page_url', window.location.href);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', cfg.ajax_url, true);
        xhr.onload = function () {
            removeTyping(typingId);
            isSending = false;
            sendBtn.classList.remove('sai-disabled');

            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success && resp.data) {
                        var msg = resp.data.message || 'Sorry, no response.';
                        var products = resp.data.products || [];

                        // Add AI response to history (text only)
                        history.push({ role: 'assistant', text: msg });

                        appendBot(formatMessage(msg), products);
                    } else {
                        var errMsg = (resp.data && resp.data.message) ? resp.data.message : 'Something went wrong.';
                        appendBot('<p>' + escHtml(errMsg) + '</p>', []);
                    }
                } catch (e) {
                    appendBot('<p>Sorry, I encountered an error. Please try again.</p>', []);
                }
            } else {
                appendBot('<p>Connection error. Please try again.</p>', []);
            }
        };
        xhr.onerror = function () {
            removeTyping(typingId);
            isSending = false;
            sendBtn.classList.remove('sai-disabled');
            appendBot('<p>Network error. Please check your connection.</p>', []);
        };
        xhr.send(formData);
    }

    /* ── Message Rendering ────────────────────────────── */

    function appendUser(text) {
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-user';
        div.innerHTML = '<div class="sai-bubble sai-bubble-user">' + escHtml(text) + '</div>';
        messagesEl.appendChild(div);
        scrollBottom();
    }

    function appendBot(html, products) {
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-bot';

        var inner = '<div class="sai-bubble sai-bubble-bot">';
        inner += '<div class="sai-bot-text">' + html + '</div>';
        inner += '</div>';

        // Product grid (outside bubble, full width)
        if (products && products.length > 0) {
            inner += '<div class="sai-products-grid">';
            for (var i = 0; i < products.length; i++) {
                inner += renderProductCard(products[i]);
            }
            inner += '</div>';
        }

        div.innerHTML = inner;
        messagesEl.appendChild(div);
        scrollBottom();
    }

    function renderProductCard(p) {
        var card = '<div class="sai-card">';

        // Image
        if (p.image) {
            card += '<a href="' + escAttr(p.url) + '" class="sai-card-img-link">';
            card += '<img class="sai-card-img" src="' + escAttr(p.image) + '" alt="' + escAttr(p.name) + '" loading="lazy" />';
            card += '</a>';
        }

        card += '<div class="sai-card-body">';

        // Category badge
        if (p.category) {
            card += '<span class="sai-card-cat">' + escHtml(p.category) + '</span>';
        }

        // Name
        card += '<a href="' + escAttr(p.url) + '" class="sai-card-name">' + escHtml(p.name) + '</a>';

        // Price
        if (p.price_html) {
            card += '<div class="sai-card-price">' + p.price_html + '</div>';
        }

        // Stock
        if (p.stock === 'outofstock') {
            card += '<span class="sai-card-stock sai-out">Out of Stock</span>';
        } else {
            card += '<span class="sai-card-stock sai-in">In Stock</span>';
        }

        // Buttons
        card += '<div class="sai-card-actions">';
        card += '<a href="' + escAttr(p.url) + '" class="sai-btn sai-btn-view">View</a>';
        if (p.stock !== 'outofstock' && p.type === 'simple') {
            card += '<a href="' + escAttr(p.add_to_cart) + '" class="sai-btn sai-btn-cart" data-product-id="' + p.id + '">Add to Cart</a>';
        }
        card += '</div>';

        card += '</div></div>';
        return card;
    }

    /* ── Typing Indicator ─────────────────────────────── */

    function showTyping() {
        var id = 'sai-typing-' + Date.now();
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-bot';
        div.id = id;
        div.innerHTML = '<div class="sai-bubble sai-bubble-bot sai-typing">' +
            '<span class="sai-dot"></span><span class="sai-dot"></span><span class="sai-dot"></span>' +
            '</div>';
        messagesEl.appendChild(div);
        scrollBottom();
        return id;
    }

    function removeTyping(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    }

    /* ── Helpers ───────────────────────────────────────── */

    function formatMessage(text) {
        if (!text) return '';
        // Split into paragraphs, escape HTML, allow line breaks
        var parts = text.split(/\n+/);
        var html = '';
        for (var i = 0; i < parts.length; i++) {
            var line = parts[i].trim();
            if (line) {
                // Escape HTML first
                line = escHtml(line);
                // Bold text **text**
                line = line.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                // Detect and linkify URLs (http, https, www)
                line = linkifyUrls(line);
                html += '<p>' + line + '</p>';
            }
        }
        return html || '<p>' + escHtml(text) + '</p>';
    }

    function linkifyUrls(text) {
        // Match URLs: https://domain.com, http://domain.com, www.domain.com
        var urlRegex = /(https?:\/\/[^\s<>]+|www\.[^\s<>]+)/gi;
        return text.replace(urlRegex, function(url) {
            // Add protocol if only www
            var href = url;
            if (!href.match(/^https?:\/\//i)) {
                href = 'https://' + href;
            }
            // Create clickable link with target="_blank" for external links
            return '<a href="' + escAttr(href) + '" target="_blank" rel="noopener noreferrer" class="sai-link">' + escHtml(url) + '</a>';
        });
    }

    function scrollBottom() {
        setTimeout(function () {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }, 50);
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})();
