<?php $this->layout('app::basic'); ?>
<div class="list-card mx-auto max-w-xl">
    <h1 class="mb-2 text-2xl font-semibold"><?= $this->e(SITE_NAME) ?></h1>
    <p class="mb-4 text-muted">
        This is an instance of TaskShare — the open-source todo app you share by link.
        Log in to create your own boards; anyone you send the link to can view, no account needed.
    </p>
    <p class="text-muted">
        Learn more, or spin up your own instance, at
        <a class="text-accent hover:underline" href="https://www.taskshare.org">www.taskshare.org</a>.
    </p>
</div>
