<?php
namespace CMS;

class Helpers
{
    /**
     * Generate a UUID v4
     */
    public static function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate required fields
     */
    public static function validateRequired(array $fields, array $data): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeArray(array $input): array
    {
        return array_map([self::class, 'sanitizeString'], $input);
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Generate slug from string
     */
    public static function generateSlug(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Find item by ID in array
     */
    public static function findById(array $items, string $id): ?array
    {
        foreach ($items as $index => $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return ['index' => $index, 'item' => $item];
            }
        }
        return null;
    }

    /**
     * Format file size
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Truncate text
     */
    public static function truncate(string $text, int $length = 100, string $append = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length)) . $append;
    }

    /**
     * Time ago formatter
     */
    public static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';

        return date('M j, Y', $timestamp);
    }

    /**
     * Paginate array
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 20): array
    {
        $total = count($items);
        $totalPages = max(ceil($total / $perPage), 1);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    /**
     * Search in array of items
     */
    public static function search(array $items, string $query, array $fields): array
    {
        $query = strtolower(trim($query));
        if (empty($query)) {
            return $items;
        }

        return array_filter($items, function($item) use ($query, $fields) {
            foreach ($fields as $field) {
                $value = $item[$field] ?? '';
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                if (stripos($value, $query) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Sort array by field
     */
    public static function sortBy(array $items, string $field, string $direction = 'asc'): array
    {
        usort($items, function($a, $b) use ($field, $direction) {
            $aVal = $a[$field] ?? '';
            $bVal = $b[$field] ?? '';

            $comparison = $aVal <=> $bVal;
            return $direction === 'desc' ? -$comparison : $comparison;
        });

        return $items;
    }
}
