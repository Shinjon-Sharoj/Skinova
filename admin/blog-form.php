<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Blog Post';
$panel     = 'admin';

$id   = get_int('id');
$post = $id ? db_one('SELECT * FROM blogs WHERE id=?', [$id]) : null;
if ($id && !$post) not_found();
$isEdit = (bool)$post;

$blogCats = db_all('SELECT * FROM blog_categories ORDER BY name');

if (is_post() && csrf_is_valid()) {
    $v = new Validator($_POST);
    $v->required('title','Title')->max('title',190,'Title');
    $v->required('content','Content');
    if ($v->errors()) { set_old($_POST,$v->errors()); redirect('admin/blog-form.php'.($id?'?id='.$id:'')); }

    $status = in_array(input('status'),['draft','published','archived'],true)?input('status'):'draft';
    $pubAt  = $status==='published' ? (input('published_at') ?: date('Y-m-d H:i:s')) : null;
    $slug   = unique_slug('blogs', input('title'), $isEdit?$id:null);

    $featImg = $isEdit ? $post['featured_image'] : null;
    if (!empty($_FILES['featured_image']['size'])) {
        $r = save_image_upload($_FILES['featured_image'],'blogs');
        if (!isset($r['error'])) { if ($featImg) delete_upload($featImg); $featImg=$r['path']; }
    }

    $data = [
        input_int('category_id')?:null, (int)$authUser['id'],
        clean_text(input('title')), $slug, input('excerpt')?clean_text(input('excerpt')):null,
        sanitize_html(input('content')), $featImg, input('tags')?clean_text(input('tags')):null,
        isset($_POST['is_tip'])?1:0, $status, $pubAt,
    ];

    if ($isEdit) { array_push($data,$id); db_exec('UPDATE blogs SET category_id=?,author_id=?,title=?,slug=?,excerpt=?,content=?,featured_image=?,tags=?,is_tip=?,status=?,published_at=? WHERE id=?',$data); }
    else { $id = db_insert('INSERT INTO blogs (category_id,author_id,title,slug,excerpt,content,featured_image,tags,is_tip,status,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',$data); }

    log_activity($isEdit?'admin_blog_updated':'admin_blog_created','blog',$id,null,(int)$authUser['id']);
    flash('success','Blog post saved.');
    redirect('admin/blog-form.php?id='.$id);
}

$pageTitle = $isEdit ? 'Edit: '.truncate($post['title'],30) : 'New Blog Post';
include __DIR__ . '/../admin/includes/header.php';
admin_page_header($pageTitle,
    '<a href="' . e(url('admin/blogs.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Posts</a>'
    . ($isEdit && $post['status']==='published' ? ' <a href="' . e(url('blog-details.php?slug='.urlencode($post['slug']))) . '" class="btn btn-outline-info btn-sm" target="_blank"><i class="bi bi-eye"></i> View</a>' : '')
);
?>
<form method="post" enctype="multipart/form-data" novalidate class="needs-validation">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input name="title" class="form-control<?= invalid_class('title') ?>" required maxlength="190" value="<?= e(old('title',$post['title']??'')) ?>">
                        <?= invalid_feedback('title') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="400"><?= e(old('excerpt',$post['excerpt']??'')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content *</label>
                        <textarea name="content" class="form-control<?= invalid_class('content') ?>" rows="16" required><?= e(old('content',$post['content']??'')) ?></textarea>
                        <?= invalid_feedback('content') ?>
                        <div class="form-text">HTML allowed (safe tags only). Markdown not supported.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Publishing</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['draft','published','archived'] as $s): ?>
                            <option value="<?= $s ?>" <?= old('status',$post['status']??'draft')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Publish date</label>
                        <input type="datetime-local" name="published_at" class="form-control form-control-sm" value="<?= e(old('published_at',($post['published_at']??'') ? str_replace(' ','T',substr($post['published_at'],0,16)) : '')) ?>">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_tip" id="isTip" value="1" <?= old('is_tip',$post['is_tip']??'0')?'checked':'' ?>>
                        <label class="form-check-label small" for="isTip">Skincare tip (short post)</label>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Organisation</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Category</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            <?php foreach ($blogCats as $bc): ?>
                            <option value="<?= $bc['id'] ?>" <?= old('category_id',$post['category_id']??'')==$bc['id']?'selected':'' ?>><?= e($bc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Tags <span class="text-muted">(comma-separated)</span></label>
                        <input name="tags" class="form-control form-control-sm" maxlength="255" value="<?= e(old('tags',$post['tags']??'')) ?>" placeholder="skincare, routine, tips">
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Featured image</div>
                <div class="card-body">
                    <?php if ($isEdit && $post['featured_image']): ?>
                    <img src="<?= e(media_url($post['featured_image'],'blog')) ?>" class="img-fluid rounded mb-2" alt="">
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control form-control-sm" accept="image/*" data-preview="featPreview">
                    <img id="featPreview" src="" class="img-fluid rounded mt-2 d-none" alt="">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Save post</button>
        </div>
    </div>
</form>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>
