<?php
require_once __DIR__ . '/includes/init.php';
require_post();
csrf_verify();
logout_user();
flash('success', 'You have been signed out.');
redirect('login.php');
