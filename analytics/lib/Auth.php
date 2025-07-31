<?php
namespace Analytics;

class Auth {
    private string $userFile;

    public function __construct(string $userFile) {
        $this->userFile = $userFile;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['analytics_user']);
    }

    public function getUser(): ?string {
        return $_SESSION['analytics_user'] ?? null;
    }

    public function login(string $username, string $password): bool {
        $users = json_decode(file_get_contents($this->userFile), true);
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
                $_SESSION['analytics_user'] = $username;
                return true;
            }
        }
        return false;
    }

    public function logout(): void {
        session_unset();
        session_destroy();
    }
}
