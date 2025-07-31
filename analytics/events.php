<?php
require_once __DIR__ . '/lib/EventTracker.php';
require_once __DIR__ . '/lib/SessionTracker.php';

use Analytics\EventTracker;
use Analytics\SessionTracker;

// Enforce POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Load session
$session = new SessionTracker(__DIR__ . '/sessions');
$sessionId = $session->getSessionId();

// Get raw JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['event_type'])) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

// Add session ID
$data['session_id'] = $sessionId;

// Save event
$tracker = new EventTracker(__DIR__ . '/events');
$tracker->logEvent($data);

// Success response
http_response_code(204);
exit;
