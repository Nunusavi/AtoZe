<?php
namespace Analytics;

class EventTracker {
    private string $eventDir;

    public function __construct(string $eventDir) {
        $this->eventDir = rtrim($eventDir, '/');
    }

    public function logEvent(array $data): void {
        $event = [
            'timestamp'   => date('c'),
            'event_type'  => $data['event_type'] ?? 'custom',
            'event_data'  => $data['event_data'] ?? [],
            'url'         => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'session_id'  => $data['session_id'] ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $file = $this->eventDir . '/' . date('Y-m-d') . '.jsonl';
        file_put_contents($file, json_encode($event) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
