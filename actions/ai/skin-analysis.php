<?php
require_once _DIR_ . '/../../includes/init.php';
require_once _DIR_ . '/../../includes/ai.php';
require_post();
csrf_verify();
$user = require_login();

if (!isset($_POST['consent'])) {
    flash('danger', 'You must give consent to analyse your skin image.');
    redirect('skin-analysis.php');
}

if (empty($_FILES['skin_image']['size'])) {
    flash('danger', 'Please select a skin photo to upload.');
    redirect('skin-analysis.php');
}

if (!rate_limit('skin_analysis:' . $user['id'], 5, 3600)) {
    flash('warning', 'Too many analyses submitted. Please wait before trying again.');
    redirect('skin-analysis.php');
}

// Save the image privately
$uploadResult = save_image_upload($_FILES['skin_image'], 'skin-images');
if (isset($uploadResult['error'])) {
    flash('danger', 'Upload failed: ' . $uploadResult['error']);
    redirect('skin-analysis.php');
}
$imagePath = $uploadResult['path'];

// Determine method and run analysis
$method   = 'image_metrics';
$provider = null;
$results  = [];
$summary  = '';

if (AI_PROVIDER !== 'none' && AI_API_KEY !== '') {
    // Try AI vision
    $fullPath  = APP_ROOT . '/' . ltrim($imagePath, '/');
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = $uploadResult['mime'] ?? 'image/jpeg';
    $aiResult  = analyse_skin_image_ai($imageData, $mimeType);
    if ($aiResult['ok'] ?? false) {
        $method   = 'ai_vision';
        $provider = $aiResult['provider'] ?? null;
        $results  = array_diff_key($aiResult, array_flip(['ok', 'method', 'provider']));
        $summary  = $aiResult['summary'] ?? '';
    }
}

if ($method === 'image_metrics') {
    $fullPath = APP_ROOT . '/' . ltrim($imagePath, '/');
    $metrics  = analyse_skin_image_metrics($fullPath);
    if (isset($metrics['error'])) {
        delete_upload($imagePath);
        flash('danger', 'Could not analyse image: ' . $metrics['error']);
        redirect('skin-analysis.php');
    }
    $results = $metrics;
    $summary = "Image metrics analysis (not AI). Detected approximate redness: {$metrics['redness']}%, oiliness: {$metrics['oiliness']}%, uneven tone: {$metrics['uneven_tone']}%. " . AI_DISCLAIMER;
}

$analysisId = db_insert(
    'INSERT INTO skin_analyses (user_id, image_path, method, provider, results, summary, consent_given) VALUES (?,?,?,?,?,?,1)',
    [$user['id'], $imagePath, $method, $provider, json_encode($results), $summary]
);

log_activity('skin_analysis', 'user', (int) $user['id'], 'method=' . $method);
flash('success', 'Analysis complete!');
redirect('skin-analysis.php?view=' . $analysisId);