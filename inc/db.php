<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    global $CFG;
    if ($pdo === null) {
        $dsn = "mysql:host={$CFG['db_host']};dbname={$CFG['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $CFG['db_user'], $CFG['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+03:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function val(string $sql, array $params = []): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** "IN (?,?,?)" için yer tutucu üretir. */
function in_ph(array $items): string
{
    return implode(',', array_fill(0, max(1, count($items)), '?'));
}
