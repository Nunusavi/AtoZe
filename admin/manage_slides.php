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
$pageTitle = "Manage Slides";

require_once __DIR__ . '/partials/header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::protect('manage_slides');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete':
            $slideId = $_POST['slide_id'] ?? '';
            if ($dataManager->delete($slidesFile, $slideId)) {
                $activityLogger->log('delete', 'slide', $slideId);
                $_SESSION['message'] = "Slide deleted successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: Could not delete slide.";
                $_SESSION['message_type'] = "error";
            }
            break;

        case 'duplicate':
            $slideId = $_POST['slide_id'] ?? '';
            $newId = $dataManager->duplicate($slidesFile, $slideId);

            if ($newId) {
                $activityLogger->log('create', 'slide', $newId, ['duplicated_from' => $slideId]);
                $_SESSION['message'] = "Slide duplicated successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: Could not duplicate slide.";
                $_SESSION['message_type'] = "error";
            }
            break;
    }

    header('Location: manage_slides.php');
    exit;
}

// Get all slides
$allSlides = $dataManager->getData($slidesFile);

// Search functionality
$searchQuery = $_GET['search'] ?? '';
if (!empty($searchQuery)) {
    $allSlides = Helpers::search($allSlides, $searchQuery, ['title', 'headline']);
}

// Filter by status
$filterStatus = $_GET['status'] ?? '';
if (!empty($filterStatus)) {
    $allSlides = array_filter($allSlides, function($slide) use ($filterStatus) {
        return ($slide['status'] ?? 'published') === $filterStatus;
    });
}

// Sort functionality
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'desc';
$allSlides = Helpers::sortBy($allSlides, $sortBy, $sortDir);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$paginated = Helpers::paginate($allSlides, $page, $perPage);
$slides = $paginated['items'];
$pagination = $paginated['pagination'];
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Slide Management</h1>
    <a href="edit_slide.php"
        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
        + Add New Slide
    </a>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="mb-4 p-4 rounded-lg shadow-lg <?= $_SESSION['message_type'] === 'success' ? 'bg-green-600' : 'bg-red-600' ?> text-white">
    <?= htmlspecialchars($_SESSION['message']) ?>
</div>
<?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>

<!-- Search and Filter Bar -->
<div class="bg-[var(--bg-card)] p-4 rounded-lg shadow-lg mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>"
                placeholder="Search slides..."
                class="w-full px-3 py-2 bg-[var(--bg-main)] border border-[var(--border-main)] rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status"
                class="w-full px-3 py-2 bg-[var(--bg-main)] border border-[var(--border-main)] rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                Apply
            </button>
            <a href="manage_slides.php"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Debug Info -->
<div class="mb-4 p-4 bg-blue-900/20 border border-blue-600/30 rounded-lg">
    <strong>Debug:</strong> Found <?= count($slides) ?> slides on this page (<?= count($allSlides) ?> total after filters)
</div>

<!-- Slides Table -->
<div class="bg-[var(--bg-card)] p-6 rounded-lg shadow-lg overflow-x-auto">
    <table class="min-w-full text-left">
        <thead class="border-b border-[var(--border-main)]">
            <tr>
                <th class="py-3 px-4">ID</th>
                <th class="py-3 px-4">Product Image</th>
                <th class="py-3 px-4">Headline</th>
                <th class="py-3 px-4">Buttons</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($slides)): ?>
            <tr>
                <td colspan="6" class="py-4 px-4 text-center text-[var(--text-muted)]">
                    No slides found.
                    <?php if ($searchQuery || $filterStatus): ?>
                    <a href="manage_slides.php" class="text-blue-500 hover:underline">Clear filters</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($slides as $slide): ?>
            <tr class="border-b border-[var(--border-main)] hover:bg-gray-500/10">
                <td class="py-2 px-4 text-xs text-[var(--text-muted)]">
                    <?= htmlspecialchars(substr($slide['id'] ?? '', 0, 8)) ?>...
                </td>
                <td class="py-2 px-4">
                    <?php if (!empty($slide['image'])): ?>
                    <img src="../<?= htmlspecialchars($slide['image']) ?>" alt="Product"
                        class="h-12 w-12 object-contain rounded-md bg-white p-1">
                    <?php else: ?>
                    <div class="h-12 w-12 bg-gray-600 rounded-md flex items-center justify-center text-xs">No Image</div>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4">
                    <div class="font-medium"><?= htmlspecialchars($slide['title'] ?? '') ?></div>
                    <div class="text-sm text-[var(--text-muted)]"><?= htmlspecialchars(Helpers::truncate($slide['headline'] ?? '', 60)) ?></div>
                </td>
                <td class="py-2 px-4 text-xs">
                    <?php if (!empty($slide['learnMoreText'])): ?>
                    <div class="text-blue-400"><?= htmlspecialchars($slide['learnMoreText']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($slide['callText'])): ?>
                    <div class="text-green-400"><?= htmlspecialchars($slide['callText']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4">
                    <?php $status = $slide['status'] ?? 'published'; ?>
                    <span class="px-2 py-1 text-xs rounded-full <?= $status === 'published' ? 'bg-green-600' : 'bg-yellow-600' ?> text-white">
                        <?= ucfirst($status) ?>
                    </span>
                </td>
                <td class="py-2 px-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="edit_slide.php?id=<?= htmlspecialchars($slide['id'] ?? '') ?>"
                            class="text-blue-500 hover:text-blue-400 font-semibold">Edit</a>

                        <form method="POST" class="inline">
                            <?= CSRFProtection::getTokenField('manage_slides') ?>
                            <input type="hidden" name="action" value="duplicate">
                            <input type="hidden" name="slide_id" value="<?= htmlspecialchars($slide['id'] ?? '') ?>">
                            <button type="submit" class="text-green-500 hover:text-green-400 font-semibold">Clone</button>
                        </form>

                        <form method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this slide?');" class="inline">
                            <?= CSRFProtection::getTokenField('manage_slides') ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="slide_id" value="<?= htmlspecialchars($slide['id'] ?? '') ?>">
                            <button type="submit" class="text-red-500 hover:text-red-400 font-semibold">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1): ?>
<div class="mt-4 flex justify-between items-center">
    <div class="text-sm text-[var(--text-muted)]">
        Showing <?= count($slides) ?> of <?= $pagination['total_items'] ?> slides
    </div>
    <div class="flex gap-2">
        <?php if ($pagination['has_prev']): ?>
        <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
            class="px-4 py-2 bg-[var(--bg-card)] border border-[var(--border-main)] rounded-md hover:bg-gray-500/10">
            Previous
        </a>
        <?php endif; ?>

        <span class="px-4 py-2 bg-blue-600 text-white rounded-md">
            Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
        </span>

        <?php if ($pagination['has_next']): ?>
        <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
            class="px-4 py-2 bg-[var(--bg-card)] border border-[var(--border-main)] rounded-md hover:bg-gray-500/10">
            Next
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
