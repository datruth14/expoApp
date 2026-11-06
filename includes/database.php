<?php
declare(strict_types=1);

/** Small prepared-query interface shared by cPanel MySQLi and offline SQLite. */
final class AppDatabase
{
    public function __construct(private mysqli|PDO $connection)
    {
    }

    public function query(string $sql, array $parameters = []): AppDatabaseResult
    {
        if ($this->connection instanceof PDO) {
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            return new AppDatabaseResult($statement);
        }

        $statement = $this->connection->prepare($sql);
        if ($parameters !== []) {
            $types = '';
            foreach ($parameters as $parameter) {
                $types .= is_int($parameter) ? 'i' : (is_float($parameter) ? 'd' : 's');
            }
            $statement->bind_param($types, ...$parameters);
        }
        $statement->execute();
        $result = $statement->get_result();
        return new AppDatabaseResult($result === false ? null : $result);
    }
}

final class AppDatabaseResult
{
    public function __construct(private mysqli_result|PDOStatement|null $result)
    {
    }

    public function fetch(): array|false
    {
        if ($this->result instanceof PDOStatement) {
            return $this->result->fetch(PDO::FETCH_ASSOC);
        }
        return $this->result?->fetch_assoc() ?: false;
    }

    public function fetchAll(): array
    {
        if ($this->result instanceof PDOStatement) {
            return $this->result->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->result?->fetch_all(MYSQLI_ASSOC) ?? [];
    }
}

function open_sqlite_database(string $path): AppDatabase
{
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('Offline mode requires the PHP pdo_sqlite extension.');
    }
    if (!is_dir(dirname($path))) {
        throw new RuntimeException('The SQLite database directory does not exist. Check sqlite_path in your configuration.');
    }

    $connection = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection->exec('PRAGMA foreign_keys = ON');
    $connection->exec('PRAGMA busy_timeout = 5000');
    $version = (int) $connection->query('PRAGMA user_version')->fetchColumn();
    if ($version < 3) {
        $connection->beginTransaction();
        try {
            if ($version >= 1) {
                $columns = $connection->query('PRAGMA table_info(feedback)')->fetchAll(PDO::FETCH_ASSOC);
                if ($columns && !in_array('delegate_id', array_column($columns, 'name'), true)) {
                    $connection->exec('ALTER TABLE feedback ADD COLUMN delegate_id INTEGER REFERENCES delegates(id) ON DELETE SET NULL');
                }
            }
            $connection->exec(file_get_contents(__DIR__ . '/../database_schema_sqlite.sql'));
            if ($version < 1) {
                $seed = $connection->prepare('INSERT OR IGNORE INTO programs (name, slug, platform_title, tagline, description, welcome_message, services_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $seed->execute([
                    'Loveword Expo 2026', 'loveword-expo-2026', 'Delegate Experience Centre',
                    'Where the World Meets Loveworld',
                    'A seamless, welcoming and memorable event experience starts here.',
                    'A seamless, welcoming and memorable event experience starts here.',
                    json_encode(default_program_services(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'published',
                ]);
            }
            $connection->exec('PRAGMA user_version = 3');
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    return new AppDatabase($connection);
}
