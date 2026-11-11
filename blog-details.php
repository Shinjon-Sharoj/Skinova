<?php
require_once _DIR_ . '/includes/init.php';

$slug = get_str('slug');
if ($slug === '') not_found();

$post = db_one(
    "SELECT b.*, u.full_name AS author_name, bc.name AS category_name, bc.slug AS category_slug
     FROM blogs b
     LEFT JOIN users u ON u.id = b.author_id
     LEFT JOIN blog_categories bc ON bc.id = b.category_id
     WHERE b.slug = ? AND b.status = 'published'",
    [$slug]
);
if (!$post) not_found();

// Increment view count
db_exec('UPDATE blogs SET view_count = view_count + 1 WHERE id = ?', [$post['id']]);

// Comments
$comments = db_all(
    "SELECT bc.*, u.full_name, up.avatar FROM blog_comments bc
     JOIN users u ON u.id = bc.user_id
     LEFT JOIN user_profiles up ON up.user_id = bc.user_id
     WHERE bc.blog_id = ? AND bc.status = 'visible' ORDER BY bc.created_at ASC",
    [$post['id']]
);

// Like state
$liked = false;
if (is_logged_in()) {
    $liked = (bool) db_value('SELECT 1 FROM blog_likes WHERE blog_id = ? AND user_id = ?', [$post['id'], current_user_id()]);
}

// Related posts
$related = db_all(
    "SELECT b.id, b.title, b.slug, b.featured_image, b.published_at FROM blogs b
     WHERE b.status = 'published' AND b.id != ? AND b.category_id = ?
     ORDER BY b.published_at DESC LIMIT 3",
    [$post['id'], $post['category_id']]
);

$pageTitle = $post['title'];
$metaDescription = $post['excerpt'] ? truncate($post['excerpt'], 160) : truncate($post['content'], 160);

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:860px">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
            <?php if ($post['category_name']): ?>
            <li class="breadcrumb-item"><a href="<?= e(url('blog.php?category=' . urlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e(truncate($post['title'], 40)) ?></li>
        </ol>
    </nav>

    <!-- Featured image -->
    <?php if ($post['featured_image']): ?>
    <img src="<?= e(media_url($post['featured_image'], 'blog')) ?>" alt="<?= e($post['title']) ?>"
         class="img-fluid rounded mb-4 w-100" style="max-height:400px;object-fit:cover">
    <?php endif; ?>

    <!-- Meta -->
    <div class="d-flex flex-wrap gap-3 align-items-center text-muted small mb-2">
        <?php if ($post['category_name']): ?>
        <a href="<?= e(url('blog.php?category=' . urlencode($post['category_slug']))) ?>" class="badge text-bg-light border text-decoration-none">
            <?= e($post['category_name']) ?>
        </a>
        <?php endif; ?>
        <span><i class="bi bi-person"></i> <?= e($post['author_name']) ?></span>
        <span><i class="bi bi-calendar3"></i> <?= format_date($post['published_at']) ?></span>
        <span><i class="bi bi-eye"></i> <?= number_format($post['view_count']) ?> views</span>
    </div>

    <h1 class="h2 mb-3"><?= e($post['title']) ?></h1>

    <!-- Content -->
    <div class="blog-content mb-4">
        <?= sanitize_html($post['content']) ?>
    </div>

    <!-- Tags -->
    <?php if ($post['tags']): ?>
    <div class="mb-4">
        <?php foreach (explode(',', $post['tags']) as $tag): ?>
        <span class="badge text-bg-light border me-1"><?= e(trim($tag)) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Like -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <?php if (is_logged_in()): ?>
        <form method="post" action="<?= e(url('actions/blogs/like.php')) ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="blog_id" value="<?= $post['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $liked ? 'btn-danger' : 'btn-outline-danger' ?>">
                <i class="bi bi-heart<?= $liked ? '-fill' : '' ?>"></i>
                <?= $liked ? 'Unlike' : 'Like' ?> (<?= number_format($post['like_count']) ?>)
            </button>
        </form>
        <?php else: ?>
        <span class="text-muted small"><i class="bi bi-heart"></i> <?= number_format($post['like_count']) ?> likes</span>
        <?php endif; ?>
    </div>

    <!-- Comments -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">Comments (<?= count($comments) ?>)</div>
        <div class="card-body">
            <?php if ($comments): ?>
            <?php foreach ($comments as $c): ?>
            <div class="d-flex gap-3 mb-3">
                <img src="<?= e(media_url($c['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle flex-shrink-0" width="40" height="40" style="object-fit:cover" alt="">
                <div>
                    <div class="fw-semibold small"><?= e($c['full_name']) ?> <span class="text-muted fw-normal">&bull; <?= time_ago($c['created_at']) ?></span></div>
                    <p class="mb-0 small"><?= e($c['comment']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-muted small mb-0">No comments yet. Be the first!</p>
            <?php endif; ?>

            <?php if (is_logged_in()): ?>
            <hr>
            <form method="post" action="<?= e(url('actions/blogs/comment.php')) ?>" novalidate class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="blog_id" value="<?= $post['id'] ?>">
                <div class="mb-2">
                    <label for="comment" class="form-label small">Leave a comment</label>
                    <textarea id="comment" name="comment" class="form-control form-control-sm<?= invalid_class('comment') ?>"
                              rows="3" required maxlength="1500" placeholder="Share your thoughts…"></textarea>
                    <?= invalid_feedback('comment') ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Post comment</button>
            </form>
            <?php else: ?>
            <p class="small mt-3 mb-0"><a href="<?= e(url('login.php')) ?>">Sign in</a> to leave a comment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related -->
    <?php if ($related): ?>
    <h4 class="h5 mb-3">Related articles</h4>
    <div class="row g-3">
        <?php foreach ($related as $r): ?>
        <div class="col-md-4">
            <a href="<?= e(url('blog-details.php?slug=' . urlencode($r['slug']))) ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <img src="<?= e(media_url($r['featured_image'] ?? null, 'blog')) ?>" class="card-img-top" alt="" style="height:120px;object-fit:cover">
                    <div class="card-body py-2">
                        <p class="card-text small fw-semibold text-dark"><?= e(truncate($r['title'], 60)) ?></p>
                        <p class="card-text" style="font-size:.75rem;color:#888"><?= format_date($r['published_at']) ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>