<?php
class AuthController {
    public static function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = User::find($username);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'fail', 'message' => 'Invalid credentials']);
            }
            exit;
        }
    }

    public static function logout() {
        session_destroy();
        header("Location: /AtoZe/Admin/login");
        exit;
    }
}
