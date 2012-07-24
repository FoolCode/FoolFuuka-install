<?php

\Autoloader::add_classes(array(
	'Install\\Model\\Install' => APPPATH.'modules/install/classes/model/install.php',
));

Autoloader::add_core_namespace('Install\\Model');
