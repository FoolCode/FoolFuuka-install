<div class="well">
	<?= Form::open(); ?>

	<label><?= Form::label(__('Login'), 'username'); ?></label>
	<?= Form::input(array(
		'name' => 'username',
		'id' => 'username',
		'value' => Input::post('login'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => __('Required')
	)); ?>

	<label><?= Form::label(__('Password'), 'password'); ?></label>
	<?= Form::password(array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
		'placeholder' => __('Required'))); ?>


	<label class="checkbox">
	<?= Form::checkbox(array(
		'name' => 'remember',
		'id' => 'remember',
		'value' => 1,
		'checked' => Input::post('remember'),
	)); ?>
	<?= Form::label(__('Remember me'), 'remember'); ?>
	</label>

	<?= Form::submit(array('name' => 'submit', 'value' => __('Login'), 'class' => 'btn btn-primary')); ?>

	<input type="button" onClick="window.location.href='<?php echo URI::create('/admin/auth/forgot_password/') ?>'" class="btn" value="<?php echo htmlspecialchars(__("Forgot password")) ?>" />
	<?php if (!\Preferences::get('ff.reg_disabled')) : ?><input type="button" onClick="window.location.href='<?= URI::create('/admin/auth/register/') ?>'" class="btn" value="<?= htmlspecialchars(__("Register")) ?>" /><?php endif; ?>

	<?= Form::close(); ?>


</div>