<?php
/**
 * Include FIRST in every admin page. Loads the app and enforces the administrator role
 * on the server (403 for other roles, redirect to login for guests).
 * Exposes $authUser.
 */
require_once __DIR__ . '/../../includes/init.php';
$authUser = require_role(ROLE_ADMIN);
