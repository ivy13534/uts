<?php
    declare(strict_types=1);

    // Memastikan e() ada
    if (!function_exists('e')) {
        require_once __DIR__ . '/function.php';
    }

    // Menentukan judul tab
    $pageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
        ? $pageTitle
        : 'Snippet Manager';

    // Menambahkan attribut class
    $bodyAttr = isset($bodyClass) && is_string($bodyClass) && $bodyClass !== ''
        ? ' class="' . e($bodyClass) . '"'
        : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Muat tema -->
    <link rel="stylesheet" href="assets/atom-one-dark.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body<?= $bodyAttr ?>>