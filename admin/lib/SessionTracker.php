<?php
namespace Analytics;

class SessionTracker {
    private string $sessionDir;
    private string $sessionId;

    public function __construct(string $sessionDir) {
        $this->sessionDir = rtrim($sessionDir, '/');
        $this->initializeSession();
    }

    public function getSessionId(): string {
        return $this->sessionId;
    }

    private function initializeSession(): void {
        // Start PHP session if not already started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check for existing session ID
        if (isset($_SESSION['analytics_session_id'])) {
            $this->sessionId = $_SESSION['analytics_session_id'];
        } else {
            $this->sessionId = $this->generateSessionId();
            $_SESSION['analytics_session_id'] = $this->sessionId;
            $this->storeNewSession(); // Only store once
        }
    }

    private function generateSessionId(): string {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return sha1($ip . $ua . uniqid((string)mt_rand(), true));
    }

    private function storeNewSession(): void {
        $data = [
            'session_id' => $this->sessionId,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'start_time' => date('c'),
            'referrer'   => $_SERVER['HTTP_REFERER'] ?? 'direct',
        ];

        $file = $this->sessionDir . '/' . $this->sessionId . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
