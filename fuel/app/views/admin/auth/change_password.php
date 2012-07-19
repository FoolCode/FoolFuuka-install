<div class="well">

	<p><?= __('Insert the new password.') ?></p>

	<?= Form::open(); ?>

	<label><?= Form::label(__('Password'), 'password'); ?></label>
	<?= Form::input(array(
		'name' => 'password',
		'id' => 'password',
		'value' => Input::post('password'),
		'placeholder' => __('Required')
	)); ?>

	<label><?= Form::label(__('Confirm Password'), 'confirm_password'); ?></label>
	<?= Form::input(array(
		'name' => 'confirm_password',
		'id' => 'confirm_password',
		'value' => Input::post('confirm_password'),
		'placeholder' => __('Required')
	)); ?>

	<?= Form::submit(array('name' => 'submit', 'value' => __('Login'), 'class' => 'btn btn-primary')); ?>

	<?= Form::close(); ?>

</div>