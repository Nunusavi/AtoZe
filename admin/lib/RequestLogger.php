<?php
namespace Analytics;

class RequestLogger {
    private string $logDir;

    public function __construct(string $logDir) {
        $this->logDir = rtrim($logDir, '/');
    }

   public function logRequest(?string $sessionId = null): void {
    $entry = [
        'timestamp'   => date('c'),
        'ip'          => $this->getClientIp(),
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referrer'    => $_SERVER['HTTP_REFERER'] ?? 'direct',
        'url'         => $_SERVER['REQUEST_URI'] ?? 'unknown',
    ];

    if ($sessionId) {
        $entry['session_id'] = $sessionId;
    }

    $logFile = $this->logDir . '/' . date('Y-m-d') . '.jsonl';
    file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}


    private function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // IP from shared internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // IP passed from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        return $ip;
    }   
}
