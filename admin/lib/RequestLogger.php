<?php
namespace Analytics;

class RequestLogger {
    private string $logDir;

    public function __construct(string $logDir) {
        $this->logDir = rtrim($logDir, '/');
    }

    public function logRequest(?string $sessionId = null): void {
        // Filter out bots
        if ($this->isBot()) {
            return;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $pageUrl = $_SERVER['HTTP_REFERER'] ?? 'direct';

        $entry = [
            'timestamp'        => date('c'),
            'ip'               => $this->getClientIp(),
            'user_agent'       => $userAgent,
            'page_url'         => $this->cleanPageUrl($pageUrl),
            'external_source'  => $this->getExternalReferrer($pageUrl),
            'browser'          => $this->getBrowser($userAgent),
            'os'               => $this->getOS($userAgent),
            'device'           => $this->getDevice($userAgent),
            'country'          => $this->getCountry($this->getClientIp()),
        ];

        if ($sessionId) {
            $entry['session_id'] = $sessionId;
        }

        $logFile = $this->logDir . '/' . date('Y-m-d') . '.jsonl';
        file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function isBot(): bool {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'yahoo', 'google',
            'baidu', 'bing', 'yandex', 'duckduck', 'archive',
            'facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram'
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function cleanPageUrl(string $url): string {
        if ($url === 'direct' || empty($url)) {
            return 'direct';
        }

        // Extract just the path and page name from full URL
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        // Remove domain if present
        if (isset($parsed['host'])) {
            return $path;
        }

        return $url;
    }

    private function getExternalReferrer(string $pageUrl): string {
        if ($pageUrl === 'direct' || empty($pageUrl)) {
            return 'Direct Traffic';
        }

        $parsed = parse_url($pageUrl);
        $host = $parsed['host'] ?? '';

        // If no host, it's internal navigation
        if (empty($host)) {
            return 'Direct Traffic';
        }

        // Check if it's from your own domain
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if (stripos($host, $currentHost) !== false || stripos($currentHost, $host) !== false) {
            return 'Direct Traffic';
        }

        // Identify major traffic sources
        if (stripos($host, 'google') !== false) return 'Google Search';
        if (stripos($host, 'bing') !== false) return 'Bing Search';
        if (stripos($host, 'yahoo') !== false) return 'Yahoo Search';
        if (stripos($host, 'facebook') !== false) return 'Facebook';
        if (stripos($host, 'twitter') !== false || stripos($host, 't.co') !== false) return 'Twitter';
        if (stripos($host, 'linkedin') !== false) return 'LinkedIn';
        if (stripos($host, 'instagram') !== false) return 'Instagram';
        if (stripos($host, 'youtube') !== false) return 'YouTube';
        if (stripos($host, 'whatsapp') !== false) return 'WhatsApp';
        if (stripos($host, 'telegram') !== false) return 'Telegram';

        // Return the domain as-is for other referrers
        return $host;
    }

    private function getBrowser(string $userAgent): string {
        if (stripos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (stripos($userAgent, 'Edg') !== false) return 'Edge';
        if (stripos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (stripos($userAgent, 'Safari') !== false) return 'Safari';
        if (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) return 'Opera';
        if (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        return 'Other';
    }

    private function getOS(string $userAgent): string {
        if (stripos($userAgent, 'Windows NT 10') !== false) return 'Windows 10';
        if (stripos($userAgent, 'Windows NT 11') !== false) return 'Windows 11';
        if (stripos($userAgent, 'Windows') !== false) return 'Windows';
        if (stripos($userAgent, 'Mac OS X') !== false) return 'macOS';
        if (stripos($userAgent, 'Android') !== false) return 'Android';
        if (stripos($userAgent, 'iOS') !== false || stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) return 'iOS';
        if (stripos($userAgent, 'Linux') !== false) return 'Linux';
        return 'Other';
    }

    private function getDevice(string $userAgent): string {
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) return 'Mobile';
        if (stripos($userAgent, 'Tablet') !== false || stripos($userAgent, 'iPad') !== false) return 'Tablet';
        return 'Desktop';
    }

    private function getCountry(string $ip): string {
        // Basic country detection - for production, use a GeoIP library or API
        // For now, return 'Unknown' - can be enhanced later with MaxMind GeoIP2 or ip-api.com
        if ($ip === '127.0.0.1' || $ip === 'unknown') {
            return 'Local/Unknown';
        }

        // Placeholder for future GeoIP integration
        return 'Ethiopia'; // Default for now, enhance with actual GeoIP lookup
    }

    private function getClientIp(): string {
        $ip = 'unknown';

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get first IP from comma-separated list (most reliable)
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Validate IP address
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return 'unknown';
        }

        return $ip;
    }
}
