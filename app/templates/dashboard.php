<?php $this->layout('basic'); ?>
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Your boards</h1>
        <span class="text-sm text-gray-500"><?= count($boards) ?> / <?= $this->e($max_boards) ?></span>
    </div>

    <?php if(empty($boards)): ?>
        <p class="mb-6 text-gray-600">No boards yet. Create your first one below.</p>
    <?php else: ?>
        <ul class="mb-6 space-y-3">
            <?php foreach($boards as $b): ?>
                <li class="list-card flex flex-wrap items-center justify-between gap-3">
                    <a class="font-medium text-accent hover:underline" href="/b/<?= $this->e($b['slug']) ?>"><?= $this->e($b['title']) ?></a>
                    <div class="flex items-center gap-2">
                        <form action="/boards/<?= $this->e($b['id']) ?>/rename" method="POST" class="flex items-center gap-1">
                            <input class="w-40 rounded border border-gray-300 px-2 py-1 text-sm" type="text" name="title" value="<?= $this->e($b['title']) ?>" required>
                            <button class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50" type="submit">Rename</button>
                        </form>
                        <form action="/boards/<?= $this->e($b['id']) ?>/delete" method="POST" onsubmit="return confirm('Delete this board and all its lists?');">
                            <button class="rounded px-3 py-1 text-sm text-red-700 hover:bg-red-50" type="submit">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if($at_cap): ?>
        <div class="list-card text-sm text-gray-600">You've reached the maximum of <?= $this->e($max_boards) ?> boards. Delete one to create another.</div>
    <?php else: ?>
        <form action="/boards" method="POST" class="list-card flex items-end gap-2">
            <div class="flex-1">
                <label for="title" class="mb-1 block text-sm font-medium">New board</label>
                <input class="field" type="text" id="title" name="title" placeholder="Board title" required>
            </div>
            <button class="btn" type="submit">Create board</button>
        </form>
    <?php endif; ?>
</div>
