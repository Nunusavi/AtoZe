<?php
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/Aggregator.php';

use Analytics\Auth;
use Analytics\Aggregator;

$auth = new Auth(__DIR__ . '/config/users.json');
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$type = $_GET['type'] ?? 'logs';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $type . '.csv"');

$output = fopen('php://output', 'w');

switch ($type) {
    case 'logs':
        exportLogs($output);
        break;
    case 'sessions':
        exportSessions($output);
        break;
    case 'events':
        exportEvents($output);
        break;
    case 'pageviews':
        $aggregator = new Aggregator(__DIR__ . '/logs', __DIR__ . '/sessions');
        fputcsv($output, ['Date', 'Pageviews']);
        foreach ($aggregator->getPageviewsPerDay(30) as $date => $count) {
            fputcsv($output, [$date, $count]);
        }
        break;
    default:
        echo 'Invalid export type';
}

fclose($output);

// ---- Export Helpers ---- //

function exportLogs($output) {
    $files = glob(__DIR__ . '/logs/*.jsonl');
    fputcsv($output, ['Timestamp', 'IP', 'User Agent', 'Referrer', 'URL', 'Session ID']);
    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            fputcsv($output, [
                $entry['timestamp'] ?? '',
                $entry['ip'] ?? '',
                $entry['user_agent'] ?? '',
                $entry['referrer'] ?? '',
                $entry['url'] ?? '',
                $entry['session_id'] ?? ''
            ]);
        }
    }
}

function exportSessions($output) {
    $files = glob(__DIR__ . '/sessions/*.json');
    fputcsv($output, ['Session ID', 'IP', 'User Agent', 'Start Time', 'Referrer']);
    foreach ($files as $file) {
        $session = json_decode(file_get_contents($file), true);
        fputcsv($output, [
            $session['session_id'] ?? '',
            $session['ip'] ?? '',
            $session['user_agent'] ?? '',
            $session['start_time'] ?? '',
            $session['referrer'] ?? ''
        ]);
    }
}

function exportEvents($output) {
    $files = glob(__DIR__ . '/events/*.jsonl');
    fputcsv($output, ['Timestamp', 'Type', 'Session ID', 'IP', 'User Agent', 'URL', 'Event Data']);
    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            fputcsv($output, [
                $entry['timestamp'] ?? '',
                $entry['event_type'] ?? '',
                $entry['session_id'] ?? '',
                $entry['ip'] ?? '',
                $entry['user_agent'] ?? '',
                $entry['url'] ?? '',
                json_encode($entry['event_data'] ?? [], JSON_UNESCAPED_SLASHES)
            ]);
        }
    }
}
