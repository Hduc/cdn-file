<?php
/**
 * PHP CDN Storage - Dynamic Stream & Cache Proxy
 * Streams files from Google Drive with 1-Year CDN Cache & Byte-Ranges
 */

declare(strict_types=1);

@set_time_limit(0);
@ini_set('max_execution_time', '0');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GoogleDriveManager.php';

$hash = $_GET['hash'] ?? '';
$ext  = $_GET['ext'] ?? '';

// Sanitize hash
$hash = preg_replace('/[^a-f0-9]/i', '', $hash);
if (empty($hash)) {
    http_response_code(404);
    exit('File Not Found');
}

// 1. Check if file is available in local disk cache
$shardPath = getShardPath($hash);
$localPattern = STORAGE_PATH . '/' . $shardPath . '/' . $hash . '*';
$localFiles = glob($localPattern);

if (!empty($localFiles) && is_file($localFiles[0])) {
    $filePath = $localFiles[0];
    $size = filesize($filePath);
    $mime = mime_content_type($filePath) ?: 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Access-Control-Allow-Origin: *');
    header('Accept-Ranges: bytes');
    readfile($filePath);
    exit;
}

// 2. Lookup file in Google Drive mapping database
$record = Database::findByHash($hash);
if (!$record) {
    http_response_code(404);
    exit('File Not Found');
}

// 3. Set CDN Edge caching headers
header('Cache-Control: public, max-age=31536000, immutable');
header('Access-Control-Allow-Origin: *');
header('Accept-Ranges: bytes');
header('Content-Type: ' . $record['mime_type']);

// 4. Stream directly from Google Drive
$range = $_SERVER['HTTP_RANGE'] ?? null;
GoogleDriveManager::stream($record['account_id'], $record['gdrive_file_id'], $range);
