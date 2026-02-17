<?php
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/Aggregator.php';

use Analytics\Auth;
use Analytics\Aggregator;

$auth = new Auth(__DIR__ . '/config/users.json');

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$aggregator = new Aggregator(__DIR__ . '/logs', __DIR__ . '/sessions');

// Get date range from filters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get all stats
$stats = $aggregator->getStats($startDate, $endDate);
$pageviews = $aggregator->getTotalPageviews();
$visitors = $aggregator->getUniqueVisitorCount();
$bounce = $aggregator->getBounceRate();
$conversionRate = $aggregator->getConversionRate();
$activeVisitors = $aggregator->getActiveVisitors();
$userJourneys = $aggregator->getUserJourneys(5);

// Set the page title for the header
$pageTitle = "Marketing Analytics Dashboard";

// Include the new header
require_once __DIR__ . '/partials/header.php';
?>

<style>
.stat-card {
    background: var(--bg-card);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    transition: transform 0.2s;
    border: 1px solid var(--border-main);
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--text-main);
    line-height: 1.2;
}
.stat-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
    font-weight: 500;
}
.chart-container {
    background: var(--bg-card);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    border: 1px solid var(--border-main);
}
.chart-container h3 {
    margin-bottom: 1rem;
}
.table-container {
    background: var(--bg-card);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    overflow-x: auto;
    border: 1px solid var(--border-main);
}
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-primary {
    background: #3b82f6;
    color: white;
}
.badge-success {
    background: #10b981;
    color: white;
}
.realtime-pulse {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text-main)]">Marketing Analytics Dashboard</h1>
            <p class="text-[var(--text-muted)] mt-1">Track your website performance and visitor behavior</p>
        </div>
        <div class="flex items-center gap-2 bg-[var(--bg-card)] px-4 py-2 rounded-lg border border-[var(--border-main)]">
            <span class="realtime-pulse"></span>
            <span class="text-[var(--text-main)]"><strong><?= $activeVisitors ?></strong> active now</span>
        </div>
    </div>

    <!-- Date Filter -->
    <form method="GET" class="flex flex-wrap gap-3 items-end bg-[var(--bg-card)] p-4 rounded-lg border border-[var(--border-main)]">
        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium mb-1 text-[var(--text-main)]">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                class="w-full px-3 py-2 border rounded-md bg-[var(--bg-main)] border-[var(--border-main)] text-[var(--text-main)]" />
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium mb-1 text-[var(--text-main)]">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                class="w-full px-3 py-2 border rounded-md bg-[var(--bg-main)] border-[var(--border-main)] text-[var(--text-main)]" />
        </div>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Apply Filter
        </button>
        <a href="?" class="px-6 py-2 border rounded-md border-[var(--border-main)] text-[var(--text-main)] hover:bg-[var(--bg-main)] transition-colors">Reset</a>
    </form>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-number"><?= number_format($pageviews) ?></div>
        <div class="stat-label">Total Pageviews</div>
        <div class="text-xs text-[var(--text-muted)] mt-1">All-time views</div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= number_format($visitors) ?></div>
        <div class="stat-label">Unique Visitors</div>
        <div class="text-xs text-[var(--text-muted)] mt-1">Total unique sessions</div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= $bounce ?>%</div>
        <div class="stat-label">Bounce Rate</div>
        <div class="text-xs mt-1 font-medium <?= $bounce < 40 ? 'text-green-500' : ($bounce < 60 ? 'text-yellow-500' : 'text-red-500') ?>">
            <?= $bounce < 40 ? '✓ Excellent' : ($bounce < 60 ? '⚠ Good' : '✗ Needs improvement') ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= $conversionRate ?>%</div>
        <div class="stat-label">Conversion Rate</div>
        <div class="text-xs text-[var(--text-muted)] mt-1"><?= $stats['conversions'] ?> conversions</div>
    </div>
</div>

<!-- Charts Row 1: Pageviews & Visitors Trend -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)]">📈 Pageviews Trend</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="pageviewsChart"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)]">👥 Visitors Trend</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="visitorsChart"></canvas>
        </div>
    </div>
</div>

<!-- Traffic Sources & Devices -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Traffic Sources -->
    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)] mb-4">🌐 Traffic Sources</h3>
        <div style="position: relative; height: 250px; margin-bottom: 1rem;">
            <canvas id="trafficSourcesChart"></canvas>
        </div>
        <div class="mt-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--border-main)]">
                        <th class="text-left py-2 text-[var(--text-main)]">Source</th>
                        <th class="text-right py-2 text-[var(--text-main)]">Visitors</th>
                        <th class="text-right py-2 text-[var(--text-main)]">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalTraffic = array_sum($stats['traffic_sources']);
                    $topSources = array_slice($stats['traffic_sources'], 0, 5, true);
                    foreach ($topSources as $source => $count):
                        $percentage = $totalTraffic > 0 ? round(($count / $totalTraffic) * 100, 1) : 0;
                    ?>
                    <tr class="border-b border-[var(--border-main)]">
                        <td class="py-2 text-[var(--text-main)]"><?= htmlspecialchars($source) ?></td>
                        <td class="text-right text-[var(--text-main)]"><?= number_format($count) ?></td>
                        <td class="text-right text-[var(--text-muted)]"><?= $percentage ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Device Breakdown -->
    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)] mb-4">📱 Device Breakdown</h3>
        <div style="position: relative; height: 250px; margin-bottom: 1rem;">
            <canvas id="devicesChart"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2">
            <?php
            $deviceIcons = ['Desktop' => '💻', 'Mobile' => '📱', 'Tablet' => '📱'];
            $totalDevices = array_sum($stats['devices']);
            foreach ($stats['devices'] as $device => $count):
                $percentage = $totalDevices > 0 ? round(($count / $totalDevices) * 100, 1) : 0;
                $icon = $deviceIcons[$device] ?? '📱';
            ?>
            <div class="text-center p-3 rounded-lg bg-[var(--bg-main)] border border-[var(--border-main)]">
                <div class="text-2xl mb-1"><?= $icon ?></div>
                <div class="font-bold text-[var(--text-main)]"><?= $percentage ?>%</div>
                <div class="text-xs text-[var(--text-muted)]"><?= $device ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Browser & OS Stats -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)] mb-4">🌐 Browser Distribution</h3>
        <div style="position: relative; height: 250px;">
            <canvas id="browsersChart"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <h3 class="text-lg font-semibold text-[var(--text-main)] mb-4">💻 Operating Systems</h3>
        <div style="position: relative; height: 250px;">
            <canvas id="osChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Pages -->
<div class="table-container mb-6">
    <h3 class="text-lg font-semibold text-[var(--text-main)] mb-4">🔥 Most Popular Pages</h3>
    <table class="w-full">
        <thead>
            <tr class="border-b-2 border-[var(--border-main)]">
                <th class="text-left py-3 text-[var(--text-main)]">Page</th>
                <th class="text-center py-3 text-[var(--text-main)]">Views</th>
                <th class="text-center py-3 text-[var(--text-main)]">Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalPageViews = array_sum($stats['pages']);
            foreach ($stats['popular_pages'] as $page => $count):
                $pagePercentage = $totalPageViews > 0 ? round(($count / $totalPageViews) * 100, 1) : 0;
            ?>
            <tr class="border-b border-[var(--border-main)]">
                <td class="py-3 text-[var(--text-main)] font-medium"><?= htmlspecialchars($page) ?></td>
                <td class="text-center">
                    <span class="badge badge-primary"><?= number_format($count) ?></span>
                </td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-32 bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $pagePercentage ?>%"></div>
                        </div>
                        <span class="text-sm text-[var(--text-muted)]"><?= $pagePercentage ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- User Journeys -->
<?php if (!empty($userJourneys)): ?>
<div class="table-container mb-6">
    <h3 class="text-lg font-semibold text-[var(--text-main)] mb-2">🗺️ Common User Journeys</h3>
    <p class="text-sm text-[var(--text-muted)] mb-4">See how visitors navigate through your website</p>
    <table class="w-full">
        <thead>
            <tr class="border-b-2 border-[var(--border-main)]">
                <th class="text-left py-3 text-[var(--text-main)]">Journey Path</th>
                <th class="text-center py-3 text-[var(--text-main)]">Sessions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($userJourneys as $journey => $count): ?>
            <tr class="border-b border-[var(--border-main)]">
                <td class="py-3 text-[var(--text-main)] font-mono text-sm">
                    <?= htmlspecialchars($journey) ?>
                </td>
                <td class="text-center">
                    <span class="badge badge-success"><?= number_format($count) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <a href="export.php?type=logs" class="stat-card text-center no-underline hover:shadow-lg transition-shadow">
        <div class="text-4xl mb-3">📥</div>
        <div class="font-semibold text-[var(--text-main)] mb-1">Export Logs</div>
        <div class="text-sm text-[var(--text-muted)]">Download raw analytics data</div>
    </a>

    <a href="export.php?type=sessions" class="stat-card text-center no-underline hover:shadow-lg transition-shadow">
        <div class="text-4xl mb-3">👥</div>
        <div class="font-semibold text-[var(--text-main)] mb-1">Export Sessions</div>
        <div class="text-sm text-[var(--text-muted)]">Download visitor sessions</div>
    </a>

    <a href="export.php?type=pageviews" class="stat-card text-center no-underline hover:shadow-lg transition-shadow">
        <div class="text-4xl mb-3">📊</div>
        <div class="font-semibold text-[var(--text-main)] mb-1">Export Pageviews</div>
        <div class="text-sm text-[var(--text-muted)]">Download pageview data</div>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js default config
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#ffffff' : '#1A2331';
    const gridColor = isDark ? '#283243' : '#d2d6dc';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // Prepare data
    const labels = <?= json_encode(array_keys($stats['pageviews'])) ?>;
    const pageviews = <?= json_encode(array_values($stats['pageviews'])) ?>;
    const visitors = <?= json_encode(array_values($stats['visitors'])) ?>;

    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: textColor },
                grid: { color: gridColor }
            },
            x: {
                ticks: { color: textColor },
                grid: { color: gridColor }
            }
        }
    };

    // Pageviews Chart
    new Chart(document.getElementById('pageviewsChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pageviews',
                data: pageviews,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: commonOptions
    });

    // Visitors Chart
    new Chart(document.getElementById('visitorsChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Visitors',
                data: visitors,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: commonOptions
    });

    // Traffic Sources Chart
    new Chart(document.getElementById('trafficSourcesChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($stats['traffic_sources'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats['traffic_sources'])) ?>,
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6',
                    '#ec4899',
                    '#06b6d4',
                    '#84cc16'
                ],
                borderWidth: 2,
                borderColor: isDark ? '#1A2331' : '#F4F4F4'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, padding: 10 }
                }
            }
        }
    });

    // Devices Chart
    new Chart(document.getElementById('devicesChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($stats['devices'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats['devices'])) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                borderWidth: 2,
                borderColor: isDark ? '#1A2331' : '#F4F4F4'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, padding: 10 }
                }
            }
        }
    });

    // Browsers Chart
    new Chart(document.getElementById('browsersChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($stats['browsers'])) ?>,
            datasets: [{
                label: 'Users',
                data: <?= json_encode(array_values($stats['browsers'])) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                x: {
                    ticks: { color: textColor },
                    grid: { display: false }
                }
            }
        }
    });

    // OS Chart
    new Chart(document.getElementById('osChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($stats['os'])) ?>,
            datasets: [{
                label: 'Users',
                data: <?= json_encode(array_values($stats['os'])) ?>,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                x: {
                    ticks: { color: textColor },
                    grid: { display: false }
                }
            }
        }
    });

    // Re-render charts on theme change
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-theme') {
                location.reload(); // Simple reload on theme change
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
});
</script>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
