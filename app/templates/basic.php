<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?=$this->e($page_title ?? SITE_NAME)?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <nav class="border-b border-gray-200">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a class="text-lg font-semibold" href="<?= SITE_URL ?>"><?= $this->e(SITE_NAME) ?></a>
            <div class="flex items-center gap-4">
                <?php if($is_logged_in): ?>
                    <a href="<?= SITE_URL ?>logged-in-page">Dashboard</a>
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

    <footer class="mx-auto max-w-5xl px-4 py-8 text-center text-sm text-gray-500">
        <a class="hover:text-accent" target="_blank" href="http://citracode.com">Powered by Citracode</a>
    </footer>

    <script src="/js/mithril.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
