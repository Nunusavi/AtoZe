<?php
class JSONModel {
    public static function load($file) {
        $path = __DIR__ . "/../../Json/{$file}.json";
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true);
    }

    public static function save($file, $data) {
        $path = __DIR__ . "/../../Json/{$file}.json";
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
