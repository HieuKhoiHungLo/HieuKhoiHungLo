<?php
// config/db.php

// MẪU KẾT NỐI CSDL SUPABASE

// MẪU KẾT NỐI CSDL SUPABASE
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$db   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'test';
$user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root'; 
$pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;options='--search_path=public'";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, // Required for Supabase PgBouncer (Port 6543)
    PDO::ATTR_PERSISTENT         => true, // PHP level Keep-Alive to avoid TLS Handshake hell
    PDO::ATTR_STRINGIFY_FETCHES  => false, // Don't cast numeric columns to string
];




return [
    'dsn' => $dsn,
    'user' => $user,
    'pass' => $pass,
    'options' => $options
];
?>
