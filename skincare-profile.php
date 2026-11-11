<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle   = 'Skin Profile';
$allConcerns = get_skin_concerns();
$allIngred   = get_all_ingredients();
$profile     = get_skin_profile((int) $authUser['id']);
$sp          = $profile['profile'] ?? null;
$userConcerns= array_column($profile['concerns'] ?? [], null, 'skin_concern_id');
$userSens    = array_column($profile['sensitivities'] ?? [], 'ingredient_id');

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">My Skin Profile</h2>
                <?php if ($sp): ?>
                <a href="<?= e(url('recommendations.php')) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-stars"></i> View recommendations
                </a>
                <?php endif; ?>
            </div>

            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i>
                Your skin profile helps us provide personalised product recommendations and routine suggestions.
                <?= e(AI_DISCLAIMER) ?>
            </div>

            <form method="post" action="<?= e(url('actions/skincare/save-profile.php')) ?>" novalidate>
                <?= csrf_field() ?>

                <!-- Skin type -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Skin type</div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (SKIN_TYPES as $type): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="skin_<?= $type ?>" name="skin_type" value="<?= $type ?>"
                                       <?= old('skin_type', $sp['skin_type'] ?? '') === $type ? 'checked' : '' ?>>
                                <label class="form-check-label" for="skin_<?= $type ?>"><?= ucfirst($type) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Concerns -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Skin concerns</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($allConcerns as $concern): ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="concern_<?= $concern['id'] ?>"
                                           name="concerns[]" value="<?= $concern['id'] ?>"
                                           <?= in_array($concern['id'], array_column($profile['concerns'] ?? [], 'skin_concern_id')) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="concern_<?= $concern['id'] ?>">
                                        <?= e($concern['name']) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Additional info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Additional information</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" id="age" name="age" class="form-control" min="10" max="120"
                                       value="<?= e(old('age', $sp['age'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="budget_min" class="form-label">Budget min (<?= e(CURRENCY_SYMBOL) ?>)</label>
                                <input type="number" id="budget_min" name="budget_min" class="form-control" min="0" step="0.01"
                                       value="<?= e(old('budget_min', $sp['budget_min'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="budget_max" class="form-label">Budget max (<?= e(CURRENCY_SYMBOL) ?>)</label>
                                <input type="number" id="budget_max" name="budget_max" class="form-control" min="0" step="0.01"
                                       value="<?= e(old('budget_max', $sp['budget_max'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="routine_complexity" class="form-label">Routine complexity</label>
                                <select id="routine_complexity" name="routine_complexity" class="form-select">
                                    <?php foreach (['minimal' => 'Minimal (2-3 steps)', 'standard' => 'Standard (4-5 steps)', 'extended' => 'Extended (6+ steps)'] as $val => $label): ?>
                                    <option value="<?= e($val) ?>" <?= old('routine_complexity', $sp['routine_complexity'] ?? 'standard') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="allergies" class="form-label">Known allergies / sensitivities <span class="text-muted">(optional)</span></label>
                                <input type="text" id="allergies" name="allergies" class="form-control" maxlength="500"
                                       value="<?= e(old('allergies', $sp['allergies'] ?? '')) ?>"
                                       placeholder="e.g. retinol, fragrance">
                            </div>
                            <div class="col-md-6">
                                <label for="preferences" class="form-label">Product preferences <span class="text-muted">(optional)</span></label>
                                <input type="text" id="preferences" name="preferences" class="form-control" maxlength="500"
                                       value="<?= e(old('preferences', $sp['preferences'] ?? '')) ?>"
                                       placeholder="e.g. vegan, fragrance-free, minimal ingredients">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingredient sensitivities -->
                <?php if ($allIngred): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Ingredient sensitivities <span class="text-muted small">(optional)</span></div>
                    <div class="card-body">
                        <p class="small text-muted">Flag any ingredients that cause you reactions so they're excluded from recommendations.</p>
                        <div class="row g-2" style="max-height:220px;overflow-y:auto">
                            <?php foreach ($allIngred as $ing): ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="ing_<?= $ing['id'] ?>"
                                           name="sensitivities[]" value="<?= $ing['id'] ?>"
                                           <?= in_array($ing['id'], $userSens) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="ing_<?= $ing['id'] ?>">
                                        <?= e($ing['name']) ?>
                                        <?php if ($ing['is_common_allergen']): ?><span class="badge text-bg-warning ms-1">Common allergen</span><?php endif; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Consent -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1"
                                   <?= ($sp && $sp['consent_given']) || old('consent') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="consent">
                                I consent to <?= e(APP_NAME) ?> storing and using my skin profile data to provide personalised recommendations.
                                I can update or delete this data at any time.
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">Save skin profile</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
