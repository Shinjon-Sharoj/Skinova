/**
 * Consultation chat JS — polls api/messages.php for new messages
 */
(function () {
    'use strict';

    const chatBox = document.getElementById('chatMessages');
    if (!chatBox) return;

    const consultationId = chatBox.dataset.consultationId;
    if (!consultationId) return;

    let lastId = parseInt(chatBox.dataset.lastId || '0');
    let pollTimer = null;

    function scrollBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    scrollBottom();

    function appendMessage(msg, isMine) {
        const empty = chatBox.querySelector('.text-center');
        if (empty) empty.remove();

        const row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-3 chat-msg ' + (isMine ? 'flex-row-reverse' : '');
        row.id = 'msg-' + msg.id;

        const avatar = document.createElement('img');
        avatar.src = msg.sender_avatar
            ? SKINOVA.baseUrl + '/' + msg.sender_avatar
            : SKINOVA.baseUrl + '/assets/images/placeholders/avatar.svg';
        avatar.className = 'rounded-circle flex-shrink-0 align-self-end';
        avatar.width = 32; avatar.height = 32;
        avatar.style.objectFit = 'cover';

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble rounded-3 px-3 py-2 ' + (isMine ? 'bg-primary text-white' : 'bg-light border');
        bubble.style.maxWidth = '75%';

        const text = document.createElement('div');
        text.className = 'small';
        text.textContent = msg.message;
        bubble.appendChild(text);

        if (msg.attachment_url) {
            const a = document.createElement('a');
            a.href = msg.attachment_url;
            a.className = 'd-block mt-1 small ' + (isMine ? 'text-white-50' : 'text-muted');
            a.textContent = '📎 ' + (msg.attachment_name || 'Attachment');
            a.target = '_blank';
            bubble.appendChild(a);
        }

        const meta = document.createElement('div');
        meta.style.cssText = 'font-size:.65rem;opacity:.6;margin-top:2px;text-align:' + (isMine ? 'right' : 'left');
        meta.textContent = 'just now';
        bubble.appendChild(meta);

        row.appendChild(avatar);
        row.appendChild(bubble);

        const end = document.getElementById('typingEnd');
        if (end) chatBox.insertBefore(row, end);
        else chatBox.appendChild(row);

        scrollBottom();
    }

    function poll() {
        fetch(SKINOVA.baseUrl + '/api/messages.php?action=poll&consultation_id=' + consultationId + '&since_id=' + lastId, {
            headers: { 'X-CSRF-Token': SKINOVA.csrf }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const myId = parseInt(document.body.dataset.userId || '0');
            (res.data.messages || []).forEach(msg => {
                if (msg.id > lastId) {
                    lastId = msg.id;
                    if (!document.getElementById('msg-' + msg.id)) {
                        appendMessage(msg, parseInt(msg.sender_id) === myId);
                    }
                }
            });
        })
        .catch(() => {});
    }

    // Start polling
    pollTimer = setInterval(poll, SKINOVA.chatPollMs || 3000);

    // Send message form
    const msgForm = document.getElementById('msgForm');
    const msgInput = document.getElementById('msgInput');
    const attachFile = document.getElementById('attachFile');
    const attachName = document.getElementById('attachName');

    if (attachFile && attachName) {
        attachFile.addEventListener('change', () => {
            if (attachFile.files[0]) {
                attachName.textContent = '📎 ' + attachFile.files[0].name;
                attachName.classList.remove('d-none');
            } else {
                attachName.classList.add('d-none');
            }
        });
    }

    if (msgForm) {
        msgForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = msgInput ? msgInput.value.trim() : '';
            const hasAttach = attachFile && attachFile.files[0];
            if (!text && !hasAttach) return;

            const fd = new FormData();
            fd.append('consultation_id', consultationId);
            fd.append('message', text);
            fd.append('csrf_token', SKINOVA.csrf);
            if (hasAttach) fd.append('attachment', attachFile.files[0]);

            fetch(SKINOVA.baseUrl + '/actions/messages/send.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        if (msgInput) msgInput.value = '';
                        if (attachFile) attachFile.value = '';
                        if (attachName) attachName.classList.add('d-none');
                        const msg = res.message || res.data;
                        if (msg && msg.id && !document.getElementById('msg-' + msg.id)) {
                            lastId = Math.max(lastId, msg.id);
                            appendMessage(msg, true);
                        }
                    } else {
                        alert(res.message || 'Could not send message.');
                    }
                })
                .catch(() => alert('Network error.'));
        });
    }

    // Stop polling when page hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(pollTimer);
        } else {
            poll();
            pollTimer = setInterval(poll, SKINOVA.chatPollMs || 3000);
        }
    });
})();