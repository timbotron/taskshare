<?php $this->layout('app::basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-4 text-xl font-semibold">Set a new password</h1>
    <form action="/password-reset/<?= $this->e($uuid) ?>" method="POST" class="space-y-3">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input class="field" type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password2" class="mb-1 block text-sm font-medium">Repeat password</label>
            <input class="field" type="password" id="password2" name="password2" required>
        </div>
        <button type="submit" class="btn">Change password</button>
    </form>
</div>
