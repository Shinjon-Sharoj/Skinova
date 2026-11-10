<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'AI Knowledge Base';
$panel     = 'admin';

// Save entry
if (is_post() && csrf_is_valid() && input('action') === 'save') {
    $kid = input_int('id');
    $data = [
        input('category') ?: 'general', clean_text(input('question')), clean_text(input('answer')),
        input('keywords') ? clean_text(input('keywords')) : null,
        isset($_POST['is_faq']) ? 1 : 0, isset($_POST['is_sensitive']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($kid) { array_push($data,$kid); db_exec('UPDATE ai_knowledge_base SET category=?,question=?,answer=?,keywords=?,is_faq=?,is_sensitive=?,is_active=? WHERE id=?',$data); }
    else { array_push($data,(int)$authUser['id']); db_insert('INSERT INTO ai_knowledge_base (category,question,answer,keywords,is_faq,is_sensitive,is_active,created_by) VALUES (?,?,?,?,?,?,?,?)',$data); }
    log_activity('admin_kb_saved','ai_knowledge',$kid,null,(int)$authUser['id']);
    flash('success','Knowledge base entry saved.');
    redirect('admin/ai-knowledge.php');
}

$categories = ['general','ingredients','products','orders','delivery','payment','returns','safety'];
$cat     = get_str('category');
$q       = get_str('q');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($cat && in_array($cat, $categories, true)) { $where[]='category=?'; $params[]=$cat; }
if ($q) { $like='%'.addcslashes($q,'%_\\').'%'; $where[]='(question LIKE ? OR keywords LIKE ?)'; $params[]=$like; $params[]=$like; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total   = (int) db_value("SELECT COUNT(*) FROM ai_knowledge_base $whereSql", $params, 0);
$pg      = paginate($total, $perPage, $page);
$entries = db_all("SELECT * FROM ai_knowledge_base $whereSql ORDER BY category, id DESC LIMIT ? OFFSET ?", array_merge($params,[$pg['per_page'],$pg['offset']]));

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('AI Knowledge Base',
    '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kbModal" onclick="resetKb()"><i class="bi bi-plus-lg"></i> New entry</button>',
    $total . ' entries'
);
?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search question…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c ?>" <?= $cat===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Category</th><th>Question</th><th>FAQ</th><th>Sensitive</th><th>Hits</th><th>Active</th><th></th></tr></thead>
            <tbody>
                <?php if ($entries): foreach ($entries as $e): ?>
                <tr>
                    <td><span class="badge text-bg-light border"><?= e($e['category']) ?></span></td>
                    <td class="small"><?= e(truncate($e['question'],60)) ?></td>
                    <td><?= $e['is_faq']?'<i class="bi bi-check-circle-fill text-success"></i>':'' ?></td>
                    <td><?= $e['is_sensitive']?'<i class="bi bi-exclamation-triangle-fill text-warning"></i>':'' ?></td>
                    <td class="small"><?= number_format($e['hit_count']) ?></td>
                    <td><?= $e['is_active']?'<span class="badge text-bg-success">Yes</span>':'<span class="badge text-bg-secondary">No</span>' ?></td>
                    <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#kbModal" onclick="fillKb(<?= htmlspecialchars(json_encode($e),ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button></td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No entries found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>

<div class="modal fade" id="kbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="kbId" value="">
            <div class="modal-header"><h5 class="modal-title">Knowledge Base Entry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Category</label>
                        <select name="category" id="kbCat" class="form-select form-select-sm">
                            <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label small">Question *</label><input name="question" id="kbQ" class="form-control form-control-sm" required maxlength="300"></div>
                    <div class="col-12"><label class="form-label small">Answer *</label><textarea name="answer" id="kbA" class="form-control form-control-sm" rows="5" required></textarea></div>
                    <div class="col-12"><label class="form-label small">Keywords <span class="text-muted">(comma-separated for matching)</span></label><input name="keywords" id="kbKw" class="form-control form-control-sm" maxlength="500"></div>
                    <div class="col-12 d-flex gap-3 flex-wrap">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_faq" id="kbFaq" value="1"><label class="form-check-label small" for="kbFaq">Show on FAQ page</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_sensitive" id="kbSens" value="1"><label class="form-check-label small" for="kbSens">Sensitive (escalate to consultant)</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="kbActive" value="1" checked><label class="form-check-label small" for="kbActive">Active</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save entry</button></div>
        </form>
    </div>
</div>
<script>
function resetKb(){document.getElementById('kbId').value='';['kbQ','kbA','kbKw'].forEach(id=>document.getElementById(id).value='');document.getElementById('kbCat').value='general';document.getElementById('kbFaq').checked=false;document.getElementById('kbSens').checked=false;document.getElementById('kbActive').checked=true;}
function fillKb(d){document.getElementById('kbId').value=d.id;document.getElementById('kbCat').value=d.category;document.getElementById('kbQ').value=d.question;document.getElementById('kbA').value=d.answer;document.getElementById('kbKw').value=d.keywords||'';document.getElementById('kbFaq').checked=d.is_faq==1;document.getElementById('kbSens').checked=d.is_sensitive==1;document.getElementById('kbActive').checked=d.is_active==1;}
</script>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>
