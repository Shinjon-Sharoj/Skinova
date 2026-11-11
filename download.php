<?php
/**
 * Authorised streaming of PRIVATE files (prescriptions, chat attachments, skin images).
 * The files live in assets/uploads/{prescriptions,skin-images}, which Apache blocks directly.
 *
 *   download.php?type=prescription&id=12
 *   download.php?type=message&id=40        (consultation chat attachment)
 *   download.php?type=skin&id=7            (skin analysis image)
 *   add &dl=1 to force a download instead of inline display
 */
require_once _DIR_ . '/includes/init.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    exit('Please log in.');
}
$type = get_str('type');
$id = get_int('id');
$path = null;
$name = 'file';
$allowed = false;

/** Is the current user a participant (customer or consultant) of the consultation? */
$isParticipant = function (int $consultationId) use ($user): bool {
    $c = db_one(
        'SELECT c.user_id, cs.user_id AS consultant_user_id FROM consultations c JOIN consultants cs ON cs.id = c.consultant_id WHERE c.id = ?',
        [$consultationId]
    );
    return $c && ((int) $c['user_id'] === (int) $user['id'] || (int) $c['consultant_user_id'] === (int) $user['id']);
};

switch ($type) {
    case 'prescription':
        $row = db_one('SELECT * FROM prescriptions WHERE id = ?', [$id]);
        if ($row) {
            $path = $row['file_path'];
            $name = $row['original_name'];
            $allowed = $user['role'] === ROLE_ADMIN
                || (int) $row['user_id'] === (int) $user['id']
                || (int) $row['uploaded_by'] === (int) $user['id']
                || ($row['consultation_id'] && $isParticipant((int) $row['consultation_id']));
        }
        break;

    case 'message':
        $row = db_one('SELECT * FROM consultation_messages WHERE id = ? AND attachment_path IS NOT NULL', [$id]);
        if ($row) {
            $path = $row['attachment_path'];
            $name = $row['attachment_name'] ?: 'attachment';
            $allowed = $user['role'] === ROLE_ADMIN || $isParticipant((int) $row['consultation_id']);
        }
        break;

    case 'skin':
        $row = db_one('SELECT * FROM skin_analyses WHERE id = ?', [$id]);
        if ($row) {
            $path = $row['image_path'];
            $name = 'skin-image-' . $row['id'];
            $allowed = $user['role'] === ROLE_ADMIN || (int) $row['user_id'] === (int) $user['id'];
        }
        break;
}

if (!$path) {
    http_response_code(404);
    exit('File not found.');
}
if (!$allowed) {
    log_activity('file_access_denied', $type, $id);
    http_response_code(403);
    exit('You do not have permission to view this file.');
}

$clean = ltrim(str_replace('\\', '/', $path), '/');
$full = realpath(APP_ROOT . '/' . $clean);
$uploadsRoot = realpath(UPLOAD_DIR);
if ($full === false || $uploadsRoot === false || strpos($full, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) {
    http_response_code(404);
    exit('File not found.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($full) ?: 'application/octet-stream';
if (!isset(ALLOWED_DOCUMENT_MIMES[$mime])) {
    http_response_code(415);
    exit('Unsupported file type.');
}
$ext = ALLOWED_DOCUMENT_MIMES[$mime];
$downloadName = preg_replace('/[^\w.\- ]/', '_', pathinfo($name, PATHINFO_FILENAME)) . '.' . $ext;
$disposition = get_int('dl') === 1 ? 'attachment' : 'inline';

log_activity('file_access', $type, $id);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: ' . $disposition . '; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
readfile($full);
exit;