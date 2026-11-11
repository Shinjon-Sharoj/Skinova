<?php
/**
 * Input validation / sanitisation.
 *
 *   $v = new Validator($_POST);
 *   $v->required('email')->email('email')->max('email', 190);
 *   $v->required('password')->password('password');
 *   if ($v->fails()) { set_old($_POST, $v->errors()); redirect_back(); }
 *   $clean = $v->get('email');
 *
 * Output escaping is done separately with e(); SQL safety comes from prepared
 * statements - sanitising here only normalises text.
 */

/** Normalise free text: trim, strip control chars, optional max length. */
function clean_text($value, int $maxLength = 0): string
{
    $value = is_string($value) ? $value : '';
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    $value = trim($value);
    if ($maxLength > 0) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function is_valid_email(string $email): bool
{
    return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** International style numbers: optional +, 7-15 digits, spaces/dashes allowed. */
function is_valid_phone(string $phone): bool
{
    $digits = preg_replace('/\D/', '', $phone);
    return (bool) preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone) && strlen($digits) >= 7 && strlen($digits) <= 15;
}

/** Normalise phone to "+digits" form so uniqueness checks work. */
function normalize_phone(string $phone): string
{
    $plus = strpos(trim($phone), '+') === 0 ? '+' : '';
    return $plus . preg_replace('/\D/', '', $phone);
}

/** Returns an error message or null. */
function password_strength_error(string $password): ?string
{
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    if (strlen($password) > 200) {
        return 'Password is too long.';
    }
    return null;
}

/**
 * Whitelist-based HTML sanitiser for admin-authored rich text (blog posts).
 * Keeps basic formatting tags; strips scripts, event handlers, styles and javascript: URLs.
 */
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';
    $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'img', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'small'];
    $allowedAttrs = ['a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'title', 'width', 'height']];

    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) return e(strip_tags($html));

    $clean = function (DOMNode $node) use (&$clean, $allowedTags, $allowedAttrs, $doc) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) continue;
            $tag = strtolower($child->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math'], true)) {
                    $node->removeChild($child);
                } else {
                    $clean($child);
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                }
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowedAttrs[$tag] ?? [], true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true)) {
                    $v = trim(preg_replace('/[\x00-\x20]+/', '', $attr->value));
                    if (!preg_match('#^(https?://|/|\#|mailto:|assets/)#i', $v)) {
                        $child->removeAttribute($attr->name);
                    }
                }
            }
            if ($tag === 'a') {
                $child->setAttribute('rel', 'noopener noreferrer nofollow');
            }
            $clean($child);
        }
    };
    $clean($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return $out;
}

function is_valid_date(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

class Validator
{
    private array $data;
    private array $errors = [];
    private array $labels = [];

    public function __construct(array $data, array $labels = [])
    {
        $this->data = $data;
        $this->labels = $labels;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function value(string $field): string
    {
        $v = $this->data[$field] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    private function fail(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function required(string $field): self
    {
        if ($this->value($field) === '') {
            $this->fail($field, $this->label($field) . ' is required.');
        }
        return $this;
    }

    public function email(string $field): self
    {
        $v = $this->value($field);
        if ($v !== '' && !is_valid_email($v)) {
            $this->fail($field, 'Please enter a valid email address.');
        }
        return $this;
    }

    public function phone(string $field): self
    {
        $v = $this->value($field);
        if ($v !== '' && !is_valid_phone($v)) {
            $this->fail($field, 'Please enter a valid phone number.');
        }
        return $this;
    }

    public function min(string $field, int $length): self
    {
        $v = $this->value($field);
        if ($v !== '' && mb_strlen($v) < $length) {
            $this->fail($field, $this->label($field) . " must be at least {$length} characters.");
        }
        return $this;
    }

    public function max(string $field, int $length): self
    {
        if (mb_strlen($this->value($field)) > $length) {
            $this->fail($field, $this->label($field) . " must not exceed {$length} characters.");
        }
        return $this;
    }

    public function password(string $field): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && ($err = password_strength_error($v))) {
            $this->fail($field, $err);
        }
        return $this;
    }

    public function matches(string $field, string $other, string $message = 'Values do not match.'): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) {
            $this->fail($field, $message);
        }
        return $this;
    }

    public function numeric(string $field, ?float $min = null, ?float $max = null): self
    {
        $v = $this->value($field);
        if ($v === '') return $this;
        if (!is_numeric($v)) {
            return $this->fail($field, $this->label($field) . ' must be a number.');
        }
        if ($min !== null && (float) $v < $min) {
            $this->fail($field, $this->label($field) . " must be at least {$min}.");
        }
        if ($max !== null && (float) $v > $max) {
            $this->fail($field, $this->label($field) . " must not exceed {$max}.");
        }
        return $this;
    }

    public function integer(string $field, ?int $min = null, ?int $max = null): self
    {
        $v = $this->value($field);
        if ($v === '') return $this;
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            return $this->fail($field, $this->label($field) . ' must be a whole number.');
        }
        if ($min !== null && (int) $v < $min) {
            $this->fail($field, $this->label($field) . " must be at least {$min}.");
        }
        if ($max !== null && (int) $v > $max) {
            $this->fail($field, $this->label($field) . " must not exceed {$max}.");
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $message = null): self
    {
        $v = $this->value($field);
        if ($v !== '' && !in_array($v, $allowed, true)) {
            $this->fail($field, $message ?? ('Invalid ' . strtolower($this->label($field)) . '.'));
        }
        return $this;
    }

    public function date(string $field, string $format = 'Y-m-d'): self
    {
        $v = $this->value($field);
        if ($v !== '' && !is_valid_date($v, $format)) {
            $this->fail($field, 'Please enter a valid date.');
        }
        return $this;
    }

    public function checked(string $field, string $message): self
    {
        if (empty($this->data[$field])) {
            $this->fail($field, $message);
        }
        return $this;
    }

    public function custom(string $field, bool $ok, string $message): self
    {
        if (!$ok) {
            $this->fail($field, $message);
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function get(string $field, string $default = ''): string
    {
        $v = $this->data[$field] ?? $default;
        return is_string($v) ? clean_text($v) : $default;
    }
}