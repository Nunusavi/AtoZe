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

$projectsFile = 'projects.json';
$projectId = $_GET['id'] ?? null;
$isEditing = ($projectId !== null);
$pageTitle = $isEditing ? "Edit Project" : "Add New Project";

require_once __DIR__ . '/partials/header.php';

$project = null;
$errors = [];

if ($projectId !== null) {
    $result = $dataManager->findById($projectsFile, $projectId);
    if ($result) {
        $project = $result['item'];
        $isEditing = true;
    } else {
        $_SESSION['message'] = "Project not found.";
        $_SESSION['message_type'] = "error";
        header('Location: manage_projects.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::protect('edit_project');

    // Validate required fields
    $required = ['company_name'];
    $errors = Helpers::validateRequired($required, $_POST);

    if (empty($errors)) {
        // Convert items textarea to an array
        $itemsArray = !empty($_POST['items']) ?
            array_values(array_filter(
                array_map([Helpers::class, 'sanitizeString'],
                explode("\n", $_POST['items']))
            )) : [];

        $newData = [
            'company_name' => Helpers::sanitizeString($_POST['company_name']),
            'items' => $itemsArray,
            'total_price' => Helpers::sanitizeString($_POST['total_price'] ?? ''),
            'location' => Helpers::sanitizeString($_POST['location'] ?? ''),
            'description' => Helpers::sanitizeString($_POST['description'] ?? ''),
            'image' => $_POST['existing_image'] ?? '',
            'status' => $_POST['status'] ?? 'published'
        ];

        // Handle file upload using FileUploader
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploader = new FileUploader(__DIR__ . '/../uploads/projects/');
            $uploadResult = $uploader->upload($_FILES['image']);

            if ($uploadResult['success']) {
                // Delete old image if it exists
                if ($isEditing && !empty($newData['image'])) {
                    $uploader->delete($newData['image']);
                }
                $newData['image'] = $uploadResult['path'];
            } else {
                $errors['upload'] = $uploadResult['error'];
            }
        }

        if (empty($errors)) {
            if ($isEditing) {
                if ($dataManager->update($projectsFile, $projectId, $newData)) {
                    $activityLogger->log('update', 'project', $projectId, [
                        'company_name' => $newData['company_name']
                    ]);
                    $_SESSION['message'] = "Project updated successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to update project.";
                }
            } else {
                if ($dataManager->create($projectsFile, $newData)) {
                    $activityLogger->log('create', 'project', $newData['id'] ?? 'unknown', [
                        'company_name' => $newData['company_name']
                    ]);
                    $_SESSION['message'] = "Project added successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to create project.";
                }
            }

            if (empty($errors)) {
                header('Location: manage_projects.php');
                exit;
            }
        }
    }
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold"><?= $pageTitle ?></h1>
    <a href="manage_projects.php"
        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
        ← Back to Projects
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
        <?= CSRFProtection::getTokenField('edit_project') ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="mb-4">
                    <label for="company_name" class="block font-medium mb-1">Company Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="company_name" name="company_name"
                        value="<?= htmlspecialchars($project['company_name'] ?? '') ?>" required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="location" class="block font-medium mb-1">Location</label>
                    <input type="text" id="location" name="location"
                        value="<?= htmlspecialchars($project['location'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="total_price" class="block font-medium mb-1">Total Price</label>
                    <input type="text" id="total_price" name="total_price"
                        value="<?= htmlspecialchars($project['total_price'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., $50,000">
                </div>
                <div class="mb-4">
                    <label for="status" class="block font-medium mb-1">Status</label>
                    <select id="status" name="status"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="published" <?= ($project['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($project['status'] ?? 'published') === 'draft' ? 'selected' : '' ?>>
                            Draft</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="description" class="block font-medium mb-1">Description</label>
                    <textarea id="description" name="description" rows="5"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                    <p class="text-xs text-[var(--text-muted)] mt-1">Brief project description</p>
                </div>
            </div>

            <div>
                <div class="mb-4">
                    <label for="items" class="block font-medium mb-1">Items/Products (one per line)</label>
                    <textarea id="items" name="items" rows="10"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter each item on a new line&#10;Example:&#10;Firewall&#10;Security Camera&#10;Access Control System"><?= isset($project['items']) ? htmlspecialchars(implode("\n", $project['items'])) : '' ?></textarea>
                    <p class="text-xs text-[var(--text-muted)] mt-1">List all products/services included in this project</p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Project Image</label>
                    <?php if ($isEditing && !empty($project['image'])): ?>
                    <div class="mb-2">
                        <img src="../<?= htmlspecialchars($project['image']) ?>"
                            class="h-32 w-auto object-cover rounded-md border border-[var(--border-main)]">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($project['image']) ?>">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                    <p class="text-xs text-[var(--text-muted)] mt-2">Supported formats: JPEG, PNG, WebP, GIF. Max size:
                        5MB</p>
                </div>
            </div>
        </div>

        <div class="mt-8 border-t border-[var(--border-main)] pt-6 flex justify-end gap-4">
            <a href="manage_projects.php"
                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200">Cancel</a>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                <?= $isEditing ? 'Update Project' : 'Create Project' ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
