<?php
/**
 * API: AI Chatbot
 * POST action=send              {message, conversation_id?} -> {content, source, provider, conversation_id}
 * POST action=new_conversation                              -> {conversation_id}
 * GET  action=history           ?conversation_id=          -> {messages}
 */
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/ai.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [60, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'send';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $convId = (int) ($in['conversation_id'] ?? 0);
    $conv   = db_one("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?", [$convId, $uid]);
    if (!$conv) api_fail('Conversation not found.', 404);
    $msgs = db_all('SELECT role, content, source, provider, created_at FROM ai_messages WHERE conversation_id = ? ORDER BY id', [$convId]);
    api_ok(['messages' => $msgs]);
}

// POST
if ($action === 'new_conversation') {
    $convId = db_insert(
        "INSERT INTO ai_conversations (user_id, title, channel) VALUES (?, 'New conversation', 'chatbot')",
        [$uid]
    );
    api_ok(['conversation_id' => $convId]);
}

if ($action === 'send') {
    $message = trim((string) ($in['message'] ?? ''));
    if ($message === '') api_fail('Message is required.', 422);

    if (!rate_limit('chatbot:' . $uid, 30, 60)) {
        api_fail('Slow down — too many messages. Please wait a moment.', 429);
    }

    // Ensure conversation exists
    $convId = (int) ($in['conversation_id'] ?? 0);
    if ($convId) {
        $conv = db_one("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?", [$convId, $uid]);
        if (!$conv) $convId = 0;
    }
    if (!$convId) {
        $title  = mb_substr($message, 0, 100);
        $convId = db_insert(
            "INSERT INTO ai_conversations (user_id, title, channel) VALUES (?, ?, 'chatbot')",
            [$uid, $title]
        );
    }

    // Load recent history
    $history = db_all(
        "SELECT role, content FROM ai_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 12",
        [$convId]
    );
    $history = array_reverse($history);

    // Save user message
    db_insert(
        "INSERT INTO ai_messages (conversation_id, role, content, source) VALUES (?, 'user', ?, 'user')",
        [$convId, mb_substr($message, 0, 1000)]
    );

    // Generate reply
    $reply = chatbot_reply($message, $history, $uid);

    // Save assistant reply
    db_insert(
        "INSERT INTO ai_messages (conversation_id, role, content, source, provider, meta) VALUES (?, 'assistant', ?, ?, ?, ?)",
        [$convId, mb_substr($reply['content'], 0, 5000), $reply['source'], $reply['provider'], $reply['meta']]
    );

    // Update conversation timestamp + auto-title
    db_exec('UPDATE ai_conversations SET updated_at = NOW() WHERE id = ?', [$convId]);

    // Log query
    db_insert(
        'INSERT INTO ai_query_logs (user_id, module, query_text, response_source, provider, status, escalated_to_human) VALUES (?,?,?,?,?,?,?)',
        [$uid, 'chatbot', mb_substr($message, 0, 500), $reply['source'], $reply['provider'], 'success', $reply['escalate'] ? 1 : 0]
    );

    api_ok([
        'content'         => $reply['content'],
        'source'          => $reply['source'],
        'provider'        => $reply['provider'],
        'conversation_id' => $convId,
        'escalate'        => $reply['escalate'],
    ]);
}

api_fail('Unknown action.', 400);