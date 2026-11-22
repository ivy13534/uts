<?php
    declare(strict_types=1);

    //Mulai session jika belum aktif lalu kosongkan session
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];

    //Timpa cookie session dengan waktu kadaluwarsa
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    //Hapus data session diserver lalu pindah ke login.php
    session_destroy();

    header('Location: login.php');
    exit;
?>