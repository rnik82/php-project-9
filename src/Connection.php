<?php

namespace Hexlet\Code;

/**
 * Создание класса Connection
 */
final class Connection
{
    /**
     * Connection
     * тип @var
     */
    private static ?Connection $conn = null;

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    public function create(string $url)
    {
        $databaseUrl = parse_url($url);
        $username = $databaseUrl['user']; // username
        $password = $databaseUrl['pass']; // password
        $host = $databaseUrl['host']; // localhost
        $port = $databaseUrl['port']; // 5432
        $dbName = ltrim($databaseUrl['path'], '/'); // dbname

        // подключение к базе данных postgresql
        // sprintf вернет строку с подставленными параметрами
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $host,
            $port,
            $dbName,
            $username,
            $password
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * возврат экземпляра объекта Connection
     * тип @return
     */
    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }

    protected function __construct()
    {

    }
}
