<?php
namespace CMS;

class ActivityLogger
{
    private string $logFile;

    public function __construct(string $logDir = null)
    {
        $logDir = $logDir ?? __DIR__ . '/../logs/activity';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/' . date('Y-m') . '.jsonl';
    }

    /**
     * Log an activity
     */
    public function log(string $action, string $entityType, string $entityId, array $data = []): void
    {
        $entry = [
            'timestamp' => date('c'),
            'user' => $_SESSION['analytics_user'] ?? 'unknown',
            'action' => $action, // create, update, delete, login, logout
            'entity_type' => $entityType, // product, project, slide, user
            'entity_id' => $entityId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];

        file_put_contents($this->logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get recent activities
     */
    public function getRecent(int $limit = 50): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Most recent first
        $lines = array_slice($lines, 0, $limit);

        $activities = [];
        foreach ($lines as $line) {
            $activity = json_decode($line, true);
            if ($activity) {
                $activities[] = $activity;
            }
        }

        return $activities;
    }

    /**
     * Get activities for specific entity
     */
    public function getByEntity(string $entityType, string $entityId): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $activities = [];

        foreach ($lines as $line) {
            $activity = json_decode($line, true);
            if ($activity && $activity['entity_type'] === $entityType && $activity['entity_id'] === $entityId) {
                $activities[] = $activity;
            }
        }

        return array_reverse($activities);
    }

    /**
     * Get activities by user
     */
    public function getByUser(string $username, int $limit = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $activities = [];

        foreach (array_reverse($lines) as $line) {
            if (count($activities) >= $limit) break;

            $activity = json_decode($line, true);
            if ($activity && $activity['user'] === $username) {
                $activities[] = $activity;
            }
        }

        return $activities;
    }
}
