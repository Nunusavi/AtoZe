<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/Model/CMS.php';

class CMSController
{
    public static function dashboard()
    {
        $team = CMS::getData('team');
        View::render('dashboard', ['team' => $team]);
    }
}

// RESTful API for AJAX actions
$action = $_GET['action'] ?? '';
$type = $_POST['type'] ?? $_GET['type'] ?? '';

if ($action === 'save' || $action === 'delete') {
    header('Content-Type: application/json');
    require_once dirname(__DIR__) . '/Model/CMS.php';
    if ($action === 'save') {
        $result = CMS::saveEntry($type, $_POST, $_FILES);
        echo json_encode(['success' => true, 'message' => 'Saved successfully']);
    } elseif ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $result = CMS::deleteEntry($type, $data['name']);
        echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
    }
    exit;
}
