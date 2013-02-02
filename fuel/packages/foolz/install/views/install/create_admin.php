<p>
	<?= __('Please enter the account details you wish to create with administrative privileges. This account will be used to manage your FoolFrame installation. It is important that you do not lose this information since it required to perform administrative tasks.') ?>
</p>

<div style="padding-top:20px;">
	<?= Form::open(array('class' => 'form-horizontal')) ?>
		<fieldset>
			<div class="control-group">
				<label class="control-label" for="username"><?= __('Username') ?></label>
				<div class="controls">
					<?= \Form::input(array('id' => 'username', 'name' => 'username', 'value' => \Input::post('username'))) ?>
					<p class="help-block small-text"><?= __('This will the the username of the account with administrative privileges created to manage your FoolFrame installation.') ?></p>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="email"><?= __('Email') ?></label>
				<div class="controls">
					<?= \Form::input(array('id' => 'email', 'name' => 'email', 'value' => \Input::post('email'))) ?>
					<p class="help-block small-text"><?= __('Enter the email address for the user account specified above. This will be used for account recovery and authentication.') ?></p>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="password"><?= __('Password') ?></label>
				<div class="controls">
					<?= \Form::password(array('id' => 'password', 'name' => 'password')) ?>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="confirm_password"><?= __('Confirm Password') ?></label>
				<div class="controls">
					<?= \Form::password(array('id' => 'confirm_password', 'name' => 'confirm_password')) ?>
				</div>
			</div>

			<hr />

			<?= \Form::submit(array('name' => 'submit', 'value' => __('Next'), 'class' => 'btn btn-success pull-right')) ?>
		</fieldset>
	<?= \Form::close() ?>
</div>