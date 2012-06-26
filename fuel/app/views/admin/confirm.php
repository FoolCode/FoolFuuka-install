<div class="alert alert-block alert-<?php echo $alert_level ?> fade in">
	<p><?php echo $message ?></p>
	<p><?php echo Form::open();
		echo Form::submit(array(
			'name' => 'confirm',
			'value' => __('Confirm'),
			'class' => 'btn btn-danger',
			'style' => 'margin-right:6px;'));
		echo '<input type="button" onClick="history.back()" class="btn" value="'. __('Go back') . '" />';
		echo Form::close();
	?></p>
</div>
