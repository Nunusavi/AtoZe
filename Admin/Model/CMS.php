<?php
class CMS
{
    public static function saveEntry($type, $data, $files)
    {
        $filePath = __DIR__ . "/../../Json/{$type}.json";
        $items = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

        $isUpdate = false;
        foreach ($items as &$item) {
            if ($item['name'] === $data['id']) {
                $item['name'] = $data['name'];
                $item['text'] = $data['text'];
                $item['socials'] = [
                    'facebook' => $data['facebook'] ?? '#',
                    'twitter' => $data['twitter'] ?? '#',
                    'instagram' => $data['instagram'] ?? '#',
                    'whatsapp' => $data['whatsapp'] ?? '#',
                    'phone' => $data['phone'] ?? '#',
                ];
                $isUpdate = true;
                break;
            }
        }
        if (!$isUpdate) {
            $item = [
                'name' => $data['name'],
                'text' => $data['text'],
                'socials' => [
                    'facebook' => $data['facebook'] ?? '#',
                    'twitter' => $data['twitter'] ?? '#',
                    'instagram' => $data['instagram'] ?? '#',
                    'whatsapp' => $data['whatsapp'] ?? '#',
                    'phone' => $data['phone'] ?? '#',
                ],
            ];
            $items[] = $item;
        }

        if (!empty($files['image']['name'])) {
            $ext = pathinfo($files['image']['name'], PATHINFO_EXTENSION);
            $imageName = time() . "." . $ext;
            $targetDir = __DIR__ . "/../../images/{$type}/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            move_uploaded_file($files['image']['tmp_name'], $targetDir . $imageName);
            $item['image'] = "images/{$type}/{$imageName}";
        }

        file_put_contents($filePath, json_encode($items, JSON_PRETTY_PRINT));
        return true;
    }

    public static function deleteEntry($type, $name)
    {
        $filePath = __DIR__ . "/../../Json/{$type}.json";
        $items = json_decode(file_get_contents($filePath), true);
        $items = array_filter($items, fn($i) => $i['name'] !== $name);
        file_put_contents($filePath, json_encode(array_values($items), JSON_PRETTY_PRINT));
        return true;
    }
    public static function getData($type)
    {
        $filePath = __DIR__ . "/../../Json/{$type}.json";
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_writable($dir)) {
            // Try to set permissions
            @chmod($dir, 0777);
            clearstatcache(true, $dir);
            if (!is_writable($dir)) {
                throw new Exception("Directory not writable: " . $dir);
            }
        }
        if (!file_exists($filePath)) return [];
        return json_decode(file_get_contents($filePath), true);
    }
}
