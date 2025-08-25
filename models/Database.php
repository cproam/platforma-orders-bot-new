<?php
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct(string $path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $this->pdo = new \PDO('sqlite:' . $path);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA journal_mode = WAL;');
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
        $this->initSchema();
    }

    public static function get(): self
    {
        if (!self::$instance) self::$instance = new self(DB_PATH);
        return self::$instance;
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                manager_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                payload TEXT NOT NULL,              -- JSON of fields
                document_path TEXT,                 -- optional upload (if ever used)
                status TEXT NOT NULL DEFAULT 'pending',
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
    }

    public function insertOrder(
        int $managerId,
        string $type,
        array $payload,
        ?string $documentPath = null
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (manager_id, type, payload, document_path)
            VALUES (:m, :t, :p, :d)
        ");
        $stmt->execute([
            ':m' => $managerId,
            ':t' => $type,
            ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':d' => $documentPath
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getManagerId(int $orderId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT manager_id FROM orders WHERE id = :id");
        $stmt->execute([':id' => $orderId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : null;
    }

    public function setStatus(int $orderId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = :s WHERE id = :id");
        $stmt->execute([':s' => $status, ':id' => $orderId]);
    }
}
