<?php 
    declare(strict_types=1);

    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/function.php';

    date_default_timezone_set('Asia/Jakarta');
    
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Location: dashboard.php');
        exit;
    }

    $stmt = $conf->prepare('SELECT * FROM snippets WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $snippet = $stmt->fetch();

    $languages          = getLanguages();
    $visibilityOptions  = getVisibilityOptions();
    $errors             = [];

    $title          = (string) $snippet['title'];
    $language       = (string) $snippet['language'];
    $visibility     = (string) $snippet['visibility'];
    $reviewDate     = (string) $snippet['review_date'];
    $code           = (string) $snippet['code'];
    $notes          = (string) ($snippet['notes'] ?? "");
    $currentAttach  = $snippet['attachment_path'];

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

        $newAttach = $currentAttach;
        if (empty($errors)) {
            $newAttach = handleSnippetUpload($_FILES['attachment'] ?? [], $currentAttach,
            $errors, true);
        }

        if (empty($errors)) {
            $upd = $conf->prepare(
                'UPDATE snippets
                SET title = :title,
                    language = :language,
                    visibility = :visibility,
                    review_date = :review_date,
                    code = :code,
                    notes = :notes,
                    attachment_path = :attachment_path
                WHERE id = :id'
            );
            $upd->execute([
                ':title'            => $title,
                ':language'         => $language,
                ':visibility'       => $visibility,
                ':review_date'      => $reviewDate,
                ':code'             => $code,
                ':notes'            => $notes !== '' ? $notes : null,
                ':attachment_path'  => $newAttach,
                ':id'               => $id,
            ]);

            header('Location: dashboard.php?msg=updated');
            exit;
        }
    }

    $initialHljsClass = $hljsLangMap[$language] ?? 'plaintext';
    $pageTitle        = 'Edit Snippet - Snippet Manager';
    require_once __DIR__ . '/includes/header.php';
?>

    <div class="wrap">
        <div class="card form-card">
            <div class="top">
                <h2>Edit Snippet #<?= $id ?></h2>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="err-box">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?= $id ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="field">
                    <label for="title" class="lbl">Judul Snippet</label>
                    <input type="text" id="title" name="title" value="<?= e($title) ?>" required>
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
                    <label for="attachment" class="lbl">Ganti Lampiran File / Gambar (Opsional, Maks. 12MB)</label>
                    <?php if (!empty($currentAttach)): ?>
                        <div class="current-file">
                            File saat ini:
                            <a href="<?= e($currentAttach) ?>" class="link-accent"
                            target="_blank" rel="noopener"><?= e($currentAttach) ?></a>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="attachment" name="attachment">
                    <div class="file-meta file-selected-info"></div>
                </div>

                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn btn-muted">Batal</a>
            </form>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>