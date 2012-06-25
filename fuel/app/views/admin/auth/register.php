<div class="well">

	<?php
	$username = array(
		'name' => 'username',
		'id' => 'username',
		'value' => Input::post('username'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	);
	$email = array(
		'name' => 'email',
		'id' => 'email',
		'value' => Input::post('email'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => __('required')
	);
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'value' => Input::post('password'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	);
	$confirm_password = array(
		'name' => 'confirm_password',
		'id' => 'confirm_password',
		'value' => Input::post('confirm_password'),
		'maxlength' => 32,
		'size' => 30,
		'placeholder' => __('required')
	);
	$captcha = array(
		'name' => 'captcha',
		'id' => 'captcha',
		'maxlength' => 8,
		'placeholder' => __('required')
	);
	?>

<?= Form::open() ?>

	<label><?php echo Form::label(__('Username'),
	$username['id']); ?></label>
	<?php echo Form::input($username); ?>

	<label><?php echo Form::label(__('Email Address'),$email['id']); ?></label>
	<?php echo Form::input($email); ?>


	<label><?php echo Form::label(__('Password'),
		$password['id']); ?></label>
	<?php echo Form::password($password); ?>


	<label><?php echo Form::label(__('Confirm Password'),
		$confirm_password['id']); ?></label>
	<?php echo Form::password($confirm_password); ?>


<br/><br/>

<?php echo Form::submit(array(
	'name' => 'register',
	'value' => __('Register'),
	'class' => 'btn btn-primary'
	)); ?>


<input type="button" onClick="window.location.href='<?php echo URI::create('/admin/auth/login/') ?>'" class="btn" value="<?php echo htmlspecialchars(__("Back to login")) ?>" />

<?php echo Form::close(); ?>


</div>