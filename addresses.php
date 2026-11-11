<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle  = 'My Addresses';
$addresses  = db_all('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC', [$authUser['id']]);

// Edit mode
$editing = null;
$editId  = get_int('edit');
if ($editId) {
    $editing = db_one('SELECT * FROM user_addresses WHERE id = ? AND user_id = ?', [$editId, $authUser['id']]);
    if (!$editing) $editId = 0;
}

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include _DIR_ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">My Addresses</h2>
                <?php if (!$editId): ?>
                <a href="?new=1" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add address</a>
                <?php endif; ?>
            </div>

            <?php if ($editId || get_int('new')): ?>
            <!-- Add / Edit form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header"><?= $editing ? 'Edit address' : 'Add new address' ?></div>
                <div class="card-body">
                    <form method="post" action="<?= e(url($editing ? 'actions/profile/address-save.php?id=' . $editing['id'] : 'actions/profile/address-save.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="label" class="form-label">Label</label>
                                <input type="text" id="label" name="label" class="form-control" value="<?= e(old('label', $editing['label'] ?? 'Home')) ?>" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_name" class="form-label">Recipient name</label>
                                <input type="text" id="recipient_name" name="recipient_name" required
                                       class="form-control<?= invalid_class('recipient_name') ?>"
                                       value="<?= e(old('recipient_name', $editing['recipient_name'] ?? $authUser['full_name'])) ?>" maxlength="120">
                                <?= invalid_feedback('recipient_name') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="addr_phone" class="form-label">Phone</label>
                                <input type="tel" id="addr_phone" name="phone" required
                                       class="form-control<?= invalid_class('phone') ?>"
                                       value="<?= e(old('phone', $editing['phone'] ?? $authUser['phone'] ?? '')) ?>" maxlength="30">
                                <?= invalid_feedback('phone') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" id="country" name="country" required class="form-control"
                                       value="<?= e(old('country', $editing['country'] ?? DEFAULT_COUNTRY)) ?>" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label for="address_line1" class="form-label">Address line 1</label>
                                <input type="text" id="address_line1" name="address_line1" required
                                       class="form-control<?= invalid_class('address_line1') ?>"
                                       value="<?= e(old('address_line1', $editing['address_line1'] ?? '')) ?>" maxlength="190">
                                <?= invalid_feedback('address_line1') ?>
                            </div>
                            <div class="col-12">
                                <label for="address_line2" class="form-label">Address line 2 <span class="text-muted">(optional)</span></label>
                                <input type="text" id="address_line2" name="address_line2" class="form-control"
                                       value="<?= e(old('address_line2', $editing['address_line2'] ?? '')) ?>" maxlength="190">
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" id="city" name="city" required
                                       class="form-control<?= invalid_class('city') ?>"
                                       value="<?= e(old('city', $editing['city'] ?? '')) ?>" maxlength="100">
                                <?= invalid_feedback('city') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State / Province</label>
                                <input type="text" id="state" name="state" class="form-control"
                                       value="<?= e(old('state', $editing['state'] ?? '')) ?>" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Postal code</label>
                                <input type="text" id="postal_code" name="postal_code" required
                                       class="form-control<?= invalid_class('postal_code') ?>"
                                       value="<?= e(old('postal_code', $editing['postal_code'] ?? '')) ?>" maxlength="20">
                                <?= invalid_feedback('postal_code') ?>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
                                           <?= old('is_default', $editing ? ($editing['is_default'] ? '1' : '0') : '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default">Set as default address</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save address</button>
                            <a href="<?= e(url('addresses.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Address list -->
            <?php if ($addresses): ?>
            <div class="row g-3">
                <?php foreach ($addresses as $addr): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 <?= $addr['is_default'] ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <?php if ($addr['is_default']): ?>
                            <span class="badge text-bg-primary mb-2">Default</span>
                            <?php endif; ?>
                            <h6 class="card-title mb-1"><?= e($addr['label']) ?></h6>
                            <p class="small text-muted mb-1"><?= e($addr['recipient_name']) ?></p>
                            <p class="small mb-1"><?= e($addr['address_line1']) ?><?= $addr['address_line2'] ? ', ' . e($addr['address_line2']) : '' ?></p>
                            <p class="small mb-1"><?= e($addr['city']) ?><?= $addr['state'] ? ', ' . e($addr['state']) : '' ?> <?= e($addr['postal_code']) ?></p>
                            <p class="small mb-0"><?= e($addr['country']) ?> &bull; <?= e($addr['phone']) ?></p>
                        </div>
                        <div class="card-footer bg-transparent d-flex gap-2">
                            <a href="?edit=<?= $addr['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                            <?php if (!$addr['is_default']): ?>
                            <form method="post" action="<?= e(url('actions/profile/address-default.php')) ?>" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $addr['id'] ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Set default</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('actions/profile/address-delete.php')) ?>"
                                  class="d-inline" onsubmit="return confirm('Delete this address?')">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $addr['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-geo-alt" style="font-size:3rem"></i>
                <p class="mt-2">No addresses saved yet.</p>
                <a href="?new=1" class="btn btn-primary">Add your first address</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>