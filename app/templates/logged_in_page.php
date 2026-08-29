<?php $this->layout('basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-2 text-xl font-semibold">You're logged in</h1>
    <p class="mb-4 text-sm text-gray-600">Your boards dashboard will live here.</p>
    <ul class="text-sm text-gray-700">
        <li>User ID: <?= $this->e($user['user_id']) ?></li>
        <li>Email: <?= $this->e($user['email']) ?></li>
    </ul>
</div>
