<div class="well">

	<p><?= __('If you want to change your password, an email will be sent to your address giving you a link to safely change it.') ?></p>

	<?= Form::open(); ?>

	<?= Form::submit(array('name' => 'submit', 'value' => __('Change password'), 'class' => 'btn btn-primary')); ?>

	<?= Form::close(); ?>

</div>