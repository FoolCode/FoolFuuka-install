<div class="well">
	<?php
	$login = array(
		'name' => 'username',
		'id' => 'username',
		'value' => Input::post('login'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => __('Required')
	);

	$login_label = __('Login');


	$password = array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
		'placeholder' => __('Required')
	);
	$remember = array(
		'name' => 'remember',
		'id' => 'remember',
		'value' => 1,
		'checked' => Input::post('remember'),
	);

	$captcha = array(
		'name' => 'captcha',
		'id' => 'captcha',
		'maxlength' => 8,
	);
	
	?>
	<?php echo Form::open(); ?>
	<?php echo isset($login_error) ? $login_error : '' ?>

	<label><?php
	echo Form::label($login_label, $login['id']);
	?></label>
	<?php echo Form::input($login); ?>
	<span class="help-inline" style="color: red;">
		<?php
		echo isset($errors[$login['name']]) ? $errors[$login['name']] : '';
		?>
	</span>

	<label><?php echo Form::label('Password', $password['id']);
		?></label>
	<?php echo Form::password($password); ?>
	<span class="help-inline" style="color: red;">
		<?php
		echo isset($errors[$password['name']]) ? $errors[$password['name']] : '';
		?>
	</span>


<label class="checkbox">
	<?php echo Form::checkbox($remember); ?>
	<?php
	echo Form::label(__('Remember me'), $remember['id']);
	?>
</label>

<?php echo Form::submit(array('name' => 'submit', 'value' => __('Login'), 'class' => 'btn btn-primary')); ?>

<input type="button" onClick="window.location.href='<?php echo URI::create('/admin/auth/forgot_password/') ?>'" class="btn" value="<?php echo htmlspecialchars(__("Forgot password")) ?>" />
<?php if (!\Preferences::get('ff.reg_disabled')) : ?><input type="button" onClick="window.location.href='<?php echo URI::create('/admin/auth/register/') ?>'" class="btn" value="<?php echo htmlspecialchars(__("Register")) ?>" /><?php endif; ?>

<?php echo Form::close(); ?>


</div>