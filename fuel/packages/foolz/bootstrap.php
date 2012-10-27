<?php

\Autoloader::add_classes([
	'Foolz\\Sphinxql\\Sphinxql' => __DIR__.'/sphinxql/classes/Foolz/Sphinxql/Sphinxql.php',
	'Foolz\\Sphinxql\\SphinxqlConnection' => __DIR__.'/sphinxql/classes/Foolz/Sphinxql/SphinxqlConnection.php',
	'Foolz\\Sphinxql\\SphinxqlExpression' => __DIR__.'/sphinxql/classes/Foolz/Sphinxql/SphinxqlExpression.php',

	'Foolz\\Plugin\\Void' => __DIR__.'/plugin/classes/Foolz/Plugin/Void.php',
	'Foolz\\Plugin\\Util' => __DIR__.'/plugin/classes/Foolz/Plugin/Util.php',
	'Foolz\\Plugin\\Loader' => __DIR__.'/plugin/classes/Foolz/Plugin/Loader.php',
	'Foolz\\Plugin\\Plugin' => __DIR__.'/plugin/classes/Foolz/Plugin/Plugin.php',
	'Foolz\\Plugin\\Hook' => __DIR__.'/plugin/classes/Foolz/Plugin/Hook.php',
	'Foolz\\Plugin\\Event' => __DIR__.'/plugin/classes/Foolz/Plugin/Event.php',
	'Foolz\\Plugin\\Result' => __DIR__.'/plugin/classes/Foolz/Plugin/Result.php',

	'Foolz\\Inet\\Inet' => __DIR__.'/inet/classes/Foolz/Inet/Inet.php',

	'Foolz\\Postdeleter\\Postdeleter' => __DIR__.'/postdeleter/classes/Foolz/Postdeleter/Postdeleter.php',

	'Foolz\\Autoupgrade\\Upgrade' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/Upgrade.php',
	'Foolz\\Autoupgrade\\Container' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/Container.php',
	'Foolz\\Autoupgrade\\ContainerType' => __DIR__.'/autoupgrade/classes/Foolz/Autoupgrade/ContainerType.php',
]);