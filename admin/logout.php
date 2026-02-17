<?php
session_start();

require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/CSRFProtection.php';

use Analytics\Auth;
use CMS\CSRFProtection;

// Validate CSRF token
CSRFProtection::protect('logout');

$auth = new Auth(__DIR__ . '/config/users.json');
$auth->logout();

header('Location: login.php');
exit;
