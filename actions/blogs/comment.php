<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$blogId  = input_int('blog_id');
$comment = input('comment');

if (!rate_limit('blog_comment:' . $user['id'], 10, 600)) {
    flash('danger', 'You are posting comments too quickly. Please slow down.');
    redirect_back('blog.php');
}

$blog = db_one("SELECT id, slug FROM blogs WHERE id = ? AND status = 'published'", [$blogId]);
if (!$blog) not_found();

$v = new Validator($_POST);
$v->required('comment', 'Comment')->max('comment', 1500, 'Comment');
if ($v->errors()) {
    set_old($_POST, $v->errors());
    redirect('blog-details.php?slug=' . urlencode($blog['slug']));
}

db_insert(
    "INSERT INTO blog_comments (blog_id, user_id, comment, status) VALUES (?,?,?,'visible')",
    [$blogId, $user['id'], clean_text($comment)]
);
db_exec('UPDATE blogs SET comment_count = comment_count + 1 WHERE id = ?', [$blogId]);

flash('success', 'Comment posted!');
redirect('blog-details.php?slug=' . urlencode($blog['slug']));