<?php
/**
 * Include at the top of any page that needs a logged-in user:
 *
 *     require_once _DIR_ . '/includes/init.php';
 *     require_once _DIR_ . '/includes/auth-check.php';   // exposes $authUser
 *
 * Set $requiredRoles = [ROLE_ADMIN] etc. BEFORE including to restrict by role.
 */
require_once _DIR_ . '/init.php';

if (isset($requiredRoles) && $requiredRoles) {
    $authUser = require_role($requiredRoles);
} else {
    $authUser = require_login();
}