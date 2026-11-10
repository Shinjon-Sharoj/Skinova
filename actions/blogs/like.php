<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$blogId = input_int('blog_id');
$blog   = db_one("SELECT id FROM blogs WHERE id = ? AND status = 'published'", [$blogId]);
if (!$blog) not_found();

$existing = db_value('SELECT 1 FROM blog_likes WHERE blog_id = ? AND user_id = ?', [$blogId, $user['id']]);
if ($existing) {
    db_exec('DELETE FROM blog_likes WHERE blog_id = ? AND user_id = ?', [$blogId, $user['id']]);
    db_exec('UPDATE blogs SET like_count = GREATEST(0, like_count - 1) WHERE id = ?', [$blogId]);
} else {
    db_exec('INSERT IGNORE INTO blog_likes (blog_id, user_id) VALUES (?,?)', [$blogId, $user['id']]);
    db_exec('UPDATE blogs SET like_count = like_count + 1 WHERE id = ?', [$blogId]);
}

redirect_back('blog.php');