<?php
/**
 * PHP CDN Storage - Delete Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed. Use POST or DELETE.'], 405);
}

if (!authenticateRequest()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized.'], 401);
}

// Read body parameters (supports JSON or form-urlencoded)
$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);

$fileName = $_POST['file_name'] ?? $json['file_name'] ?? $_GET['file_name'] ?? '';
$hash     = $_POST['hash'] ?? $json['hash'] ?? $_GET['hash'] ?? '';

if (empty($fileName) && empty($hash)) {
    jsonResponse(['success' => false, 'error' => 'Missing file_name or hash parameter.'], 400);
}

if (!empty($fileName)) {
    $targetHash = pathinfo($fileName, PATHINFO_FILENAME);
} else {
    $targetHash = $hash;
}

// Clean hash
$targetHash = preg_replace('/[^a-f0-9]/i', '', $targetHash);
if (empty($targetHash)) {
    jsonResponse(['success' => false, 'error' => 'Invalid hash/filename.'], 400);
}

$shardDir = STORAGE_PATH . '/' . getShardPath($targetHash);

// Locate matching files in shardDir starting with $targetHash
$deleted = false;
$deletedFiles = [];

if (is_dir($shardDir)) {
    $files = scandir($shardDir);
    if ($files !== false) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (strpos($file, $targetHash) === 0) {
                $filePath = $shardDir . '/' . $file;
                if (is_file($filePath)) {
                    if (@unlink($filePath)) {
                        $deleted = true;
                        $deletedFiles[] = $file;
                    }
                }
            }
        }
    }
}

if (!$deleted) {
    jsonResponse(['success' => false, 'error' => 'File not found or already deleted.'], 404);
}

// Clean empty shard directories if empty
@rmdir($shardDir);
@rmdir(dirname($shardDir));

jsonResponse([
    'success' => true,
    'message' => 'File(s) deleted successfully.',
    'deleted' => $deletedFiles
]);
