<?php

$runtimeDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache'
];

foreach ($runtimeDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Ensure SQLite database exists in writable /tmp
$tmpDb = '/tmp/database.sqlite';
if (!file_exists($tmpDb) || filesize($tmpDb) === 0) {
    $seededDb = __DIR__ . '/../backend/database/database.sqlite.seed';
    if (!file_exists($seededDb)) {
        $seededDb = __DIR__ . '/../backend/database/database.sqlite';
    }
    if (file_exists($seededDb)) {
        copy($seededDb, $tmpDb);
    } else {
        touch($tmpDb);
    }
}

require __DIR__ . '/../backend/public/index.php';
