<?php
session_start();

require_once __DIR__ . '/lib/Auth.php';

use Analytics\Auth;
use CMS\CSRFProtection;
use CMS\Helpers;

$auth = new Auth(__DIR__ . '/config/users.json');
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$productsFile = 'normalized_products.json';
$productId = $_GET['id'] ?? null;
$isEditing = ($productId !== null);
$pageTitle = $isEditing ? "Edit Product" : "Add New Product";

require_once __DIR__ . '/partials/header.php';

$product = null;
$errors = [];

if ($productId !== null) {
    $result = $dataManager->findById($productsFile, $productId);
    if ($result) {
        $product = $result['item'];
        $isEditing = true;
    } else {
        $_SESSION['message'] = "Product not found.";
        $_SESSION['message_type'] = "error";
        header('Location: manage_products.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::protect('edit_product');

    // Validate required fields
    $required = ['name', 'category', 'brand'];
    $errors = Helpers::validateRequired($required, $_POST);

    if (empty($errors)) {
        $newData = [
            'category' => Helpers::sanitizeString($_POST['category']),
            'brand' => Helpers::sanitizeString($_POST['brand']),
            'model' => Helpers::sanitizeString($_POST['model'] ?? ''),
            'name' => Helpers::sanitizeString($_POST['name']),
            'description' => Helpers::sanitizeString($_POST['description'] ?? ''),
            'summary' => Helpers::sanitizeString($_POST['summary'] ?? ''),
            'features' => array_values(array_filter(
                array_map([Helpers::class, 'sanitizeString'], $_POST['features'] ?? [])
            )),
            'image' => $_POST['existing_images'] ?? [],
            'status' => $_POST['status'] ?? 'published'
        ];

        // Handle file uploads using FileUploader
        $uploader = new FileUploader(__DIR__ . '/../uploads/products/');

        if (!empty($_FILES['new_images']['name'][0])) {
            $uploadedFiles = [
                'name' => $_FILES['new_images']['name'],
                'type' => $_FILES['new_images']['type'],
                'tmp_name' => $_FILES['new_images']['tmp_name'],
                'error' => $_FILES['new_images']['error'],
                'size' => $_FILES['new_images']['size']
            ];

            $uploadResults = $uploader->uploadMultiple($uploadedFiles);

            foreach ($uploadResults as $result) {
                if ($result['success']) {
                    $newData['image'][] = $result['path'];
                } else {
                    $errors['upload'] = $result['error'];
                }
            }
        }

        // Handle deleted images
        $imagesToDelete = $_POST['deleted_images'] ?? [];
        foreach ($imagesToDelete as $imagePath) {
            $uploader->delete($imagePath);
        }
        $newData['image'] = array_values(array_diff($newData['image'], $imagesToDelete));

        if (empty($errors)) {
            if ($isEditing) {
                if ($dataManager->update($productsFile, $productId, $newData)) {
                    $activityLogger->log('update', 'product', $productId, [
                        'name' => $newData['name']
                    ]);
                    $_SESSION['message'] = "Product updated successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to update product.";
                }
            } else {
                if ($dataManager->create($productsFile, $newData)) {
                    $activityLogger->log('create', 'product', $newData['id'] ?? 'unknown', [
                        'name' => $newData['name']
                    ]);
                    $_SESSION['message'] = "Product added successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to create product.";
                }
            }

            if (empty($errors)) {
                header('Location: manage_products.php');
                exit;
            }
        }
    }
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold"><?= $pageTitle ?></h1>
    <a href="manage_products.php"
        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
        ← Back to Products
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-4 p-4 rounded-lg shadow-lg bg-red-600 text-white">
    <h3 class="font-bold mb-2">Please fix the following errors:</h3>
    <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="bg-[var(--bg-card)] p-6 rounded-lg shadow-lg">
    <form method="POST" action="" enctype="multipart/form-data">
        <?= CSRFProtection::getTokenField('edit_product') ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="mb-4">
                    <label for="name" class="block font-medium mb-1">Product Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                        required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="category" class="block font-medium mb-1">Category <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="category" name="category"
                        value="<?= htmlspecialchars($product['category'] ?? '') ?>" required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="brand" class="block font-medium mb-1">Brand <span class="text-red-500">*</span></label>
                    <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>"
                        required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="model" class="block font-medium mb-1">Model</label>
                    <input type="text" id="model" name="model" value="<?= htmlspecialchars($product['model'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="status" class="block font-medium mb-1">Status</label>
                    <select id="status" name="status"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="published" <?= ($product['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($product['status'] ?? 'published') === 'draft' ? 'selected' : '' ?>>
                            Draft</option>
                    </select>
                </div>
            </div>

            <div>
                <div class="mb-4">
                    <label for="summary" class="block font-medium mb-1">Summary</label>
                    <textarea id="summary" name="summary" rows="3"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($product['summary'] ?? '') ?></textarea>
                    <p class="text-xs text-[var(--text-muted)] mt-1">Brief one-sentence description</p>
                </div>
                <div class="mb-4">
                    <label for="description" class="block font-medium mb-1">Description</label>
                    <textarea id="description" name="description" rows="8"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    <p class="text-xs text-[var(--text-muted)] mt-1">Detailed product description</p>
                </div>
            </div>
        </div>

        <div class="mt-6 border-t border-[var(--border-main)] pt-6">
            <h2 class="text-xl font-bold mb-3">Features</h2>
            <div id="features-container" class="space-y-2">
                <?php if (!empty($product['features'])): foreach ($product['features'] as $feature): ?>
                <div class="flex items-center gap-2 feature-item">
                    <input type="text" name="features[]" value="<?= htmlspecialchars($feature) ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button"
                        class="remove-feature-btn bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition-colors">&times;</button>
                </div>
                <?php endforeach;
                else: ?>
                <p class="text-sm text-[var(--text-muted)]">No features added yet. Click "Add Feature" to get started.
                </p>
                <?php endif; ?>
            </div>
            <button type="button" id="add-feature-btn"
                class="mt-2 bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors">+
                Add Feature</button>
        </div>

        <div class="mt-6 border-t border-[var(--border-main)] pt-6">
            <h2 class="text-xl font-bold mb-3">Images</h2>

            <?php if (!empty($product['image'])): ?>
            <div id="images-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-4">
                <?php foreach ($product['image'] as $image): ?>
                <div class="relative group image-item">
                    <img src="../<?= htmlspecialchars($image) ?>" class="w-full h-32 object-cover rounded-lg border border-[var(--border-main)]">
                    <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($image) ?>">
                    <button type="button"
                        class="remove-image-btn absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white rounded-full h-7 w-7 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div id="images-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-4">
                <p class="text-sm text-[var(--text-muted)] col-span-full">No images uploaded yet.</p>
            </div>
            <?php endif; ?>

            <div class="bg-[var(--bg-main)] p-4 rounded-lg border border-[var(--border-main)]">
                <label for="new_images" class="block font-medium mb-2">Add New Images</label>
                <input type="file" id="new_images" name="new_images[]" multiple accept="image/*"
                    class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                <p class="text-xs text-[var(--text-muted)] mt-2">Supported formats: JPEG, PNG, WebP, GIF. Max size: 5MB
                    per image.</p>
            </div>
            <div id="deleted-images-container"></div>
        </div>

        <div class="mt-8 border-t border-[var(--border-main)] pt-6 flex justify-end gap-4">
            <a href="manage_products.php"
                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200">Cancel</a>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                <?= $isEditing ? 'Update Product' : 'Create Product' ?>
            </button>
        </div>
    </form>
</div>

<template id="feature-template">
    <div class="flex items-center gap-2 feature-item">
        <input type="text" name="features[]"
            class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter a new feature">
        <button type="button"
            class="remove-feature-btn bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition-colors">&times;</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const featuresContainer = document.getElementById('features-container');
    const addFeatureBtn = document.getElementById('add-feature-btn');
    const featureTemplate = document.getElementById('feature-template');

    // Add feature functionality
    addFeatureBtn.addEventListener('click', () => {
        const newFeature = featureTemplate.content.cloneNode(true);
        featuresContainer.appendChild(newFeature);

        // Focus on the newly added input
        const newInput = featuresContainer.querySelector('.feature-item:last-child input');
        if (newInput) newInput.focus();
    });

    // Remove feature functionality
    featuresContainer.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('remove-feature-btn')) {
            e.target.closest('.feature-item').remove();

            // Show placeholder if no features left
            if (featuresContainer.querySelectorAll('.feature-item').length === 0) {
                featuresContainer.innerHTML = '<p class="text-sm text-[var(--text-muted)]">No features added yet. Click "Add Feature" to get started.</p>';
            }
        }
    });

    // Image removal functionality
    const imagesContainer = document.getElementById('images-container');
    const deletedImagesContainer = document.getElementById('deleted-images-container');

    imagesContainer.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('remove-image-btn')) {
            const imageItem = e.target.closest('.image-item');
            const imagePath = imageItem.querySelector('input[name="existing_images[]"]').value;

            // Create hidden input to mark for deletion
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'deleted_images[]';
            hiddenInput.value = imagePath;
            deletedImagesContainer.appendChild(hiddenInput);

            // Remove from DOM
            imageItem.remove();

            // Show placeholder if no images left
            if (imagesContainer.querySelectorAll('.image-item').length === 0) {
                imagesContainer.innerHTML = '<p class="text-sm text-[var(--text-muted)] col-span-full">No images uploaded yet.</p>';
            }
        }
    });

    // Image preview for new uploads
    const newImagesInput = document.getElementById('new_images');
    newImagesInput.addEventListener('change', (e) => {
        const files = e.target.files;
        if (files.length > 0) {
            console.log(`Selected ${files.length} file(s) for upload`);
        }
    });
});
</script>


<?php require_once __DIR__ . '/partials/footer.php'; ?>
