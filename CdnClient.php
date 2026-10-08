<?php
/**
 * PHP CDN Client SDK
 * Simple, zero-dependency helper to upload and delete files on your PHP CDN.
 */

declare(strict_types=1);

class CdnClient
{
    private string $endpoint;
    private string $apiKey;
    private int $timeout;

    public function __construct(string $endpoint, string $apiKey, int $timeout = 60)
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey   = $apiKey;
        $this->timeout  = $timeout;
    }

    /**
     * Upload a local file via standard multipart form-data
     *
     * @param string $localFilePath Path to local file
     * @param string|null $customFileName Optional custom filename
     * @return array Decoded JSON response
     * @throws RuntimeException
     */
    public function uploadFile(string $localFilePath, ?string $customFileName = null): array
    {
        if (!file_exists($localFilePath) || !is_readable($localFilePath)) {
            throw new InvalidArgumentException("File not found or not readable: {$localFilePath}");
        }

        $cFile = curl_file_create(
            $localFilePath,
            mime_content_type($localFilePath) ?: 'application/octet-stream',
            $customFileName ?: basename($localFilePath)
        );

        $postData = ['file' => $cFile];

        return $this->request('/upload.php', 'POST', $postData);
    }

    /**
     * Upload binary data directly (stream without creating a multipart body)
     *
     * @param string $binaryContent Binary content
     * @param string $fileName Target filename (with extension)
     * @return array
     */
    public function uploadBinary(string $binaryContent, string $fileName): array
    {
        $url = $this->endpoint . '/upload.php';
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'X-File-Name: ' . $fileName,
            'Content-Type: application/octet-stream',
            'Content-Length: ' . strlen($binaryContent)
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $binaryContent,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("cURL stream upload failed: {$error}");
        }

        $json = json_decode((string)$response, true);
        if ($httpCode >= 400 || !($json['success'] ?? false)) {
            $msg = $json['error'] ?? "HTTP error {$httpCode}: {$response}";
            throw new RuntimeException("Upload failed: {$msg}");
        }

        return $json;
    }

    /**
     * Upload large files in chunks (chunked upload)
     *
     * @param string $localFilePath Path to file
     * @param int $chunkSize Chunk size in bytes (default 5MB)
     * @return array Final JSON response
     */
    public function uploadChunked(string $localFilePath, int $chunkSize = 5242880): array
    {
        if (!file_exists($localFilePath)) {
            throw new InvalidArgumentException("File not found: {$localFilePath}");
        }

        $fileSize = filesize($localFilePath);
        $totalChunks = (int)ceil($fileSize / $chunkSize);
        $uploadId = bin2hex(random_bytes(16));
        $fileName = basename($localFilePath);

        $handle = fopen($localFilePath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Cannot read file: {$localFilePath}");
        }

        $lastResponse = [];

        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $chunkData = fread($handle, $chunkSize);

            $url = $this->endpoint . '/upload.php';
            $ch = curl_init($url);

            $headers = [
                'Authorization: Bearer ' . $this->apiKey,
                'X-Upload-Id: ' . $uploadId,
                'X-Chunk-Index: ' . $chunkIndex,
                'X-Total-Chunks: ' . $totalChunks,
                'X-File-Name: ' . $fileName,
                'Content-Type: application/octet-stream',
                'Content-Length: ' . strlen($chunkData)
            ];

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $chunkData,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                fclose($handle);
                throw new RuntimeException("Chunk {$chunkIndex} failed: {$error}");
            }

            $lastResponse = json_decode((string)$response, true);
            if ($httpCode >= 400 || !($lastResponse['success'] ?? false)) {
                fclose($handle);
                $msg = $lastResponse['error'] ?? "HTTP error {$httpCode}";
                throw new RuntimeException("Chunk {$chunkIndex} failed: {$msg}");
            }
        }

        fclose($handle);
        return $lastResponse;
    }

    /**
     * Delete a file from CDN by hash or filename
     */
    public function deleteFile(string $hashOrFileName): array
    {
        return $this->request('/delete.php', 'POST', ['file_name' => $hashOrFileName]);
    }

    /**
     * Check server health
     */
    public function health(): array
    {
        return $this->request('/health.php', 'GET');
    }

    private function request(string $path, string $method = 'GET', array $data = []): array
    {
        $url = $this->endpoint . $path;
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("cURL Request to {$url} failed: {$error}");
        }

        $json = json_decode((string)$response, true);
        if ($httpCode >= 400 || !($json['success'] ?? false)) {
            $msg = $json['error'] ?? "HTTP error {$httpCode}: {$response}";
            throw new RuntimeException("CDN Error: {$msg}");
        }

        return $json;
    }
}
