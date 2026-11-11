<?php
/**
 * Consultation & messaging helpers.
 */

// ---------------------------------------------------------------------------
// Consultants
// ---------------------------------------------------------------------------

function get_consultant_by_user_id(int $userId): ?array
{
    return db_one(
        'SELECT c.*, u.full_name, u.email, u.phone, up.avatar
         FROM consultants c
         JOIN users u ON u.id = c.user_id
         LEFT JOIN user_profiles up ON up.user_id = c.user_id
         WHERE c.user_id = ?',
        [$userId]
    );
}

function get_consultant_by_id(int $consultantId): ?array
{
    return db_one(
        'SELECT c.*, u.full_name, u.email, u.phone, up.avatar
         FROM consultants c
         JOIN users u ON u.id = c.user_id
         LEFT JOIN user_profiles up ON up.user_id = c.user_id
         WHERE c.id = ?',
        [$consultantId]
    );
}

function get_available_consultants(int $limit = 20): array
{
    return db_all(
        "SELECT c.*, u.full_name, u.email, up.avatar
         FROM consultants c
         JOIN users u ON u.id = c.user_id
         LEFT JOIN user_profiles up ON up.user_id = c.user_id
         WHERE c.status = 'active' AND c.is_available = 1
         ORDER BY c.rating_avg DESC, c.rating_count DESC LIMIT ?",
        [$limit]
    );
}

// ---------------------------------------------------------------------------
// Consultations
// ---------------------------------------------------------------------------

function get_consultation(int $id, ?int $userId = null, ?int $consultantUserId = null): ?array
{
    $sql = 'SELECT cn.*, u.full_name AS customer_name, u.email AS customer_email,
                   cu.full_name AS consultant_name, cu.email AS consultant_email,
                   c.specialization, c.consultation_fee, c.id AS consultant_id
            FROM consultations cn
            JOIN users u ON u.id = cn.user_id
            JOIN consultants c ON c.id = cn.consultant_id
            JOIN users cu ON cu.id = c.user_id
            WHERE cn.id = ?';
    $params = [$id];

    if ($userId !== null) {
        $sql .= ' AND cn.user_id = ?';
        $params[] = $userId;
    }
    if ($consultantUserId !== null) {
        $sql .= ' AND c.user_id = ?';
        $params[] = $consultantUserId;
    }
    return db_one($sql, $params);
}

function get_consultation_messages(int $consultationId, int $sinceId = 0): array
{
    return db_all(
        'SELECT m.*, u.full_name AS sender_name, up.avatar AS sender_avatar
         FROM consultation_messages m
         JOIN users u ON u.id = m.sender_id
         LEFT JOIN user_profiles up ON up.user_id = m.sender_id
         WHERE m.consultation_id = ? AND m.id > ?
         ORDER BY m.id ASC',
        [$consultationId, $sinceId]
    );
}

function mark_messages_read(int $consultationId, int $readerId): void
{
    db_exec(
        'UPDATE consultation_messages SET is_read = 1, read_at = NOW()
         WHERE consultation_id = ? AND sender_id != ? AND is_read = 0',
        [$consultationId, $readerId]
    );
}

function unread_message_count_for_user(int $userId): int
{
    return (int) db_value(
        'SELECT COUNT(*) FROM consultation_messages m
         JOIN consultations cn ON cn.id = m.consultation_id
         WHERE m.sender_id != ? AND m.is_read = 0
           AND (cn.user_id = ? OR cn.consultant_id IN (SELECT id FROM consultants WHERE user_id = ?))',
        [$userId, $userId, $userId],
        0
    );
}

function send_consultation_message(int $consultationId, int $senderId, string $message, ?string $attachmentPath = null, ?string $attachmentName = null): int
{
    $msgId = db_insert(
        'INSERT INTO consultation_messages (consultation_id, sender_id, message, attachment_path, attachment_name) VALUES (?,?,?,?,?)',
        [$consultationId, $senderId, mb_substr($message, 0, 10000), $attachmentPath, $attachmentName]
    );
    db_exec('UPDATE consultations SET updated_at = NOW() WHERE id = ?', [$consultationId]);
    return $msgId;
}

function refresh_consultant_rating(int $consultantId): void
{
    db_exec(
        "UPDATE consultants SET
            rating_avg   = COALESCE((SELECT ROUND(AVG(user_rating),2) FROM consultations WHERE consultant_id=? AND user_rating IS NOT NULL AND status='completed'), 0),
            rating_count = (SELECT COUNT(*) FROM consultations WHERE consultant_id=? AND user_rating IS NOT NULL AND status='completed')
         WHERE id = ?",
        [$consultantId, $consultantId, $consultantId]
    );
}