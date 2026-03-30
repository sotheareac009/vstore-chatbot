/**
 * AI Shopping Assistant – Chat Widget
 * Renders chat UI with product card recommendations
 */
(function () {
    'use strict';

    var cfg, toggle, chatWindow, messagesEl, inputEl, sendBtn, headerName, closeBtn, fullscreenBtn, newChatBtn, modelSelect, attachBtn, fileInput, attachPreview;
    var history = [];
    var attachments = []; // { name, type, data (base64), previewUrl }
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
        attachBtn     = document.getElementById('sai-attach-btn');
        fileInput     = document.getElementById('sai-file-input');
        attachPreview = document.getElementById('sai-attach-preview');

        if (!toggle || !chatWindow) return;

        headerName.textContent = cfg.bot_name;

        // Events
        toggle.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', toggleChat);
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        newChatBtn.addEventListener('click', newChat);
        sendBtn.addEventListener('click', sendMessage);
        attachBtn.addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', handleFileSelect);

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

        // Drag & drop files onto chat window
        chatWindow.addEventListener('dragover', function (e) {
            e.preventDefault();
            chatWindow.classList.add('sai-dragover');
        });
        chatWindow.addEventListener('dragleave', function (e) {
            e.preventDefault();
            chatWindow.classList.remove('sai-dragover');
        });
        chatWindow.addEventListener('drop', function (e) {
            e.preventDefault();
            chatWindow.classList.remove('sai-dragover');
            if (e.dataTransfer && e.dataTransfer.files.length) {
                handleDroppedFiles(e.dataTransfer.files);
            }
        });
    }

    function newChat() {
        history = [];
        messagesEl.innerHTML = '';
        clearAttachments();
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
        var hasFiles = attachments.length > 0;
        if (!text && !hasFiles) return;

        // Show user message with attachment thumbnails
        appendUser(text, attachments.slice());
        inputEl.value = '';
        inputEl.style.height = 'auto';

        // Add to history
        history.push({ role: 'user', text: text || '(attached file)' });

        // Grab current attachments and clear
        var filesToSend = attachments.slice();
        clearAttachments();

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

        // Attach files as base64 JSON
        if (filesToSend.length > 0) {
            var fileData = [];
            for (var f = 0; f < filesToSend.length; f++) {
                fileData.push({
                    name: filesToSend[f].name,
                    type: filesToSend[f].type,
                    data: filesToSend[f].data
                });
            }
            formData.append('attachments', JSON.stringify(fileData));
        }

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

    /* ── Attachment Handling ──────────────────────────── */

    function handleDroppedFiles(files) {
        var allowed = /^(image\/(jpeg|png|gif|webp)|application\/pdf)$/;
        for (var i = 0; i < files.length; i++) {
            if (allowed.test(files[i].type)) {
                processFile(files[i]);
            }
        }
    }

    function handleFileSelect(e) {
        var files = e.target.files;
        if (!files || !files.length) return;

        for (var i = 0; i < files.length; i++) {
            processFile(files[i]);
        }

        // Reset so same file can be re-selected
        fileInput.value = '';
    }

    function processFile(file) {
        // Max 5MB per file
        if (file.size > 5 * 1024 * 1024) {
            alert(file.name + ' is too large. Max 5MB.');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (ev) {
            var base64 = ev.target.result.split(',')[1];
            attachments.push({
                name: file.name,
                type: file.type,
                data: base64,
                previewUrl: ev.target.result
            });
            renderAttachPreview();
        };
        reader.readAsDataURL(file);
    }

    function renderAttachPreview() {
        if (!attachments.length) {
            attachPreview.innerHTML = '';
            attachPreview.classList.remove('sai-has-files');
            return;
        }

        var html = '<div class="sai-attach-thumbs">';
        for (var i = 0; i < attachments.length; i++) {
            var a = attachments[i];
            var isImage = /^image\//.test(a.type);
            html += '<div class="sai-attach-item" data-index="' + i + '">';
            if (isImage) {
                html += '<img src="' + a.previewUrl + '" alt="' + escAttr(a.name) + '" />';
            } else {
                html += '<div class="sai-attach-file-icon">&#128196;</div>';
                html += '<span class="sai-attach-name">' + escHtml(a.name) + '</span>';
            }
            html += '<button class="sai-attach-remove" data-index="' + i + '" title="Remove">&times;</button>';
            html += '</div>';
        }
        html += '</div>';

        // Quick action buttons
        var hasImage = attachments.some(function (a) { return /^image\//.test(a.type); });
        var hasPdf = attachments.some(function (a) { return a.type === 'application/pdf'; });

        html += '<div class="sai-quick-actions">';
        if (hasImage) {
            html += '<button class="sai-quick-btn" data-cmd="find_product">&#x1F50D; Find Product</button>';
            html += '<button class="sai-quick-btn" data-cmd="read_image">&#x1F4D6; Read Text</button>';
            html += '<button class="sai-quick-btn" data-cmd="summarize_image">&#x2728; Summarize</button>';
        }
        if (hasPdf) {
            html += '<button class="sai-quick-btn" data-cmd="summarize_pdf">&#x1F4C4; Summarize PDF</button>';
            html += '<button class="sai-quick-btn" data-cmd="read_pdf">&#x1F4D6; Read PDF</button>';
        }
        html += '</div>';

        attachPreview.innerHTML = html;
        attachPreview.classList.add('sai-has-files');

        // Bind remove buttons
        var removeBtns = attachPreview.querySelectorAll('.sai-attach-remove');
        for (var r = 0; r < removeBtns.length; r++) {
            removeBtns[r].addEventListener('click', function () {
                var idx = parseInt(this.getAttribute('data-index'), 10);
                attachments.splice(idx, 1);
                renderAttachPreview();
            });
        }

        // Bind quick action buttons
        var quickBtns = attachPreview.querySelectorAll('.sai-quick-btn');
        for (var q = 0; q < quickBtns.length; q++) {
            quickBtns[q].addEventListener('click', function () {
                var cmd = this.getAttribute('data-cmd');
                inputEl.value = getCommandText(cmd);
                sendMessage();
            });
        }
    }

    function getCommandText(cmd) {
        switch (cmd) {
            case 'find_product':  return '[FIND_PRODUCT] Find matching or similar products from the store based on this image.';
            case 'read_image':    return '[READ_TEXT] Read and extract all text from this image.';
            case 'summarize_image': return '[SUMMARIZE] Describe and summarize what is in this image.';
            case 'summarize_pdf': return '[SUMMARIZE] Summarize the key points of this PDF document.';
            case 'read_pdf':      return '[READ_TEXT] Read and extract the content of this PDF.';
            default:              return '';
        }
    }

    function clearAttachments() {
        attachments = [];
        renderAttachPreview();
    }

    /* ── Message Rendering ────────────────────────────── */

    function appendUser(text, files) {
        var div = document.createElement('div');
        div.className = 'sai-msg sai-msg-user';

        var inner = '';
        // Show attachment thumbnails in user bubble
        if (files && files.length) {
            inner += '<div class="sai-user-attachments">';
            for (var i = 0; i < files.length; i++) {
                if (/^image\//.test(files[i].type)) {
                    inner += '<img class="sai-user-attach-img" src="' + files[i].previewUrl + '" alt="' + escAttr(files[i].name) + '" />';
                } else {
                    inner += '<div class="sai-user-attach-file">&#128196; ' + escHtml(files[i].name) + '</div>';
                }
            }
            inner += '</div>';
        }
        if (text) inner += escHtml(text);

        div.innerHTML = '<div class="sai-bubble sai-bubble-user">' + inner + '</div>';
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

        var lines = text.split('\n');
        var html = '';
        var i = 0;

        while (i < lines.length) {
            var line = lines[i];
            var trimmed = line.trim();

            // Empty line → skip
            if (!trimmed) { i++; continue; }

            // Fenced code block: ```
            if (/^```/.test(trimmed)) {
                var lang = trimmed.slice(3).trim();
                var codeLines = [];
                i++;
                while (i < lines.length && !/^```/.test(lines[i].trim())) {
                    codeLines.push(escHtml(lines[i]));
                    i++;
                }
                i++; // skip closing ```
                html += '<div class="sai-code-block">';
                if (lang) html += '<div class="sai-code-lang">' + escHtml(lang) + '</div>';
                var langClass = lang ? ' class="language-' + escAttr(lang) + '"' : '';
                html += '<pre><code' + langClass + '>' + codeLines.join('\n') + '</code></pre></div>';
                continue;
            }

            // Horizontal rule: --- or *** or ___
            if (/^[-*_]{3,}$/.test(trimmed)) {
                html += '<hr class="sai-hr">';
                i++; continue;
            }

            // Headers: # to ######
            var hMatch = trimmed.match(/^(#{1,6})\s+(.+)$/);
            if (hMatch) {
                var level = hMatch[1].length;
                html += '<h' + level + ' class="sai-h">' + inlineFormat(hMatch[2]) + '</h' + level + '>';
                i++; continue;
            }

            // Unordered list: - item, * item, • item
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

            // Ordered list: 1. item or 1) item
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

            // Blockquote: > text
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
        // Code inline: `text`
        s = s.replace(/`([^`]+?)`/g, '<code class="sai-inline-code">$1</code>');
        // Bold: **text**
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Italic: *text* (but not inside **)
        s = s.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
        // Strikethrough: ~~text~~
        s = s.replace(/~~(.+?)~~/g, '<del>$1</del>');
        // Linkify URLs
        s = linkifyUrls(s);
        return s;
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
