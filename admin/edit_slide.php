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

$slidesFile = 'slides.json';
$slideId = $_GET['id'] ?? null;
$isEditing = ($slideId !== null);
$pageTitle = $isEditing ? "Edit Slide" : "Add New Slide";

require_once __DIR__ . '/partials/header.php';

$slide = null;
$errors = [];

if ($slideId !== null) {
    $result = $dataManager->findById($slidesFile, $slideId);
    if ($result) {
        $slide = $result['item'];
        $isEditing = true;
    } else {
        $_SESSION['message'] = "Slide not found.";
        $_SESSION['message_type'] = "error";
        header('Location: manage_slides.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::protect('edit_slide');

    // Validate required fields
    $required = ['title', 'headline'];
    $errors = Helpers::validateRequired($required, $_POST);

    if (empty($errors)) {
        $newData = [
            'title' => Helpers::sanitizeString($_POST['title']),
            'headline' => Helpers::sanitizeString($_POST['headline']),
            'learnMoreText' => Helpers::sanitizeString($_POST['learnMoreText'] ?? ''),
            'learnMoreLink' => Helpers::sanitizeString($_POST['learnMoreLink'] ?? ''),
            'callText' => Helpers::sanitizeString($_POST['callText'] ?? ''),
            'callLink' => Helpers::sanitizeString($_POST['callLink'] ?? ''),
            'background' => $_POST['existing_background'] ?? '',
            'image' => $_POST['existing_image'] ?? '',
            'status' => $_POST['status'] ?? 'published'
        ];

        // Handle file uploads using FileUploader
        $uploader = new FileUploader(__DIR__ . '/../uploads/slides/');

        // Handle background image
        if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $uploader->upload($_FILES['background']);

            if ($uploadResult['success']) {
                // Delete old background if it exists
                if ($isEditing && !empty($newData['background'])) {
                    $uploader->delete($newData['background']);
                }
                $newData['background'] = $uploadResult['path'];
            } else {
                $errors['background'] = $uploadResult['error'];
            }
        }

        // Handle foreground image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $uploader->upload($_FILES['image']);

            if ($uploadResult['success']) {
                // Delete old image if it exists
                if ($isEditing && !empty($newData['image'])) {
                    $uploader->delete($newData['image']);
                }
                $newData['image'] = $uploadResult['path'];
            } else {
                $errors['image'] = $uploadResult['error'];
            }
        }

        if (empty($errors)) {
            if ($isEditing) {
                if ($dataManager->update($slidesFile, $slideId, $newData)) {
                    $activityLogger->log('update', 'slide', $slideId, [
                        'title' => $newData['title']
                    ]);
                    $_SESSION['message'] = "Slide updated successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to update slide.";
                }
            } else {
                if ($dataManager->create($slidesFile, $newData)) {
                    $activityLogger->log('create', 'slide', $newData['id'] ?? 'unknown', [
                        'title' => $newData['title']
                    ]);
                    $_SESSION['message'] = "Slide added successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $errors['save'] = "Failed to create slide.";
                }
            }

            if (empty($errors)) {
                header('Location: manage_slides.php');
                exit;
            }
        }
    }
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold"><?= $pageTitle ?></h1>
    <a href="manage_slides.php"
        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
        ← Back to Slides
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
        <?= CSRFProtection::getTokenField('edit_slide') ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div>
                <div class="mb-4">
                    <label for="title" class="block font-medium mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($slide['title'] ?? '') ?>"
                        required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-[var(--text-muted)] mt-1">Main title displayed on the slide</p>
                </div>

                <div class="mb-4">
                    <label for="headline" class="block font-medium mb-1">Headline <span
                            class="text-red-500">*</span></label>
                    <textarea id="headline" name="headline" rows="3" required
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($slide['headline'] ?? '') ?></textarea>
                    <p class="text-xs text-[var(--text-muted)] mt-1">Supporting text or description</p>
                </div>

                <div class="mb-4">
                    <label for="status" class="block font-medium mb-1">Status</label>
                    <select id="status" name="status"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="published" <?= ($slide['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($slide['status'] ?? 'published') === 'draft' ? 'selected' : '' ?>>
                            Draft</option>
                    </select>
                </div>

                <hr class="border-[var(--border-main)] my-4">
                <h3 class="text-lg font-semibold mb-3">Learn More Button</h3>

                <div class="mb-4">
                    <label for="learnMoreText" class="block font-medium mb-1">Button Text</label>
                    <input type="text" id="learnMoreText" name="learnMoreText"
                        value="<?= htmlspecialchars($slide['learnMoreText'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Learn More">
                </div>

                <div class="mb-4">
                    <label for="learnMoreLink" class="block font-medium mb-1">Button Link</label>
                    <input type="text" id="learnMoreLink" name="learnMoreLink"
                        value="<?= htmlspecialchars($slide['learnMoreLink'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="/about">
                </div>

                <hr class="border-[var(--border-main)] my-4">
                <h3 class="text-lg font-semibold mb-3">Call to Action Button</h3>

                <div class="mb-4">
                    <label for="callText" class="block font-medium mb-1">Button Text</label>
                    <input type="text" id="callText" name="callText"
                        value="<?= htmlspecialchars($slide['callText'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Contact Us">
                </div>

                <div class="mb-4">
                    <label for="callLink" class="block font-medium mb-1">Button Link</label>
                    <input type="text" id="callLink" name="callLink"
                        value="<?= htmlspecialchars($slide['callLink'] ?? '') ?>"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="/contact">
                </div>
            </div>

            <!-- Right Column - Images -->
            <div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">Background Image</label>
                    <?php if ($isEditing && !empty($slide['background'])): ?>
                    <div class="mb-2">
                        <img src="../<?= htmlspecialchars($slide['background']) ?>"
                            class="w-full h-48 object-cover rounded-md border border-[var(--border-main)]">
                        <input type="hidden" name="existing_background"
                            value="<?= htmlspecialchars($slide['background']) ?>">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="background" accept="image/*"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                    <p class="text-xs text-[var(--text-muted)] mt-2">Full-width background image for the slide. Recommended
                        size: 1920x1080px</p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Foreground Image (Optional)</label>
                    <?php if ($isEditing && !empty($slide['image'])): ?>
                    <div class="mb-2">
                        <img src="../<?= htmlspecialchars($slide['image']) ?>"
                            class="w-full h-48 object-cover rounded-md border border-[var(--border-main)]">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($slide['image']) ?>">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                        class="w-full bg-transparent border border-[var(--border-main)] rounded-md p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                    <p class="text-xs text-[var(--text-muted)] mt-2">Optional foreground/overlay image. Max size: 5MB</p>
                </div>

                <div class="bg-blue-900/20 border border-blue-600/30 rounded-lg p-4 mt-6">
                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Tips for Best Results
                    </h4>
                    <ul class="text-sm text-[var(--text-muted)] space-y-1">
                        <li>• Use high-quality images (1920x1080 or larger)</li>
                        <li>• Ensure text is readable over background</li>
                        <li>• Keep file sizes under 5MB for fast loading</li>
                        <li>• Test buttons lead to correct pages</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-8 border-t border-[var(--border-main)] pt-6 flex justify-end gap-4">
            <a href="manage_slides.php"
                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200">Cancel</a>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                <?= $isEditing ? 'Update Slide' : 'Create Slide' ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
