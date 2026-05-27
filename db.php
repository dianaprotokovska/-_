<?php
$host = '127.0.0.1';
$dbname = 'mydb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:unix_socket=/tmp/mysql.sock;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Помилка підключення: " . $e->getMessage());
}
