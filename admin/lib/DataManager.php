<?php

namespace CMS;

class DataManager
{
    private string $jsonDir;

    /**
     * Constructor for the DataManager.
     * @param string $jsonDirectory The absolute path to the directory containing JSON files.
     */
    public function __construct(string $jsonDirectory)
    {
        // Use realpath to resolve any '..' and get a clean, absolute path.
        $this->jsonDir = realpath($jsonDirectory);
        if ($this->jsonDir === false || !is_dir($this->jsonDir)) {
            // A simple but effective debugging feature.
            // In a production environment, you would log this error.
            die("Error: The specified JSON directory does not exist or is not a directory: " . htmlspecialchars($jsonDirectory));
        }
    }

    /**
     * Reads and decodes a JSON file.
     * @param string $filename The name of the JSON file (e.g., 'normalized_products.json').
     * @return array The decoded data as an associative array. Returns empty array on failure.
     */
    public function getData(string $filename): array
    {
        $filePath = $this->jsonDir . '/' . $filename;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            // Log this error in a real application.
            // For now, returning an empty array is a safe default.
            return [];
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        // Check for JSON decoding errors.
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle error, maybe log json_last_error_msg().
            return [];
        }

        return $data ?? [];
    }

    /**
     * Encodes data to JSON and saves it to a file.
     * @param string $filename The name of the JSON file.
     * @param array $data The data to be saved.
     * @return bool True on success, false on failure.
     */
    public function saveData(string $filename, array $data): bool
    {
        $filePath = $this->jsonDir . '/' . $filename;

        if (!is_writable(dirname($filePath))) {
            // Debugging check for directory permissions.
            error_log("DataManager Error: Directory is not writable: " . dirname($filePath));
            return false;
        }

        // JSON_PRETTY_PRINT makes the file human-readable.
        // JSON_UNESCAPED_SLASHES prevents escaping forward slashes in URLs/paths.
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonContent === false) {
            error_log("DataManager Error: Failed to encode JSON for file " . $filename);
            return false;
        }

        // Use file_put_contents for an atomic write operation.
        // LOCK_EX prevents other processes from writing to the file at the same time.
        $result = file_put_contents($filePath, $jsonContent, LOCK_EX);

        return $result !== false;
    }

    /**
     * Find item by ID
     */
    public function findById(string $filename, string $id): ?array
    {
        $data = $this->getData($filename);
        foreach ($data as $index => $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return ['index' => $index, 'item' => $item];
            }
        }
        return null;
    }

    /**
     * Create new item with UUID
     */
    public function create(string $filename, array $item): bool
    {
        $data = $this->getData($filename);
        $item['id'] = $item['id'] ?? Helpers::generateUUID();
        $item['created_at'] = date('c');
        $item['updated_at'] = date('c');
        $item['status'] = $item['status'] ?? 'published';

        $data[] = $item;
        return $this->saveData($filename, $data);
    }

    /**
     * Update item by ID
     */
    public function update(string $filename, string $id, array $updates): bool
    {
        $data = $this->getData($filename);
        $found = $this->findById($filename, $id);

        if ($found === null) {
            return false;
        }

        $updates['updated_at'] = date('c');
        $data[$found['index']] = array_merge($found['item'], $updates);

        return $this->saveData($filename, $data);
    }

    /**
     * Delete item by ID
     */
    public function delete(string $filename, string $id): bool
    {
        $data = $this->getData($filename);
        $found = $this->findById($filename, $id);

        if ($found === null) {
            return false;
        }

        array_splice($data, $found['index'], 1);
        return $this->saveData($filename, $data);
    }

    /**
     * Duplicate/clone item
     */
    public function duplicate(string $filename, string $id): ?string
    {
        $found = $this->findById($filename, $id);
        if ($found === null) {
            return null;
        }

        $newItem = $found['item'];
        $newItem['id'] = Helpers::generateUUID();
        $newItem['created_at'] = date('c');
        $newItem['updated_at'] = date('c');

        // Add "Copy" to name if exists
        if (isset($newItem['name'])) {
            $newItem['name'] = $newItem['name'] . ' (Copy)';
        } elseif (isset($newItem['title'])) {
            $newItem['title'] = $newItem['title'] . ' (Copy)';
        } elseif (isset($newItem['company_name'])) {
            $newItem['company_name'] = $newItem['company_name'] . ' (Copy)';
        }

        $data = $this->getData($filename);
        $data[] = $newItem;

        return $this->saveData($filename, $data) ? $newItem['id'] : null;
    }
}