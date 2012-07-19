<div class="well">

	<p><?= __('Insert the Email of your account to receive the password reset email.') ?></p>

	<?= Form::open(); ?>

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