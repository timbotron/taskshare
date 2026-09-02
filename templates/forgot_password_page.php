<?php $this->layout('app::basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-2 text-xl font-semibold">Forgot password?</h1>
    <p class="mb-4 text-sm text-muted">Enter your email. If it matches an account, we'll send a reset link.</p>
    <form action="/password-forgot" method="POST" class="space-y-3">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input class="field" type="email" id="email" name="email" value="<?= $this->e($post_content['email'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn">Send reset link</button>
    </form>
    <div class="mt-4 text-sm">
        <a class="text-accent hover:underline" href="<?= SITE_URL ?>login">Back to login</a>
    </div>
</div>
