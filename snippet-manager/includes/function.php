<?php
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            //Kembalikan $oldpath ke edit atau null ke create
            return $replaceOld ? $oldPath : null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah file.';
            return $replaceOld ? $oldPath : null;
        }

        //Cek ukuran file
        $maxBytes = 2 * 1024; // 2 MB
        if (($file['size'] ?? 0) > $maxBytes) {
            $errors[] = 'Ukuran file maksimal 2 MB.';
            return $replaceOld ? $oldPath : null;
        }

        //Cek ekstension file
        $allowedExt = getAllowedUploadExtensions();
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Ekstensi file tidak diizinkan.';
            return $replaceOld ? $oldPath : null;
        }

        //Buat folder uploads
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $errors[] = 'Folder uploads tidak dapat dibuat.';
            return $replaceOld ? $oldPath : null;
        }

        //Buat nama file random
        $newName = 'snip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . '/' /$newName;

        //Memindahkan file
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            $errors[] = 'Gagal memindahkan file ke folder uploads.';
            return $replaceOld ? $oldPath : null;
        }

        //Menghapus file lama diedit jika $replaceOld true
        if ($replaceOld && !empty($oldPath)) {
            $oldFull = dirname(__DIR__) . '/' . ltrim($oldPath, '/');
            if (is_file($oldFull)) {
                @unlink($oldFull);
            }
        }

        return 'uploads/' . $newName;
    }
?>