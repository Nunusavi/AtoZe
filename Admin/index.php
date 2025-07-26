<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//logger
function logMessage($message)
{
    $logFile = './logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

session_start();
require_once './Core/View.php';
require_once './Controller/AuthController.php';
require_once './Controller/CMSController.php';
require_once './Model/Users.php';


$page = $_GET['page'] ?? 'dashboard';

// Allow login route even without session
if (!isset($_SESSION['user']) && $page !== 'login' && $page !== 'auth') {
    header("Location: index.php?page=login");
    exit;
}

// Routes
switch ($page) {
    case 'login':
        View::render('login');
        break;

    case 'auth':
        AuthController::login(); // handles POST
        break;

    case 'logout':
        AuthController::logout();
        break;

    case 'dashboard':
        CMSController::dashboard();
        break;

    default:
        http_response_code(404);
        echo "404 Page Not Found";
        break;
}
