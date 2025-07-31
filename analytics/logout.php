<?php
require_once __DIR__ . '/lib/Auth.php';

use Analytics\Auth;

$auth = new Auth(__DIR__ . '/config/users.json');
$auth->logout();

header('Location: login.php');
exit;
