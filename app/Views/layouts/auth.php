<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'ContactCore Login', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(Auth::url('/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/base.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="auth-body">
    <main class="auth-page">
        <div class="auth-box">
            <?= $content ?>
        </div>
    </main>
</body>
</html>
