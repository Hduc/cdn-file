<?php
/**
 * PHP CDN Storage - Health & Diagnostics Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GoogleDriveManager.php';

// Public status or auth-protected detail
$isAuthenticated = authenticateRequest();

$storageWritable = is_writable(STORAGE_PATH);
$tmpWritable = is_writable(TMP_PATH);

$diskFree = @disk_free_space(STORAGE_PATH);
$diskTotal = @disk_total_space(STORAGE_PATH);

$dbStats = Database::getStats();
$gdriveAccounts = GoogleDriveManager::getAccountsList();

$status = [
    'success' => true,
    'status'  => 'healthy',
    'timestamp' => time(),
    'service' => 'PHP CDN Storage Server',
    'version' => '1.1.0',
    'storage' => [
        'writable'      => $storageWritable,
        'tmp_writable'  => $tmpWritable,
        'free_bytes'    => $diskFree !== false ? $diskFree : null,
        'free_human'    => $diskFree !== false ? round($diskFree / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A',
        'total_human'   => $diskTotal !== false ? round($diskTotal / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A',
        'total_files_tracked' => $dbStats['total_files'],
        'total_size_tracked'  => round($dbStats['total_size'] / (1024 * 1024), 2) . ' MB'
    ],
    'google_drive' => [
        'enabled'       => GoogleDriveManager::isEnabled(),
        'account_count' => count($gdriveAccounts),
        'accounts'      => $gdriveAccounts
    ]
];

if ($isAuthenticated) {
    $status['php_environment'] = [
        'php_version'          => PHP_VERSION,
        'sapi'                 => PHP_SAPI,
        'upload_max_filesize'  => ini_get('upload_max_filesize'),
        'post_max_size'        => ini_get('post_max_size'),
        'memory_limit'         => ini_get('memory_limit'),
        'max_execution_time'   => ini_get('max_execution_time'),
        'file_uploads_enabled' => (bool)ini_get('file_uploads'),
        'hash_algo'            => HASH_ALGO,
        'shard_depth'          => SHARD_DEPTH,
        'clean_url_enabled'    => USE_CLEAN_URL,
        'base_url'             => getBaseUrl()
    ];
}

jsonResponse($status);
