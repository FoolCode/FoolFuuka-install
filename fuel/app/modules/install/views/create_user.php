<p><?= __('The connection to the database was successful. Now, go ahead to create an administrator account.') ?></p>

<div style="text-align:center">

	<?= \Form::open(); ?>

	<label><?= __('Username') ?></label>
	<?= \Form::input(array(
		'name' => 'username'
	)); ?>

	<label><?= __('Email') ?></label>
	<?= \Form::input(array(
		'name' => 'email'
	)); ?>

	<label><?= __('Password') ?></label>
	<?= \Form::password(array(
		'name' => 'password'
	)); ?>

	<label><?= __('Confirm Password') ?></label>
	<?= \Form::password(array(
		'name' => 'confirm_password'
	)); ?>

	<br/><br/>

	<?= \Form::submit(array(
		'name' => 'submit',
		'value' => __('Create account'),
		'class' => 'btn btn-success btn-large pull-right',
	)); ?>

	<?= \Form::close() ?>

</div>