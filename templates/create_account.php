<?php $this->layout('app::basic'); ?>
<div class="list-card mx-auto max-w-md">
    <h1 class="mb-2 text-xl font-semibold">Create your account</h1>
    <p class="mb-4 text-sm text-muted">Enter your email and we'll send a link to set your password.</p>
    <form action="/create-account" method="POST" class="space-y-3">
        <?= $this->csrf_field() ?>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input class="field" type="email" id="email" name="email" value="<?= $this->e($post_content['email'] ?? '') ?>" required>
        </div>
        <div>
            <label for="email2" class="mb-1 block text-sm font-medium">Repeat email</label>
            <input class="field" type="email" id="email2" name="email2" value="<?= $this->e($post_content['email2'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn">Create account</button>
    </form>
    <div class="mt-4 text-sm">
        <a class="text-accent hover:underline" href="<?= SITE_URL ?>login">Back to login</a>
    </div>
</div>
