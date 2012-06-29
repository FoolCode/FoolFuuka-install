<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

echo '<div class="table"><h3>' . __('Update database') . '</h3>';

echo __("There's a newer version of the database available. Just update it by clicking on the update button.");
echo '<br/>';
echo sprintf(__("In case you're using a very large installation of {{FOOL_NAME}}, to avoid timeouts while updating the database, you can use the command line, and enter this: %s"), '<br/><b><code>' . $CLI_code . '</code></b>');

echo '<br/><br/>';
echo buttoner(array(
	'text' => __("Upgrade"),
	'href' => site_url('/admin/database/do_upgrade'),
	'plug' => __("Do you really want to upgrade your database?")
));

echo '<br/><br/></div>';