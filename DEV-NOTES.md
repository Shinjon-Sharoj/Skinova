# Skinova - developer contract (read fully before writing any code)

Project root: W:\Help\Shoroj\SkiNova (PHP 8.2, MariaDB, Bootstrap 5.3 vendored in assets/vendor, XAMPP).
Database skinova already imported (schema + sample data). *MySQL is running* (if a query says "can't connect", start it with
Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{CommandLine='C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone'}).
CLI: C:\xampp\php\php.exe, C:\xampp\mysql\bin\mysql.exe -u root skinova -e "..." (no password).

## IMPORTANT tooling note (file writes)
The write_file tool has sometimes reported success without the file reaching disk when many files were written in one parallel batch.
*Write at most 2 files per tool-call block, and after each group verify with* cmd /c "dir /s /b /a-d <your folders>" (terminal). If a file is missing, write it again.
Lint every PHP file you create: C:\xampp\php\php.exe -l path\file.php.

## Sample accounts (password for all: Password@123)
admin@skinova.test (admin) | nadia@, marcus@, priya@skinova.test (consultants; users 2,3,4; consultants rows 1,2,3) | aisha@skinova.test (customer id 5, has skin profile) | daniel@skinova.test (customer id 6)
Shared DB: do not delete sample rows you did not create; clean up your own test data. If you get rate-limited while testing: DELETE FROM rate_limits.

## Running / testing
Start a dev server on YOUR port (see your section) detached, then test with curl.exe (cookie jar -c/-b, CSRF token from the page's <meta name="csrf-token"> or hidden csrf_token field):
Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{CommandLine='C:\xampp\php\php.exe -S 127.0.0.1:PORT -t W:\Help\Shoroj\SkiNova'}
Stop it when you finish (Get-CimInstance Win32_Process | Where-Object CommandLine -match 'PORT' | Invoke-CimMethod -MethodName Terminate).
Do not use shell substitutions like $var or $(...) inside terminal commands (tool restriction). Check the PHP error log storage/logs/php-error.log and storage/logs/app.log while testing.
Every page you build must actually be loaded with curl and return HTTP 200 (no PHP warnings in the body) for the happy path, and key POST flows must be exercised.

## Shared foundation (already written - READ these files first)
config/{app,constants,database}.php, includes/{init,functions,csrf,validation,jwt,auth,auth-check,api,upload,notifications,catalog,commerce,header,navbar,footer,sidebar}.php,
admin/includes/* (dashboard layout for admin AND consultant), assets/js/main.js, assets/css/{style,responsive,admin}.css, download.php, database/schema.sql, database/sample-data.sql.
Rules:
- *Do NOT edit shared foundation files* except for small, backwards-compatible bug fixes (never rename/remove functions). List any such fix in your final report. If you need a new helper, put it in a NEW file you own (e.g. includes/<module>.php) and require_once it where needed.
- Do not edit database/schema.sql. If you truly need a schema change, report it instead (use only existing tables/columns).
- Never change files owned by another module (see ownership table).

### Page skeleton (customer site)
php
<?php
require_once __DIR__ . '/includes/init.php';            // root pages. admin/, consultant/, api/: '/../includes/init.php'; actions/<x>/: '/../../includes/init.php'
require_once __DIR__ . '/includes/auth-check.php';      // only for private pages; gives $authUser (use $requiredRoles = [ROLE_ADMIN] before it to restrict)
$pageTitle = 'Title'; $extraCss = ['css/products.css'];  $extraJs = ['js/products.js'];  // optional, set BEFORE include header / footer
include __DIR__ . '/includes/header.php';
?> ... <div class="container py-4"> ... </div> <?php include __DIR__ . '/includes/footer.php';

Account-area pages use the sidebar: <div class="row"><div class="col-lg-3 mb-4"><?php include __DIR__.'/includes/sidebar.php'; ?></div><div class="col-lg-9">...</div></div>.
Admin/consultant pages: require_once __DIR__.'/includes/auth-check.php' (admin only; consultant pages instead require_once __DIR__.'/../includes/init.php'; $authUser = require_role(ROLE_CONSULTANT);), then $pageTitle=...; $panel='admin'|'consultant'; include __DIR__.'/includes/header.php' (consultant pages include __DIR__.'/../admin/includes/header.php' with $panel='consultant' and the matching footer). Helpers there: admin_page_header($title,$actionsHtml,$subtitle), admin_stat_card(...), admin_empty_row($cols,$msg).

### Helper API (see the source for exact signatures)
- Output/URLs: e(), url('x.php?a=1'), asset('css/a.css'), media_url($dbPath,'product|category|blog|avatar|banner'), redirect($path), redirect_back(), safe_next_path(), not_found(), forbidden().
- Flash + forms: flash('success|danger|warning|info',$msg), set_old($_POST,$errors), old('f'), field_error('f'), invalid_class('f'), invalid_feedback('f') (old input cleared automatically by footer).
- Requests: is_post(), require_post(), input(), input_int(), input_float(), get_str(), get_int(), csrf_field(), csrf_verify() (*call at top of EVERY POST handler*), class Validator, clean_text(), is_valid_email/phone, normalize_phone(), password_strength_error().
- DB (PDO prepared statements only, never concatenate user input): db_all/db_one/db_value/db_exec/db_insert/db_transaction/db_placeholders.
- Auth: current_user(), current_user_id(), is_logged_in(), has_role(), require_login(), require_verified(), require_role(), login_user(), logout_user(), find_user_by_login(), create_verification_token(), consume_verification_token(), activate_user_if_verified(), home_url_for_role(). *Authorize every protected operation on the server* (ownership checks: WHERE id=? AND user_id=?).
- Misc: money(), format_date(), format_datetime(), time_ago(), truncate(), humanize(), status_badge($status), slugify(), unique_slug($table,$text,$ignoreId), paginate()/render_pagination(), get_setting()/set_setting(), log_activity($action,$entityType,$entityId,$details) (audit log - use for important writes), app_log(), rate_limit($key,$max,$seconds), rate_limit_reset(), send_mail()/send_sms() (log to storage/logs/mail.log in dev), absolute_url(), render_stars().
- Uploads: save_image_upload($_FILES['x'],'reviews'), save_document_upload($file,'prescriptions'), normalize_files_array(), delete_upload(), private_file_url('prescription|message|skin',$id). Private dirs: prescriptions, skin-images (never link directly; always download.php via private_file_url).
- Notifications: notify($userId,$type,$title,$message,$link), notify_many, notify_all_customers, notify_wishlist_price_drop, notify_new_product, unread_notification_count, mark_notification_read, mark_all_notifications_read, notification_icon. Types: order, shipping, delivery, discount, new_product, wishlist_discount, consultation, message, system. Links are app-relative paths.
- Catalog (includes/catalog.php): get_products($filters,$page,$perPage), get_product(), get_product_by_slug(), get_products_by_ids(), get_product_details(), get_related_products(), get_best_sellers/new_arrivals/featured_products, get_categories(), get_brands(), get_skin_concerns(), get_all_ingredients(), track_product_view(), get_recently_viewed(), log_search(), wishlist_product_ids(), render_product_card(), render_product_grid(), get_skin_profile($userId), refresh_product_rating($productId), product_url(), product_image_url().
- Commerce (includes/commerce.php): cart (cart_add/set_quantity/remove/clear/count/items/set_coupon/set_loyalty_points), calculate_pricing($userId) (authoritative totals; never trust browser totals), validate_coupon, place_order($userId,$addressId,'cod|online',$notes), get_order/get_order_items/get_order_history, update_order_status($orderId,$status,$note,$actorId,$shipment), cancel_order, customer_can_cancel, mark_order_paid, mark_payment_failed, create_refund, process_refund, award_loyalty, user_has_purchased, online_payment_provider().

### JSON API contract (api/*.php)
php
require_once __DIR__ . '/../includes/init.php';
$user = api_init(['methods'=>['GET','POST'], 'auth'=>true, 'roles'=>[], 'rate'=>[120,60]]);   // handles method/JWT/session/CSRF/rate-limit/JSON headers
$in = api_input();  ...  api_ok($data,'message');  api_fail('message',422,['field'=>'error']);

Envelope: {"success":bool,"message":string,"data":any,"errors"?:{}}. Session writes need header X-CSRF-Token (the global Skinova.api() in assets/js/main.js sends it automatically); Bearer JWT (from api/auth.php) bypasses CSRF. Actions are selected with an action field.
Global JS contract already in main.js: buttons with data-action="add-to-cart" data-product-id=".." [data-qty-input="#sel"|data-qty=".."] POST {action:'add',product_id,quantity} to api/cart.php and expect data.cart_count; data-action="toggle-wishlist" data-product-id POST {action:'toggle',product_id} to api/wishlist.php and expect data.in_wishlist, data.wishlist_count; navbar polls GET api/notifications.php?action=count expecting data.unread.

### Action handlers (actions/<module>/<name>.php) - classic form POST
require_post(); csrf_verify(); auth/role check; validate (Validator); do work; flash(); redirect(...) (Post/Redirect/Get). Invalid input -> set_old($_POST,$errors); redirect_back('page.php'). Pages show errors with invalid_class() + invalid_feedback() and Bootstrap needs-validation. No business logic duplicated in pages: put reusable logic in includes/<module>.php.

### Cross-module URL / parameter conventions (everyone must use these)
product-details.php?slug=<slug> | products.php?category=<slug>&brand=<slug>&skin_type=&concern=<slug>&min_price=&max_price=&sort=popular|newest|price_asc|price_desc|rating&q= | search.php?q= | categories.php
cart.php wishlist.php checkout.php payment.php?order=<id> order-success.php?id=<id> orders.php order-details.php?id=<id> track-order.php?id=<id> invoice.php?id=<id> returns.php?order=<id>
consultations.php (directory + my consultations; ?book=<consultant_id>&topic=<text> pre-fills the booking form) | consultation-details.php?id=<id> | messages.php | consultant/*.php
blog.php?category=<slug>&q= | blog-details.php?slug=<slug> | faq.php | notifications.php?open=<id> (marks read then redirects to the notification link) | skincare-profile.php | skincare-routine.php?id=<routine> | recommendations.php | ingredient-analyzer.php?ingredient=<slug> | product-comparison.php?ids=1,2,3 | skin-analysis.php | ai-chatbot.php
login.php?next=<path> register.php verify-account.php logout.php (POST + CSRF only) profile.php edit-profile.php addresses.php
Admin pages live in admin/; admin POSTs are handled by the page itself (PRG) after csrf_verify() (the page is already protected by admin/includes/auth-check.php; keep every state change behind that guard + log_activity).

### Product principles (important)
- *Human consultation is a first-class feature*: customers can book a skincare professional and chat with them 1-to-1 (live polling chat, attachments, prescriptions, advice records). The AI side is optional and configurable; when no AI provider is configured, features must say so honestly and still work (knowledge base / rule engine) and always offer "Talk to a human consultant" (consultations.php?topic=...).
- *Never present simulated or rule-based output as genuine AI.* Label sources everywhere: "AI (provider)", "Knowledge base", "Rule-based recommendation", "Image metrics (not AI)". Always show AI_DISCLAIMER (in config/constants.php) near skin/AI results; never claim a diagnosis; escalate sensitive/serious cases to professionals.
- Skin-related personal data (skin profile, skin photos, consultations, prescriptions) require consent and strict access control (owner / assigned consultant / admin only).
- Everything dynamic comes from the database - no hardcoded stats or AI outputs.
- Accessibility & UX: labelled form controls, aria-* where needed, loading + empty + error + success states, responsive Bootstrap layouts, Bootstrap Icons (bi bi-*).
- Security: prepared statements, e() on all output, CSRF on all POSTs, server-side authorization, validation, upload validation helpers, rate limits on brute-forceable/expensive actions, audit log entries.

## Module ownership (each agent writes ONLY its own files)
*A - Accounts, skin profile, blog, FAQ*: login.php register.php verify-account.php forgot-password.php reset-password.php logout.php profile.php edit-profile.php addresses.php notifications.php skincare-profile.php blog.php blog-details.php faq.php, actions/auth/*, actions/profile/*, actions/notifications/*, actions/blogs/*, api/auth.php, api/notifications.php, assets/css/auth.css, assets/js/auth.js. Port 8081.
*B - Storefront & reviews*: index.php about.php contact.php products.php product-details.php search.php categories.php, actions/products/*, actions/reviews/*, api/products.php, api/search.php, assets/css/products.css, assets/js/products.js. Port 8082.
*C - Cart, checkout, payments, orders*: cart.php wishlist.php checkout.php payment.php order-success.php orders.php order-details.php track-order.php returns.php invoice.php, actions/{cart,wishlist,checkout,orders,payments}/*, api/cart.php api/wishlist.php api/orders.php, includes/payments.php, assets/js/cart.js, assets/js/checkout.js. Port 8083.
*D - Skincare AI & tools*: ai-chatbot.php recommendations.php skincare-routine.php ingredient-analyzer.php product-comparison.php skin-analysis.php, actions/ai/*, actions/skincare/*, api/{chatbot,recommendations,skincare-routine,ingredient-analysis,skin-analysis}.php, includes/ai.php, includes/skincare-engine.php, assets/css/skincare.css, assets/js/{skincare,chatbot}.js. Port 8084.
*E - Human consultation & messaging*: consultations.php consultation-details.php messages.php, consultant/{dashboard,consultations,consultation-details,messages,profile}.php, actions/consultations/*, actions/messages/*, api/consultations.php, api/messages.php, includes/consultation.php, assets/css/consultation.css, assets/js/consultation.js. Port 8085.
*F - Admin part 1*: admin/{dashboard,analytics,reports,users,user-details,products,product-form,categories,inventory,settings,notifications}.php. Port 8086.
*G - Admin part 2*: admin/{orders,order-details,payments,refunds,reviews,coupons,promotions,consultants,consultations,blogs,blog-form,ai-knowledge,ai-queries,ai-recommendations}.php. Port 8087.
(The lead owns everything else: foundation, schema, sample data, README.)

Data you can rely on in sample data: 20 products (ids 1-20; id 20 out of stock, id 17 low stock), 7 categories, 5 brands, 26 ingredients, 11 skin concerns, users 1-6 as above. The lead will add coupons, promotions, blog posts, knowledge-base/FAQ entries, sample orders, reviews, consultations and messages shortly; build against the schema and do not depend on them being present at first (handle empty states).