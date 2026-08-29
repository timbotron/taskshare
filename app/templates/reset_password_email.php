<h2 style="font-family: Arial, Helvetica, sans-serif;"><?= $this->e($page_title) ?></h2>

<p style="font-family: Arial, Helvetica, sans-serif;">Hello — you requested to <?= $reset_type == 'new' ? 'create an account' : 'reset your password' ?>. Use the link below to <?= $reset_type == 'new' ? 'set' : 'reset' ?> your password.</p>

<p style="font-family: Arial, Helvetica, sans-serif;"><a href="<?= $this->e($reset_link) ?>"><?= $this->e($reset_link) ?></a></p>

<p style="font-family: Arial, Helvetica, sans-serif;">If you did not request this, please disregard.</p>

<p style="font-family: Arial, Helvetica, sans-serif;">— The <?= $this->e($page_title) ?> team</p>
