<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/ai.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle        = 'Skincare Assistant';
$extraCss         = ['css/skincare.css'];
$extraJs          = ['js/chatbot.js'];
$hideFloatingChat = true;

// Load or create conversation
$convId = get_int('conv');
$conversation = null;
if ($convId) {
    $conversation = db_one("SELECT * FROM ai_conversations WHERE id = ? AND user_id = ?", [$convId, $authUser['id']]);
}
if (!$conversation) {
    // Use most recent chatbot conversation or create new one later via JS
    $conversation = db_one(
        "SELECT * FROM ai_conversations WHERE user_id = ? AND channel = 'chatbot' ORDER BY updated_at DESC LIMIT 1",
        [$authUser['id']]
    );
}

$messages = [];
if ($conversation) {
    $messages = db_all(
        'SELECT role, content, source, provider, created_at FROM ai_messages WHERE conversation_id = ? ORDER BY id',
        [$conversation['id']]
    );
}

// All user conversations for sidebar
$conversations = db_all(
    "SELECT id, title, updated_at FROM ai_conversations WHERE user_id = ? AND channel = 'chatbot' ORDER BY updated_at DESC LIMIT 20",
    [$authUser['id']]
);

include _DIR_ . '/includes/header.php';
?>
<div class="container-fluid py-0" style="max-width:1100px">
    <div class="row g-0" style="height:calc(100vh - 120px);min-height:500px">

        <!-- Conversation history sidebar -->
        <div class="col-md-3 border-end d-none d-md-flex flex-column bg-light">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Conversations</h6>
                <button class="btn btn-primary btn-sm" id="newConvBtn">
                    <i class="bi bi-plus-lg"></i> New
                </button>
            </div>
            <div class="overflow-auto flex-grow-1">
                <?php foreach ($conversations as $conv): ?>
                <a href="?conv=<?= $conv['id'] ?>"
                   class="d-block p-2 px-3 text-decoration-none text-dark border-bottom conv-item <?= ($conversation && $conversation['id'] == $conv['id']) ? 'bg-white fw-semibold' : '' ?> hover-bg">
                    <div class="small text-truncate"><?= e($conv['title'] ?? 'Chat ' . $conv['id']) ?></div>
                    <div class="text-muted" style="font-size:.7rem"><?= time_ago($conv['updated_at']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat area -->
        <div class="col-md-9 d-flex flex-column">
            <!-- Header -->
            <div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
                <i class="bi bi-chat-heart text-primary fs-4"></i>
                <div>
                    <div class="fw-semibold">Skincare Assistant</div>
                    <div class="text-muted small">
                        <?php if (AI_PROVIDER !== 'none'): ?>
                        Powered by AI (<?= e(AI_PROVIDER) ?>) &bull; Always honest about limitations
                        <?php else: ?>
                        Knowledge-base &amp; rule-based — <span class="text-warning">No AI provider configured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= e(url('consultations.php')) ?>" class="btn btn-outline-primary btn-sm ms-auto">
                    <i class="bi bi-person-heart"></i> Talk to a human
                </a>
            </div>

            <!-- Disclaimer -->
            <div class="alert alert-warning rounded-0 mb-0 py-1 px-3 small border-0 border-bottom">
                <i class="bi bi-exclamation-triangle"></i> <?= e(AI_DISCLAIMER) ?>
            </div>

            <!-- Messages -->
            <div class="flex-grow-1 overflow-auto p-3 chat-messages" id="chatMessages">
                <?php if (!$messages): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-chat-heart fs-1"></i>
                    <p class="mt-2">Ask me anything about skincare, ingredients, or product recommendations!</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <?php foreach (['What moisturizer suits oily skin?', 'What does niacinamide do?', 'How do I build a routine?'] as $q): ?>
                        <button class="btn btn-outline-secondary btn-sm suggestion-btn" data-text="<?= e($q) ?>"><?= e($q) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <div class="message-row d-flex <?= $msg['role'] === 'user' ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
                    <div class="message-bubble <?= $msg['role'] === 'user' ? 'bg-primary text-white' : 'bg-light border' ?> rounded-3 px-3 py-2"
                         style="max-width:75%">
                        <div class="small"><?= nl2br(e($msg['content'])) ?></div>
                        <?php if ($msg['role'] === 'assistant' && $msg['source']): ?>
                        <div class="mt-1" style="font-size:.65rem;opacity:.6">
                            Source: <?= $msg['source'] === 'ai' ? 'AI (' . e($msg['provider'] ?? 'AI') . ')' : humanize($msg['source']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <div id="typingIndicator" class="d-none text-muted small p-2">
                    <span class="spinner-border spinner-border-sm"></span> Thinking…
                </div>
            </div>

            <!-- Input -->
            <div class="p-3 border-top bg-white">
                <form id="chatForm" class="d-flex gap-2">
                    <input type="hidden" id="convId" value="<?= $conversation ? $conversation['id'] : '' ?>">
                    <input type="text" id="chatInput" class="form-control" placeholder="Ask about skincare…"
                           maxlength="1000" autocomplete="off" aria-label="Chat message">
                    <button type="submit" class="btn btn-primary px-3" id="sendBtn">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>