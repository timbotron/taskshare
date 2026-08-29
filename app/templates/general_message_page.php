<?php $this->layout('basic');?>
<div class="grd-row-col-2-6--md">
	
</div>
<div class="brdr--light-gray grd-row-col-2-6--md p1">
<h4><?=$this->e($top_title)?></h4>

<?=$page_message; ?>

<?php if($is_error): ?>
	<p>Please contact support if desired.</p>
<?php endif; ?>

<?php if($is_error): ?>
	<a href="mailto:<?= EMAIL_SUPPORT_ADDRESS;?>" class="btn">Email Support</a>
<?php endif; ?>

</div>