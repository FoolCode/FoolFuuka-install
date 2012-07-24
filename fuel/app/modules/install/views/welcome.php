<p><?= __('Welcome to the FoolFrame installation.') ?></p>

<p><?= __('Will you be able to run FoolFrame? Here\'s a rundown:') ?></p>
<p>
<ul style="margin: 20px 40px">

	<?php $error = false ?>

	<?php foreach($check as $key => $item) :?>
		<li><p><?= e($item['string']) ?></li>
		<?php if ($item['result']) : ?>
			<span class="label label-success"><?= __('Available!') ?></span>
		<?php else : ?>
			<?php $error = true ?>
			<span class="label label-important"><?= __('Not available') ?></span>
			<p style="font-size:0.8em"><?= e($item['not_available_string']) ?></p>
		<?php endif; ?>
			</p>
	<?php endforeach; ?>

</ul>
</p>

<?php if (!$error) : ?>
	<p><?= e(__('Congratulations! Your server is ready to run FoolFrame. Next, we\'ll check if we can connect to a database.')) ?></p>
	<?= \Form::open('install/database') ?>
		<?= \Form::submit(array(
			'name' => 'submit',
			'value' => __('Go forth'),
			'class' => 'btn btn-success btn-large pull-right',
		)); ?>
	<?= \Form::close() ?>
<?php else : ?>
	<p><?= e(__('FoolFrame won\'t be able to run if the above isn\'t available. You will have to install and update the components to be able to run FoolFrame.')) ?>
<?php endif; ?>