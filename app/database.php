<?php

declare(strict_types=1);

function database(): ?PDO
{
    static $connection = null;
    static $attempted = false;

    if ($attempted) {
        return $connection;
    }

    $attempted = true;
    $config = require APP_ROOT . '/config/database.php';

    if ($config['name'] === '' || $config['username'] === '') {
        return null;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );

    try {
        $connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        log_message('Database connection failed: ' . $exception->getMessage());
    }

    return $connection;
}
