<?php
class User {
    private static $file = __DIR__ . '/../data/users.json';

    public static function find($username) {
        $users = json_decode(file_get_contents(self::$file), true);
        foreach ($users as $user) {
            if ($user['username'] === $username) return $user;
        }
        return null;
    }
}
