<?php $this->layout('app::basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-4 text-xl font-semibold">Log in</h1>
    <form action="/login" method="POST" class="space-y-3">
        <?= $this->csrf_field() ?>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input class="field" type="email" id="email" name="email" value="<?= $this->e($post_content['email'] ?? '') ?>" required>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input class="field" type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Log in</button>
    </form>
    <div class="mt-4 flex justify-between text-sm">
        <a class="text-accent hover:underline" href="<?= SITE_URL ?>password-forgot">Forgot password?</a>
        <?php if(ALLOW_SIGNUPS): ?>
            <a class="text-accent hover:underline" href="<?= SITE_URL ?>create-account">Create account</a>
        <?php endif; ?>
    </div>
</div>
