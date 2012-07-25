
<p><?= __('FoolFrame supports only MySQL at this time. Insert the info to connect to a MySQL database.') ?></p>

<div style="text-align:center">

<?= Form::open() ?>

	<label><?= __('Hostname:') ?></label>
	<?= Form::input(array(
		'name' => 'hostname',
		'value' => \Input::post('hostname', 'localhost')
	)) ?>

	<p style="font-size:0.8em"><?= __('It will be "localhost" unless you have a multiserver setup, in which case you should insert an IP.') ?></p>

	<label><?= __('Table prefix:') ?></label>
	<?= Form::input(array(
		'name' => 'prefix',
		'value' => \Input::post('prefix', 'ff_')
	)) ?>

	<p style="font-size:0.8em"><?= __('The table prefix is used to not mix tables in the same database, if there are other tables at all.') ?></p>

	<label><?= __('Username:') ?></label>
	<?= Form::input(array(
		'name' => 'username',
		'value' => \Input::post('username')
	)) ?>

	<label><?= __('Password:') ?></label>
	<?= Form::password(array(
		'name' => 'password',
		'value' => \Input::post('password')
	)) ?>

	<label><?= __('Database name:') ?></label>
	<?= Form::input(array(
		'name' => 'database',
		'value' => \Input::post('database')
	)) ?>

	<br/><br/>

	<?= \Form::submit(array(
		'name' => 'submit',
		'value' => __('Test connection'),
		'class' => 'btn btn-success btn-large pull-right',
	)); ?>

	<?= \Form::close() ?>

</div>