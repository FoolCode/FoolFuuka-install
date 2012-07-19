<div class="well">

	<?= Form::open() ?>

	<label><?php echo Form::label(__('Username'), 'username'); ?></label>
	<?php echo Form::input(array(
		'name' => 'username',
		'id' => 'username',
		'value' => Input::post('username'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	)); ?>

	<label><?php echo Form::label(__('Email Address'), 'email'); ?></label>
	<?php echo Form::input(array(
		'name' => 'email',
		'id' => 'email',
		'value' => Input::post('email'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => __('required')
	)); ?>


	<label><?php echo Form::label(__('Password'), 'password'); ?></label>
	<?php echo Form::password(array(
		'name' => 'password',
		'id' => 'password',
		'value' => Input::post('password'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	)); ?>


	<label><?php echo Form::label(__('Confirm Password'), 'confirm_password'); ?></label>
	<?php echo Form::password(array(
		'name' => 'confirm_password',
		'id' => 'confirm_password',
		'value' => Input::post('confirm_password'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	)); ?>


	<br/><br/>

	<?php echo Form::submit(array(
		'name' => 'register',
		'value' => __('Register'),
		'class' => 'btn btn-primary'
	)); ?>


	<input type="button" onClick="window.location.href='<?php echo URI::create('/admin/auth/login/') ?>'" class="btn" value="<?php echo htmlspecialchars(__("Back to login")) ?>" />

	<?php echo Form::close(); ?>


</div>