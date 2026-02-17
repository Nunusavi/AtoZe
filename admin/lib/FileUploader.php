<?php
namespace CMS;

class FileUploader
{
    private string $uploadDir;
    private array $allowedTypes;
    private int $maxFileSize;
    private bool $createThumbnails;

    public function __construct(string $uploadDir, array $allowedTypes = [], int $maxFileSize = 5242880, bool $createThumbnails = true)
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedTypes = empty($allowedTypes) ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif'] : $allowedTypes;
        $this->maxFileSize = $maxFileSize; // Default 5MB
        $this->createThumbnails = $createThumbnails;

        // Create directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a single file
     */
    public function upload(array $file): array
    {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Generate safe filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '-' . time() . '.' . $extension;
        $targetPath = $this->uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        // Create thumbnail if it's an image
        $thumbnailPath = null;
        if ($this->createThumbnails && $this->isImage($file['type'])) {
            $thumbnailPath = $this->createThumbnail($targetPath, $filename);
        }

        // Optimize image
        if ($this->isImage($file['type'])) {
            $this->optimizeImage($targetPath);
        }

        return [
            'success' => true,
            'filename' => $filename,
            'path' => str_replace(__DIR__ . '/../../', '', $targetPath),
            'thumbnail' => $thumbnailPath,
            'size' => filesize($targetPath)
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files): array
    {
        $results = [];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $results[] = $this->upload($file);
            }
        }

        return $results;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSize = Helpers::formatFileSize($this->maxFileSize);
            return ['success' => false, 'error' => "File too large. Maximum size: {$maxSize}"];
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $this->allowedTypes)];
        }

        // Additional image validation
        if ($this->isImage($mimeType)) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['success' => false, 'error' => 'Invalid image file'];
            }
        }

        // Validate filename (prevent directory traversal)
        $filename = basename($file['name']);
        if (preg_match('/[^a-zA-Z0-9\._-]/', $filename)) {
            // Sanitize filename
            $file['name'] = preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);
        }

        return ['success' => true];
    }

    /**
     * Check if file is an image
     */
    private function isImage(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * Create thumbnail
     */
    private function createThumbnail(string $sourcePath, string $filename): ?string
    {
        $thumbnailDir = $this->uploadDir . 'thumbs/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailPath = $thumbnailDir . $filename;
        $maxWidth = 300;
        $maxHeight = 300;

        list($width, $height, $type) = getimagesize($sourcePath);

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        // Create new image
        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }

        // Resize
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save thumbnail
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $thumbnailPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $thumbnailPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $thumbnailPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb, $thumbnailPath, 85);
                break;
        }

        imagedestroy($thumb);
        imagedestroy($source);

        return str_replace(__DIR__ . '/../../', '', $thumbnailPath);
    }

    /**
     * Optimize image (reduce file size)
     */
    private function optimizeImage(string $path): bool
    {
        list($width, $height, $type) = getimagesize($path);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                imagejpeg($image, $path, 85); // 85% quality
                imagedestroy($image);
                return true;

            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                imagepng($image, $path, 8); // Compression level 8
                imagedestroy($image);
                return true;

            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($path);
                imagewebp($image, $path, 85);
                imagedestroy($image);
                return true;
        }

        return false;
    }

    /**
     * Delete file
     */
    public function delete(string $path): bool
    {
        $fullPath = __DIR__ . '/../../' . $path;

        if (file_exists($fullPath)) {
            // Delete thumbnail if exists
            $thumbPath = str_replace(basename($path), 'thumbs/' . basename($path), $fullPath);
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }

            return @unlink($fullPath);
        }

        return false;
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(array $paths): int
    {
        $deleted = 0;
        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $deleted++;
            }
        }
        return $deleted;
    }
}
