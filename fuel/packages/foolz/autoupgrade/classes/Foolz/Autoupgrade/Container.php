<?php

namespace Foolz\Autoupgrade;

use \Foolz\Postdeleter\Postdeleter;

class ContainerException extends \Exception {}
class ContainerNotFoundException extends ContainerException {}
class ContainerUploadException extends ContainerException {}
class ContainerInsertException extends ContainerException {}
class ContainerJsonException extends ContainerException {}

class Container
{
	/**
	 * Autoincrement value from database
	 *
	 * @var  int
	 */
	public $id = 0;

	/**
	 * The type of container by ID to join on ContainerType
	 *
	 * @var  int
	 */
	public $type_id = 0;

	/**
	 * The string of the type. This isn't stored in database
	 *
	 * @var  string
	 */
	public $type = null;

	/**
	 * Composer name of the container
	 *
	 * @var  null|string
	 */
	public $name = null;

	/**
	 * Pretty name of the container
	 *
	 * @var  null|string
	 */
	public $pretty_name = null;

	/**
	 * Human readable description
	 *
	 * @var  null|string
	 */
	public $description = null;

	/**
	 * Whether the ContainerType should be hidden
	 *
	 * @var  boolean
	 */
	public $hidden = false;

	/**
	 * Version's <major>
	 *
	 * @var  null|int
	 */
	public $major = null;

	/**
	 * Version's <minor>
	 *
	 * @var  null|int
	 */
	public $minor = null;

	/**
	 * Version's <patch>
	 *
	 * @var  null|int
	 */
	public $patch = null;

	/**
	 * Version's <rc>
	 *
	 * @var  null|int
	 */
	public $rc = null;

	/**
	 * Number to determine the database schema and other migrations
	 *
	 * @var  null|int
	 */
	public $revision = null;

	/**
	 * The timestamp of creation
	 *
	 * @var  null|int
	 */
	public $created = null;

	/**
	 * A link to reach the download redirection
	 *
	 * @var  null|string
	 */
	public $link = null;

	/**
	 * Files and directories to delete recursively
	 *
	 * @var  array
	 */
	public $to_delete = [];

	/**
	 * The path to the directory of the uploaded file
	 *
	 * @var  null|string
	 */
	public $temp_path = null;

	/**
	 * The temporary filename (not the upload one) the uploaded file
	 *
	 * @var  null|string
	 */
	public $temp_filename = null;

	/**
	 * The size of the uploaded file
	 *
	 * @var  null|string
	 */
	public $temp_size = null;

	/**
	 * The extension of the uploaded file
	 *
	 * @var  null|string
	 */
	public $temp_extension = null;

	/**
	 *
	 * @param type $array
	 * @return  \Foolz\Autoupgrade\Container
	 */
	public static function forge($array)
	{
		$new = new static();

		foreach ($array as $key => $item)
		{
			$new->$key = $item;
		}

		// feature we can have only with database support
		if (isset($new->id))
		{
			$new->link = \Uri::create('download/'.$new->id);
			$new->api_link = \Uri::create('_/api/download/?id='.$new->id);
		}

		return $new;
	}


	public static function forgeFromComposer($path_to_composer)
	{
		$composer = file_get_contents($path_to_composer);

		if ($composer === false)
		{
			throw new UpgradeException(__('The composer.json file could not be read in '.$path_to_composer));
		}

		$new = new static();

		$new->validateJson($composer);

		return $new;
	}

	public static function get($type, $name, $mode = 'all', $dev = false)
	{
		try
		{
			$container_type = ContainerType::getBySlug($type);
		}
		catch (\Foolz\Foolpod\Model\ContainerTypeNotFoundException $e)
		{
			throw new ContainerNotFoundException('The type specified doesn\'t exist.');
		}

		$query = \DC::qb()
			->select('*')
			->from(\DC::p('containers'), 'c')
			->where('type_id = :type_id')
			->andWhere('name = :name')
			->setParameters([':type_id' => $container_type->id, ':name' => $name])
			->orderBy('major', 'DESC')
			->addOrderBy('minor', 'DESC')
			->addOrderBy('patch', 'DESC')
			->addOrderBy('(case when rc = 0 then 10000 else rc end)', 'DESC');

		if ($dev === false)
		{
			$query->andWhere('rc = 0');
		}

		switch ($mode)
		{
			case 'all':
				break;
			case 'latest':
				$query->setMaxResults(1);
				break;
		}

		$result = $query->execute()
			->fetchAll();

		if ( ! count($result))
		{
			throw new ContainerNotFoundException(__('There are no containers with this name.'));
		}

		$objects = [];
		foreach ($result as $item)
		{
			$objects[] = static::forge($item);
		}

		return $objects;
	}

	public static function getById($id)
	{
		$result = \DC::qb()
			->select('*')
			->from(\DC::p('containers'), 'c')
			->where('id = :id')
			->setParameter(':id', $id)
			->execute()
			->fetch();

		if ( ! $result)
		{
			throw new ContainerNotFoundException(__('There are no containers with this ID.'));
		}

		return static::forge($result);
	}


	public function getDirectLink()
	{
		$type = ContainerType::getById($this->type_id);

		return 'foolpod/containers/'.$type->slug.'/'.$this->name.'/download/'
			.str_replace('/', '_', $this->name).'-'.$this->major.'.'.$this->minor.'.'
			.$this->patch.($this->rc ? '-RC'.$this->rc : '').'.zip';
	}

	/**
	 * Picks up the data from an upload, stores the file and puts the JSON data in database
	 *
	 * @param type $data
	 */
	public static function forgeFromUpload()
	{
		\Upload::process([
			'path' => APPPATH.'tmp/pod_container_upload/',
			'max_size' => \Auth::has_access('media.upload_limitless') ? 9999 * 1024 * 1024 : 5000 * 1024,
			'randomize' => true,
			'max_length' => 64,
			'ext_whitelist' => ['zip'],
			'mime_whitelist' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed']
		]);

		if ( ! \Upload::is_valid() || count(\Upload::get_files()) != 1)
		{
			throw new ContainerUploadException(__('Nothing valid was uploaded.'));
		}

		\Upload::save();

		$file = \Upload::get_files(0);

		$new = new static();

		$new->temp_size = $file['size'];
		$new->temp_path = $file['saved_to'];
		$new->temp_filename = $file['saved_as'];
		$new->temp_extension = $file['extension'];

		Postdeleter::forge([$new->temp_path.$new->temp_filename]);

		return $new;
	}

	/**
	 * Checks the data and inserts in database
	 *
	 * @throws ContainerInsertException
	 */
	public function insert($data = [])
	{
		$zip = new \ZipArchive;

		$res = $zip->open($this->temp_path.$this->temp_filename);

		if ($res !== true)
		{
			throw new ContainerInsertException(__('Couldn\'t open the zip.'));
		}

		$dir = APPPATH.'tmp/pod_container_unzip/'.\Str::random('alnum', 10).'/';
		Postdeleter::forge([$dir]);
		$this->to_delete[] = $dir;

		$zip->extractTo($dir);
		$zip->close();

		$json = file_get_contents($dir.'composer.json');

		if ($json === false)
		{
			throw new ContainerInsertException(__('Couldn\'t find the json file.'));
		}

		$json_array = json_decode($json);

		// for database storage
		$this->json = json_encode($json_array);

		try
		{
			$this->validateJson($json_array);
		}
		catch(ContainerJsonException $e)
		{
			throw new ContainerInsertException($e->getMessage());
		}

		$this->hidden = ! empty($data['hidden']) ? (bool) $data['hidden'] : false;

		$to_insert = [
			'type_id' => $this->type_id,
			'name' => $this->name,
			'pretty_name' => $this->pretty_name,
			'description' => $this->description,
			'hidden' => $this->hidden,
			'major' => $this->major,
			'minor' => $this->minor,
			'patch' => $this->patch,
			'rc' => $this->rc,
			'revision' => $this->revision,
			'json' => $this->json,
			'created' => time()
		];

		$find = \DC::qb()
			->select('*')
			->from(\DC::p('containers'), 'c')
			->where('name = :name')
			->andWhere('major = :major')
			->andWhere('minor = :minor')
			->andWhere('patch = :patch')
			->andWhere('rc = :rc')
			->setParameters([
				':name' => $this->name,
				':major' => $this->major,
				':minor' => $this->minor,
				':patch' => $this->patch,
				':rc' => $this->rc
			])
			->execute()
			->fetch();

		\DC::forge()->beginTransaction();

		if ($find)
		{
			$query = \DC::qb()
				->update(\DC::p('containers'))
				->where('id = :id')
				->setParameter(':id', $find['id']);

			foreach ($to_insert as $key => $item)
			{
				$query->set(\DC::forge()->quoteIdentifier($key), \DC::forge()->quote($item));
			}

			$query->execute();
		}
		else
		{
			\DC::forge()->insert(\DC::p('containers'), $to_insert);
		}

		$type = ContainerType::getBySlug($this->type);

		$dest = DOCROOT.'foolpod/containers/'.$type->slug.'/'.$this->name.'/download/';

		if ( ! file_exists($dest))
		{
			if ( ! mkdir($dest, 0777, true))
			{
				\DC::forge()->rollBack();
				throw new ContainerInsertException(__('Couldn\'t create the directory for the container.'));
			}
		}

		$copy = copy(
			$this->temp_path.$this->temp_filename,
			$dest.str_replace('/', '_', $this->name).'-'.$this->major.'.'.$this->minor.'.'.$this->patch.($this->rc ? '-RC'.$this->rc : '').'.'.$this->temp_extension
		);

		if ( ! $copy)
		{
			\DC::forge()->rollBack();
			throw new ContainerInsertException(__('Couldn\'t create the file for the container.'));
		}

		\DC::forge()->commit();

		return $this;
	}

	/**
	 * Validates the json content and sets the object variables
	 */
	public function validateJson($json)
	{
		if (is_null($json))
		{
			throw new ContainerJsonException(__('The JSON file contained invalid characters.'));
		}

		$this->type = $json->type;
		$this->name = $json->name;
		$this->description = $json->description;
		$this->pretty_name = $json->extra->pretty_name;
		$this->type = $json->type;
		$version = $json->version;

		if (isset($json->extra->revision))
		{
			$this->revision = $json->extra->revision;
		}
		else
		{
			$this->revision = 0;
		}

		// check and set type
		/*
		try
		{
			$type = ContainerType::getBySlug($this->type);
		}
		catch (ContainerTypeNotFoundException $e)
		{
			throw new ContainerInsertException(__('The type of container is undefined.'));
		}

		$this->type_id = $type->id;
		 *
		 */

		$ver = explode('.', $version);

		$ver[3] = 0;
		$dev = explode('-RC', $ver[2]);

		if (count($dev) > 1)
		{
			$ver[2] = $dev[0];
			$ver[3] = $dev[1];
		}

		foreach ($ver as $v)
		{
			if ( ! ctype_digit((string) $v) && $v < 100)
			{
				throw new ContainerInsertException(__('The version is malformed.'));
				break;
			}
		}

		$this->major = $ver[0];
		$this->minor = $ver[1];
		$this->patch = $ver[2];
		$this->rc = $ver[3];
	}
}