<?php
/**
 * PHP CDN Storage - Database Layer (SQLite)
 * Lightweight, zero-config metadata storage for Google Drive files
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $dbDir = __DIR__ . '/data';
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0755, true);
            }

            $dbPath = $dbDir . '/cdn_store.db';
            self::$pdo = new PDO('sqlite:' . $dbPath);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable WAL mode for high concurrency
            self::$pdo->exec('PRAGMA journal_mode = WAL;');
            self::$pdo->exec('PRAGMA synchronous = NORMAL;');

            self::initSchema();
        }

        return self::$pdo;
    }

    private static function initSchema(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS files (
                hash TEXT PRIMARY KEY,
                file_name TEXT NOT NULL,
                mime_type TEXT NOT NULL,
                size INTEGER NOT NULL,
                account_id TEXT NOT NULL,
                gdrive_file_id TEXT NOT NULL,
                created_at INTEGER NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_files_hash ON files(hash);
            CREATE INDEX IF NOT EXISTS idx_files_account ON files(account_id);
        ";
        self::$pdo->exec($sql);
    }

    public static function findByHash(string $hash): ?array
    {
        $stmt = self::getConnection()->prepare("SELECT * FROM files WHERE hash = :hash LIMIT 1");
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function save(
        string $hash,
        string $fileName,
        string $mimeType,
        int $size,
        string $accountId = 'local',
        string $gdriveFileId = ''
    ): bool {
        $stmt = self::getConnection()->prepare("
            INSERT OR REPLACE INTO files (hash, file_name, mime_type, size, account_id, gdrive_file_id, created_at)
            VALUES (:hash, :file_name, :mime_type, :size, :account_id, :gdrive_file_id, :created_at)
        ");

        return $stmt->execute([
            ':hash'           => $hash,
            ':file_name'      => $fileName,
            ':mime_type'      => $mimeType,
            ':size'           => $size,
            ':account_id'     => $accountId,
            ':gdrive_file_id' => $gdriveFileId,
            ':created_at'     => time()
        ]);
    }

    public static function delete(string $hash): ?array
    {
        $file = self::findByHash($hash);
        if (!$file) {
            return null;
        }

        $stmt = self::getConnection()->prepare("DELETE FROM files WHERE hash = :hash");
        $stmt->execute([':hash' => $hash]);
        return $file;
    }

    public static function getStats(): array
    {
        $stmt = self::getConnection()->query("SELECT COUNT(*) AS total_files, COALESCE(SUM(size), 0) AS total_size FROM files");
        $row = $stmt->fetch();
        return [
            'total_files' => (int)($row['total_files'] ?? 0),
            'total_size'  => (int)($row['total_size'] ?? 0)
        ];
    }
}
