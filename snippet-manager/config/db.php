<?php
    //Mencegah konversi tipe nilai balik
    declare(strict_types=1);

    try {
        $conf = new PDO(
            // Koneksi ke database
            dsn: 'mysql:host=localhost;dbname=snippet_manager_3716;charset=utf8mb4',
            username: 'root',
            password: 'ivy13534',
            options: [
                // Error sql sql dilempar ke blok catch
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                // Hasil fetch  dan fetchall jadi associative array
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
                // Prepared statement
                PDO::ATTR_EMULATE_PREPARES      => FALSE,
            ]
        );
    } catch (\Throwable $e) {
        die('Koneksi gagal: '. htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
?>