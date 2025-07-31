<?php
require_once __DIR__ . '/lib/RequestLogger.php';
require_once __DIR__ . '/lib/SessionTracker.php';

use Analytics\RequestLogger;
use Analytics\SessionTracker;

$session = new SessionTracker(__DIR__ . '/sessions');
$sessionId = $session->getSessionId();

$logger = new RequestLogger(__DIR__ . '/logs');
$logger->logRequest($sessionId); // You’ll pass session ID to logging now

// Send 1x1 transparent gif
header("Content-Type: image/gif");
echo base64_decode("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==");
exit;
