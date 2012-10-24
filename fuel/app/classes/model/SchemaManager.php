<?php

namespace Foolz\Foolframe\Model;

class SchemaManager
{
	/**
	 * The database connection
	 *
	 * @var  \Doctrine\DBAL\Connection
	 */
	protected $connection;

	/**
	 * Set a prefix to ignore all the tables that aren't prefixed by this
	 *
	 * @var  string
	 */
	protected $prefix = null;

	/**
	 * The schema that holds what the code explains
	 *
	 * @var  \Doctrine\DBAL\Schema\Schema
	 */
	protected $coded_schema;

	/**
	 * The current database schema
	 *
	 * @var  \Doctrine\DBAL\Schema\Schema
	 */
	protected $database_schema;

	/**
	 * Creates a schema manager for testing if the modules are up to date
	 *
	 * @param  \Doctrine\DBAL\Connection  $connection  The doctrine database connection
	 * @param  string                     $prefix      The prefix used for the database
	 */
	public static function forgeForModules(\Doctrine\DBAL\Connection $connection, $prefix = '')
	{
		$new = new static();
		$new->connection = $connection;
		$new->prefix = $prefix;

		$sm = $new->connection->getSchemaManager();
		$tables = $sm->listTables();

		// get rid of the tables that don't have the same prefix
		if ($prefix !== null)
		{
			foreach ($tables as $key => $table)
			{
				if (strpos($table->getName(), $this->prefix) !== 0)
				{
					unset($tables[$key]);
				}
			}
		}

		// create a database "how it is now"
		$new->database_schema = new \Doctrine\DBAL\Schema\Schema($tables, array(), $sm->createSchemaConfig());

		// make an empty schema
		$new->coded_schema = new \Doctrine\DBAL\Schema\Schema(array(), array(), $sm->createSchemaConfig());

		return $new;
	}

	/**
	 * Get the prefix for the database
	 *
	 * @return  null|string  null if there's no prefix, string if set
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Returns the database schema that has to be edited to the destination one
	 *
	 * @return  \Doctrine\DBAL\Schema\Schema  Returns an empty (or edited) schema for you to edit
	 */
	public function getCodedSchema()
	{
		return $this->coded_schema;
	}

	/**
	 * Returns the live database schema
	 *
	 * @return  \Doctrine\DBAL\Schema\Schema  Returns the schema that was generated out of the database
	 */
	public function getDatabaseSchema()
	{
		return $this->database_schema;
	}

	/**
	 * Returns the array of changes that will take place if commit is run
	 *
	 * @return  array  An array with SQL queries that correspond to the changes
	 */
	public function getChanges()
	{
		return $this->coded_schema->getMigrateFromSql($this->database_schema, $this->connection->getSchemaManager()->getDatabasePlatform());
	}

	/**
	 * Runs the changes to the schema
	 */
	public function commit()
	{
		$this->connection->beginTransaction();

		foreach ($this->getChanges() as $sql)
		{
			$this->connection->query($sql);
		}

		$this->connection->commit();
	}
}