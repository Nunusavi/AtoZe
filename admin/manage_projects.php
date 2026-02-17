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

require_once __DIR__ . '/partials/header.php';

$projectsFile = 'projects.json';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::protect('manage_projects');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete':
            $projectId = $_POST['project_id'] ?? '';
            $result = $dataManager->findById($projectsFile, $projectId);

            if ($result) {
                // Delete associated image
                if (!empty($result['item']['image'])) {
                    $uploader = new FileUploader(__DIR__ . '/../uploads/projects/');
                    $uploader->delete($result['item']['image']);
                }

                if ($dataManager->delete($projectsFile, $projectId)) {
                    $activityLogger->log('delete', 'project', $projectId, [
                        'company_name' => $result['item']['company_name'] ?? 'Unknown'
                    ]);
                    $_SESSION['message'] = "Project deleted successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error: Could not delete project.";
                    $_SESSION['message_type'] = "error";
                }
            }
            break;

        case 'bulk_delete':
            $selectedIds = $_POST['selected_projects'] ?? [];
            $deleted = 0;

            foreach ($selectedIds as $projectId) {
                $result = $dataManager->findById($projectsFile, $projectId);
                if ($result) {
                    // Delete image
                    if (!empty($result['item']['image'])) {
                        $uploader = new FileUploader(__DIR__ . '/../uploads/projects/');
                        $uploader->delete($result['item']['image']);
                    }

                    if ($dataManager->delete($projectsFile, $projectId)) {
                        $activityLogger->log('delete', 'project', $projectId, [
                            'company_name' => $result['item']['company_name'] ?? 'Unknown',
                            'bulk' => true
                        ]);
                        $deleted++;
                    }
                }
            }

            $_SESSION['message'] = "$deleted project(s) deleted successfully.";
            $_SESSION['message_type'] = "success";
            break;

        case 'bulk_status':
            $selectedIds = $_POST['selected_projects'] ?? [];
            $status = $_POST['status'] ?? 'published';
            $updated = 0;

            foreach ($selectedIds as $projectId) {
                if ($dataManager->update($projectsFile, $projectId, ['status' => $status])) {
                    $activityLogger->log('update', 'project', $projectId, [
                        'status' => $status,
                        'bulk' => true
                    ]);
                    $updated++;
                }
            }

            $_SESSION['message'] = "$updated project(s) updated to $status.";
            $_SESSION['message_type'] = "success";
            break;

        case 'duplicate':
            $projectId = $_POST['project_id'] ?? '';
            $newId = $dataManager->duplicate($projectsFile, $projectId);

            if ($newId) {
                $activityLogger->log('create', 'project', $newId, [
                    'duplicated_from' => $projectId
                ]);
                $_SESSION['message'] = "Project duplicated successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: Could not duplicate project.";
                $_SESSION['message_type'] = "error";
            }
            break;
    }

    header('Location: manage_projects.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Get all projects
$allProjects = $dataManager->getData($projectsFile);

// Search functionality
$searchQuery = $_GET['search'] ?? '';
if (!empty($searchQuery)) {
    $allProjects = Helpers::search($allProjects, $searchQuery, ['company_name', 'location', 'description']);
}

// Filter by status
$filterStatus = $_GET['status'] ?? '';
if (!empty($filterStatus)) {
    $allProjects = array_filter($allProjects, function($project) use ($filterStatus) {
        return ($project['status'] ?? 'published') === $filterStatus;
    });
}

// Sort functionality
$sortBy = $_GET['sort'] ?? 'company_name';
$sortDir = $_GET['dir'] ?? 'asc';
$allProjects = Helpers::sortBy($allProjects, $sortBy, $sortDir);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$paginated = Helpers::paginate($allProjects, $page, $perPage);
$projects = $paginated['items'];
$pagination = $paginated['pagination'];

$pageTitle = "Manage Projects";
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Project Management</h1>
    <a href="edit_project.php"
        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
        + Add New Project
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
                placeholder="Search projects..."
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
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium mb-1">Sort By</label>
            <select name="sort"
                class="w-full px-3 py-2 bg-[var(--bg-main)] border border-[var(--border-main)] rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="company_name" <?= $sortBy === 'company_name' ? 'selected' : '' ?>>Company Name</option>
                <option value="location" <?= $sortBy === 'location' ? 'selected' : '' ?>>Location</option>
                <option value="total_price" <?= $sortBy === 'total_price' ? 'selected' : '' ?>>Total Price</option>
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                <option value="updated_at" <?= $sortBy === 'updated_at' ? 'selected' : '' ?>>Date Updated</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                Apply
            </button>
            <a href="manage_projects.php"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Bulk Actions Bar -->
<div id="bulkActionsBar" class="bg-[var(--bg-card)] p-4 rounded-lg shadow-lg mb-4 hidden">
    <form method="POST" id="bulkActionsForm" class="flex flex-wrap gap-4 items-center">
        <?= CSRFProtection::getTokenField('manage_projects') ?>
        <input type="hidden" name="action" id="bulkAction">
        <input type="hidden" name="status" id="bulkStatus">
        <span class="font-medium"><span id="selectedCount">0</span> selected</span>
        <button type="button" onclick="bulkDelete()"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
            Delete Selected
        </button>
        <button type="button" onclick="bulkStatus('published')"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
            Set Published
        </button>
        <button type="button" onclick="bulkStatus('draft')"
            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
            Set Draft
        </button>
        <button type="button" onclick="clearSelection()"
            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">
            Clear Selection
        </button>
    </form>
</div>

<div class="bg-[var(--bg-card)] p-6 rounded-lg shadow-lg overflow-x-auto">
    <table class="min-w-full text-left">
        <thead class="border-b border-[var(--border-main)]">
            <tr>
                <th class="py-3 px-4">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                        class="rounded border-gray-400 text-blue-600 focus:ring-blue-500">
                </th>
                <th class="py-3 px-4">Image</th>
                <th class="py-3 px-4">Company Name</th>
                <th class="py-3 px-4">Location</th>
                <th class="py-3 px-4">Total Price</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
            <tr>
                <td colspan="7" class="py-4 px-4 text-center text-[var(--text-muted)]">
                    No projects found.
                    <?php if ($searchQuery || $filterStatus): ?>
                    <a href="manage_projects.php" class="text-blue-500 hover:underline">Clear filters</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($projects as $project): ?>
            <tr class="border-b border-[var(--border-main)] hover:bg-gray-500/10">
                <td class="py-2 px-4">
                    <input type="checkbox" name="project_select" value="<?= htmlspecialchars($project['id'] ?? '') ?>"
                        onchange="updateBulkActions()"
                        class="rounded border-gray-400 text-blue-600 focus:ring-blue-500">
                </td>
                <td class="py-2 px-4">
                    <?php if (!empty($project['image'])): ?>
                    <img src="../<?= htmlspecialchars($project['image']) ?>"
                        alt="<?= htmlspecialchars($project['company_name']) ?>"
                        class="h-12 w-12 object-cover rounded-md">
                    <?php else: ?>
                    <div class="h-12 w-12 bg-gray-600 rounded-md flex items-center justify-center text-xs">No Image</div>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 font-medium"><?= htmlspecialchars($project['company_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($project['location']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($project['total_price']) ?></td>
                <td class="py-2 px-4">
                    <?php $status = $project['status'] ?? 'published'; ?>
                    <span class="px-2 py-1 text-xs rounded-full <?= $status === 'published' ? 'bg-green-600' : 'bg-yellow-600' ?> text-white">
                        <?= ucfirst($status) ?>
                    </span>
                </td>
                <td class="py-2 px-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="edit_project.php?id=<?= htmlspecialchars($project['id'] ?? '') ?>"
                            class="text-blue-500 hover:text-blue-400 font-semibold">Edit</a>

                        <form method="POST" class="inline">
                            <?= CSRFProtection::getTokenField('manage_projects') ?>
                            <input type="hidden" name="action" value="duplicate">
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id'] ?? '') ?>">
                            <button type="submit" class="text-green-500 hover:text-green-400 font-semibold">Clone</button>
                        </form>

                        <form method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this project?');" class="inline">
                            <?= CSRFProtection::getTokenField('manage_projects') ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id'] ?? '') ?>">
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
        Showing <?= count($projects) ?> of <?= $pagination['total_items'] ?> projects
    </div>
    <div class="flex gap-2">
        <?php if ($pagination['has_prev']): ?>
        <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?><?= $sortBy ? '&sort=' . urlencode($sortBy) : '' ?><?= $sortDir ? '&dir=' . urlencode($sortDir) : '' ?>"
            class="px-4 py-2 bg-[var(--bg-card)] border border-[var(--border-main)] rounded-md hover:bg-gray-500/10">
            Previous
        </a>
        <?php endif; ?>

        <span class="px-4 py-2 bg-blue-600 text-white rounded-md">
            Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
        </span>

        <?php if ($pagination['has_next']): ?>
        <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?><?= $sortBy ? '&sort=' . urlencode($sortBy) : '' ?><?= $sortDir ? '&dir=' . urlencode($sortDir) : '' ?>"
            class="px-4 py-2 bg-[var(--bg-card)] border border-[var(--border-main)] rounded-md hover:bg-gray-500/10">
            Next
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="project_select"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('input[name="project_select"]:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countSpan = document.getElementById('selectedCount');

    countSpan.textContent = checkboxes.length;

    if (checkboxes.length > 0) {
        bulkBar.classList.remove('hidden');
    } else {
        bulkBar.classList.add('hidden');
    }
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('input[name="project_select"]:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkDelete() {
    if (!confirm('Are you sure you want to delete the selected projects?')) return;

    const form = document.getElementById('bulkActionsForm');
    document.getElementById('bulkAction').value = 'bulk_delete';

    const selectedIds = getSelectedIds();
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_projects[]';
        input.value = id;
        form.appendChild(input);
    });

    form.submit();
}

function bulkStatus(status) {
    if (!confirm(`Are you sure you want to set selected projects to ${status}?`)) return;

    const form = document.getElementById('bulkActionsForm');
    document.getElementById('bulkAction').value = 'bulk_status';
    document.getElementById('bulkStatus').value = status;

    const selectedIds = getSelectedIds();
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_projects[]';
        input.value = id;
        form.appendChild(input);
    });

    form.submit();
}

function clearSelection() {
    document.querySelectorAll('input[name="project_select"]').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
