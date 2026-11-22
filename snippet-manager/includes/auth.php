<?php
    declare(strict_types=1);

    //Mulai session PHP jika belum aktif
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    //Belum login dikirim ke login.php
    if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
?>