<?php
// Script: create_talent_test_tables.php
// Run via: php scripts/create_talent_test_tables.php

require_once __DIR__ . '/../app/Core/Database.php';

// Load .env manually for CLI
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(sprintf('%s=%s', trim($name), trim($value)));
    }
}

$db = \App\Core\Database::getInstance()->getConnection();

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS talent_test_sessions (
    id SERIAL PRIMARY KEY,
    year INT NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS talent_test_subjects (
    id SERIAL PRIMARY KEY,
    session_id INT NOT NULL,
    major_code VARCHAR(10) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    max_score INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_session FOREIGN KEY (session_id) REFERENCES talent_test_sessions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS talent_test_rooms (
    id SERIAL PRIMARY KEY,
    session_id INT NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_session_room FOREIGN KEY (session_id) REFERENCES talent_test_sessions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS talent_test_assignments (
    id SERIAL PRIMARY KEY,
    candidate_id INT NOT NULL,
    subject_id INT NOT NULL,
    room_id INT NOT NULL,
    exam_number VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'not_taken')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subject FOREIGN KEY (subject_id) REFERENCES talent_test_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_room FOREIGN KEY (room_id) REFERENCES talent_test_rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS talent_test_scores (
    id SERIAL PRIMARY KEY,
    assignment_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignment FOREIGN KEY (assignment_id) REFERENCES talent_test_assignments(id) ON DELETE CASCADE
);
SQL;

try {
    $db->exec($sql);
    echo "Talent test tables created successfully.\n";
} catch (\PDOException $e) {
    echo "Error creating talent test tables: " . $e->getMessage() . "\n";
    exit(1);
}
?>
