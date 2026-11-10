/**
 * AI Chatbot page JS — talks to api/chatbot.php
 */
(function () {
    'use strict';

    const form      = document.getElementById('chatForm');
    const input     = document.getElementById('chatInput');
    const messages  = document.getElementById('chatMessages');
    const typing    = document.getElementById('typingIndicator');
    const sendBtn   = document.getElementById('sendBtn');
    const convIdEl  = document.getElementById('convId');
    const newConvBtn = document.getElementById('newConvBtn');

    if (!form) return;

    function scrollBottom() {
        if (messages) messages.scrollTop = messages.scrollHeight;
    }
    scrollBottom();

    function appendMessage(role, content, source, provider) {
        const row = document.createElement('div');
        row.className = 'message-row d-flex mb-2 ' + (role === 'user' ? 'justify-content-end' : 'justify-content-start');

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble rounded-3 px-3 py-2 ' + (role === 'user' ? 'bg-primary text-white' : 'bg-light border');
        bubble.style.maxWidth = '75%';

        const text = document.createElement('div');
        text.className = 'small';
        text.textContent = content;
        bubble.appendChild(text);

        if (role === 'assistant' && source) {
            const src = document.createElement('div');
            src.style.cssText = 'font-size:.65rem;opacity:.6;margin-top:4px';
            src.textContent = 'Source: ' + (source === 'ai' ? 'AI (' + (provider || 'AI') + ')' : source.replace('_', ' '));
            bubble.appendChild(src);
        }

        row.appendChild(bubble);
        if (typing && typing.parentNode === messages) {
            messages.insertBefore(row, typing);
        } else {
            messages.appendChild(row);
        }
        scrollBottom();
    }

    async function sendMessage(text) {
        text = text.trim();
        if (!text) return;

        appendMessage('user', text, null, null);
        input.value = '';
        sendBtn.disabled = true;
        typing.classList.remove('d-none');
        scrollBottom();

        try {
            const res = await SKINOVA.api('chatbot.php', 'POST', {
                action: 'send',
                message: text,
                conversation_id: convIdEl.value || null,
            });

            typing.classList.add('d-none');
            sendBtn.disabled = false;

            if (res.ok) {
                if (res.data.conversation_id) convIdEl.value = res.data.conversation_id;
                appendMessage('assistant', res.data.content, res.data.source, res.data.provider);
            } else {
                appendMessage('assistant', res.message || 'Sorry, I could not process that. Please try again.', 'fallback', null);
            }
        } catch (e) {
            typing.classList.add('d-none');
            sendBtn.disabled = false;
            appendMessage('assistant', 'Network error. Please check your connection.', 'fallback', null);
        }
    }

    form.addEventListener('submit', e => {
        e.preventDefault();
        sendMessage(input.value);
    });

    // Suggestion buttons
    document.querySelectorAll('.suggestion-btn').forEach(btn => {
        btn.addEventListener('click', () => sendMessage(btn.dataset.text));
    });

    // New conversation
    newConvBtn?.addEventListener('click', () => {
        SKINOVA.api('chatbot.php', 'POST', { action: 'new_conversation' }).then(res => {
            if (res.ok && res.data.conversation_id) {
                window.location.href = window.SKINOVA.baseUrl + '/ai-chatbot.php?conv=' + res.data.conversation_id;
            }
        });
    });
})();