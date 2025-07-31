<?php
namespace Analytics;

class Aggregator {
    private string $logDir;
    private string $sessionDir;

    public function __construct(string $logDir, string $sessionDir) {
        $this->logDir = rtrim($logDir, '/');
        $this->sessionDir = rtrim($sessionDir, '/');
    }

    public function getPageviewsPerDay(int $days = 7): array {
        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $file = "{$this->logDir}/{$date}.jsonl";
            $count = 0;

            if (file_exists($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = count($lines);
            }

            $result[$date] = $count;
        }

        return $result;
    }

    public function getUniqueVisitorCount(): int {
        $files = glob($this->sessionDir . '/*.json');
        return count($files);
    }

    public function getTotalPageviews(): int {
        $files = glob($this->logDir . '/*.jsonl');
        $count = 0;

        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $count += count($lines);
        }

        return $count;
    }

    public function getBounceRate(): float {
        $sessionFiles = glob($this->sessionDir . '/*.json');
        $singlePageSessions = 0;

        foreach ($sessionFiles as $file) {
            $sessionId = basename($file, '.json');
            $logs = $this->getLogsBySession($sessionId);
            if (count($logs) <= 1) {
                $singlePageSessions++;
            }
        }

        $totalSessions = count($sessionFiles);
        return $totalSessions > 0 ? round(($singlePageSessions / $totalSessions) * 100, 2) : 0.0;
    }

    private function getLogsBySession(string $sessionId): array {
        $logs = [];
        $logFiles = glob($this->logDir . '/*.jsonl');
        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if (isset($entry['session_id']) && $entry['session_id'] === $sessionId) {
                    $logs[] = $entry;
                }
            }
        }
        return $logs;
    }
  public static function getStats($fromDate = null, $toDate = null)
{
    $stats = [
        'pageviews' => [],
        'visitors' => [],
        'events' => [],
        'pages' => [],
        'referrers' => [],
    ];

    $from = $fromDate ? strtotime($fromDate) : strtotime('-7 days');
    $to = $toDate ? strtotime($toDate) : time();

    for ($ts = $from; $ts <= $to; $ts += 86400) {
        $day = date('Y-m-d', $ts);
        $logFile = __DIR__ . "/../logs/{$day}.jsonl";
        $eventFile = __DIR__ . "/../events/{$day}.jsonl";

        $dailyPageviews = 0;
        $dailyVisitors = [];
        $dailyEvents = 0;

        if (file_exists($logFile)) {
            foreach (file($logFile) as $line) {
                $entry = json_decode($line, true);
                if (!$entry) continue;

                $dailyPageviews++;
                $dailyVisitors[$entry['session_id'] ?? uniqid()] = true;

                // Count URLs
                $url = $entry['url'] ?? '/';
                if (!isset($stats['pages'][$url])) $stats['pages'][$url] = 0;
                $stats['pages'][$url]++;

                // Count referrers
                $ref = $entry['referrer'] ?? 'direct';
                $ref = $ref === '' ? 'direct' : $ref;
                if (!isset($stats['referrers'][$ref])) $stats['referrers'][$ref] = 0;
                $stats['referrers'][$ref]++;
            }
        }

        if (file_exists($eventFile)) {
            foreach (file($eventFile) as $line) {
                $dailyEvents++;
            }
        }

        $stats['pageviews'][$day] = $dailyPageviews;
        $stats['visitors'][$day] = count($dailyVisitors);
        $stats['events'][$day] = $dailyEvents;
    }

    arsort($stats['pages']);
    arsort($stats['referrers']);

    return $stats;
}


}
