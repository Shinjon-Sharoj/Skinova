<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();
cart_clear((int) $user['id']);
flash('info', 'Cart cleared.');
redirect('cart.php');
