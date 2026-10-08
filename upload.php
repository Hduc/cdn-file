<?php
/**
 * PHP CDN Storage - Upload Endpoint
 * Handles Multipart, Binary Stream, and Chunked Uploads
 */

declare(strict_types=1);

// Set unlimited execution time and high memory if permitted
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require_once __DIR__ . '/config.php';

// Allow CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type, X-File-Name, X-Upload-Id, X-Chunk-Index, X-Total-Chunks');
    http_response_code(204);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

// 1. Authenticate
if (!authenticateRequest()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized. Invalid or missing API key.'], 401);
}

// Ensure storage and tmp dirs exist
if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}
if (!is_dir(TMP_PATH)) {
    @mkdir(TMP_PATH, 0755, true);
}

// Check chunked parameters
$uploadId    = $_POST['upload_id'] ?? $_SERVER['HTTP_X_UPLOAD_ID'] ?? null;
$chunkIndex  = isset($_POST['chunk_index']) ? (int)$_POST['chunk_index'] : (isset($_SERVER['HTTP_X_CHUNK_INDEX']) ? (int)$_SERVER['HTTP_X_CHUNK_INDEX'] : null);
$totalChunks = isset($_POST['total_chunks']) ? (int)$_POST['total_chunks'] : (isset($_SERVER['HTTP_X_TOTAL_CHUNKS']) ? (int)$_SERVER['HTTP_X_TOTAL_CHUNKS'] : null);

// Temporary file tracker
$tempFilePath = null;
$originalName = 'file.bin';
$isChunked    = ($uploadId !== null && $chunkIndex !== null && $totalChunks !== null && $totalChunks > 1);

try {
    if ($isChunked) {
        // Sanitize uploadId
        $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$uploadId);
        if (empty($cleanId)) {
            jsonResponse(['success' => false, 'error' => 'Invalid upload_id'], 400);
        }

        $chunkTempFile = TMP_PATH . '/' . $cleanId . '.part';

        // Read chunk data
        $in = null;
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $in = fopen($_FILES['file']['tmp_name'], 'rb');
            $originalName = $_FILES['file']['name'];
        } else {
            $in = fopen('php://input', 'rb');
            $originalName = $_SERVER['HTTP_X_FILE_NAME'] ?? $_POST['file_name'] ?? 'file.bin';
        }

        if (!$in) {
            jsonResponse(['success' => false, 'error' => 'Failed to read chunk payload'], 400);
        }

        // Open target file in append mode
        $out = fopen($chunkTempFile, ($chunkIndex === 0) ? 'wb' : 'ab');
        if (!$out) {
            fclose($in);
            jsonResponse(['success' => false, 'error' => 'Failed to open temp chunk storage'], 500);
        }

        while (!feof($in)) {
            $buf = fread($in, 65536);
            if ($buf !== false && $buf !== '') {
                fwrite($out, $buf);
            }
        }
        fclose($in);
        fclose($out);

        // If not the final chunk, respond with progress
        if ($chunkIndex < ($totalChunks - 1)) {
            jsonResponse([
                'success'       => true,
                'status'        => 'chunk_received',
                'chunk_index'   => $chunkIndex,
                'total_chunks'  => $totalChunks,
                'bytes_written' => filesize($chunkTempFile)
            ]);
        }

        // Final chunk received -> assembly complete
        $tempFilePath = $chunkTempFile;

    } elseif (!empty($_FILES['file']['tmp_name'])) {
        // Standard Multipart Form-Data
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
            ];
            $err = $uploadErrors[$_FILES['file']['error']] ?? 'Unknown upload error.';
            jsonResponse(['success' => false, 'error' => $err], 400);
        }

        $tempFilePath = $_FILES['file']['tmp_name'];
        $originalName = $_FILES['file']['name'];

    } else {
        // Direct Raw Stream (php://input)
        $originalName = $_SERVER['HTTP_X_FILE_NAME'] ?? 'streamed_file.bin';
        $streamTemp = TMP_PATH . '/stream_' . bin2hex(random_bytes(8)) . '.tmp';
        
        $in = fopen('php://input', 'rb');
        if (!$in) {
            jsonResponse(['success' => false, 'error' => 'Failed to read input stream'], 400);
        }
        $out = fopen($streamTemp, 'wb');
        if (!$out) {
            fclose($in);
            jsonResponse(['success' => false, 'error' => 'Failed to create temp stream file'], 500);
        }

        $bytesCopied = 0;
        while (!feof($in)) {
            $buf = fread($in, 65536);
            if ($buf !== false && $buf !== '') {
                fwrite($out, $buf);
                $bytesCopied += strlen($buf);
                if ($bytesCopied > MAX_UPLOAD_SIZE) {
                    fclose($in);
                    fclose($out);
                    @unlink($streamTemp);
                    jsonResponse(['success' => false, 'error' => 'File exceeds MAX_UPLOAD_SIZE'], 413);
                }
            }
        }
        fclose($in);
        fclose($out);

        if ($bytesCopied === 0) {
            @unlink($streamTemp);
            jsonResponse(['success' => false, 'error' => 'No file received in request'], 400);
        }

        $tempFilePath = $streamTemp;
    }

    // 2. Validate Size
    $fileSize = (int)@filesize($tempFilePath);
    if ($fileSize === 0) {
        if ($isChunked || isset($streamTemp)) @unlink($tempFilePath);
        jsonResponse(['success' => false, 'error' => 'Empty file received'], 400);
    }
    if ($fileSize > MAX_UPLOAD_SIZE) {
        if ($isChunked || isset($streamTemp)) @unlink($tempFilePath);
        jsonResponse(['success' => false, 'error' => 'File size exceeds maximum allowed size (' . MAX_UPLOAD_SIZE . ' bytes)'], 413);
    }

    // 3. Detect MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $tempFilePath) ?: 'application/octet-stream';
    finfo_close($finfo);

    // 4. Calculate Content Hash (CAS)
    $hash = hash_file(HASH_ALGO, $tempFilePath);
    if ($hash === false) {
        throw new RuntimeException('Failed to compute file hash');
    }

    // 5. Build Safe Filename & Storage Target
    $safeExt = getSafeExtension($originalName, $detectedMime);
    $fileName = $hash . ($safeExt !== '' ? '.' . $safeExt : '');
    $shardDir = STORAGE_PATH . '/' . getShardPath($hash);
    $destination = $shardDir . '/' . $fileName;

    $baseUrl = getBaseUrl();
    $relativeDirectPath = 'storage/' . getShardPath($hash) . '/' . $fileName;
    $directUrl = $baseUrl . '/' . $relativeDirectPath;
    $cleanUrl = $baseUrl . '/f/' . $fileName;
    $publicUrl = USE_CLEAN_URL ? $cleanUrl : $directUrl;

    // 6. Check for deduplication
    if (file_exists($destination) && filesize($destination) === $fileSize) {
        // File already exists with identical hash and size
        if ($isChunked || isset($streamTemp)) {
            @unlink($tempFilePath);
        }

        jsonResponse([
            'success'       => true,
            'status'        => 'exists',
            'deduplicated'  => true,
            'file_name'     => $fileName,
            'hash'          => $hash,
            'url'           => $publicUrl,
            'clean_url'     => $cleanUrl,
            'direct_url'    => $directUrl,
            'size'          => $fileSize,
            'mime_type'     => $detectedMime,
            'original_name' => basename($originalName)
        ]);
    }

    // 7. Store new file
    if (!is_dir($shardDir)) {
        if (!mkdir($shardDir, 0755, true) && !is_dir($shardDir)) {
            throw new RuntimeException('Failed to create storage directory: ' . $shardDir);
        }
    }

    // Move from temp location
    if (is_uploaded_file($tempFilePath)) {
        if (!move_uploaded_file($tempFilePath, $destination)) {
            throw new RuntimeException('Failed to move uploaded file');
        }
    } else {
        if (!rename($tempFilePath, $destination)) {
            if (!copy($tempFilePath, $destination)) {
                throw new RuntimeException('Failed to copy file to destination');
            }
            @unlink($tempFilePath);
        }
    }

    @chmod($destination, 0644);

    // 8. Return response
    jsonResponse([
        'success'       => true,
        'status'        => 'uploaded',
        'deduplicated'  => false,
        'file_name'     => $fileName,
        'hash'          => $hash,
        'url'           => $publicUrl,
        'clean_url'     => $cleanUrl,
        'direct_url'    => $directUrl,
        'size'          => $fileSize,
        'mime_type'     => $detectedMime,
        'original_name' => basename($originalName),
        'uploaded_at'   => time()
    ], 201);

} catch (Throwable $e) {
    if (!empty($tempFilePath) && ($isChunked || isset($streamTemp)) && file_exists($tempFilePath)) {
        @unlink($tempFilePath);
    }

    jsonResponse([
        'success' => false,
        'error'   => $e->getMessage()
    ], 500);
}
