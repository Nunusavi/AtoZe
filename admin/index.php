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

$pageviews = $aggregator->getTotalPageviews();
$visitors  = $aggregator->getUniqueVisitorCount();
$bounce    = $aggregator->getBounceRate();
$chartData = $aggregator->getPageviewsPerDay();
$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;
$stats = Aggregator::getStats($start, $end);

?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="public/image/icon.webp" type="image/x-icon">
    <title>Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              light1: '#E8E8E8',
              light2: '#F4F4F4',
              dark1: '#010A1A',
              dark2: '#1A2331',
              dark3: '#283243',
            }
          }
        }
      }
    </script>
    <style>
      html[data-theme='dark'] {
        --bg-main: #010A1A;
        --bg-card: #1A2331;
        --text-main: #fff;
        --border-main: #283243;
      }
      html[data-theme='light'] {
        --bg-main: #E8E8E8;
        --bg-card: #F4F4F4;
        --text-main: #1A2331;
        --border-main: #E8E8E8;
      }
    </style>
</head>
<body class="relative h-screen w-full text-[var(--text-main)] transition-colors duration-300">
  <div id="bg-dark" class="fixed inset-0 min-h-screen w-full bg-slate-950 bg-[radial-gradient(circle_500px_at_50%_200px,#3e3e3e,transparent)] -z-10 pointer-events-none"></div>
  <div id="bg-light" class="fixed inset-0 min-h-screen w-full bg-slate-100 bg-[linear-gradient(to_right,#f0f0f0_1px,transparent_1px),linear-gradient(to_bottom,#f0f0f0_1px,transparent_1px)] bg-[size:6rem_4rem] [&>div]:absolute [&>div]:inset-0 [&>div]:bg-[radial-gradient(circle_800px_at_100%_200px,#d5c5ff,transparent)] -z-10 pointer-events-none"></div>
  <div class="min-h-screen flex flex-col items-center justify-start py-8 px-2">
    <div class="w-full max-w-5xl flex flex-row justify-between items-center mb-6">
      <h1 class="text-3xl font-bold ">Welcome, <?= htmlspecialchars($auth->getUser()) ?></h1>
      <div class="flex items-center gap-4">
        <form action="logout.php" method="post" class="inline">
          <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 transition-colors duration-200 shadow focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
            </svg>
            Logout
          </button>
        </form>
        <button id="themeToggle" aria-label="Toggle Theme" class="w-10 h-6 flex items-center bg-[var(--bg-card)] border border-[var(--border-main)] rounded-full p-1 transition-colors duration-300 focus:outline-none shadow relative">
          <span id="themeThumb" class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-300"></span>
          <svg id="themeIconDark" class="w-4 h-4 absolute left-1 top-1 text-yellow-400 transition-opacity duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z"/></svg>
          <svg id="themeIconLight" class="w-4 h-4 absolute right-1 top-1 text-gray-700 opacity-0 transition-opacity duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2m11-11h-2M3 12H1m16.95 7.07l-1.41-1.41M6.34 6.34L4.93 4.93m12.02 0l-1.41 1.41M6.34 17.66l-1.41 1.41"/></svg>
        </button>
        <!-- Theme toggle script moved to end of body to avoid redeclaration -->
      </div>
    </div>
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col items-start">
      <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
        Export Data
      </h3>
      <ul class="space-y-2 w-full">
        <li>
        <a class="flex items-center gap-2 text-blue-500 hover:text-blue-700 font-medium transition-colors px-3 py-2 rounded hover:bg-blue-100/10 focus:outline-none focus:ring-2 focus:ring-blue-400"
           href="export.php?type=logs">
          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 4v12"/></svg>
          Download Logs
        </a>
        </li>
        <li>
        <a class="flex items-center gap-2 text-blue-500 hover:text-blue-700 font-medium transition-colors px-3 py-2 rounded hover:bg-blue-100/10 focus:outline-none focus:ring-2 focus:ring-blue-400"
           href="export.php?type=sessions">
          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06A1.65 1.65 0 0015 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 008.6 15a1.65 1.65 0 00-1.82-.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0015 8.6a1.65 1.65 0 001.82.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 15z"/></svg>
          Download Sessions
        </a>
    </li>
        <li>
        <a class="flex items-center gap-2 text-blue-500 hover:text-blue-700 font-medium transition-colors px-3 py-2 rounded hover:bg-blue-100/10 focus:outline-none focus:ring-2 focus:ring-blue-400"
           href="export.php?type=pageviews">
          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12s4-8 9-8 9 8 9 8-4 8-9 8-9-8-9-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Download Pageviews (30 days)
        </a>
        </li>
      </ul>
    </div>
      <form method="GET" class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col gap-2">
        <label class="font-medium">Start:
          <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" class="mt-1 block w-full rounded border border-[var(--border-main)] bg-transparent px-2 py-1" />
        </label>
        <label class="font-medium">End:
          <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" class="mt-1 block w-full rounded border border-[var(--border-main)] bg-transparent px-2 py-1" />
        </label>
        <button type="submit" class="mt-2 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Filter</button>
      </form>
    </div>
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col items-center">
        <h3 class="text-lg font-semibold mb-2">Total Pageviews</h3>
        <div class="text-3xl font-bold"><?= $pageviews ?></div>
      </div>
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col items-center">
        <h3 class="text-lg font-semibold mb-2">Unique Visitors</h3>
        <div class="text-3xl font-bold"><?= $visitors ?></div>
      </div>
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col items-center">
        <h3 class="text-lg font-semibold mb-2">Bounce Rate</h3>
        <div class="text-3xl font-bold"><?= $bounce ?>%</div>
      </div>
    </div>
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Pageviews (Last 7 Days)</h3>
        <canvas id="viewsChart" height="200" class="bg-transparent"></canvas>
      </div>
    <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col gap-4">
      <h3 class="text-lg font-semibold mb-2">Pageviews Trend</h3>
      <canvas id="pageviewsChart" height="300`" class="bg-transparent"></canvas>
    </div>
    <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col gap-4">
      <h3 class="text-lg font-semibold mb-2">Visitors Trend</h3>
      <canvas id="visitorsChart" height="200" class="bg-transparent"></canvas>
    </div>
    <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 flex flex-col gap-4">
      <h3 class="text-lg font-semibold mb-2">Events Trend</h3>
      <canvas id="eventsChart" height="200" class="bg-transparent"></canvas>
    </div>
    </div>
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 overflow-x-auto">
        <h2 class="text-xl font-bold mb-2">Top Pages</h2>
        <table class="min-w-full text-left border border-[var(--border-main)]">
          <thead>
            <tr class="bg-[var(--bg-card)] text-[var(--text-main)] border-b border-[var(--border-main)]">
              <th class="py-2 px-4">Page URL</th>
              <th class="py-2 px-4">Views</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($stats['pages'], 0, 10) as $url => $count): ?>
              <tr class="even:bg-[var(--bg-main)] odd:bg-[var(--bg-card)] text-[var(--text-main)]">
                <td class="py-2 px-4"><?= htmlspecialchars($url) ?></td>
                <td class="py-2 px-4"><?= $count ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="bg-[var(--bg-card)] rounded-xl shadow-lg p-6 overflow-x-auto">
        <h2 class="text-xl font-bold mb-2">Top Referrers</h2>
        <table class="min-w-full text-left border border-[var(--border-main)]">
          <thead>
            <tr class="bg-[var(--bg-card)] text-[var(--text-main)] border-b border-[var(--border-main)]">
              <th class="py-2 px-4">Referrer</th>
              <th class="py-2 px-4">Count</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($stats['referrers'], 0, 10) as $ref => $count): ?>
              <tr class="even:bg-[var(--bg-main)] odd:bg-[var(--bg-card)] text-[var(--text-main)]">
                <td class="py-2 px-4"><?= htmlspecialchars($ref) ?></td>
                <td class="py-2 px-4"><?= $count ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
    // Enhanced theme toggle switch logic
    (function() {
      const themeToggle = document.getElementById('themeToggle');
      const themeThumb = document.getElementById('themeThumb');
      const iconDark = document.getElementById('themeIconDark');
      const iconLight = document.getElementById('themeIconLight');
      function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (theme === 'dark') {
          themeThumb.style.transform = 'translateX(0)';
          iconDark.style.opacity = '1';
          iconLight.style.opacity = '0';
          document.getElementById('bg-dark').style.display = '';
          document.getElementById('bg-light').style.display = 'none';
        } else {
          themeThumb.style.transform = 'translateX(16px)';
          iconDark.style.opacity = '0';
          iconLight.style.opacity = '1';
          document.getElementById('bg-dark').style.display = 'none';
          document.getElementById('bg-light').style.display = '';
        }
      }
      function getTheme() {
        return localStorage.getItem('theme') || 'dark';
      }
      setTheme(getTheme());
      themeToggle.addEventListener('click', () => {
        const current = getTheme();
        setTheme(current === 'dark' ? 'light' : 'dark');
      });
    })();

    // Chart.js logic - run immediately at end of body
    // Debug Chart.js data
    const chartLabels = <?= json_encode(array_keys($chartData)) ?>;
    const chartDataVals = <?= json_encode(array_values($chartData)) ?>;
    const labels = <?= json_encode(array_keys($stats['pageviews'])) ?>;
    const pageViews = <?= json_encode(array_values($stats['pageviews'])) ?>;
    const visitors = <?= json_encode(array_values($stats['visitors'])) ?>;
    const events = <?= json_encode(array_values($stats['events'])) ?>;

    try {
      const ctx = document.getElementById('viewsChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: chartLabels,
          datasets: [{
            label: 'Pageviews',
            data: chartDataVals,
            borderColor: '#007bff',
            fill: false,
            tension: 0.3
          }]
        },
        options: {
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    } catch (e) { console.error('viewsChart error', e); }

    try {
      new Chart(document.getElementById('pageviewsChart').getContext('2d'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Page Views',
            data: pageViews,
            borderColor: 'blue',
            fill: false
          }]
        }
      });
    } catch (e) { console.error('pageviewsChart error', e); }

    try {
      new Chart(document.getElementById('visitorsChart').getContext('2d'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Unique Visitors',
            data: visitors,
            borderColor: 'green',
            fill: false
          }]
        }
      });
    } catch (e) { console.error('visitorsChart error', e); }

    try {
      new Chart(document.getElementById('eventsChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Events',
            data: events,
            backgroundColor: 'orange'
          }]
        }
      });
    } catch (e) { console.error('eventsChart error', e); }
  </script>
</body>
</html>
