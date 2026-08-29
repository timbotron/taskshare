<h3><?=$this->e($page_title)?></h3>

<p>Hello, you requested to <?= $reset_type == 'new' ? 'create an account' : 'reset your password'?>. Use the link below to  <?= $reset_type == 'new' ? 'set' : 'reset'?> your password.</p>

<p><a href="<?=$reset_link?>"><?=$reset_link?></a></p>

<p>If you did not request this, please disregard.</p>

<p>- The <?=$this->e($page_title)?> Team</p>