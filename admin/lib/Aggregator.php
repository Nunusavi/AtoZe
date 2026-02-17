<?php
namespace Analytics;

class Aggregator {
    private string $logDir;
    private string $sessionDir;
    private string $cacheFile;

    public function __construct(string $logDir, string $sessionDir) {
        $this->logDir = rtrim($logDir, '/');
        $this->sessionDir = rtrim($sessionDir, '/');
        $this->cacheFile = $this->logDir . '/_cache.json';
    }

    /**
     * Get total pageviews across all time
     */
    public function getTotalPageviews(): int {
        $files = glob($this->logDir . '/*.jsonl');
        $count = 0;

        foreach ($files as $file) {
            if (basename($file) === '_cache.json') continue;
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $count += count($lines);
        }

        return $count;
    }

    /**
     * Get unique visitor count (based on unique sessions)
     */
    public function getUniqueVisitorCount(): int {
        $files = glob($this->sessionDir . '/*.json');
        return count($files);
    }

    /**
     * Calculate bounce rate (sessions with only 1 pageview)
     * Uses caching to avoid recalculating on every request
     */
    public function getBounceRate(): float {
        $cache = $this->getCache();
        $cacheKey = 'bounce_rate_' . date('Y-m-d-H'); // Cache for 1 hour

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $sessionPageviews = [];
        $logFiles = glob($this->logDir . '/*.jsonl');

        foreach ($logFiles as $file) {
            if (basename($file) === '_cache.json') continue;
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if (!$entry || !isset($entry['session_id'])) continue;

                $sessionId = $entry['session_id'];
                if (!isset($sessionPageviews[$sessionId])) {
                    $sessionPageviews[$sessionId] = 0;
                }
                $sessionPageviews[$sessionId]++;
            }
        }

        $totalSessions = count($sessionPageviews);
        if ($totalSessions === 0) {
            return 0.0;
        }

        $bouncedSessions = 0;
        foreach ($sessionPageviews as $count) {
            if ($count <= 1) {
                $bouncedSessions++;
            }
        }

        $bounceRate = round(($bouncedSessions / $totalSessions) * 100, 2);

        // Cache the result
        $cache[$cacheKey] = $bounceRate;
        $this->saveCache($cache);

        return $bounceRate;
    }

    /**
     * Get pageviews per day for the last N days
     */
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

    /**
     * Get comprehensive stats for a date range (Marketing Dashboard)
     */
    public function getStats(?string $fromDate = null, ?string $toDate = null): array {
        $stats = [
            'pageviews' => [],
            'visitors' => [],
            'pages' => [],
            'traffic_sources' => [],
            'browsers' => [],
            'os' => [],
            'devices' => [],
            'countries' => [],
            'popular_pages' => [],
            'conversions' => 0,
        ];

        $from = $fromDate ? strtotime($fromDate) : strtotime('-7 days');
        $to = $toDate ? strtotime($toDate) : time();

        for ($ts = $from; $ts <= $to; $ts += 86400) {
            $day = date('Y-m-d', $ts);
            $logFile = "{$this->logDir}/{$day}.jsonl";
            $eventFile = __DIR__ . "/../events/{$day}.jsonl";

            $dailyPageviews = 0;
            $dailyVisitors = [];

            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    $entry = json_decode($line, true);
                    if (!$entry) continue;

                    $dailyPageviews++;

                    // Track unique visitors
                    if (isset($entry['session_id'])) {
                        $dailyVisitors[$entry['session_id']] = true;
                    }

                    // Count page visits
                    $page = $entry['page_url'] ?? 'unknown';
                    if ($page !== 'direct' && !str_contains($page, 'tracker.php')) {
                        $stats['pages'][$page] = ($stats['pages'][$page] ?? 0) + 1;
                    }

                    // Count traffic sources
                    $source = $entry['external_source'] ?? 'Unknown';
                    $stats['traffic_sources'][$source] = ($stats['traffic_sources'][$source] ?? 0) + 1;

                    // Count browsers
                    $browser = $entry['browser'] ?? 'Unknown';
                    $stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;

                    // Count operating systems
                    $os = $entry['os'] ?? 'Unknown';
                    $stats['os'][$os] = ($stats['os'][$os] ?? 0) + 1;

                    // Count devices
                    $device = $entry['device'] ?? 'Unknown';
                    $stats['devices'][$device] = ($stats['devices'][$device] ?? 0) + 1;

                    // Count countries
                    $country = $entry['country'] ?? 'Unknown';
                    $stats['countries'][$country] = ($stats['countries'][$country] ?? 0) + 1;
                }
            }

            // Count conversions (form submissions) from events
            if (file_exists($eventFile)) {
                $eventLines = file($eventFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($eventLines as $line) {
                    $event = json_decode($line, true);
                    if ($event && isset($event['event_type']) && $event['event_type'] === 'form_submit') {
                        $stats['conversions']++;
                    }
                }
            }

            $stats['pageviews'][$day] = $dailyPageviews;
            $stats['visitors'][$day] = count($dailyVisitors);
        }

        // Sort by popularity
        arsort($stats['pages']);
        arsort($stats['traffic_sources']);
        arsort($stats['browsers']);
        arsort($stats['os']);
        arsort($stats['devices']);
        arsort($stats['countries']);

        // Get top 10 popular pages
        $stats['popular_pages'] = array_slice($stats['pages'], 0, 10, true);

        return $stats;
    }

    /**
     * Get top traffic sources
     */
    public function getTopTrafficSources(int $limit = 5): array {
        $stats = $this->getStats();
        return array_slice($stats['traffic_sources'], 0, $limit, true);
    }

    /**
     * Get device breakdown
     */
    public function getDeviceBreakdown(): array {
        $stats = $this->getStats();
        return $stats['devices'];
    }

    /**
     * Get browser breakdown
     */
    public function getBrowserBreakdown(): array {
        $stats = $this->getStats();
        return $stats['browsers'];
    }

    /**
     * Get conversion rate (conversions / total visitors * 100)
     */
    public function getConversionRate(): float {
        $stats = $this->getStats();
        $totalVisitors = array_sum($stats['visitors']);
        $conversions = $stats['conversions'];

        if ($totalVisitors === 0) {
            return 0.0;
        }

        return round(($conversions / $totalVisitors) * 100, 2);
    }

    /**
     * Get user journey data (most common page sequences)
     */
    public function getUserJourneys(int $limit = 10): array {
        $journeys = [];
        $logFiles = glob($this->logDir . '/*.jsonl');

        foreach ($logFiles as $file) {
            if (basename($file) === '_cache.json') continue;

            $sessionPages = [];
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if (!$entry || !isset($entry['session_id'])) continue;

                $sessionId = $entry['session_id'];
                $page = $entry['page_url'] ?? 'unknown';

                if (!isset($sessionPages[$sessionId])) {
                    $sessionPages[$sessionId] = [];
                }

                $sessionPages[$sessionId][] = $page;
            }

            // Build journey patterns
            foreach ($sessionPages as $pages) {
                if (count($pages) > 1) {
                    $journey = implode(' → ', array_slice($pages, 0, 5)); // First 5 pages
                    $journeys[$journey] = ($journeys[$journey] ?? 0) + 1;
                }
            }
        }

        arsort($journeys);
        return array_slice($journeys, 0, $limit, true);
    }

    /**
     * Get real-time active visitors (last 5 minutes)
     */
    public function getActiveVisitors(): int {
        $fiveMinutesAgo = date('c', strtotime('-5 minutes'));
        $todayLog = $this->logDir . '/' . date('Y-m-d') . '.jsonl';

        if (!file_exists($todayLog)) {
            return 0;
        }

        $activeSessions = [];
        $lines = file($todayLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry || !isset($entry['timestamp'], $entry['session_id'])) continue;

            if ($entry['timestamp'] >= $fiveMinutesAgo) {
                $activeSessions[$entry['session_id']] = true;
            }
        }

        return count($activeSessions);
    }

    /**
     * Clean up old log files (data retention)
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int {
        $deletedCount = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));

        $logFiles = glob($this->logDir . '/*.jsonl');
        foreach ($logFiles as $file) {
            $filename = basename($file, '.jsonl');
            if ($filename < $cutoffDate) {
                unlink($file);
                $deletedCount++;
            }
        }

        // Also cleanup old sessions
        $sessionFiles = glob($this->sessionDir . '/*.json');
        foreach ($sessionFiles as $file) {
            $mtime = filemtime($file);
            if ($mtime < strtotime("-{$daysToKeep} days")) {
                unlink($file);
            }
        }

        return $deletedCount;
    }

    /**
     * Cache management
     */
    private function getCache(): array {
        if (file_exists($this->cacheFile)) {
            $data = json_decode(file_get_contents($this->cacheFile), true);
            return $data ?? [];
        }
        return [];
    }

    private function saveCache(array $cache): void {
        file_put_contents($this->cacheFile, json_encode($cache), LOCK_EX);
    }
}
