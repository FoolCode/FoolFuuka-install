<?php

namespace Model;

/**
 * Datamapper connection manager for FuelPHP 1.x that uses config/db.php
 */
class DC
{
	/**
	 * The connections to the database
	 *
	 * @var  array
	 */
	protected static $instances = array();

	/**
	 * Creates a new \Doctrine\DBAL\Connection or returns the existing instance
	 *
	 * @param   string  $instance  The named instance
	 * @return  \Doctrine\DBAL\Connection
	 * @throws  \DomainException  If the database configuration doesn't exist
	 */
	public static function forge($instance = 'default')
	{
		if (isset(static::$instances[$instance]))
		{
			return static::$instances[$instance];
		}

		$config = new \Doctrine\DBAL\Configuration();

		if (\Config::get('db') === null)
		{
			\Config::load('db', 'db');
		}

		$db_data = \Config::get('db.'.$instance.'.connection', false);

		if ($db_data === false)
		{
			throw new \DomainException('There\'s no such a database configuration available');
		}

		$data = [
			'dbname' => $db_data['database'],
			'user' => $db_data['username'],
			'password' => $db_data['password'],
			'host' => $db_data['hostname'],
			'driver' => 'pdo_mysql',
		];

		return static::$instances[$instance] = \Doctrine\DBAL\DriverManager::getConnection($data, $config);
	}

	/**
	 * Returns a query builder
	 *
	 * @param   string  $instance  The named instance
	 * @return  \Doctrine\DBAL\Query\QueryBuilder
	 * @throws  \DomainException  If the database configuration doesn't exist
	 */
	public static function qb($instance = 'default')
	{
		return static::forge($instance)->createQueryBuilder();
	}
}