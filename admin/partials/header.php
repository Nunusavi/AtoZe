<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load CMS classes
require_once __DIR__ . '/../lib/CSRFProtection.php';
require_once __DIR__ . '/../lib/DataManager.php';
require_once __DIR__ . '/../lib/ActivityLogger.php';
require_once __DIR__ . '/../lib/FileUploader.php';
require_once __DIR__ . '/../lib/Helpers.php';

use CMS\CSRFProtection;
use CMS\DataManager;
use CMS\ActivityLogger;
use CMS\FileUploader;
use CMS\Helpers;

// Clean up expired CSRF tokens
CSRFProtection::cleanupExpiredTokens();

// Initialize global instances
$dataManager = new DataManager(__DIR__ . '/../../Json');
$activityLogger = new ActivityLogger(__DIR__ . '/../logs/activity');

// Determine the currently active page for sidebar styling
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="public/image/icon.webp" type="image/x-icon">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel' ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
        --bg-sidebar: #1A2331;
        --text-main: #fff;
        --text-muted: #a0aec0;
        --border-main: #283243;
    }

    html[data-theme='light'] {
        --bg-main: #E8E8E8;
        --bg-card: #F4F4F4;
        --bg-sidebar: #ffffff;
        --text-main: #1A2331;
        --text-muted: #4a5568;
        --border-main: #d2d6dc;
    }

    /* Style for active sidebar link */
    .sidebar-link-active {
        background-color: #2563eb;
        /* Blue-600 */
        color: white;
    }
    </style>
</head>

<body class="bg-[var(--bg-main)] text-[var(--text-main)] transition-colors duration-300">
    <div id="bg-dark"
        class="fixed inset-0 min-h-screen w-full bg-slate-950 bg-[radial-gradient(circle_500px_at_50%_200px,#3e3e3e,transparent)] -z-10 pointer-events-none">
    </div>
    <div id="bg-light"
        class="fixed inset-0 min-h-screen w-full bg-slate-100 bg-[linear-gradient(to_right,#f0f0f0_1px,transparent_1px),linear-gradient(to_bottom,#f0f0f0_1px,transparent_1px)] bg-[size:6rem_4rem] [&>div]:absolute [&>div]:inset-0 [&>div]:bg-[radial-gradient(circle_800px_at_100%_200px,#d5c5ff,transparent)] -z-10 pointer-events-none">
    </div>

    <aside id="sidebar"
        class="bg-[var(--bg-sidebar)] shadow-lg w-64 fixed top-0 left-0 h-full z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="p-4 text-2xl font-bold border-b border-[var(--border-main)]">Atoze CMS</div>
        <nav class="mt-4">
            <a href="index.php"
                class="flex items-center gap-3 px-4 py-3 text-[var(--text-muted)] hover:bg-blue-600 hover:text-white transition-colors duration-200 <?= $current_page == 'index.php' ? 'sidebar-link-active' : '' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Dashboard
            </a>
            <h3 class="px-4 py-2 mt-4 text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider">Content
            </h3>
            <a href="manage_products.php"
                class="flex items-center gap-3 px-4 py-3 text-[var(--text-muted)] hover:bg-blue-600 hover:text-white transition-colors duration-200 <?= $current_page == 'manage_products.php' ? 'sidebar-link-active' : '' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7l8 4"></path>
                </svg>
                Products
            </a>
            <a href="manage_projects.php"
                class="flex items-center gap-3 px-4 py-3 text-[var(--text-muted)] hover:bg-blue-600 hover:text-white transition-colors duration-200 <?= $current_page == 'manage_projects.php' ? 'sidebar-link-active' : '' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                    </path>
                </svg>
                Projects
            </a>
            <a href="manage_slides.php"
                class="flex items-center gap-3 px-4 py-3 text-[var(--text-muted)] hover:bg-blue-600 hover:text-white transition-colors duration-200 <?= $current_page == 'manage_slides.php' ? 'sidebar-link-active' : '' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                    </path>
                </svg>
                Slides
            </a>
        </nav>
    </aside>

    <div class="md:ml-64 transition-all duration-300 ease-in-out">
        <header class="bg-[var(--bg-card)] shadow-md p-4 flex justify-between items-center">
            <button id="menu-btn" class="md:hidden text-[var(--text-main)]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7">
                    </path>
                </svg>
            </button>
            <h1 class="text-xl font-bold hidden md:block">Welcome, <?= htmlspecialchars($auth->getUser()) ?></h1>
            <div class="flex items-center gap-4">
                <form action="logout.php" method="post" class="inline">
                    <?= CSRFProtection::getTokenField('logout') ?>
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 transition-colors duration-200 shadow focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
                        </svg>
                        Logout
                    </button>
                </form>
                <button id="themeToggle" aria-label="Toggle Theme"
                    class="w-10 h-6 flex items-center bg-[var(--bg-card)] border border-[var(--border-main)] rounded-full p-1 transition-colors duration-300 focus:outline-none shadow relative">
                    <span id="themeThumb"
                        class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-300"></span>
                    <svg id="themeIconDark"
                        class="w-4 h-4 absolute left-1 top-1 text-yellow-400 transition-opacity duration-300"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
                    </svg>
                    <svg id="themeIconLight"
                        class="w-4 h-4 absolute right-1 top-1 text-gray-700 opacity-0 transition-opacity duration-300"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="5" />
                        <path
                            d="M12 1v2m0 18v2m11-11h-2M3 12H1m16.95 7.07l-1.41-1.41M6.34 6.34L4.93 4.93m12.02 0l-1.41 1.41M6.34 17.66l-1.41 1.41" />
                    </svg>
                </button>
            </div>
        </header>

        <main class="p-4 md:p-8">