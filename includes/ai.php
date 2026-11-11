<?php
/**
 * AI integration helpers.
 * AI_PROVIDER = 'none' | 'openai' | 'gemini'
 *
 * All functions degrade gracefully when AI_PROVIDER = 'none':
 *   - Recommendations: rule-based engine
 *   - Routine:         rule-based engine
 *   - Chatbot:         knowledge-base lookup
 *   - Ingredient/skin: image-metrics / DB data
 *
 * Source labelling (enforced):
 *   'ai'            – provider returned a real AI response
 *   'knowledge_base'– matched a KB row
 *   'rule_based'    – deterministic rule engine
 *   'image_metrics' – pixel-level colour analysis, no AI
 *   'fallback'      – nothing matched
 */

// ---------------------------------------------------------------------------
// Low-level HTTP call to OpenAI-compatible endpoint
// ---------------------------------------------------------------------------

/**
 * @param array $messages [['role'=>'user','content'=>'...']]
 * @param bool  $vision   true to use AI_VISION_MODEL
 * @return array{ok:bool, content:string, provider:string, error?:string}
 */
function ai_chat(array $messages, bool $vision = false): array
{
    if (AI_PROVIDER === 'none' || AI_API_KEY === '') {
        return ['ok' => false, 'content' => '', 'provider' => 'none', 'error' => 'No AI provider configured.'];
    }

    $model = $vision ? AI_VISION_MODEL : AI_MODEL;

    if (AI_PROVIDER === 'gemini') {
        return ai_chat_gemini($messages, $model);
    }

    // OpenAI-compatible
    $payload = json_encode(['model' => $model, 'messages' => $messages, 'max_tokens' => 1024, 'temperature' => 0.5]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAuthorization: Bearer " . AI_API_KEY . "\r\n",
        'content' => $payload,
        'timeout' => AI_TIMEOUT,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(rtrim(AI_BASE_URL, '/') . '/chat/completions', false, $ctx);
    if ($raw === false) {
        return ['ok' => false, 'content' => '', 'provider' => 'openai', 'error' => 'HTTP request failed.'];
    }
    $data = json_decode($raw, true);
    if (!empty($data['choices'][0]['message']['content'])) {
        return ['ok' => true, 'content' => trim($data['choices'][0]['message']['content']), 'provider' => 'openai'];
    }
    return ['ok' => false, 'content' => '', 'provider' => 'openai', 'error' => $data['error']['message'] ?? 'Unknown error.'];
}

function ai_chat_gemini(array $messages, string $model): array
{
    $parts = [];
    foreach ($messages as $m) {
        if ($m['role'] !== 'system') {
            $parts[] = ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]];
        }
    }
    $payload = json_encode(['contents' => $parts]);
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . AI_API_KEY;
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $payload,
        'timeout' => AI_TIMEOUT,
        'ignore_errors' => true,
    ]]);
    $raw  = @file_get_contents($url, false, $ctx);
    $data = $raw ? json_decode($raw, true) : null;
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($text) {
        return ['ok' => true, 'content' => trim($text), 'provider' => 'gemini'];
    }
    return ['ok' => false, 'content' => '', 'provider' => 'gemini', 'error' => 'No response from Gemini.'];
}

// ---------------------------------------------------------------------------
// Knowledge-base lookup
// ---------------------------------------------------------------------------

/**
 * Search the KB by FULLTEXT + keyword fallback.
 * @return array{row:?array, source:'knowledge_base'|'fallback', hit:bool}
 */
function kb_lookup(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return ['row' => null, 'source' => 'fallback', 'hit' => false];
    }
    // FULLTEXT
    $row = db_one(
        "SELECT * FROM ai_knowledge_base WHERE is_active = 1
         AND MATCH(question, answer, keywords) AGAINST(? IN BOOLEAN MODE)
         ORDER BY is_sensitive ASC, hit_count DESC LIMIT 1",
        [$query . '*']
    );
    if (!$row) {
        // Fallback: token-based LIKE
        $tokens = array_filter(array_map('trim', explode(' ', strtolower($query))));
        foreach ($tokens as $tok) {
            if (strlen($tok) < 3) continue;
            $like = '%' . addcslashes($tok, '%_\\') . '%';
            $row  = db_one(
                "SELECT * FROM ai_knowledge_base WHERE is_active = 1
                 AND (question LIKE ? OR keywords LIKE ?) ORDER BY hit_count DESC LIMIT 1",
                [$like, $like]
            );
            if ($row) break;
        }
    }
    if ($row) {
        db_exec('UPDATE ai_knowledge_base SET hit_count = hit_count + 1 WHERE id = ?', [$row['id']]);
        return ['row' => $row, 'source' => 'knowledge_base', 'hit' => true];
    }
    return ['row' => null, 'source' => 'fallback', 'hit' => false];
}

// ---------------------------------------------------------------------------
// Chatbot
// ---------------------------------------------------------------------------

/**
 * Process a chatbot message. Returns the assistant reply row data.
 * @return array{content:string, source:string, provider:?string, meta:?string, escalate:bool}
 */
function chatbot_reply(string $userMessage, array $conversationHistory, int $userId): array
{
    $userMessage = mb_substr(trim($userMessage), 0, 1000);
    $escalate    = false;

    // Try knowledge base first
    $kb = kb_lookup($userMessage);
    if ($kb['hit'] && $kb['row']) {
        $row    = $kb['row'];
        $meta   = null;
        if ($row['is_sensitive']) {
            $escalate = true;
            $meta = json_encode(['escalation' => true, 'reason' => 'sensitive_topic']);
        }
        // Suggest products from KB category
        $suggested = [];
        if (in_array($row['category'], ['products', 'ingredients'], true)) {
            $prods = get_best_sellers(3);
            foreach ($prods as $p) {
                $suggested[] = ['id' => $p['id'], 'name' => $p['name'], 'url' => product_url($p), 'price' => money($p['final_price'])];
            }
            if ($suggested) {
                $meta = json_encode(['suggested_products' => $suggested] + ($escalate ? ['escalation' => true] : []));
            }
        }
        return [
            'content'  => $row['answer'] . ($escalate ? "\n\n⚠️ " . AI_DISCLAIMER . "\n\n[Talk to a human consultant](" . url('consultations.php?topic=' . urlencode($userMessage)) . ")" : ''),
            'source'   => 'knowledge_base',
            'provider' => null,
            'meta'     => $meta,
            'escalate' => $escalate,
        ];
    }

    // Try AI provider
    if (AI_PROVIDER !== 'none' && AI_API_KEY !== '') {
        $profile    = get_skin_profile($userId);
        $systemMsg  = "You are a friendly, knowledgeable skincare assistant for " . APP_NAME . ". "
            . "Give clear, practical skincare advice. Never diagnose medical conditions. "
            . "Always recommend consulting a professional for serious skin issues. "
            . "Keep responses concise and helpful. "
            . ($profile['profile'] ? "The user has " . ($profile['profile']['skin_type'] ?? 'unknown') . " skin type." : "");

        $messages = [['role' => 'system', 'content' => $systemMsg]];
        foreach (array_slice($conversationHistory, -6) as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $res = ai_chat($messages);
        if ($res['ok']) {
            return [
                'content'  => $res['content'] . "\n\n*Source: AI (" . $res['provider'] . "). " . AI_DISCLAIMER . "*",
                'source'   => 'ai',
                'provider' => $res['provider'],
                'meta'     => null,
                'escalate' => false,
            ];
        }
    }

    // Fallback
    return [
        'content'  => "I don't have a specific answer for that right now. For personalised skincare advice, I recommend [booking a consultation](" . url('consultations.php') . ") with one of our certified skincare professionals. You can also browse our [FAQ](" . url('faq.php') . ").",
        'source'   => 'fallback',
        'provider' => null,
        'meta'     => json_encode(['escalation' => true]),
        'escalate' => true,
    ];
}

// ---------------------------------------------------------------------------
// Recommendations (rule-based engine)
// ---------------------------------------------------------------------------

/**
 * Generate personalised product recommendations for a user.
 * Saves to ai_recommendations and returns the product rows.
 * @return array{products:array, source:string, provider:?string}
 */
function generate_recommendations(int $userId, string $recType = 'personalized', int $limit = 8): array
{
    $profile = get_skin_profile($userId);
    $sp      = $profile['profile'];
    $source  = 'rule_based';
    $provider = null;

    if (!$sp || !$sp['consent_given']) {
        // No profile: return best sellers
        $products = get_best_sellers($limit);
        return ['products' => $products, 'source' => $source, 'provider' => $provider];
    }

    $f = ['sort' => 'rating', 'in_stock' => true];
    if ($sp['skin_type']) $f['skin_type'] = $sp['skin_type'];
    if ($sp['budget_max']) $f['max_price'] = (float) $sp['budget_max'];
    if ($sp['budget_min']) $f['min_price'] = (float) $sp['budget_min'];

    // First concern
    if (!empty($profile['concerns'])) {
        $f['concern'] = $profile['concerns'][0]['slug'];
    }

    $result = get_products($f, 1, $limit * 2);
    $products = $result['items'];

    // Exclude products with user's sensitive ingredients
    if ($profile['sensitivities']) {
        $sensIds = array_column($profile['sensitivities'], 'id');
        $products = array_filter($products, function ($p) use ($sensIds) {
            $ingr = db_all('SELECT ingredient_id FROM product_ingredients WHERE product_id = ?', [$p['id']]);
            foreach ($ingr as $i) {
                if (in_array((int) $i['ingredient_id'], $sensIds, true)) return false;
            }
            return true;
        });
    }

    $products = array_slice(array_values($products), 0, $limit);

    // Try AI for enhanced recommendations
    if (AI_PROVIDER !== 'none' && AI_API_KEY !== '' && $sp) {
        $concerns = implode(', ', array_column($profile['concerns'], 'name'));
        $productNames = implode(', ', array_column(array_slice($products, 0, 5), 'name'));
        $prompt = "A customer has {$sp['skin_type']} skin. Concerns: {$concerns}. Budget: " . ($sp['budget_min'] ?? '0') . "-" . ($sp['budget_max'] ?? 'any') . ". "
            . "From these products: {$productNames}. In 2-3 sentences, explain why these are good for them. Be specific and helpful.";
        $res = ai_chat([['role' => 'user', 'content' => $prompt]]);
        if ($res['ok']) {
            $source   = 'ai';
            $provider = $res['provider'];
        }
    }

    // Save batch
    if ($products) {
        $batchKey = substr(bin2hex(random_bytes(8)), 0, 16);
        foreach ($products as $idx => $p) {
            db_exec(
                'INSERT INTO ai_recommendations (user_id, batch_key, product_id, rec_type, score, source, provider, status)
                 VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)',
                [$userId, $batchKey, $p['id'], $recType, round(100 - $idx * 5, 2), $source, $provider, 'active']
            );
        }
    }

    return ['products' => $products, 'source' => $source, 'provider' => $provider];
}

// ---------------------------------------------------------------------------
// Routine generator (rule-based)
// ---------------------------------------------------------------------------

/**
 * Generate a skincare routine for a user based on their skin profile.
 * Returns the saved skincare_routines row id.
 */
function generate_routine(int $userId): ?int
{
    $profile = get_skin_profile($userId);
    $sp      = $profile['profile'];

    if (!$sp || !$sp['consent_given']) return null;

    $skinType    = $sp['skin_type'] ?? 'normal';
    $concerns    = array_column($profile['concerns'], 'name');
    $complexity  = $sp['routine_complexity'] ?? 'standard';
    $budgetMax   = $sp['budget_max'] ? (float) $sp['budget_max'] : null;

    // Step definitions per complexity
    $morningSteps = ['cleanser', 'toner', 'serum', 'moisturizer', 'sunscreen'];
    $eveningSteps = ['cleanser', 'toner', 'serum', 'treatment', 'moisturizer'];
    if ($complexity === 'minimal') {
        $morningSteps = ['cleanser', 'moisturizer', 'sunscreen'];
        $eveningSteps = ['cleanser', 'moisturizer'];
    } elseif ($complexity === 'extended') {
        $morningSteps = ['cleanser', 'toner', 'serum', 'eye_care', 'moisturizer', 'sunscreen'];
        $eveningSteps = ['cleanser', 'toner', 'treatment', 'serum', 'eye_care', 'moisturizer'];
    }

    $allSteps   = array_unique(array_merge($morningSteps, $eveningSteps));
    $routineCost = 0.0;

    // Fetch best product per step matching skin type
    $stepProducts = [];
    foreach ($allSteps as $step) {
        $f = [
            'sort'       => 'rating',
            'in_stock'   => true,
            'skin_type'  => $skinType,
        ];
        if ($budgetMax) $f['max_price'] = $budgetMax / count($allSteps) * 2; // per-step budget hint

        $res = get_products(array_merge($f, ['include_inactive' => false]), 1, 5);
        // Filter by routine_step
        $matching = array_filter($res['items'], fn($p) => $p['routine_step'] === $step);
        $prod = array_values($matching)[0] ?? null;

        // fallback without skin_type filter
        if (!$prod) {
            $res2 = get_products(['sort' => 'rating', 'in_stock' => true], 1, 20);
            foreach ($res2['items'] as $item) {
                if ($item['routine_step'] === $step) { $prod = $item; break; }
            }
        }

        $stepProducts[$step] = $prod;
        if ($prod) $routineCost += (float) $prod['final_price'];
    }

    $concernsSummary = implode(', ', array_slice($concerns, 0, 5));
    $source  = 'rule_based';
    $provider = null;
    $precautions = "Always patch test new products. Introduce one new product at a time.";

    // AI enhancement
    if (AI_PROVIDER !== 'none' && AI_API_KEY !== '' && $concerns) {
        $stepList = implode(', ', array_filter(array_map(fn($s) => $stepProducts[$s] ? "$s ({$stepProducts[$s]['name']})" : null, $allSteps)));
        $prompt = "Create a brief skincare routine explanation for someone with {$skinType} skin and concerns: {$concernsSummary}. "
            . "Products: {$stepList}. Include key precautions in 2 sentences. Keep it friendly and practical.";
        $res = ai_chat([['role' => 'user', 'content' => $prompt]]);
        if ($res['ok']) {
            $precautions = $res['content'];
            $source   = 'ai';
            $provider = $res['provider'];
        }
    }

    $routineId = db_insert(
        'INSERT INTO skincare_routines (user_id, title, skin_type, concerns_summary, budget_max, total_cost, generation_source, ai_provider, precautions)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [$userId, ucfirst($skinType) . ' skin routine', $skinType, $concernsSummary, $budgetMax, $routineCost, $source, $provider, $precautions]
    );

    // Insert morning steps
    foreach ($morningSteps as $order => $step) {
        $prod = $stepProducts[$step] ?? null;
        db_insert(
            'INSERT INTO routine_steps (routine_id, period, step_order, step_name, product_id, instructions, reason) VALUES (?,?,?,?,?,?,?)',
            [$routineId, 'morning', $order + 1, ucfirst(str_replace('_', ' ', $step)),
             $prod ? $prod['id'] : null,
             $prod ? ($prod['usage_instructions'] ?? 'Apply as directed.') : 'Choose a product suitable for your skin type.',
             $prod ? "Recommended for {$skinType} skin." : null]
        );
    }

    // Insert evening steps
    foreach ($eveningSteps as $order => $step) {
        $prod = $stepProducts[$step] ?? null;
        db_insert(
            'INSERT INTO routine_steps (routine_id, period, step_order, step_name, product_id, instructions, reason) VALUES (?,?,?,?,?,?,?)',
            [$routineId, 'evening', $order + 1, ucfirst(str_replace('_', ' ', $step)),
             $prod ? $prod['id'] : null,
             $prod ? ($prod['usage_instructions'] ?? 'Apply as directed.') : 'Choose a product suitable for your skin type.',
             $prod ? "Recommended for {$skinType} skin." : null]
        );
    }

    log_activity('routine_generated', 'user', $userId, 'source=' . $source);
    return $routineId;
}

// ---------------------------------------------------------------------------
// Skin image analysis (image_metrics fallback, no AI vision)
// ---------------------------------------------------------------------------

/**
 * Analyse a skin image using basic GD colour metrics.
 * Returns an array of detected signals (never presented as medical diagnosis).
 */
function analyse_skin_image_metrics(string $imagePath): array
{
    if (!is_file($imagePath) || !function_exists('imagecreatefromjpeg')) {
        return ['error' => 'Image processing not available.'];
    }
    $info = @getimagesize($imagePath);
    if (!$info) return ['error' => 'Could not read image.'];

    switch ($info[2]) {
        case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($imagePath); break;
        case IMAGETYPE_PNG:  $img = @imagecreatefrompng($imagePath);  break;
        case IMAGETYPE_WEBP: $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($imagePath) : false; break;
        default: return ['error' => 'Unsupported image format.'];
    }
    if (!$img) return ['error' => 'Could not decode image.'];

    $w = imagesx($img); $h = imagesy($img);
    $totalR = $totalG = $totalB = 0;
    $samples = 0;
    $step = max(1, (int) ($w / 40));

    for ($x = 0; $x < $w; $x += $step) {
        for ($y = 0; $y < $h; $y += $step) {
            $rgb = imagecolorat($img, $x, $y);
            $totalR += ($rgb >> 16) & 0xFF;
            $totalG += ($rgb >> 8) & 0xFF;
            $totalB += $rgb & 0xFF;
            $samples++;
        }
    }
    imagedestroy($img);

    if ($samples === 0) return ['error' => 'Image sampling failed.'];

    $avgR = $totalR / $samples;
    $avgG = $totalG / $samples;
    $avgB = $totalB / $samples;

    // Heuristic signals (very approximate — labelled clearly as image metrics, not AI)
    $redness     = round(max(0, min(100, ($avgR - $avgG) / 2)));
    $oiliness    = round(max(0, min(100, ($avgR + $avgG + $avgB) / 7.65 - 30)));  // overall brightness proxy
    $unevenTone  = round(max(0, min(100, abs($avgR - $avgB) * 0.8)));

    return [
        'method'      => 'image_metrics',
        'redness'     => $redness,
        'oiliness'    => $oiliness,
        'uneven_tone' => $unevenTone,
        'avg_rgb'     => [$avgR, $avgG, $avgB],
        'disclaimer'  => AI_DISCLAIMER,
    ];
}

/**
 * Analyse skin image with AI vision (if provider configured).
 */
function analyse_skin_image_ai(string $base64Image, string $mimeType = 'image/jpeg'): array
{
    if (AI_PROVIDER === 'none' || AI_API_KEY === '') {
        return ['ok' => false, 'error' => 'No AI provider.'];
    }

    $prompt = "Analyse this facial skin image and identify visible skin characteristics such as: "
        . "signs of oiliness, dryness, redness, acne-like spots, uneven tone, or dark spots. "
        . "Respond with a JSON object: {\"oiliness\":0-100, \"redness\":0-100, \"uneven_tone\":0-100, \"acne_like_spots\":0-100, \"dark_spots\":0-100, \"summary\":\"...\"}. "
        . "IMPORTANT: You are not a dermatologist. State this is not a medical diagnosis.";

    $messages = [['role' => 'user', 'content' => [
        ['type' => 'text', 'text' => $prompt],
        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64Image}", 'detail' => 'low']],
    ]]];

    $res = ai_chat($messages, vision: true);
    if (!$res['ok']) return ['ok' => false, 'error' => $res['error']];

    // Parse JSON from response
    preg_match('/\{.*\}/s', $res['content'], $m);
    $data = $m ? json_decode($m[0], true) : null;
    if (!$data) $data = ['summary' => $res['content']];

    return array_merge(['ok' => true, 'method' => 'ai_vision', 'provider' => $res['provider']], $data);
}