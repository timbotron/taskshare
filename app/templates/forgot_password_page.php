<?php $this->layout('basic');?>
<div class="grd-row-col-2-6--md">
	
</div>
<div class="brdr--light-gray grd-row-col-2-6--md p1">
<h3>Forgot password?</h3>

<p>Enter your email address. If there is an email that matches in our system we will send you a reset link.</p>

<form action="/password-forgot" method="POST">
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="<?= $this->e($post_content['email'] ?? '') ?>" required>
        <input type="submit" value="Forgot Password" class="btn btn--green">
</form>
</div>