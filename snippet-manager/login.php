<?php
    declare(strict_types=1);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(); // Mulai session
    }

    require_once __DIR__ . '/config/db.php'; // Sambung ke koneksi PDO
    require_once __DIR__ . '/includes/function.php';

    // Jika sudah login, membuka login.php maka diarahkan ke dashboard.php
    if (!empty($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }

    $error     = '';
    $username  = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Ambil username dan password dari $_POST
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            // Query user dengan prepared statement
            $stmt = $conf->prepare(
                'SELECT id, username, password_hash, full_name
                FROM users
                WHERE username = :username
                LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            $isValidPassword = false;
            if ($user) {
                $hash = (string) $user['password_hash'];
                if (password_verify($password, $hash)) { // Cocokan password
                    $isValidPassword = true;
                } elseif ($password === $hash) { // fallback jika password plaintext
                    $isValidPassword = true;
                }
            }

            if ($user && $isValidPassword) {
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

    $pageTitle  = 'Login - Snippet Manager';
    $bodyClass  = 'login-page';
    require_once __DIR__ . '/includes/header.php';
?>

    <main class="login-center">
        <div class="card login-card">
            <h2>Login Snippet Manager</h2>
            <p class="muted-text">Masuk untuk mengelola dokumentasi kode 
                (<code>snippet_manager_3716</code>)
            </p>

            <?php if ($error !== ''): ?>
                <div class="err-box"><?=  e($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                    value="<?=  e($username) ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="passowrd" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full" 
                style="width: 100%">Masuk</button>
            </form>
        </div>
    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>