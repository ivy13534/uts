<?php
    declare(strict_types=1);
    
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/function.php';
    require_once __DIR__ . '/config/db.php';

    $languages          = getLanguages();
    $visibilityOptions  = getVisibilityOptions();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);

        if ($deleteId > 0) {
            $getFile = $conf->prepare('SELECT attachment_path FROM snippets WHERE id = :id');
            $getFile->execute([':id' => $deleteId]);
            $rowFile = $getFile->fetch();

            $del = $conf->prepare('DELETE FROM snippets WHERE id = :id');
            $del->execute([':id' => $deleteId]);

            if (!empty($rowFile['attachment_path'])) {
                $fullPath = __DIR__ . '/' . ltrim((string) $rowFile['attachment_path'], '/');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        header('Location: dashboard.php?msg=deleted');
        exit;
    }

    $search     = trim((string) ($_GET['q'] ?? ''));
    $filterVis  = (string) ($_GET['visibility'] ?? '');
    $msg        = (string) ($_GET['msg'] ?? '');

    $notices = [
        'created' => 'Snippet baru berhasil ditambahkan.',
        'updated' => 'Perubahan snippet berhasil disimpan.',
        'deleted' => 'Snippet berhasil dihapus.',
    ];

    $sql = 'SELECT s.*, u.username AS author
            FROM snippets s
            JOIN users u ON s.user_id = u.id
            WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $likeTerm = '%' . $search . '%';
        $sql .= ' AND (s.title LIKE :q_title OR s.code LIKE :q_code OR s.notes LIKE :q_notes)';
        $params[':q_title'] = $likeTerm;
        $params[':q_code']  = $likeTerm;
        $params[':q_notes'] = $likeTerm;
    }

    if (array_key_exists($filterVis, $visibilityOptions)) {
        $sql .= ' AND s.visibility = :visibility';
        $params[':visibility'] = $filterVis;
    }

    $sql .= ' ORDER BY s.created_at DESC';

    $stmt = $conf->prepare($sql);
    $stmt->execute($params);
    $snippets = $stmt->fetchAll();

    $counts = [
        'total'             => count($snippets),
        'public_internal'   => 0,
        'private'           => 0,
    ];

    foreach ($snippets as $item) {
        $visKey = (string) $item['visibility'];
        if (isset($counts[$visKey])) {
            $counts[$visKey]++;
        }
    }

    $hljsLangMap = [
        'php'           => 'php',
        'cpp'           => 'cpp',
        'sql'           => 'sql',
        'javascript'    => 'javascript',
        'bash'          => 'bash',
        'html_css'      => 'xml',
    ];

    $pageTitle = 'Dashboard - Snippet Manager';
    require_once __DIR__ . '/includes/header.php';
?>

    <div class="top">
        <div>
            <h1>Dashboard Snippet Manager</h1>
            <small class="muted-text">
                Login sebagai: <strong><?= e($_SESSION['username']) ?></strong> • DB :
                <code>snippet_manager_3716</code>
            </small>
        </div>
        <div class="nav">
            <a href="create.php" class="btn btn-primary">+ Tambah Snippet</a>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>
    </div>

    <?php if (isset($notices[$msg])) : ?>
        <div class="ok-box"><?=  e($notices[$msg]) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat">Total Data: <strong><?= (int) $counts['total'] ?></strong></div>
        <div class="stat">Publik / Umum : <strong><?= (int) $counts['public_internal'] ?></strong></div>
        <div class="stat">Pribadi: <strong><?= (int) $counts['private'] ?></strong></div>
    </div>

    <form action="dashboard.php" method="GET" class="filter">
        <input type="text" name="q" placeholder="Cari di database (judul, kode, catatan)..." value="<?=  e($search) ?>">
        <select name="visibility">
            <option value="">-- Semua Visibilitas --</option>
            <?php foreach($visibilityOptions as $val => $text): ?>
                <option value="<?= e($val) ?>" <?= $filterVis === $val ? 'selected' : "" ?>>
                    <?= e($text) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Cari</button>
        <a href="dashboard.php" class="btn btn-muted">Reset</a>
    </form>

    <div class="field">
        <input type="text" id="quickFilter" placeholder="Filter cepat baris tabel yang tampil di layar...">
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul &amp; Kode</th>
                    <th>Bahasa</th>
                    <th>Visibility</th>
                    <th>Tgl Review</th>
                    <th>Lampiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snippets)): ?>
                    <tr>
                        <td colspan="7">Belum ada data snippet yang sesuai.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($snippets as $idx => $row): ?>
                        <?php
                        $rowId = (int) $row['id'];
                        $rawLang = (string) $row['language'];
                        $hljsClass = $hljsLangMap[$rawLang] ?? 'plaintext';
                        $searchBlob = strtolower(
                            (string) $row['title'] . ' ' .
                            (string) ($row['notes'] ?? '') . ' ' .
                            (string) $row['code'] . ' ' .
                            $rawLang
                        );
                        ?>

                        <tr data-search="<?= e($searchBlob) ?>">
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= e($row['title']) ?></strong>
                                <?php if (!empty($row['notes'])): ?>
                                    <div class="snippet-note"><?= e($row['notes']) ?></div>
                                <?php endif; ?>
                                <div class="code-toolbar">
                                    <button type="button" class="btn btn-sm btn-copy" 
                                    data-target="code-<?= $rowId ?>">Copy</button>
                                    <button type="button" class="btn btn-sm btn-toggle-code"
                                    data-target="code-<?= $rowId ?>">Perluas</button>
                                </div>
                                <pre><code id="code-<?= $rowId ?>" class="language-<?= 
                                e($hljsClass) ?>"><?= e($row['code']) ?></code></pre>
                            </td>
                            <td><?= e($languages[$rawLang] ?? $rawLang) ?></td>
                            <td><?= e($visibilityOptions[$row['visibility']] ?? $row['visibility']) ?></td>
                            <td><?= e($row['review_date']) ?></td>
                            <td>
                                <?php if (!empty($row['attachment_path'])): ?>
                                    <a href="<?= e($row['attachment_path']) ?>" class="link-accent"
                                    target="_blank" rel="noopener">Lihat File</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= $rowId ?>" class="btn btn-edit">Edit</a>
                                    <form action="dashboard.php" method="POST" onsubmit="return 
                                    confirm('Hapus snippet ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $rowId ?>">
                                        <button class="btn btn-del" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>