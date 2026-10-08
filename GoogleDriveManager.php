<?php
/**
 * PHP CDN Storage - Google Drive Pool Manager
 * Manages multiple Google Drive accounts with seamless distribution
 */

declare(strict_types=1);

require_once __DIR__ . '/GoogleDriveDriver.php';

class GoogleDriveManager
{
    private static ?array $drivers = null;
    private static array $loadErrors = [];

    public static function getLoadErrors(): array
    {
        return self::$loadErrors;
    }

    /**
     * Discover all registered Service Accounts in credentials/
     */
    public static function getDrivers(): array
    {
        if (self::$drivers !== null) {
            return self::$drivers;
        }

        self::$drivers = [];
        self::$loadErrors = [];
        $credDir = __DIR__ . '/credentials';
        if (!is_dir($credDir)) {
            self::$loadErrors[] = "Thư mục credentials/ không tồn tại tại: {$credDir}";
            return self::$drivers;
        }

        $files = glob($credDir . '/*.json');
        if ($files === false || empty($files)) {
            self::$loadErrors[] = "Không tìm thấy bất kỳ file *.json nào trong: {$credDir}";
            return self::$drivers;
        }

        $realAccountCount = 0;
        foreach ($files as $file) {
            if (basename($file) === 'account_sample.json') {
                continue;
            }

            $realAccountCount++;
            try {
                $driver = new GoogleDriveDriver($file);
                self::$drivers[$driver->getAccountId()] = $driver;
            } catch (Throwable $e) {
                $errMsg = "Lỗi đọc file [" . basename($file) . "]: " . $e->getMessage();
                self::$loadErrors[] = $errMsg;
                error_log("Failed to load Google Drive credential [{$file}]: " . $e->getMessage());
            }
        }

        if ($realAccountCount === 0) {
            self::$loadErrors[] = "Thư mục credentials/ chỉ có file mẫu account_sample.json. Chưa có file key cấu hình thật (ví dụ aigiup-cdn-*.json).";
        }

        return self::$drivers;
    }


    public static function isEnabled(): bool
    {
        return !empty(self::getDrivers());
    }

    public static function getDriver(?string $accountId = null): ?GoogleDriveDriver
    {
        $drivers = self::getDrivers();
        if (empty($drivers)) {
            return null;
        }

        if ($accountId !== null && isset($drivers[$accountId])) {
            return $drivers[$accountId];
        }

        // Round-robin / selection
        $keys = array_keys($drivers);
        $selectedKey = $keys[array_rand($keys)];
        return $drivers[$selectedKey];
    }

    /**
     * Upload file to the Google Drive pool
     */
    public static function upload(string $localFilePath, string $fileName, string $mimeType): array
    {
        $driver = self::getDriver();
        if (!$driver) {
            throw new RuntimeException('No Google Drive account configured in credentials/');
        }

        $driveFile = $driver->uploadFile($localFilePath, $fileName, $mimeType);

        return [
            'account_id'     => $driver->getAccountId(),
            'gdrive_file_id' => $driveFile['id'],
            'client_email'   => $driver->getClientEmail()
        ];
    }

    /**
     * Stream file content from specific account
     */
    public static function stream(string $accountId, string $gdriveFileId, ?string $range = null): void
    {
        $driver = self::getDriver($accountId);
        if (!$driver) {
            http_response_code(404);
            exit('Google Drive storage account not found');
        }

        $driver->streamDownload($gdriveFileId, $range);
    }

    /**
     * Delete file from Google Drive pool
     */
    public static function delete(string $accountId, string $gdriveFileId): bool
    {
        $driver = self::getDriver($accountId);
        if (!$driver) {
            return false;
        }

        return $driver->deleteFile($gdriveFileId);
    }

    /**
     * List all active accounts status
     */
    public static function getAccountsList(): array
    {
        $list = [];
        foreach (self::getDrivers() as $id => $driver) {
            $list[] = [
                'account_id'   => $id,
                'client_email' => $driver->getClientEmail(),
                'folder_id'    => $driver->getFolderId() ?: 'Root Drive'
            ];
        }
        return $list;
    }
}
