<?php
    declare(strict_types=1);

    session_start();
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/function.php';

    //Jika sudah login, membuka login.php maka diarahkan ke dashboard.php
    if (!empty($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }

    $error = '';
    $usernameInput = '';

    function loginUser(PDO $conf, string $username, string $password): ?array {
        //Prepare query login
        $stmt = $conf->prepare(
            'SELECT id, username, password_hash, full_name FROM users WHERE username = :username LIMIT 1'
        );
        //Eksekusi
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        //Cek password hash
        $validHash  = password_verify($password, (string) $user['password_hash']);
        //Cek password string
        $validPlain = hash_equals((string) $user['password_hash'], $password);
        return ($validHash || $validPlain) ? $user : null;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        //Ambil username dan password dari $_POST
        $usernameInput = trim((string) ($_POST['username'] ?? ''));
        $passwordInput = (string) ($_POST['password'] ?? '');

        if ($usernameInput === '' || $passwordInput === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            //Panggil fungsi cek
            $user = loginUser($conf, $usernameInput, $passwordInput);

            if ($user !== null) {
                //Buat session id baru jika berhasil
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int) $user['id'];
                $_SESSION['username']   = (string) $user['username'];
                $_SESSION['full_name']  = (string) $user['full_name'];

                header('Location: dashboard.php');
                exit;
            }

            $error = 'Username atau password salah.';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Snippet Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <h2>Login Snippet Manager</h2>
            <?php if ($error !== ''): ?>
                <div class="err-box"><?=  e($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                    value="<?=  e($usernameInput) ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="passowrd" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Masuk</button>
            </form>
        </div>
    </div>
    <script src="assets/app.js" defer></script>
</body>
</html>