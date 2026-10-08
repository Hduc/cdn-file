<?php
/**
 * PHP CDN Storage - Google Drive Service Account Driver
 * Pure PHP implementation of Google OAuth2 RS256 JWT & Drive v3 API
 * Zero composer dependencies.
 */

declare(strict_types=1);

class GoogleDriveDriver
{
    private array $credentials;
    private string $accountId;
    private string $tokenFile;

    public function __construct(string $credentialFilePath)
    {
        if (!file_exists($credentialFilePath)) {
            throw new InvalidArgumentException("Credential file not found: {$credentialFilePath}");
        }

        $json = json_decode((string)file_get_contents($credentialFilePath), true);
        if (!is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new InvalidArgumentException("Invalid Google Service Account JSON: {$credentialFilePath}");
        }

        $this->credentials = $json;
        $this->accountId   = basename($credentialFilePath, '.json');
        $this->tokenFile   = (__DIR__ . '/tmp') . '/gtoken_' . md5($json['client_email']) . '.json';
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getClientEmail(): string
    {
        return $this->credentials['client_email'] ?? '';
    }

    public function getFolderId(): ?string
    {
        return $this->credentials['folder_id'] ?? (defined('GDRIVE_DEFAULT_FOLDER_ID') ? GDRIVE_DEFAULT_FOLDER_ID : null);
    }


    /**
     * Get OAuth2 Access Token (cached with expiration check)
     */
    public function getAccessToken(): string
    {
        if (file_exists($this->tokenFile)) {
            $cached = json_decode((string)file_get_contents($this->tokenFile), true);
            if (!empty($cached['access_token']) && !empty($cached['expires_at'])) {
                if ($cached['expires_at'] > (time() + 120)) {
                    return $cached['access_token'];
                }
            }
        }

        $token = $this->fetchNewAccessToken();
        return $token;
    }

    /**
     * Generate RS256 JWT and exchange with Google for access token
     */
    private function fetchNewAccessToken(): string
    {
        $now = time();
        $jwtHeader = $this->base64UrlEncode((string)json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwtClaim = $this->base64UrlEncode((string)json_encode([
            'iss'   => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud'   => $this->credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ]));

        $dataToSign = "{$jwtHeader}.{$jwtClaim}";
        $signature = '';

        $privateKey = openssl_pkey_get_private($this->credentials['private_key']);
        if (!$privateKey) {
            throw new RuntimeException("Failed to parse private key: " . openssl_error_string());
        }

        $success = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new RuntimeException("Failed to sign JWT with OpenSSL: " . openssl_error_string());
        }

        $jwt = "{$dataToSign}." . $this->base64UrlEncode($signature);

        // Exchange JWT for access token
        $ch = $this->initCurl($this->credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            throw new RuntimeException("Failed to exchange JWT for Google access token: {$err}");
        }

        $tokenData = json_decode((string)$res, true);
        if ($httpCode !== 200 || empty($tokenData['access_token'])) {
            $msg = $tokenData['error_description'] ?? $tokenData['error'] ?? $res;
            throw new RuntimeException("Google OAuth2 token error ({$httpCode}): {$msg}");
        }

        $accessToken = $tokenData['access_token'];
        $expiresIn   = (int)($tokenData['expires_in'] ?? 3600);

        if (!is_dir(dirname($this->tokenFile))) {
            @mkdir(dirname($this->tokenFile), 0755, true);
        }

        file_put_contents($this->tokenFile, json_encode([
            'access_token' => $accessToken,
            'expires_at'   => $now + $expiresIn
        ]));

        return $accessToken;
    }

    /**
     * Upload a file to Google Drive using Resumable Upload
     *
     * @param string $localFilePath Path to file
     * @param string $targetFileName Filename on Google Drive
     * @param string $mimeType MIME type
     * @param string|null $folderId Target folder ID
     * @return array Drive file metadata (id, name, size)
     */
    public function uploadFile(string $localFilePath, string $targetFileName, string $mimeType, ?string $folderId = null): array
    {
        if (!file_exists($localFilePath)) {
            throw new InvalidArgumentException("Local file not found: {$localFilePath}");
        }

        $token = $this->getAccessToken();
        $targetFolder = $folderId ?: $this->getFolderId();
        if (empty($targetFolder) || $targetFolder === 'PASTE_YOUR_SHARED_GOOGLE_DRIVE_FOLDER_ID_HERE') {
            throw new RuntimeException("File credentials/{$this->accountId}.json chưa có 'folder_id'! Vui lòng mở file và thêm 'folder_id' của thư mục Google Drive (ví dụ cdn-aigiup).");
        }

        $fileSize = filesize($localFilePath);

        // Step 1: Initiate Resumable Session
        $metadata = [
            'name'    => $targetFileName,
            'parents' => [$targetFolder]
        ];

        $ch = $this->initCurl('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($metadata),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json; charset=UTF-8',
                'X-Upload-Content-Type: ' . $mimeType,
                'X-Upload-Content-Length: ' . $fileSize
            ],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException("Failed to initiate resumable upload to Google Drive ({$httpCode}): {$response}");
        }

        $headers = substr((string)$response, 0, $headerSize);
        if (!preg_match('/location:\s*(.*)/i', $headers, $matches)) {
            throw new RuntimeException("Resumable upload session failed: Missing Location header.");
        }
        $uploadUrl = trim($matches[1]);

        // Step 2: Stream upload binary to Google Drive
        $handle = fopen($localFilePath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Cannot open local file for streaming: {$localFilePath}");
        }

        $ch = $this->initCurl($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_UPLOAD         => true,
            CURLOPT_INFILE         => $handle,
            CURLOPT_INFILESIZE     => $fileSize,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: ' . $mimeType,
                'Content-Length: ' . $fileSize
            ],
            CURLOPT_TIMEOUT        => 600
        ]);

        $putResponse = curl_exec($ch);
        $putCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $putErr = curl_error($ch);
        fclose($handle);
        curl_close($ch);

        if ($putCode !== 200 && $putCode !== 201) {
            throw new RuntimeException("Google Drive upload failed ({$putCode}): {$putErr} - {$putResponse}");
        }

        $driveFile = json_decode((string)$putResponse, true);
        if (empty($driveFile['id'])) {
            throw new RuntimeException("Invalid response from Google Drive: {$putResponse}");
        }

        return $driveFile;
    }

    /**
     * Delete a file from Google Drive
     */
    public function deleteFile(string $driveFileId): bool
    {
        $token = $this->getAccessToken();
        $ch = $this->initCurl("https://www.googleapis.com/drive/v3/files/{$driveFileId}?supportsAllDrives=true");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 204 || $httpCode === 200);
    }

    /**
     * Stream a file directly to the client browser / CDN with Range support
     */
    public function streamDownload(string $driveFileId, ?string $rangeHeader = null): void
    {
        $token = $this->getAccessToken();
        $url = "https://www.googleapis.com/drive/v3/files/{$driveFileId}?alt=media&supportsAllDrives=true";

        $headers = [
            'Authorization: Bearer ' . $token
        ];

        if (!empty($rangeHeader)) {
            $headers[] = 'Range: ' . $rangeHeader;
        }

        $ch = $this->initCurl($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => false, // Output directly to output buffer
            CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) {
                // Forward content length, content range, content type from Google Drive
                $lower = strtolower($headerLine);
                if (
                    strpos($lower, 'content-type:') === 0 ||
                    strpos($lower, 'content-length:') === 0 ||
                    strpos($lower, 'content-range:') === 0
                ) {
                    header(trim($headerLine));
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) {
                echo $chunk;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                return strlen($chunk);
            },
            CURLOPT_TIMEOUT        => 600
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Initialize cURL handle with automatic SSL fallback for environments missing CA bundle
     *
     * @return \CurlHandle|resource
     */
    private function initCurl(string $url)
    {
        $ch = curl_init($url);
        $caFile = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        if (empty($caFile) || !file_exists($caFile)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        return $ch;
    }
}

