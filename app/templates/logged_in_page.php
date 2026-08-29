<?php $this->layout('basic');?>
<div class="grd-row-col-2-6--md">
	
</div>
<div class="brdr--light-gray grd-row-col-2-6--md p1">
<h3>You are logged in.</h3>

<p>This is a simple page letting you know you are logged in successfully. You'll probably want to remove this in your project.</p>

<p>Here is some user info:</p>

<ul>
    <li>User ID: <?=$this->e($user['user_id'])?></li>
    <li>Email: <?=$this->e($user['email'])?></li>
</ul>

</div>