<?php $this->layout('basic');?>
<div class="grd-row-col-2-6--md">
	
</div>
<div class="brdr--light-gray grd-row-col-2-6--md p1">
<h3>Login</h3>

<form action="/login" method="POST">
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="<?= $this->e($post_content['email'] ?? '') ?>" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <input type="submit" value="Login" class="btn btn--green">
</form>

        <div class="login-footer">
            <a class="flt--left" href="<?= SITE_URL ?>password-forgot">Forgot Password</a>
            <?php if(ALLOW_SIGNUPS): ?>
                    <a class="flt--right" href="<?= SITE_URL ?>create-account">Create Account</a>

            <?php endif; ?>
        </div>
</div>