<?php
/**
 * Analytics Data Cleanup Script
 *
 * This script automatically deletes old analytics data based on retention policy.
 * Run this script via cron job daily or weekly.
 *
 * Cron example (runs daily at 2 AM):
 * 0 2 * * * /usr/bin/php /path/to/admin/cleanup.php
 */

require_once __DIR__ . '/lib/Aggregator.php';

use Analytics\Aggregator;

// Configuration
$daysToKeep = 90; // Keep data for 90 days (adjust as needed)

// Initialize aggregator
$aggregator = new Aggregator(__DIR__ . '/logs', __DIR__ . '/sessions');

// Run cleanup
echo "[" . date('Y-m-d H:i:s') . "] Starting analytics cleanup...\n";
echo "Retention policy: Keep last {$daysToKeep} days of data\n\n";

$deletedCount = $aggregator->cleanupOldLogs($daysToKeep);

echo "Cleanup complete!\n";
echo "Deleted {$deletedCount} old log files\n";
echo "Session files older than {$daysToKeep} days also removed\n";
echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
