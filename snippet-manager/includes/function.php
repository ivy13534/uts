<?php
    declare(strict_types=1);

    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function parseIniBytes(string $val): int {
        $val = trim($val);
        if ($val ==='') {
            return 0;
        }

        $last = strtolower($val[strlen($val) -1]);
        $num = (float) $val;

        return match ($last) {
            'g'     => (int) ($num * 1024 * 1024 * 1024),
            'm'     => (int) ($num * 1024 * 1024),
            'k'     => (int) ($num * 1024),
            default => (int) $num,
        };
    }

    // Kembalikan array asosiatif daftar bahasa
    function getLanguages(): array {
        return [
            'php'        => 'PHP',
            'cpp'        => 'C++',
            'sql'        => 'SQL / MariaDB',
            'javascript' => 'JavaScript',
            'bash'       => 'Bash / SHell',
            'html_css'   => 'HMTL & CSS',
        ];
    }

    // Kembalikan array asosiatif daftar visibilitas
    function getVisibilityOptions(): array {
        return [
            'public_internal'   => 'Publik / Catatan Umum',
            'private'           => 'Pribadi / Rahasia',
        ];
    }

    // Daftar ekstensi file yang diperbolehkan
    function getAllowedUploadExtensions(): array {
        return ['txt', 'md', 'sql', 'php', 'cpp', 'js', 'png', 'jpg', 'jpeg', 'webp'];
    }

    function handleSnippetUpload(array $file, ?string $oldPath, array &$errors, bool $replaceOld = false): ?string {
        $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($errCode === UPLOAD_ERR_NO_FILE) {
            return $replaceOld ? $oldPath : null;
        }

        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'Ukuran file melebihi batas upload 12 MB.';
            return $replaceOld ? $oldPath : null;
        }

        if ($errCode !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah file';
            return $replaceOld ? $oldPath : null;
        }

        //Cek ukuran file
        $maxBytes = 12 * 1024 * 1024; // 12 MB
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $errors[] = 'Ukuran file maksimal 12 MB.';
            return $replaceOld ? $oldPath : null;
        }

        //Cek ekstension file
        $allowedExt = getAllowedUploadExtensions();
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            $errors[] = 'Ekstensi file tidak diizinkan.';
            return $replaceOld ? $oldPath : null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $errors[] = 'File upload tidak valid.';
            return $replaceOld ? $oldPath : null;
        }

        //Buat folder uploads
        $uploadDir = dirname(__DIR__) . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $errors[] = 'Folder uploads tidak dapat dibuat.';
            return $replaceOld ? $oldPath : null;
        }

        if (!is_writable($uploadDir)) {
            $errors[] ='Folder uploads tidak memiliki izin tulis.';
            return $replaceOld ? $oldPath : null;
        }

        //Buat nama file random
        $newName = 'snip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . '/' . $newName;

        //Memindahkan file
        if (!move_uploaded_file($tmpName, $target)) {
            $errors[] = 'Gagal memindahkan file ke folder uploads.';
            return $replaceOld ? $oldPath : null;
        }

        //Menghapus file lama diedit jika $replaceOld true
        if ($replaceOld && !empty($oldPath)) {
            $oldFull = dirname(__DIR__) . '/' . ltrim((string) $oldPath, '/');
            if (is_file($oldFull)) {
                @unlink($oldFull);
            }
        }

        return 'uploads/' . $newName;
    }
?>