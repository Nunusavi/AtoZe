<?php
namespace CMS;

class CSRFProtection
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_tokens';

    /**
     * Generate a new CSRF token
     */
    public static function generateToken(string $formName = 'default'): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$formName] = [
            'token' => $token,
            'timestamp' => time()
        ];

        return $token;
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken(string $token, string $formName = 'default'): bool
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY][$formName])) {
            return false;
        }

        $storedData = $_SESSION[self::SESSION_KEY][$formName];

        // Check if token expired (1 hour expiration)
        if (time() - $storedData['timestamp'] > 3600) {
            unset($_SESSION[self::SESSION_KEY][$formName]);
            return false;
        }

        // Use hash_equals to prevent timing attacks
        $isValid = hash_equals($storedData['token'], $token);

        // Remove token after validation (one-time use)
        if ($isValid) {
            unset($_SESSION[self::SESSION_KEY][$formName]);
        }

        return $isValid;
    }

    /**
     * Generate hidden input field for forms
     */
    public static function getTokenField(string $formName = 'default'): string
    {
        $token = self::generateToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate token from POST request
     */
    public static function validateRequest(string $formName = 'default'): bool
    {
        $token = $_POST['csrf_token'] ?? '';

        if (empty($token)) {
            return false;
        }

        return self::validateToken($token, $formName);
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanupExpiredTokens(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }

        $currentTime = time();
        foreach ($_SESSION[self::SESSION_KEY] as $formName => $data) {
            if ($currentTime - $data['timestamp'] > 3600) {
                unset($_SESSION[self::SESSION_KEY][$formName]);
            }
        }
    }

    /**
     * Middleware to protect routes
     */
    public static function protect(string $formName = 'default'): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::validateRequest($formName)) {
                http_response_code(403);
                die('CSRF token validation failed. Please refresh the page and try again.');
            }
        }
    }
}
