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
        appendBot(cfg.welcome_msg, []);
    }

    /* ── Send Message ─────────────────────────────────── */

    function sendMessage() {
        if (isSending) return;
        var text = inputEl.value.trim();

        var selectedOptions = getSelectedOptionsFromLastMessage();

        var fullMessage = text;
        if (selectedOptions.length > 0) {
            fullMessage = selectedOptions.join(', ') + (text ? '\n' + text : '');
        }

        if (!fullMessage) return;

        appendUser(fullMessage);
        inputEl.value = '';
        inputEl.style.height = 'auto';

        history.push({ role: 'user', text: fullMessage });

        var typingId = showTyping();
        isSending = true;
        sendBtn.classList.add('sai-disabled');

        var formData = new FormData();
        formData.append('action', 'shopys_ai_chat');
        formData.append('nonce', cfg.nonce);
        formData.append('message', fullMessage);
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
                        history.push({ role: 'assistant', text: msg });
                        appendBot(msg, products);
                    } else {
                        var errMsg = (resp.data && resp.data.message) ? resp.data.message : 'Something went wrong.';
                        appendBot(errMsg, []);
                    }
                } catch (e) {
                    appendBot('Sorry, I encountered an error. Please try again.', []);
                }
            } else {
                appendBot('Connection error. Please try again.', []);
            }
        };
        xhr.onerror = function () {
            removeTyping(typingId);
            isSending = false;
            sendBtn.classList.remove('sai-disabled');
            appendBot('Network error. Please check your connection.', []);
        };
        xhr.send(formData);
    }

    /* ── PC Build Detection ───────────────────────────── */

    /**
     * Detects PC build specs from raw text (before HTML formatting).
     * Handles Claude's common formats:
     *   "**CPU**: Intel Core i7 — $350"
     *   "CPU: Intel Core i7 - $350"
     *   "• **GPU**: RTX 4060 – $400"
     *   "1. RAM: 16GB DDR5 - $80"
     */
    function detectAndFormatPCSpecs(rawText) {
        if (!/build|spec|component|processor|graphics|memory|storage|cooling|cpu|gpu|ram|ssd|hdd|motherboard|psu|power supply/i.test(rawText)) {
            return null;
        }

        var specs = [];
        var totalPrice = 0;
        var introLines = [];
        var foundFirst = false;
        var lines = rawText.split('\n');

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (!line) continue;

            // Skip markdown headers and separator lines
            if (/^#{1,6}\s/.test(line) || /^[-=*]{3,}$/.test(line)) continue;

            // Skip "Total" lines (we calculate our own)
            if (/^\*{0,2}total\*{0,2}[\s:]/i.test(line)) continue;

            // Match: optional bullet/number + optional **bold** + Component: Item - Price
            // Covers: "**CPU**: Intel i9 — $450", "• GPU: RTX 4070 - $600", "1. RAM: 32GB - $120"
            var match = line.match(
                /^(?:\d+[.)]\s*|[•●▪▸\-\*]\s*)?\*{0,2}([^*:\n]+?)\*{0,2}\s*:\s*(.+?)\s*[-–—]\s*(\$[\d,]+(?:\.\d{2})?|€[\d,]+(?:\.\d{2})?|£[\d,]+(?:\.\d{2})?|៛[\d,]+|[\d,]+(?:\.\d{2})?)/
            );

            if (match) {
                var component = match[1].trim();
                var item = match[2].trim();
                var priceStr = match[3];
                var price = parsePrice(priceStr);

                // Skip if component looks like a regular sentence (too many words)
                if (component.split(' ').length > 6 || price === 0) {
                    if (!foundFirst) introLines.push(line);
                    continue;
                }

                specs.push({ component: component, item: item, price: price, priceStr: priceStr });
                totalPrice += price;
                foundFirst = true;
            } else if (!foundFirst) {
                introLines.push(line);
            }
        }

        if (specs.length >= 3) {
            return {
                specs: specs,
                totalPrice: totalPrice,
                introText: introLines.join('\n').trim()
            };
        }

        return null;
    }

    function parsePrice(priceStr) {
        var numStr = priceStr.replace(/[^\d.]/g, '');
        return parseFloat(numStr) || 0;
    }

    function formatCurrency(price) {
        if (price > 100000) {
            return '៛' + Math.floor(price).toLocaleString();
        }
        return '$' + price.toFixed(2);
    }

    function renderPCSpecsTable(buildData) {
        var html = '<div class="sai-pc-build-container">';

        html += '<div class="sai-build-header">';
        html += '<h3 class="sai-build-title">&#x1F4BB; PC Build Configuration</h3>';
        html += '<button class="sai-btn-print" onclick="window.print();" title="Print this build">&#x1F5A8;&#xFE0F; Print</button>';
        html += '</div>';

        html += '<table class="sai-specs-table">';
        html += '<thead><tr>';
        html += '<th class="sai-col-component">Component</th>';
        html += '<th class="sai-col-item">Item</th>';
        html += '<th class="sai-col-price">Price</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        for (var i = 0; i < buildData.specs.length; i++) {
            var spec = buildData.specs[i];
            html += '<tr class="sai-spec-row">';
            html += '<td class="sai-col-component"><span class="sai-component-label">' + escHtml(spec.component) + '</span></td>';
            html += '<td class="sai-col-item"><span class="sai-product-name">' + escHtml(spec.item) + '</span></td>';
            html += '<td class="sai-col-price"><span class="sai-price-badge">' + escHtml(spec.priceStr) + '</span></td>';
            html += '</tr>';
        }

        html += '<tr class="sai-spec-total">';
        html += '<td colspan="2"><strong>Total Build Cost</strong></td>';
        html += '<td><strong class="sai-total-cost">' + formatCurrency(buildData.totalPrice) + '</strong></td>';
        html += '</tr>';

        html += '</tbody></table></div>';
        return html;
    }

    /* ── Message Rendering ────────────────────────────── */

    function appendUser(text) {
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-user';
        div.innerHTML = '<div class="sai-bubble sai-bubble-user">' + escHtml(text) + '</div>';
        messagesEl.appendChild(div);
        scrollBottom();
    }

    /**
     * Render a bot message. rawText is always the original plain text from Claude.
     * Detection runs on rawText (before HTML formatting) so newlines are preserved.
     */
    function appendBot(rawText, products) {
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-bot';

        var pcBuildData = detectAndFormatPCSpecs(rawText);
        var questionGroups = pcBuildData ? null : detectQuestionGroups(rawText);
        var html = formatMessage(rawText);

        var inner = '<div class="sai-bubble sai-bubble-bot">';

        if (pcBuildData) {
            if (pcBuildData.introText) {
                inner += '<div class="sai-bot-text">' + formatMessage(pcBuildData.introText) + '</div>';
            }
            inner += renderPCSpecsTable(pcBuildData);
        } else if (questionGroups) {
            inner += '<div class="sai-bot-text">' + html + '</div>';
            inner += renderQuestionGroups(questionGroups);
        } else {
            inner += '<div class="sai-bot-text">' + html + '</div>';
        }

        inner += '</div>';

        if (products && products.length > 0) {
            inner += '<div class="sai-products-grid">';
            for (var i = 0; i < products.length; i++) {
                inner += renderProductCard(products[i]);
            }
            inner += '</div>';
        }

        div.innerHTML = inner;
        messagesEl.appendChild(div);

        // Syntax highlight code blocks
        if (typeof hljs !== 'undefined') {
            var codeBlocks = div.querySelectorAll('.sai-code-block code');
            for (var c = 0; c < codeBlocks.length; c++) {
                hljs.highlightElement(codeBlocks[c]);
            }
        }

        scrollBottom();
    }

    function renderProductCard(p) {
        var card = '<div class="sai-card">';

        if (p.image) {
            card += '<a href="' + escAttr(p.url) + '" class="sai-card-img-link">';
            card += '<img class="sai-card-img" src="' + escAttr(p.image) + '" alt="' + escAttr(p.name) + '" loading="lazy" />';
            card += '</a>';
        }

        card += '<div class="sai-card-body">';

        if (p.category) {
            card += '<span class="sai-card-cat">' + escHtml(p.category) + '</span>';
        }

        card += '<a href="' + escAttr(p.url) + '" class="sai-card-name">' + escHtml(p.name) + '</a>';

        if (p.price_html) {
            card += '<div class="sai-card-price">' + p.price_html + '</div>';
        }

        if (p.stock === 'outofstock') {
            card += '<span class="sai-card-stock sai-out">Out of Stock</span>';
        } else {
            card += '<span class="sai-card-stock sai-in">In Stock</span>';
        }

        card += '<div class="sai-card-actions">';
        card += '<a href="' + escAttr(p.url) + '" class="sai-btn sai-btn-view">View</a>';
        if (p.stock !== 'outofstock' && p.type === 'simple') {
            card += '<a href="' + escAttr(p.add_to_cart) + '" class="sai-btn sai-btn-cart" data-product-id="' + p.id + '">Add to Cart</a>';
        }
        card += '</div>';

        card += '</div></div>';
        return card;
    }

    /* ── Question Group Detection & Rendering ───────────── */

    function detectQuestionGroups(rawText) {
        var lines = rawText.split('\n');
        var groups = [];
        var currentGroup = null;

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (!line) continue;

            if (/^#{1,6}\s/.test(line)) continue;

            var numberedQ = line.match(/^\d+[.)]\s+(.+\?.*)$/);
            if (numberedQ) {
                currentGroup = { question: numberedQ[1].trim(), options: [] };
                groups.push(currentGroup);
                continue;
            }

            if (line.indexOf('?') !== -1 && !/^[•●▪▸\-\*]\s/.test(line) && groups.length === 0) {
                currentGroup = { question: line, options: [] };
                groups.push(currentGroup);
                continue;
            }

            if (currentGroup) {
                var bulletMatch = line.match(/^[•●▪▸\-\*]\s+(.+)$/);
                if (bulletMatch && bulletMatch[1].trim().length > 0) {
                    currentGroup.options.push(bulletMatch[1].trim());
                    continue;
                }
            }
        }

        var valid = [];
        for (var g = 0; g < groups.length; g++) {
            if (groups[g].options.length >= 2) {
                valid.push(groups[g]);
            }
        }

        return valid.length > 0 ? valid : null;
    }

    function renderQuestionGroups(groups) {
        var html = '<div class="sai-options-container">';
        var ts = Date.now();

        for (var g = 0; g < groups.length; g++) {
            var group = groups[g];
            var groupName = 'sai-grp-' + g + '-' + ts;

            html += '<div class="sai-option-group">';
            html += '<div class="sai-option-question">' + escHtml(group.question) + '</div>';

            for (var i = 0; i < group.options.length; i++) {
                var optText = escHtml(group.options[i]);
                html += '<label class="sai-option-label">';
                html += '<input type="checkbox" name="' + groupName + '" value="' + escAttr(group.options[i]) + '" class="sai-option-input" />';
                html += '<span class="sai-option-text">' + optText + '</span>';
                html += '</label>';
            }

            html += '</div>';
        }

        html += '<button class="sai-option-send" type="button" onclick="document.getElementById(\'sai-send\').click();">Send Selection &#x27A4;</button>';
        html += '</div>';

        return html;
    }

    function getSelectedOptionsFromLastMessage() {
        var botMessages = messagesEl.querySelectorAll('.sai-msg-bot');
        if (botMessages.length === 0) return [];

        var lastBotMsg = botMessages[botMessages.length - 1];
        var selectedOptions = [];
        var inputs = lastBotMsg.querySelectorAll('.sai-option-input:checked');

        inputs.forEach(function (input) {
            selectedOptions.push(input.value);
        });

        return selectedOptions;
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

        var lines = text.split('\n');
        var html = '';
        var i = 0;

        while (i < lines.length) {
            var line = lines[i];
            var trimmed = line.trim();

            if (!trimmed) { i++; continue; }

            // Fenced code block
            if (/^```/.test(trimmed)) {
                var lang = trimmed.slice(3).trim();
                var codeLines = [];
                i++;
                while (i < lines.length && !/^```/.test(lines[i].trim())) {
                    codeLines.push(escHtml(lines[i]));
                    i++;
                }
                i++;
                html += '<div class="sai-code-block">';
                if (lang) html += '<div class="sai-code-lang">' + escHtml(lang) + '</div>';
                var langClass = lang ? ' class="language-' + escAttr(lang) + '"' : '';
                html += '<pre><code' + langClass + '>' + codeLines.join('\n') + '</code></pre></div>';
                continue;
            }

            // Horizontal rule
            if (/^[-*_]{3,}$/.test(trimmed)) {
                html += '<hr class="sai-hr">';
                i++; continue;
            }

            // Headers
            var hMatch = trimmed.match(/^(#{1,6})\s+(.+)$/);
            if (hMatch) {
                var level = hMatch[1].length;
                html += '<h' + level + ' class="sai-h">' + inlineFormat(hMatch[2]) + '</h' + level + '>';
                i++; continue;
            }

            // Unordered list
            if (/^[•●▪▸\-\*]\s+/.test(trimmed)) {
                html += '<ul class="sai-list">';
                while (i < lines.length && /^[•●▪▸\-\*]\s+/.test(lines[i].trim())) {
                    var liText = lines[i].trim().replace(/^[•●▪▸\-\*]\s+/, '');
                    html += '<li>' + inlineFormat(liText) + '</li>';
                    i++;
                }
                html += '</ul>';
                continue;
            }

            // Ordered list
            if (/^\d+[.)]\s+/.test(trimmed)) {
                html += '<ol class="sai-list sai-ol">';
                while (i < lines.length && /^\d+[.)]\s+/.test(lines[i].trim())) {
                    var olText = lines[i].trim().replace(/^\d+[.)]\s+/, '');
                    html += '<li>' + inlineFormat(olText) + '</li>';
                    i++;
                }
                html += '</ol>';
                continue;
            }

            // Blockquote
            if (/^>\s*/.test(trimmed)) {
                var bqLines = [];
                while (i < lines.length && /^>\s*/.test(lines[i].trim())) {
                    bqLines.push(lines[i].trim().replace(/^>\s*/, ''));
                    i++;
                }
                html += '<blockquote class="sai-bq">' + inlineFormat(bqLines.join(' ')) + '</blockquote>';
                continue;
            }

            // Regular paragraph
            html += '<p>' + inlineFormat(trimmed) + '</p>';
            i++;
        }

        return html || '<p>' + escHtml(text) + '</p>';
    }

    function inlineFormat(text) {
        var s = escHtml(text);
        s = s.replace(/`([^`]+?)`/g, '<code class="sai-inline-code">$1</code>');
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
        s = s.replace(/~~(.+?)~~/g, '<del>$1</del>');
        s = linkifyUrls(s);
        return s;
    }

    function linkifyUrls(text) {
        var urlRegex = /(https?:\/\/[^\s<>]+|www\.[^\s<>]+)/gi;
        return text.replace(urlRegex, function (url) {
            var href = url;
            if (!href.match(/^https?:\/\//i)) {
                href = 'https://' + href;
            }
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
