<?php
    declare(strict_types=1);

    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/function.php';

    date_default_timezone_set('Asia/Jakarta');

    //Inisialisasi nilai bawaan
    $languages          = getLanguages();
    $visibilityOptions  = getVisibilityOptions();
    $errors             = [];

    $title              = '';
    $language           = 'php';
    $visibility         = 'public_internal';
    $reviewDate         = date('Y-m-d');
    $code               = '';
    $notes              = '';

    $hljsLangMap = [
        'php'           => 'php',
        'cpp'           => 'cpp',
        'sql'           => 'sql',
        'javascript'    => 'javascript',
        'bash'          => 'bash',
        'html_css'      => 'xml',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $maxUploadBytes = 12 * 1024 * 1024;
        $contentLength  = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes   = parseIniBytes((string) ini_get('post_max_size'));
        $serverLimit    = $postMaxBytes > 0 ? $postMaxBytes : $maxUploadBytes;

        //Proses validasi
        $title      = trim((string) ($_POST['title'] ?? ''));
        $language   = (string) ($_POST['language'] ?? 'php');
        $visibility = (string) ($_POST['visibility'] ?? 'public_internal');
        $rawDate    = trim((string) ($_POST['review_date'] ?? ''));
        $code       = (string) ($_POST['code'] ?? '');
        $notes      = trim((string) ($_POST['notes'] ?? ''));

        $parsedDate = null;
        foreach (['m-d-Y', 'm/d/Y', 'Y-m-d'] as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $fmt, $rawDate);
            if ($dt !== false && $dt->format($fmt) === $rawDate) {
                $parsedDate = $dt;
                break;
            }
        }

        $reviewDate = $parsedDate ? $parsedDate->format('Y-m-d') : date('Y-m-d');

        if ($contentLength > $maxUploadBytes || (empty($_POST) && empty($_FILES) &&
        $contentLength > $serverLimit)) {
            $errors[] = 'Ukuran file/request melebihi batas maksimal 12 MB. Data tidak disimpan.';
        }
        
        if ($title === '') {
            $errors[] = 'Judul snippet wajib diisi.';
        }

        if (!array_key_exists($language, $languages)) {
            $errors[] = 'Bahasa tidak valid.';
        }

        if (!array_key_exists($visibility, $visibilityOptions)) {
            $errors[] = 'Pilihan visibilitas tidak valid.';
        }

        if ($reviewDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reviewDate)) {
            $errors[] = 'Tanggal review harus valid.';
        }

        if (trim($code) === '') {
            $errors[] = 'Kode snippet wajib diisi.';
        }

        //Panggil untuk memproses file upload
        $attachmentPath = null;
        if (empty($errors)) {
            $attachmentPath = handleSnippetUpload($_FILES['attachment'] ?? [], null, $errors, false);
        }

        if (empty($errors)) {
            //Menyiapkan query
            $stmt = $conf->prepare(
                'INSERT INTO snippets (user_id, title, language, visibility, review_date, code, notes, attachment_path)
                VALUES (:user_id, :title, :language, :visibility, :review_date, :code, :notes, :attachment_path)'
            );
            //Menjalankan query
            $stmt->execute([
                //user_id diambil dari $_SESSION['user_id']
                ':user_id'          => (int) $_SESSION['user_id'],
                ':title'            => $title,
                ':language'         => $language,
                ':visibility'       => $visibility,
                ':review_date'      => $reviewDate,
                ':code'             => $code,
                ':notes'            => $notes !== '' ? $notes : null,
                ':attachment_path'  => $attachmentPath,
            ]);

            header('Location: dashboard.php?msg=created');
            exit;
        }
    }

    $initialHljsClass = $hljsLangMap[$language] ?? 'plaintext';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Snippet - Snippet Manager</title>
    <link rel="stylesheet" href="assets/atom-one-dark.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="wrap">
        <div class="card form-card">
            <div class="top">
                <h2>Tambah Snippet Baru</h2>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="err-box">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?=  e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST" enctype="multipart/form-data">
                <div class="field">
                    <label for="title" class="lbl">Judul Snippet</label>
                    <input type="text" id="title" name="title" value="<?=  e($title) ?>" required>
                </div>

                <div class="field">
                    <label for="language" class="lbl">Bahasa / Topik</label>
                    <select name="language" id="language">
                        <?php foreach ($languages as $key => $label): ?>
                            <option value="<?=  e($key) ?>" <?=  $language === $key ? 'selected' : '' ?>>
                                <?=  e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <span class="lbl">Visibilitas</span>
                    <div class="radio-row">
                        <?php foreach ($visibilityOptions as $val => $text): ?>
                            <label>
                                <input type="radio" name="visibility" value="<?=  e($val) ?>" 
                                <?=  $visibility === $val ? 'checked' : '' ?>>
                                <?=  e($text) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field">
                    <label for="review_date" class="lbl">Tanggal Review</label>
                    <input type="date" id="review_date" name="review_date" 
                    value="<?=  e($reviewDate) ?>" required>
                </div>

                <div class="field">
                    <label for="code" class="lbl">Potongan Kode</label>
                    <textarea name="code" id="code" class="code-area" required><?= e($code) ?></textarea>
                </div>

                <div class="field">
                    <div class="code-toolbar" style="justify-content: space-between; align-items:center;">
                        <span class="lbl" style="margin: 0;">Pratinjau highlight</span>
                        <button type="button" class="btn btn-sm btn-copy" 
                        data-target="liveCodePreview">Copy</button>
                    </div>
                    <pre><code id="liveCodePreview" class="language-<?= e($initialHljsClass) ?>">
                        <?= e($code) ?>
                    </code></pre>
                </div>

                <div class="field">
                    <label for="notes" class="lbl">Catatan &amp; Penjelasan</label>
                    <textarea name="notes" id="notes" class="notes-area"><?=  e($notes) ?></textarea>
                </div>

                <div class="field">
                    <label for="attachment" class="lbl">Lampiran File / Gambar (Maks. 12MB)</label>
                    <input type="file" id="attachment" name="attachment">
                    <div class="file-meta file-selected-info"></div>
                </div>

                <button class="btn btn-primary" type="submit">Simpan Snippet</button>
                <a href="dashboard.php" class="btn btn-muted">Kembali</a>
            </form>
        </div>
    </div>
    <script src="assets/highlight.min.js"></script>
    <script src="assets/app.js" defer></script>
</body>
</html>