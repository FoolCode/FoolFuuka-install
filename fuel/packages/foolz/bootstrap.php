<?php

\Autoloader::add_classes([
	'Foolz\\Config\\Config' => __DIR__.'/config/classes/Foolz/Config/Config.php',

	'Foolz\\Postdeleter\\Postdeleter' => __DIR__.'/postdeleter/classes/Foolz/Postdeleter/Postdeleter.php',

	'Foolz\\Autoupgrade\\Upgrade' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/Upgrade.php',
	'Foolz\\Autoupgrade\\Container' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/Container.php',
	'Foolz\\Autoupgrade\\ContainerType' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/ContainerType.php',
]);