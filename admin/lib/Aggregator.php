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
public static function getAggregatedStats($fromDate = null, $toDate = null)
{
    $stats = [
        'pages' => [],
        'referrers' => [],
        'countries' => [],
        'browsers' => [],
        'os' => [],
        'devices' => [],
    ];

    $files = glob(__DIR__ . '/../logs/*.json');

    foreach ($files as $file) {
        $date = basename($file, '.json');

        // Filter by date range if specified
        if ($fromDate && $date < $fromDate) continue;
        if ($toDate && $date > $toDate) continue;

        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;

        foreach ($data as $entry) {
            $page = $entry['url'] ?? null;
            $ref = $entry['referrer'] ?? null;
            $country = $entry['country'] ?? 'Unknown';
            $browser = $entry['browser'] ?? 'Unknown';
            $os = $entry['os'] ?? 'Unknown';
            $device = $entry['device'] ?? 'Unknown';

            // ✅ Only count meaningful public pages
            if ($page && !str_contains($page, 'tracker.php')) {
                $stats['pages'][$page] = ($stats['pages'][$page] ?? 0) + 1;
            }

            // ✅ Only count external referrers, ignore null, empty, or self-referring domains
            if ($ref && $ref !== '-' && !str_contains($ref, '/admin/') && !str_contains($ref, 'tracker.php')) {
                $stats['referrers'][$ref] = ($stats['referrers'][$ref] ?? 0) + 1;
            }

            // Count countries
            if ($country) {
                $stats['countries'][$country] = ($stats['countries'][$country] ?? 0) + 1;
            }

            // Count browsers
            if ($browser) {
                $stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;
            }

            // Count operating systems
            if ($os) {
                $stats['os'][$os] = ($stats['os'][$os] ?? 0) + 1;
            }

            // Count devices
            if ($device) {
                $stats['devices'][$device] = ($stats['devices'][$device] ?? 0) + 1;
            }
        }
    }

    // Sort each stat array in descending order
    foreach ($stats as &$stat) {
        arsort($stat);
    }

    return $stats;
}

}
