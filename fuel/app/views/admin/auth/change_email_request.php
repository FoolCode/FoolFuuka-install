<div class="well">

	<p><?= __('Enter your current password and the new email. You will receive an email to confirm the change.') ?></p>

	<?= Form::open(); ?>

	<label><?= Form::label(__('Password'), 'password'); ?></label>
	<?= Form::password(array(
		'name' => 'password',
		'id' => 'password',
		'value' => Input::post('password'),
		'placeholder' => __('Required')
	)); ?>

	<label><?= Form::label(__('Email'), 'email'); ?></label>
	<?= Form::input(array(
		'name' => 'email',
		'id' => 'email',
		'value' => Input::post('email'),
		'placeholder' => __('Required')
	)); ?>

	<br/>

	<?= Form::submit(array('name' => 'submit', 'value' => __('Submit'), 'class' => 'btn btn-primary')); ?>

	<?= Form::close(); ?>

</div>