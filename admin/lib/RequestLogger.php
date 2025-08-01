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
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $key) {
            if (!empty($_SERVER[$key])) {
                return explode(',', $_SERVER[$key])[0];
            }
        }
        return 'unknown';
    }
}
