<?php
/**
 * PHP CDN Storage - Configuration
 * Optimized for Shared Hosting & Cloudflare/CDN
 */

declare(strict_types=1);

// Prevent direct access to config if called in browser
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') {
    http_response_code(403);
    exit('Access Denied');
}

// -------------------------------------------------------------
// 1. AUTHENTICATION & SECURITY
// -------------------------------------------------------------
// Change this key immediately before deploying to production!
define('CDN_API_KEY', getenv('CDN_API_KEY') ?: 'cdn_secret_key_change_me_123456');

// Blocked file extensions (never allow executable scripts)
define('BLOCKED_EXTENSIONS', [
    'php',
    'php3',
    'php4',
    'php5',
    'php7',
    'phtml',
    'phar',
    'sh',
    'bash',
    'py',
    'pl',
    'cgi',
    'exe',
    'bat',
    'cmd',
    'htaccess',
    'htpasswd',
    'env',
    'ini',
    'conf'
]);

// -------------------------------------------------------------
// 2. STORAGE & DIRECTORY SHARDING
// -------------------------------------------------------------
define('STORAGE_PATH', __DIR__ . '/storage');
define('TMP_PATH', __DIR__ . '/tmp');

// Hash algorithm: 'sha1' (40 chars) or 'md5' (32 chars) or 'sha256' (64 chars)
define('HASH_ALGO', 'sha1');

// Directory sharding depth: 2 levels -> storage/ab/cd/{hash}.{ext}
// Keeps directory entries < 1,000 on high-volume filesystems
define('SHARD_DEPTH', 2);

// Max upload size in bytes (default 500MB)
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024);

// -------------------------------------------------------------
// 3. CDN & BASE URL SETTINGS
// -------------------------------------------------------------
// Leave empty to auto-detect current domain (e.g., https://cdn.yourdomain.com)
// Or set explicitly: define('CDN_BASE_URL', 'https://cdn.yourdomain.com');
define('CDN_BASE_URL', getenv('CDN_BASE_URL') ?: '');

// Use clean URLs: true -> /f/{hash}.{ext} (requires .htaccess rewrite)
//                 false -> /storage/ab/cd/{hash}.{ext}
define('USE_CLEAN_URL', true);

// Keep a copy on hosting storage for lightning-fast origin response?
// true: Keep file on hosting disk cache + sync to Google Drive
// false: Stream to Google Drive then delete from hosting (0% disk used on hosting)
define('GDRIVE_KEEP_LOCAL_CACHE', true);

// Ưu tiên & Bắt buộc Google Drive:
// true: Bắt buộc upload lên Google Drive thành công. Nếu lỗi hoặc chưa cấu hình -> Báo lỗi ngay lập tức!
// false: Upload hosting trước, Drive đồng bộ phụ dưới nền.
define('GDRIVE_REQUIRED', true);

// Folder ID mặc định trên Google Drive (Thư mục id)
define('GDRIVE_DEFAULT_FOLDER_ID', '');

// -------------------------------------------------------------
// 4. TỰ ĐỘNG CHUYỂN ĐỔI ẢNH SANG WEBP (AUTO WEBP CONVERSION)
// -------------------------------------------------------------
// Tự động nén và chuyển các định dạng ảnh (JPEG, PNG, GIF, BMP) sang WebP
define('AUTO_CONVERT_WEBP', true);

// Chất lượng nén WebP (1 - 100). Mức 82 cho chất lượng mắt thường khó phân biệt nhưng dung lượng giảm 60-80%
define('WEBP_QUALITY', 82);

// true: Chỉ dùng WebP nếu dung lượng nhỏ hơn file gốc. false: Luôn đổi sang WebP chuẩn hóa
define('WEBP_ONLY_IF_SMALLER', false);




// -------------------------------------------------------------
// 4. HELPER FUNCTIONS
// -------------------------------------------------------------

/**
 * Send JSON response and exit
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Authenticate incoming request via Bearer token, X-API-Key, or POST api_key
 */
function authenticateRequest(): bool
{
    $apiKey = CDN_API_KEY;
    if (empty($apiKey)) {
        return false;
    }

    // 1. Check Authorization: Bearer <key>
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        if (hash_equals($apiKey, trim($matches[1]))) {
            return true;
        }
    }

    // 2. Check X-API-Key header
    $customHeader = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!empty($customHeader) && hash_equals($apiKey, trim($customHeader))) {
        return true;
    }

    // 3. Check POST parameter
    $postKey = $_POST['api_key'] ?? '';
    if (!empty($postKey) && hash_equals($apiKey, trim($postKey))) {
        return true;
    }

    // 4. Check GET parameter (optional, useful for quick tests)
    $getKey = $_GET['api_key'] ?? '';
    if (!empty($getKey) && hash_equals($apiKey, trim($getKey))) {
        return true;
    }

    return false;
}

/**
 * Get base URL of current host
 */
function getBaseUrl(): string
{
    if (!empty(CDN_BASE_URL)) {
        return rtrim(CDN_BASE_URL, '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Determine script directory relative to document root
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir === '/') {
        $scriptDir = '';
    }

    return $protocol . $host . $scriptDir;
}

/**
 * Get sharded relative directory path for a hash
 * Example: sha1 "a1b2c3d4..." -> "a1/b2"
 */
function getShardPath(string $hash): string
{
    $parts = [];
    for ($i = 0; $i < SHARD_DEPTH; $i++) {
        $parts[] = substr($hash, $i * 2, 2);
    }
    return implode('/', $parts);
}

/**
 * Sanitize and extract safe file extension
 */
function getSafeExtension(string $filename, string $detectedMime = ''): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Fallback to mime type extension if empty
    if (empty($ext) && !empty($detectedMime)) {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'application/zip' => 'zip'
        ];
        $ext = $mimeMap[$detectedMime] ?? 'bin';
    }

    if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
        return 'txt'; // Neutralize blocked executable extensions
    }

    return preg_replace('/[^a-z0-9]/', '', $ext);
}

/**
 * Convert an image file (JPEG, PNG, GIF, BMP) to WebP format with alpha channel preservation
 *
 * @param string $sourcePath Path to image file
 * @param string $mimeType Detected MIME type
 * @param int $quality Compression quality (1 - 100)
 * @return string|null Path to generated WebP file on success, null on failure
 */
function convertImageToWebP(string $sourcePath, string $mimeType, int $quality = 82): ?string
{
    if (!function_exists('imagewebp')) {
        return null;
    }

    $image = null;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($sourcePath);
            }
            break;

        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($sourcePath);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
            }
            break;

        case 'image/gif':
            if (function_exists('imagecreatefromgif')) {
                $image = @imagecreatefromgif($sourcePath);
                if ($image) {
                    imagepalettetotruecolor($image);
                }
            }
            break;

        case 'image/bmp':
        case 'image/x-ms-bmp':
            if (function_exists('imagecreatefrombmp')) {
                $image = @imagecreatefrombmp($sourcePath);
            }
            break;

        case 'image/webp':
            // Already WebP
            return $sourcePath;

        default:
            return null;
    }

    if (!$image) {
        return null;
    }

    $webpTemp = $sourcePath . '.webp';
    $saved = @imagewebp($image, $webpTemp, max(1, min(100, $quality)));
    imagedestroy($image);

    if ($saved && file_exists($webpTemp) && filesize($webpTemp) > 0) {
        return $webpTemp;
    }

    if (file_exists($webpTemp)) {
        @unlink($webpTemp);
    }
    return null;
}

