<!DOCTYPE html>
<html lang="en"<?php if($is_logged_in): ?> data-theme="<?= $this->e($user_theme ?? 'light') ?>" data-auth="1"<?php elseif(isset($owner_theme)): ?> data-owner-theme="<?= $this->e($owner_theme) ?>"<?php endif; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <title><?=$this->e($page_title ?? SITE_NAME)?></title>
    <link rel="stylesheet" href="/css/app.css">
    <script>
      // Resolve the anonymous viewer's theme before first paint (no flash).
      // Precedence: their saved choice (localStorage) > the board owner's default.
      // Logged-in users keep the server-rendered data-theme; with neither set, the
      // stylesheet's prefers-color-scheme fallback applies.
      (function () {
        var el = document.documentElement
        if (el.getAttribute('data-auth')) return
        var saved = null
        try { saved = localStorage.getItem('ts-theme') } catch (e) {}
        var theme = saved || el.getAttribute('data-owner-theme')
        if (theme) el.setAttribute('data-theme', theme)
      })()
    </script>
</head>
<body class="min-h-screen bg-app text-fg antialiased">
    <nav class="border-b border-line">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a class="text-lg font-semibold" href="<?= SITE_URL ?>"><?= $this->e(SITE_NAME) ?></a>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="options-btn" title="Toggle light / dark" aria-label="Toggle theme">◐</button>
                <?php if($is_logged_in): ?>
                    <a href="<?= SITE_URL ?>dashboard">Dashboard</a>
                    <a href="<?= SITE_URL ?>logout">Logout</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if(isset($messages) && is_array($messages) && count($messages) > 0): ?>
    <div class="mx-auto mt-4 max-w-5xl space-y-2 px-4">
        <?php foreach($messages as $m): ?>
            <div class="rounded px-3 py-2 text-sm <?= $m['type'] == 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                <?= $this->e($m['value']) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <main class="mx-auto max-w-5xl px-4 py-6">
        <?=$this->section('content')?>
    </main>

    <footer class="mx-auto max-w-5xl px-4 py-8 text-center text-sm text-muted">
        <a class="hover:text-accent" target="_blank" href="http://citracode.com">Powered by Citracode</a>
    </footer>

    <script src="/js/mithril.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
