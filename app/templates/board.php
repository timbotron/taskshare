<?php $this->layout('basic'); ?>
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold"><?= $this->e($board['title']) ?></h1>
        <?php if($is_owner): ?>
            <span class="text-sm text-gray-500">You own this board</span>
        <?php endif; ?>
    </div>
    <div id="board-app"></div>
</div>
<script>window.__TASKSHARE__ = <?= $state_json ?>;</script>
