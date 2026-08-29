<?php $this->layout('basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-3 text-xl font-semibold"><?= $this->e($top_title) ?></h1>
    <div class="text-sm text-gray-700"><?= $page_message ?></div>
    <?php if($is_error): ?>
        <p class="mt-3 text-sm text-gray-600">Please contact support if desired.</p>
        <a href="mailto:<?= $this->e(EMAIL_SUPPORT_ADDRESS) ?>" class="btn mt-3">Email support</a>
    <?php endif; ?>
</div>
