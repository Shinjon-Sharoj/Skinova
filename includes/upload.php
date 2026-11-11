<?php
/**
 * Secure file-upload handling.
 *
 * - validates PHP upload errors, size, real MIME type (finfo), extension map
 * - images are verified with getimagesize() and re-encoded with GD (strips EXIF/GPS and any embedded payload)
 * - files get random names and are stored under assets/uploads/<subdir>/
 * - 'prescriptions' and 'skin-images' are PRIVATE: .htaccess denies direct access
 *   and files are streamed through download.php after an authorisation check.
 *
 * Paths returned/stored are relative to the project root, e.g. "assets/uploads/reviews/ab12.jpg".
 */

/**
 * @return array{ok:bool,error:?string,path:?string,original_name:?string,mime:?string,size:?int}
 */
function save_uploaded_file(array $file, string $subdir, array $allowedMimes, int $maxBytes, bool $reencodeImages = true): array
{
    $fail = fn(string $msg) => ['ok' => false, 'error' => $msg, 'path' => null, 'original_name' => null, 'mime' => null, 'size' => null];

    if (!isset($file['error']) || is_array($file['error'])) {
        return $fail('Invalid upload.');
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_NO_FILE: return $fail('No file was uploaded.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return $fail('The file is too large.');
        default: return $fail('The upload failed. Please try again.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return $fail('Invalid upload.');
    }
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        return $fail('The file must be smaller than ' . round($maxBytes / 1048576, 1) . ' MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($file['tmp_name']);
    if (!isset($allowedMimes[$mime])) {
        return $fail('Unsupported file type. Allowed: ' . implode(', ', array_unique(array_values($allowedMimes))) . '.');
    }
    $ext = $allowedMimes[$mime];
    $isImage = strpos($mime, 'image/') === 0;
    if ($isImage && @getimagesize($file['tmp_name']) === false) {
        return $fail('The image file is corrupted or invalid.');
    }

    $subdir = trim(str_replace(['\\', '..'], ['/', ''], $subdir), '/');
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return $fail('Upload directory is not writable.');
    }
    ensure_private_upload_guard($subdir);

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    $saved = false;
    if ($isImage && $reencodeImages && function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($img !== false) {
            if ($mime === 'image/jpeg') {
                $saved = imagejpeg($img, $dest, 88);
            } elseif ($mime === 'image/png') {
                imagesavealpha($img, true);
                $saved = imagepng($img, $dest, 6);
            } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                $saved = imagewebp($img, $dest, 85);
            }
            imagedestroy($img);
        }
    }
    if (!$saved) {
        $saved = move_uploaded_file($file['tmp_name'], $dest);
    }
    if (!$saved) {
        return $fail('Could not save the file.');
    }
    @chmod($dest, 0644);

    return [
        'ok' => true,
        'error' => null,
        'path' => 'assets/uploads/' . $subdir . '/' . $name,
        'original_name' => mb_substr(preg_replace('/[^\w.\- ]/u', '_', basename((string) $file['name'])), 0, 180),
        'mime' => $mime,
        'size' => (int) filesize($dest),
    ];
}

/** Image only (jpg/png/webp, 5 MB). */
function save_image_upload(array $file, string $subdir): array
{
    return save_uploaded_file($file, $subdir, ALLOWED_IMAGE_MIMES, MAX_IMAGE_BYTES, true);
}

/** PDF or image (8 MB) - for prescriptions / chat attachments. */
function save_document_upload(array $file, string $subdir): array
{
    return save_uploaded_file($file, $subdir, ALLOWED_DOCUMENT_MIMES, MAX_DOCUMENT_BYTES, true);
}

/**
 * Turn the awkward $_FILES['x'] multi-upload structure into a list of single files.
 * @return array<int,array>
 */
function normalize_files_array(?array $files): array
{
    if (!$files || !isset($files['name'])) return [];
    if (!is_array($files['name'])) return [$files];
    $out = [];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
    }
    return $out;
}

/** Delete a stored upload (only inside assets/uploads or assets/images). */
function delete_upload(?string $path): void
{
    if (!$path) return;
    $clean = ltrim(str_replace('\\', '/', $path), '/');
    if (strpos($clean, '..') !== false || !preg_match('#^assets/(uploads|images)/#', $clean)) return;
    $full = APP_ROOT . '/' . $clean;
    if (is_file($full)) @unlink($full);
}

/** Make sure private upload folders deny direct HTTP access (Apache). */
function ensure_private_upload_guard(string $subdir): void
{
    $top = explode('/', $subdir)[0];
    if (!in_array($top, PRIVATE_UPLOAD_SUBDIRS, true)) return;
    $ht = UPLOAD_DIR . '/' . $top . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "# Private files - served only through download.php\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
    }
}

/** URL for a private file (authorised streaming via download.php). $type: prescription | message | skin */
function private_file_url(string $type, int $id): string
{
    return url('download.php?type=' . urlencode($type) . '&id=' . $id);
}