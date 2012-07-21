<div class="well">

	<p><?= __('Insert the account password. You will receive an email with a link to delete your account.') ?></p>

	<?= Form::open(); ?>

	<label><?= Form::label(__('Password'), 'password'); ?></label>
	<?= Form::password(array(
		'name' => 'password',
		'id' => 'password',
		'value' => Input::post('password'),
		'placeholder' => __('Required')
	)); ?>

	<br/>

	<?= Form::submit(array('name' => 'submit', 'value' => __('Request account deletion'), 'class' => 'btn btn-primary')); ?>

	<?= Form::close(); ?>

</div>