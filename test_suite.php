<?php
/**
 * PHP CDN Storage - Automated Test Suite
 */

declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8088';
$apiKey  = 'cdn_secret_key_change_me_123456';

echo "=== [1] Testing Health Endpoint ===\n";
$health = file_get_contents("{$baseUrl}/health.php");
$healthData = json_decode((string)$health, true);
assert($healthData['success'] === true, 'Health check should be true');
echo "✓ Health check passed. Status: {$healthData['status']}\n\n";

echo "=== [2] Testing Unauthorized Upload ===\n";
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile(__DIR__ . '/test1.txt', 'text/plain', 'test1.txt')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assert($code === 401, "Expected 401 Unauthorized, got {$code}");
echo "✓ 401 Unauthorized correctly enforced without API key.\n\n";

echo "=== [3] Testing File Upload (v1) ===\n";
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$apiKey}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile(__DIR__ . '/test1.txt', 'text/plain', 'test1.txt')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$upload1 = json_decode((string)$res, true);
assert($code === 201 && $upload1['success'] === true, "Upload v1 failed: {$res}");
$hash1 = $upload1['hash'];
$url1  = $upload1['url'];
echo "✓ Upload v1 successful!\n";
echo "  Hash: {$hash1}\n";
echo "  URL:  {$url1}\n\n";

echo "=== [4] Testing Content Deduplication (Upload exact same file) ===\n";
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$apiKey}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile(__DIR__ . '/test1.txt', 'text/plain', 'test1.txt')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$uploadDup = json_decode((string)$res, true);
assert($uploadDup['deduplicated'] === true, 'Upload duplicate should have deduplicated=true');
assert($uploadDup['hash'] === $hash1, 'Duplicate hash must match exactly');
echo "✓ Content deduplication confirmed! Same file returned identical hash and URL without duplicating storage.\n\n";

echo "=== [5] Testing Modified File (Content Change -> New Hash & New CDN URL) ===\n";
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$apiKey}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile(__DIR__ . '/test2_modified.txt', 'text/plain', 'test1.txt')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$upload2 = json_decode((string)$res, true);
assert($upload2['hash'] !== $hash1, 'Modified content MUST have a different hash');
echo "✓ Cache-Busting verified!\n";
echo "  Modified Hash: {$upload2['hash']}\n";
echo "  Modified URL:  {$upload2['url']}\n\n";

echo "=== [6] Testing Binary Stream Upload (php://input) ===\n";
$payload = "Binary streamed raw data payload content " . time();
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "X-File-Name: stream_test.txt",
    "Content-Type: application/octet-stream"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$uploadStream = json_decode((string)$res, true);
assert($code === 201 && $uploadStream['success'] === true, "Stream upload failed: {$res}");
echo "✓ Stream upload successful! Hash: {$uploadStream['hash']}\n\n";

echo "=== [7] Testing Dangerous File Extension Blocking ===\n";
file_put_contents(__DIR__ . '/malicious.php', '<?php echo "hacked"; ?>');
$ch = curl_init("{$baseUrl}/upload.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$apiKey}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile(__DIR__ . '/malicious.php', 'application/x-php', 'malicious.php')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$uploadMalicious = json_decode((string)$res, true);
@unlink(__DIR__ . '/malicious.php');
assert(pathinfo($uploadMalicious['file_name'], PATHINFO_EXTENSION) !== 'php', 'Dangerous .php extension must not be saved as executable');
echo "✓ Dangerous .php extension neutralized to .txt: {$uploadMalicious['file_name']}\n\n";

echo "=== [8] Testing File Delete ===\n";
$ch = curl_init("{$baseUrl}/delete.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$apiKey}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['hash' => $hash1]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$delRes = json_decode((string)$res, true);
assert($code === 200 && $delRes['success'] === true, "Delete failed: {$res}");
echo "✓ File {$hash1} deleted successfully via API.\n\n";

echo "========================================\n";
echo "🎉 ALL 8 TESTS PASSED SUCCESSFULLY!\n";
echo "========================================\n";
