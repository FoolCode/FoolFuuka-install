<div class="well">

	<p>
		<?= __('To reset your password, please enter the following information to begin the process of resetting your account password.') ?>
	</p>

	<?= \Form::open() ?>

	<label><?= \Form::label(__("Email Address"), 'email') ?></label>
	<?= \Form::input(array(
		'name' => 'email',
		'id' => 'email',
		'value' => \Input::post('email'),
		'placeholder' => __('Required')
	)) ?>

	<br/>

	<?= \Form::submit(array('name' => 'submit', 'value' => __('Submit'), 'class' => 'btn btn-primary')) ?>

	<?= \Form::close() ?>

</div>