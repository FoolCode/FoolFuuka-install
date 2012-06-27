<?php

namespace Model;

class Radix extends \Model
{

	/**
	 * An array of all the preloaded boards
	 *
	 * @var null|array
	 */
	private static $preloaded_radixes = null;

	/**
	 * The currently selected radix to use with get_selected_radix()
	 *
	 * @var object
	 */
	private static $selected_radix = null;


	/**
	 * Preload on construct
	 */
	public static function _init()
	{
		self::preload();
	}


	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = Plugins::run_hook('model/radix/call/before/'.$name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = Plugins::run_hook('model/radix/call/replace/'.$name, $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters))
			{
				case 0:
					$return = $this->{'p_'.$name}();
					break;
				case 1:
					$return = $this->{'p_'.$name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_'.$name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_'.$name), $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = Plugins::run_hook('model/radix/call/after/'.$name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	public static function __callStatic($name, $parameters)
	{
		$before = Plugins::run_hook('model/radix/call/before/'.$name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = Plugins::run_hook('model/radix/call/replace/'.$name, $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			$pname = 'p_'.$name;
			switch (count($parameters))
			{
				case 0:
					$return = self::$pname();
					break;
				case 1:
					$return = self::$pname($parameters[0]);
					break;
				case 2:
					$return = self::$pname($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = self::$pname($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = self::$pname($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = self::$pname($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, $pname), $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = Plugins::run_hook('model/radix/call/after/'.$name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * The structure of the radix table to be used with validation and form creator
	 *
	 * @param Object $radix If available insert to customize the
	 * @return array the structure
	 */
	private static function p_structure($radix = NULL)
	{
		$structure = array(
			'open' => array(
				'type' => 'open',
			),
			'id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$query = \DB::select()->from('boards')->where('id', $input['id'])->execute();
					if (count($query) != 1)
					{
						return array(
							'error_code' => 'ID_NOT_FOUND',
							'error' => __('Couldn\'t find the board with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			),
			'name' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => __('Name'),
				'help' => __('Insert the name of the board normally shown as title.'),
				'placeholder' => __('Required'),
				'class' => 'span3',
				'validation' => 'required|max_length[128]'
			),
			'shortname' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => __('Shortname'),
				'help' => __('Insert the shorter name of the board. Reserved: "api", "cli", "admin".'),
				'placeholder' => __('Req.'),
				'class' => 'span1',
				'validation' => 'required|max_length[5]|alpha_dash',
				'validation_func' => function($input, $form_internal)
				{
					// if we're not using the special subdomain for peripherals
					if (Preferences::get('fs_srv_sys_subdomain', FOOL_PREF_SYS_SUBDOMAIN) === FALSE)
					{
						if (in_array($input['shortname'], unserialize(FOOL_PROTECTED_RADIXES)))
						{
							return array(
								'error_code' => 'PROTECTED_RADIX',
								'error' => __('You can\'t use the protected shortnames unless you activate the system subdomain feature. The protected shortnames are:').' "'.implode(", ",
									unserialize(FOOL_PROTECTED_RADIXES)).'".'
							);
						}
					}

					// if we're working on the same object
					if (isset($input['id']))
					{
						// existence ensured by CRITICAL in the ID check
						$query = \DB::select()->from('board')->where('id', $input['id'])->as_object()->execute();

						// no change?
						if ($input['shortname'] == $query[0]->shortname)
						{
							// no change
							return array('success' => TRUE);
						}
					}

					// check that there isn't already a board with that name
					$query = \DB::select()->from('boards')->where('shortname', $input['shortname'])->execute();
					if (count($query))
					{
						return array(
							'error_code' => 'ALREADY_EXISTS',
							'error' => __('The shortname is already used for another board.')
						);
					}
				}
			),
			'rules' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'textarea',
				'label' => __('General rules'),
				'help' => __('Full board rules displayed in a separate page, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => __('MarkDown goes here')
			),
			'separator-3' => array(
				'type' => 'separator'
			),
			'posting_rules' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'textarea',
				'label' => __('Posting rules'),
				'help' => __('Posting rules displayed in the posting area, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => __('MarkDown goes here')
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'threads_per_page' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'label' => __('Maximum number of threads to display in the index pages'),
				'type' => 'input',
				'class' => 'span1',
				'validation' => 'trim|required|is_natural',
				'default_value' => FOOL_RADIX_THREADS_PER_PAGE
			),
			'archive' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => __('Is this a 4chan archiving board?'),
				'sub' => array(
					'paragraph' => array(
						'type' => 'paragraph',
						'help' => __('Options for archive boards')
					),
					'board_url' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('URL to the 4chan board (facultative)'),
						'placeholder' => 'http://boards.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'thumbs_url' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('URL to the board thumbnails (facultative)'),
						'placeholder' => 'http://0.thumbs.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'images_url' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('URL to the board images (facultative)'),
						'placeholder' => 'http://images.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'media_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('Image fetching workers'),
						'help' => __('The number of workers that will fetch full images. Set to zero not to fetch them.'),
						'placeholder' => 5,
						'value' => 0,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thumb_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('Thumbnail fetching workers'),
						'help' => __('The number of workers that will fetch thumbnails'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'new_threads_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => __('Thread fetching workers'),
						'help' => __('The number of workers that fetch new threads'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thread_refresh_rate' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'hidden',
						'value' => 3,
						'label' => __('Minutes to refresh the thread'),
						'placeholder' => 3,
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'page_settings' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'textarea',
						'label' => __('Thread refresh rate'),
						'help' => __('Array of refresh rates in seconds per page in JSON format'),
						'placeholder' => htmlspecialchars('[{"delay": 30, "pages": [0, 1, 2]},'.
							'{"delay": 120, "pages": [3, 4, 5, 6, 7, 8, 9, 10, 11, 12]},'.
							'{"delay": 30, "pages": [13, 14, 15]}]'),
						'class' => 'span4',
						'style' => 'height:70px;',
						'validation_func' => function($input, $form_internal)
						{
							$json = @json_decode($input['page_settings']);
							if (is_null($json))
							{
								return array(
									'error_code' => 'NOT_JSON',
									'error' => __('The JSON inputted is not valid.')
								);
							}
						}
					)
				),
				'sub_inverse' => array(
					'paragraph' => array(
						'type' => 'paragraph',
						'help' => __('Options for normal boards')
					),
					'thumbnail_op_width' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Opening post thumbnail maximum width after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_OP_WIDTH
					),
					'thumbnail_op_height' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Opening post thumbnail maximum height after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_OP_HEIGHT
					),
					'thumbnail_reply_width' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Reply thumbnail maximum width after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_REPLY_WIDTH
					),
					'thumbnail_reply_height' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Reply thumbnail maximum height after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_REPLY_HEIGHT
					),
					'max_image_size_kilobytes' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Full image maximum size in kilobytes'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_MAX_IMAGE_SIZE_KILOBYTES
					),
					'max_image_size_width' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Full image maximum width in pixels'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_MAX_IMAGE_SIZE_WIDTH
					),
					'max_image_size_height' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('Full image maximum height in pixels'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_MAX_IMAGE_SIZE_HEIGHT
					),
					'max_posts_count' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('The maximum amount of posts before a thread "dies"'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural',
						'default_value' => FOOL_RADIX_MAX_POSTS_COUNT
					),
					'max_images_count' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('The maximum amount of images in replies before posting more is prohibited'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural',
						'default_value' => FOOL_RADIX_MAX_IMAGES_COUNT
					),
					'min_image_repost_hours' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => __('The minimum time in hours to repost the same image (0 means no limit, -1 means never allowing a repost)'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|integer|greater_than[-2]',
						'default_value' => FOOL_RADIX_MIN_IMAGE_REPOST_HOURS
					)
				)
			),
			'anonymous_default_name' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'label' => __('The default name when an user doesn\'t enter a name'),
				'type' => 'input',
				'class' => 'span3',
				'validation' => 'trim|required',
				'default_value' => FOOL_RADIX_ANONYMOUS_DEFAULT_NAME
			),
			'transparent_spoiler' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'help' => __('Should the image spoilers be semi-transparent? (mods and admins have it always on for moderation)'),
				'type' => 'checkbox',
			),
			'display_exif' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'help' => __('Show the EXIF data (EXIF data is saved in the database regardless)'),
				'type' => 'checkbox',
				'disabled' => 'disabled',
			),
			'enable_poster_hash' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'help' => __('Enable poster hashes, an IP-based code to temporarily distinguish Anonymous users'),
				'type' => 'checkbox',
			),
			'disable_ghost' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'help' => __('Don\'t allow ghost posting (disallows infinite replying)'),
				'type' => 'checkbox',
			),
			'hide_thumbnails' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => __('Hide the thumbnails?')
			),
			'delay_thumbnails' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'checkbox',
				'help' => __('Hide the thumbnails for 24 hours? (for moderation purposes)')
			),
			'sphinx' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => __('Use SphinxSearch as search engine?')
			),
			'hidden' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => __('Hide the board from public access? (only admins and mods will be able to browse it)')
			),
			'myisam_search' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'internal',
				'default_value' => FOOL_RADIX_MYISAM_SEARCH
			),
		);

		$structure = Plugins::run_hook('fu_radix_model_structure_alter', array($structure), 'simple');

		$structure = array_merge($structure,
			array(
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			),
			'close' => array(
				'type' => 'close'
			)));
		return $structure;
	}


	/**
	 * Clears the APC/memcached cache
	 */
	function p_clear_cache()
	{
		$all = self::get_all();

		\Cache::delete('mode.radix.preload');

		foreach ($all as $a)
		{
			\Cache::delete('model.radix.load_preferences.'.$a->id);
		}
	}


	/**
	 * Saves the data for a board. Plains the structure, runs the validation.
	 * If 'id' is not set, it creates a new board.
	 *
	 * @param type $data
	 */
	private static function p_save($data)
	{
		// filter _boards data from _boards_preferences data
		$structure = self::structure();
		$data_boards = array();
		$data_boards_preferences = array();

		foreach ($structure as $key => $item)
		{
			// mix the sub and sub_inverse and flatten the array
			if (isset($item['sub_inverse']) && isset($item['sub']))
			{
				$item['sub'] = array_merge($item['sub'], $item['sub_inverse']);
			}

			if (isset($item['sub']))
			{
				foreach ($item['sub'] as $k => $i)
				{
					if (isset($i['boards_preferences']) && isset($data[$k]))
					{
						$data_boards_preferences[$k] = $data[$k];
						unset($data[$k]);
					}
				}
			}

			if (isset($item['boards_preferences']) && isset($data[$key]))
			{
				$data_boards_preferences[$key] = $data[$key];
				unset($data[$key]);
			}
		}

		// data must be already sanitized through the form array
		if (isset($data['id']))
		{
			if (!$radix = self::get_by_id($data['id']))
			{
				show_404();
			}

			// save normal values
			\DB::update('boards')->set($data)->where('id', $data['id'])->execute();

			// save extra preferences
			foreach ($data_boards_preferences as $name => $value)
			{
				$query = \DB::select()->from('boards_preferences')->where('board_id', $data['id'])
					->and_where('name', $name)->execute();

				if (count($query))
				{
					\DB::update('boards_preferences')->value('value', $value)->where('name', $name)
						->and_where('board_id', $data['id'])->execute();
				}
				else
				{
					\DB::insert('boards_preferences')
						->set(array('board_id' => $data['id'], 'name' => $name, 'value' => $value))->execute();
				}
			}

			self::clear_cache();
			self::preload(TRUE);
		}
		else
		{
			list($id, $rows_affected) = \DB::insert('boards')->set($data)->execute();

			// save extra preferences
			foreach ($data_boards_preferences as $name => $value)
			{
				$query = \DB::select()->from('boards_preferences')->where('board_id', $id)
					->and_where('name', $name)->execute();

				if (count($query))
				{
					\DB::update('boards_preferences')->value($name, $value)
						->where('board_id', $id)->and_where('name', $name)->execute();
				}
				else
				{
					\DB::insert('boards_preferences')
						->set(array('board_id' => $id, 'name' => $name, 'value' => $value))->execute();
				}
			}

			self::clear_cache();
			self::preload(TRUE);
			$board = self::get_by_shortname($data['shortname']);

			// remove the triggers just to be safe
			self::mysql_remove_triggers($board);
			self::mysql_create_tables($board);
			self::mysql_create_extra($board);
			self::mysql_create_triggers($board);

			// if the user didn't select sphinx for search, enable the table _search silently
			if (!$board->sphinx)
			{
				self::mysql_create_search($board);
			}
		}
	}


	/**
	 * Removes the board and renames its dir with a _removed suffix and with a number
	 * in case of collision
	 *
	 * @param type $id the ID of the board
	 * @return boolean TRUE on success, FALSE on failure
	 */
	private static function p_remove($id)
	{
		$board = self::get_by_id($id);

		// always remove the triggers first
		self::mysql_remove_triggers($board);
		\DB::delete('boards_preferences')->where('board_id', $id)->execute();
		\DB::delete('boards')->where('id', $id)->execute();

		// rename the directory and prevent directory collision
		$base = Preferences::get('fu.boards_directory').'/'.$board->shortname;
		if (file_exists($base.'_removed'))
		{
			$incremented = \String::increment('_removed');
			while (file_exists($base.$incremented))
			{
				$incremented = \String::increment($incremented);
			}

			$rename_to = $base.$incremented;
		}
		else
		{
			$rename_to = Preferences::get('fu.boards_directory').'/'.$board->shortname.'_removed';
		}

		rename($base, $rename_to);

		// for huge boards, this may time out with PHP, while MySQL will keep going
		self::mysql_remove_tables($board);

		self::clear_cache();

		return true;
	}


	/**
	 * Maintenance function to remove leftover _removed folders
	 *
	 * @param type $echo echo CLI output
	 * @return boolean TRUE on success, FALSE on failure
	 */
	private static function p_remove_leftover_dirs($echo = FALSE)
	{
		$all = self::get_all();

		$array = array();

		// get all directories
		if ($handle = opendir(Preferences::get('fu.boards_directory')))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (in_array($file, array('..', '.')))
					continue;

				if (is_dir(Preferences::get('fu.boards_directory').'/'.$file))
				{
					$array[] = $file;
				}
			}
			closedir($handle);
		}
		else
		{
			return false;
		}

		// make sure it's a removed folder
		foreach ($array as $key => $dir)
		{
			if (strpos($dir, '_removed') === false)
			{
				unset($array[$key]);
			}

			foreach ($all as $a)
			{
				if ($a->shortname === $dir)
				{
					unset($array[$key]);
				}
			}
		}

		// exec the deletion
		foreach ($array as $dir)
		{
			$cmd = 'rm -Rv '.Preferences::get('fu.boards_directory').'/'.$dir;
			if ($echo)
			{
				echo $cmd.PHP_EOL;
				passthru($cmd);
				echo PHP_EOL;
			}
			else
			{
				exec($cmd).PHP_EOL;
			}
		}

		return true;
	}


	/**
	 * Puts the table in readily available variables
	 *
	 * @param bool $preferences if TRUE it loads all the extra preferences for all the boards
	 * @return FALSE if there is no boards, TRUE otherwise
	 */
	private static function p_preload($preferences = false)
	{
		if (!\Auth::has_access('maccess.mod'))
		{
			try
			{
				$object = \Cache::get('model.radix.preload');
			}
			catch (\CacheNotFoundException $e)
			{
				$object = \DB::select()->from('boards')->where('hidden', 0)->order_by('shortname', 'asc')
					->as_object()->execute()->as_array('id');
				\Cache::set('model.radix.preload', $object, 900);
			}
		}
		else
		{
			$object = \DB::select()->from('boards')->where('hidden', 0)->order_by('shortname', 'asc')
				->as_object()->execute()->as_array('id');
		}


		if (!is_array($object) || empty($object))
		{
			self::$preloaded_radixes = array();
			return false;
		}

		foreach ($object as $item)
		{
			$structure = self::structure($item);

			$result_object[$item->id] = $item;
			$result_object[$item->id]->formatted_title = ($item->name) ?
				'/'.$item->shortname.'/ - '.$item->name : '/'.$item->shortname.'/';

			if ($item->archive == 1)
			{
				$result_object[$item->id]->href = \Uri::create(array('@archive', $item->shortname));
			}
			else
			{
				$result_object[$item->id]->href = \Uri::create(array('@board', $item->shortname));
			}

			// load the basic value of the preferences
			foreach ($structure as $key => $arr)
			{
				if (!isset($result_object[$item->id]->$key) && isset($arr['boards_preferences']))
				{
					if (isset($arr['default_value']))
						$result_object[$item->id]->$key = $arr['default_value'];
					else
						$result_object[$item->id]->$key = false;
				}

				foreach (array('sub', 'sub_inverse') as $sub)
				{
					if (isset($arr[$sub]))
					{
						foreach ($arr[$sub] as $k => $a)
						{
							if (!isset($result_object[$item->id]->$k) && isset($a['boards_preferences']))
							{
								if (isset($a['default_value']))
									$result_object[$item->id]->$k = $a['default_value'];
								else
									$result_object[$item->id]->$k = false;
							}
						}
					}
				}
			}
		}

		self::$preloaded_radixes = $result_object;

		if ($preferences == true)
			self::load_preferences();

		return false;
	}


	/**
	 * Loads preferences data for the board.
	 *
	 * @param null|int|array|object $board null/array of IDs/ID/board object
	 * @return object the object of the board chosen
	 */
	private static function p_load_preferences($board = null)
	{
		if (is_null($board))
		{
			$ids = array_keys(self::$preloaded_radixes);
		}
		else if (is_array($board))
		{
			$ids = $board;
		}
		else if (is_object($board))
		{
			$ids = array($board->id);
		}
		else // it's an id
		{
			$ids = array($board);
		}

		$selected = false;
		foreach ($ids as $id)
		{
			try
			{
				$result = \Cache::get('model.radix.load_preferences.'.$id);
			}
			catch (\CacheNotFoundException $e)
			{
				$result = \DB::select()->from('boards_preferences')->where('board_id', $id)
					->as_object()->execute()->as_array();
				\Cache::set('model.radix.load_preferences.'.$id, $result, 900);
			}

			foreach ($result as $value)
			{
				static::$preloaded_radixes[$id]->{$value->name} = $value->value;
			}

			$selected = static::$preloaded_radixes[$id];
		}

		// useful if only one has been selected
		return $selected;
	}


	/**
	 * Get the board table name with protexted identifiers
	 *
	 * @param string $shortname The shortname, or the whole board object
	 * @param string $suffix board suffix like _images
	 * @return string the table name with protected identifiers
	 */
	private static function p_get_table($shortname, $suffix = '')
	{
		if (is_object($shortname))
			$shortname = $shortname->shortname;

		if (Preferences::get('fu.boards_db'))
		{
			return '`'.Preferences::get('fu.boards_db').'`.`'.$shortname.$suffix.'`';
		}
		else
		{
			return \DB::quote_identifier('board_'.$shortname.$suffix);
		}
	}


	/**
	 * Set a radix for contiguous use
	 *
	 * @param type $shortname the board shortname
	 * @return bool|object FALSE on failure, else the board object
	 */
	private static function p_set_selected_by_shortname($shortname)
	{
		if (false != ($val = self::get_by_shortname($shortname)))
		{
			$val = self::load_preferences($val);
			self::$selected_radix = $val;
			return $val;
		}

		self::$selected_radix = false;

		return false;
	}


	/**
	 * Returns the object of the selected radix
	 *
	 * @return bool|object FALSE if not set, else the board object
	 */
	private static function p_get_selected()
	{
		if (is_null(self::$selected_radix))
		{
			return false;
		}

		return self::$selected_radix;
	}


	/**
	 * Returns all the radixes as array of objects
	 *
	 * @return array the objects of the preloaded radixes
	 */
	private static function p_get_all()
	{
		return self::$preloaded_radixes;
	}


	/**
	 * Returns the single radix
	 *
	 * @param int $radix_id the ID of the board
	 * @return object the board object
	 */
	private static function p_get_by_id($radix_id)
	{
		$items = self::get_all();

		if (isset($items[$radix_id]))
			return $items[$radix_id];

		return false;
	}


	/**
	 * Returns the single radix by type selected
	 *
	 * @param string $value the value searched
	 * @param string $type the variable name on which to match
	 * @param bool $switch TRUE if it must be equal or FALSE if not equal
	 * @return bool|object FALSE if not found or the board object
	 */
	private static function p_get_by_type($value, $type, $switch = true)
	{
		$items = self::get_all();

		foreach ($items as $item)
		{
			if ($switch == ($item->$type === $value))
			{
				return $item;
			}
		}

		return false;
	}


	/**
	 * Returns the single radix by shortname
	 *
	 * @return object the board with the shortname
	 */
	private static function p_get_by_shortname($shortname)
	{
		return static::get_by_type($shortname, 'shortname');
	}


	/**
	 * Returns only the type specified (exam)
	 *
	 * @param string $type the variable name
	 * @param boolean $switch the value to match
	 * @return array the board objects
	 */
	private static function p_filter_by_type($type, $switch)
	{
		$items = self::get_all();

		foreach ($items as $key => $item)
		{
			if ($item->$type != $switch)
				unset($items[$key]);
		}

		return $items;
	}


	/**
	 * Returns an array of objects that are archives
	 *
	 * @return array the board objects that are archives
	 */
	private static function p_get_archives()
	{
		return self::filter_by_type('archive', true);
	}


	/**
	 * Returns an array of objects that are boards (not archives)
	 *
	 * @return array the board objects that are boards
	 */
	private static function p_get_boards()
	{
		return self::filter_by_type('archive', false);
	}


	/**
	 * Tells us if the entire MySQL server is compatible with multibyte
	 *
	 * @param bool $as_string if TRUE it returns the strong as in utf8 or utf8mb4
	 * @return bool|string TRUE or FALSE, or the compatibe charset depending on $as_string
	 */
	private static function p_mysql_check_multibyte($as_string = false)
	{
		$query = \DB::query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4';")->execute();

		if (!$as_string)
		{
			return (boolean) count($query);
		}
		else
		{
			return count($query) > 0 ? 'utf8mb4' : 'utf8';
		}
	}


	/**
	 * Creates the tables for the board
	 *
	 * @param object $board the board object
	 */
	private static function p_mysql_create_tables($board)
	{
		// with true it gives the charset string directly
		$charset = $this->mysql_check_multibyte(true);

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board)." (
				doc_id int unsigned NOT NULL auto_increment,
				media_id int unsigned NOT NULL DEFAULT '0',
				poster_ip decimal(39,0) unsigned NOT NULL DEFAULT '0',
				num int unsigned NOT NULL,
				subnum int unsigned NOT NULL,
				thread_num int unsigned NOT NULL DEFAULT '0',
				op bool NOT NULL DEFAULT '0',
				timestamp int unsigned NOT NULL,
				timestamp_expired int unsigned NOT NULL,
				preview_orig varchar(20),
				preview_w smallint unsigned NOT NULL DEFAULT '0',
				preview_h smallint unsigned NOT NULL DEFAULT '0',
				media_filename text,
				media_w smallint unsigned NOT NULL DEFAULT '0',
				media_h smallint unsigned NOT NULL DEFAULT '0',
				media_size int unsigned NOT NULL DEFAULT '0',
				media_hash varchar(25),
				media_orig varchar(20),
				spoiler bool NOT NULL DEFAULT '0',
				deleted bool NOT NULL DEFAULT '0',
				capcode enum('N', 'M', 'A', 'G') NOT NULL DEFAULT 'N',
				email varchar(100),
				name varchar(100),
				trip varchar(25),
				title varchar(100),
				comment text,
				delpass tinytext,
				sticky bool NOT NULL DEFAULT '0',
				poster_hash varchar(8),
				exif text,

				PRIMARY KEY (`doc_id`),
				UNIQUE num_subnum_index (`num`, `subnum`),
				INDEX thread_num_subnum_index (`thread_num`, `num`, `subnum`),
				INDEX subnum_index (`subnum`),
				INDEX op_index (`op`),
				INDEX media_id_index (`media_id`),
				INDEX media_hash_index (`media_hash`),
				INDEX media_orig_index (`media_orig`),
				INDEX name_trip_index (`name`, `trip`),
				INDEX trip_index (`trip`),
				INDEX email_index (`email`),
				INDEX poster_ip_index (`poster_ip`),
				INDEX timestamp_index (`timestamp`)
			) engine=InnoDB CHARSET=".$charset.";
		")->execute();

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_threads')." (
				`thread_num` int unsigned NOT NULL,
				`time_op` int unsigned NOT NULL,
				`time_last` int unsigned NOT NULL,
				`time_bump` int unsigned NOT NULL,
				`time_ghost` int unsigned DEFAULT NULL,
				`time_ghost_bump` int unsigned DEFAULT NULL,
				`nreplies` int unsigned NOT NULL DEFAULT '0',
				`nimages` int unsigned NOT NULL DEFAULT '0',

				PRIMARY KEY (`thread_num`),
				INDEX time_op_index (`time_op`),
				INDEX time_bump_index (`time_bump`),
				INDEX time_ghost_bump_index (`time_ghost_bump`)
			) ENGINE=InnoDB CHARSET=".$charset.";
		")->execute();


		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_users')." (
				`user_id` int unsigned NOT NULL auto_increment,
				`name` varchar(100) NOT NULL DEFAULT '',
				`trip` varchar(25) NOT NULL DEFAULT '',
				`firstseen` int(11) NOT NULL,
				`postcount` int(11) NOT NULL,

				PRIMARY KEY (`user_id`),
				UNIQUE name_trip_index (`name`, `trip`),
				INDEX firstseen_index (`firstseen`),
				INDEX postcount_index (`postcount`)
			) ENGINE=InnoDB DEFAULT CHARSET=".$charset.";
		")->execute();

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_images')." (
				`media_id` int unsigned NOT NULL auto_increment,
				`media_hash` varchar(25) NOT NULL,
				`media` varchar(20),
				`preview_op` varchar(20),
				`preview_reply` varchar(20),
				`total` int(10) unsigned NOT NULL DEFAULT '0',
				`banned` smallint unsigned NOT NULL DEFAULT '0',

				PRIMARY KEY (`media_id`),
				UNIQUE media_hash_index (`media_hash`),
				INDEX total_index (`total`),
				INDEX banned_index (`banned`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		")->execute();

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_daily')." (
				`day` int(10) unsigned NOT NULL,
				`posts` int(10) unsigned NOT NULL,
				`images` int(10) unsigned NOT NULL,
				`sage` int(10) unsigned NOT NULL,
				`anons` int(10) unsigned NOT NULL,
				`trips` int(10) unsigned NOT NULL,
				`names` int(10) unsigned NOT NULL,

				PRIMARY KEY (`day`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		")->execute();

		// populate _images table with banned media from global table
		\DB::query('
			INSERT INTO '.$this->radix->get_table($board, '_images').'
			(
				media_hash, media, preview_op, preview_reply, total, banned
			)
			(
				SELECT md5, NULL, NULL, NULL, 0, 1
				FROM '.$this->db->protect_identifiers('banned_md5',
				TRUE).'
			)
			ON DUPLICATE KEY UPDATE banned = 1
		')->execute();
	}


	/**
	 * Creates the special "_extra" table for plugins
	 *
	 * @param object $board the board object
	 */
	private static function p_mysql_create_extra($board)
	{
		// with true it gives the charset string directly
		$charset = $this->mysql_check_multibyte(TRUE);

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_extra')." (
				doc_id int unsigned NOT NULL,
				json text,

				PRIMARY KEY (`doc_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=".$charset.";
		")->execute();

		Plugins::run_hook('model/radix/mysql_create_extra/columns');
	}


	/**
	 * Create the triggers for the board
	 *
	 * @param object $board the board object
	 */
	private static function p_mysql_create_triggers($board)
	{
		// triggers fail if we try to send it from the other database, so switch it for a moment
		// the alternative would be adding a database prefix to the trigger name which would be messy
		if (Preferences::get('fu.boards_db'))
			\DB::query('USE '.Preferences::get('fs_fuuka_boards_db'))->execute();

		\DB::query("
			CREATE PROCEDURE `update_thread_".$board->shortname."` (tnum INT)
			BEGIN
			UPDATE
				".$this->get_table($board,
				'_threads')." op
			SET
				op.time_last = (
				COALESCE(GREATEST(
					op.time_op,
					(SELECT MAX(timestamp) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index)
					WHERE re.thread_num = tnum AND re.subnum = 0)
					), op.time_op)
				),
				op.time_bump = (
					COALESCE(GREATEST(
					op.time_op,
					(SELECT MAX(timestamp) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index)
					WHERE re.thread_num = tnum AND (re.email <> 'sage' OR re.email IS NULL) AND re.subnum = 0)
					), op.time_op)
				),
				op.time_ghost = (
					SELECT MAX(timestamp) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index)
					WHERE re.thread_num = tnum AND re.subnum <> 0
				),
				op.time_ghost_bump = (
					SELECT MAX(timestamp) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index)
					WHERE re.thread_num = tnum AND re.subnum <> 0 AND (re.email <> 'sage' OR re.email IS NULL)
				),
				op.nreplies = (
					SELECT COUNT(*) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index) WHERE
					re.thread_num = tnum
				),
				op.nimages = (
					SELECT COUNT(media_hash) FROM ".$this->get_table($board)." re FORCE INDEX(thread_num_subnum_index)
					WHERE re.thread_num = tnum
				)
				WHERE op.thread_num = tnum;
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `create_thread_".$board->shortname."` (num INT, timestamp INT)
			BEGIN
				INSERT IGNORE INTO ".$this->get_table($board,
				'_threads')." VALUES (num, timestamp, timestamp,
					timestamp, NULL, NULL, 0, 0);
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `delete_thread_".$board->shortname."` (tnum INT)
			BEGIN
				DELETE FROM ".$this->get_table($board,
				'_threads')." WHERE thread_num = tnum;
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `insert_image_".$board->shortname."` (n_media_hash VARCHAR(25),
			n_media VARCHAR(20), n_preview VARCHAR(20), n_op INT)
			BEGIN
				IF n_op = 1 THEN
					INSERT INTO ".$this->get_table($board,
				'_images')." (media_hash, media, preview_op, total)
					VALUES (n_media_hash, n_media, n_preview, 1)
					ON DUPLICATE KEY UPDATE
					media_id = LAST_INSERT_ID(media_id),
					total = (total + 1),
					preview_op = COALESCE(preview_op, VALUES(preview_op)),
					media = COALESCE(media, VALUES(media));
				ELSE
					INSERT INTO ".$this->get_table($board,
				'_images')." (media_hash, media, preview_reply, total)
					VALUES (n_media_hash, n_media, n_preview, 1)
					ON DUPLICATE KEY UPDATE
					media_id = LAST_INSERT_ID(media_id),
					total = (total + 1),
					preview_reply = COALESCE(preview_reply, VALUES(preview_reply)),
					media = COALESCE(media, VALUES(media));
				END IF;
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `delete_image_".$board->shortname."` (n_media_id INT)
			BEGIN
			UPDATE ".$this->get_table($board,
				'_images')." SET total = (total - 1) WHERE media_id = n_media_id;
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `insert_post_".$board->shortname."` (p_timestamp INT, p_media_hash VARCHAR(25),
			p_email VARCHAR(100), p_name VARCHAR(100), p_trip VARCHAR(25))
			BEGIN
				DECLARE d_day INT;
				DECLARE d_image INT;
				DECLARE d_sage INT;
				DECLARE d_anon INT;
				DECLARE d_trip INT;
				DECLARE d_name INT;

				SET d_day = FLOOR(p_timestamp/86400)*86400;
				SET d_image = p_media_hash IS NOT NULL;
				SET d_sage = COALESCE(p_email = 'sage', 0);
				SET d_anon = COALESCE(p_name = 'Anonymous' AND p_trip IS NULL, 0);
				SET d_trip = p_trip IS NOT NULL;
				SET d_name = COALESCE(p_name <> 'Anonymous' AND p_trip IS NULL, 1);

				INSERT INTO ".$this->get_table($board,
				'_daily')." VALUES(d_day, 1, d_image, d_sage, d_anon, d_trip,
					d_name)
					ON DUPLICATE KEY UPDATE posts=posts+1, images=images+d_image,
					sage=sage+d_sage, anons=anons+d_anon, trips=trips+d_trip,
					names=names+d_name;

				IF (SELECT trip FROM ".$this->get_table($board,
				'_users')." WHERE trip = p_trip) IS NOT NULL THEN
					UPDATE ".$this->get_table($board, '_users')." SET postcount=postcount+1,
						firstseen = LEAST(p_timestamp, firstseen),
						name = COALESCE(p_name, '')
					WHERE trip = p_trip;
				ELSE
					INSERT INTO ".$this->get_table($board,
				'_users')." VALUES(
						NULL, COALESCE(p_name,''), COALESCE(p_trip,''), p_timestamp, 1)
					ON DUPLICATE KEY UPDATE postcount=postcount+1,
						firstseen = LEAST(VALUES(firstseen), firstseen),
						name = COALESCE(p_name, '');
				END IF;
			END;
		")->execute();

		\DB::query("
			CREATE PROCEDURE `delete_post_".$board->shortname."` (p_timestamp INT, p_media_hash VARCHAR(25), p_email VARCHAR(100), p_name VARCHAR(100), p_trip VARCHAR(25))
			BEGIN
				DECLARE d_day INT;
				DECLARE d_image INT;
				DECLARE d_sage INT;
				DECLARE d_anon INT;
				DECLARE d_trip INT;
				DECLARE d_name INT;

				SET d_day = FLOOR(p_timestamp/86400)*86400;
				SET d_image = p_media_hash IS NOT NULL;
				SET d_sage = COALESCE(p_email = 'sage', 0);
				SET d_anon = COALESCE(p_name = 'Anonymous' AND p_trip IS NULL, 0);
				SET d_trip = p_trip IS NOT NULL;
				SET d_name = COALESCE(p_name <> 'Anonymous' AND p_trip IS NULL, 1);

				UPDATE ".$this->get_table($board,
				'_daily')." SET posts=posts-1, images=images-d_image,
					sage=sage-d_sage, anons=anons-d_anon, trips=trips-d_trip,
					names=names-d_name WHERE day = d_day;

				IF (SELECT trip FROM ".$this->get_table($board,
				'_users')." WHERE trip = p_trip) IS NOT NULL THEN
					UPDATE ".$this->get_table($board, '_users')." SET postcount = postcount-1 WHERE trip = p_trip;
				ELSE
					UPDATE ".$this->get_table($board,
				'_users')." SET postcount = postcount-1
						WHERE name = COALESCE(p_name, '') AND trip = COALESCE(p_trip, '');
				END IF;
			END;
		")->execute();

		\DB::query("
			CREATE TRIGGER `before_ins_".$board->shortname."` BEFORE INSERT ON ".$this->get_table($board)."
			FOR EACH ROW
			BEGIN
				IF NEW.media_hash IS NOT NULL THEN
					CALL insert_image_".$board->shortname."(NEW.media_hash, NEW.media_orig, NEW.preview_orig, NEW.op);
					SET NEW.media_id = LAST_INSERT_ID();
				END IF;
			END;
		")->execute();

		\DB::query("
			CREATE TRIGGER `after_ins_".$board->shortname."` AFTER INSERT ON ".$this->get_table($board)."
			FOR EACH ROW
			BEGIN
				IF NEW.op = 1 THEN
					CALL create_thread_".$board->shortname."(NEW.num, NEW.timestamp);
				END IF;
				CALL update_thread_".$board->shortname."(NEW.thread_num);
				CALL insert_post_".$board->shortname."(NEW.timestamp, NEW.media_hash, NEW.email, NEW.name, NEW.trip);
			END;
		")->execute();

		\DB::query("
			CREATE TRIGGER `after_del_".$board->shortname."` AFTER DELETE ON ".$this->get_table($board)."
			FOR EACH ROW
			BEGIN
				CALL update_thread_".$board->shortname."(OLD.thread_num);
				IF OLD.op = 1 THEN
					CALL delete_thread_".$board->shortname."(OLD.num);
				END IF;
				CALL delete_post_".$board->shortname."(OLD.timestamp, OLD.media_hash, OLD.email, OLD.name, OLD.trip);
				IF OLD.media_hash IS NOT NULL THEN
					CALL delete_image_".$board->shortname."(OLD.media_id);
				END IF;
			END;
		")->execute();

		if (Preferences::get('fu.boards_db'))
			\DB::query('USE '.$this->db->database)->execute();
	}


	/**
	 * Remove the tables associated to the board
	 *
	 * @param object $board the board object
	 */
	private static function p_mysql_remove_tables($board)
	{
		$tables = array(
			'',
			'_images',
			'_threads',
			'_users',
			'_daily',
			'_search',
			'_extra'
		);

		foreach ($tables as $table)
			\DB::query("DROP TABLE IF EXISTS ".$this->get_table($board, $table))->execute();
	}


	/**
	 * Remove the MySQL triggers for the boards
	 *
	 * @param object $board the board object
	 */
	private static function p_mysql_remove_triggers($board)
	{
		if (Preferences::get('fu.boards_db'))
			\DB::query('USE '.Preferences::get('fs_fuuka_boards_db'))->execute();

		$prefixes_procedure = array(
			'update_thread_',
			'create_thread_',
			'delete_thread_',
			'insert_image_',
			'delete_image_',
			'insert_post_',
			'delete_post_'
		);

		$prefixes_trigger = array(
			'before_ins_',
			'after_ins_',
			'after_del_'
		);

		foreach ($prefixes_procedure as $prefix)
			\DB::query("DROP PROCEDURE IF EXISTS `".$prefix.$board->shortname."`")->execute();

		foreach ($prefixes_trigger as $prefix)
			\DB::query("DROP TRIGGER IF EXISTS `".$prefix.$board->shortname."`")->execute();

		if (Preferences::get('fs_fuuka_boards_db'))
			\DB::query('USE '.\Config::get('db.default.connection.Database'))->execute();
	}


	/**
	 * Finds out which is the shortest word that the fulltext can look for
	 *
	 * @return int the fulltext min word length
	 */
	private static function p_mysql_get_min_word_length()
	{
		// get the length of the word so we can get rid of a lot of rows
		$length_res = \DB::query("SHOW VARIABLES WHERE Variable_name = 'ft_min_word_len'")
			->as_object()->execute();
		return $length_res[0]->Value;
	}


	/**
	 * Create the supplementary search table and fill it with the comments
	 * Prefer this to the prefixed functions for future-proof database coverage
	 *
	 * @param object $board board object
	 * @return
	 */
	private static function p_create_search($board)
	{
		return $this->mysql_create_search($board);
	}


	/**
	 * Create the supplementary search table and fill it with the comments
	 * Does also a bit of magic not to store useless columns
	 *
	 * @param object $board board object
	 */
	private static function p_mysql_create_search($board)
	{
		// with true it gives the charset string directly
		$charset = $this->mysql_check_multibyte(true);

		\DB::query("
			CREATE TABLE IF NOT EXISTS ".$this->get_table($board, '_search')." (
				doc_id int unsigned NOT NULL auto_increment,
				num int unsigned NOT NULL,
				subnum int unsigned NOT NULL,
				thread_num int unsigned NOT NULL DEFAULT '0',
				media_filename text,
				comment text,

				PRIMARY KEY (doc_id),
				INDEX num_index (`num`),
				INDEX subnum_index (`subnum`),
				INDEX thread_num_subnum_index (`thread_num`),
				FULLTEXT media_filename_fulltext(`media_filename`),
				FULLTEXT comment_fulltext(`comment`)
			) engine=MyISAM CHARSET=".$charset.";
		")->execute();

		// get the minumum word length
		$word_length = $this->mysql_get_min_word_length();

		// save in the database the fact that this is a MyISAM
		$this->save(array('id' => $board->id, 'myisam_search' => 1));

		// fill only where there's a point to
		\DB::query("
			INSERT IGNORE INTO ".$this->get_table($board, '_search')."
			SELECT doc_id, num, subnum, thread_num, media_filename, comment
			FROM ".$this->get_table($board)."
			WHERE
				CHAR_LENGTH(media_filename) > :len
					OR
				CHAR_LENGTH(comment) > :len

		")->parameters(array(':len' => &$word_length))->execute();

		return true;
	}


	/**
	 * Drop the _search table
	 * Prefer this to the prefixed functions for future-proof database coverage
	 *
	 * @param object $board board object
	 */
	private static function p_remove_search($board)
	{
		return $this->mysql_remove_search($board);
	}


	/**
	 * Drop the _search table
	 * MySQL version
	 *
	 * @param object $board board object
	 */
	private static function p_mysql_remove_search($board)
	{
		\DB::query("DROP TABLE IF EXISTS ".$this->get_table($board, '_search'))->execute();

		// set in preferences that this is not a board with MyISAM search
		$this->save(array('id' => $board->id, 'myisam_search' => 0));

		return true;
	}


	/**
	 * Figures out if the table is already utf8mb4 or not
	 *
	 * @param object $board
	 * @param string $suffix the table suffix like _threads
	 * @return boolean true if the table is NOT utf8mb4
	 */
	private static function p_mysql_check_charset($board, $suffix)
	{
		// rather than using information_schema, for ease let's just check the output of the create table
		\DB::query('SHOW CREATE TABLE '.$this->get_table($board, $suffix))->execute();

		$row = $this->row_array();

		$create_table = $row['Create Table'];

		return strpos($create_table, 'CHARSET=utf8mb4') === false;
	}


	/**
	 * Convert to utf8mb4 if possible
	 *
	 * @param object $board board object
	 * @return bool TRUE on success, FALSE on failure (in case MySQL doesn't support multibyte)
	 */
	private static function p_mysql_change_charset($board)
	{
		// if utf8mb4 is not supported, stop the machines
		if (!$this->mysql_check_multibyte())
		{
			cli_notice('error',
				__('Your MySQL installation doesn\'t support multibyte characters. Update MySQL to version 5.5 or higher.'));
			return false;
		}

		// these take ages
		$tables = array('', '_threads', '_users');

		// also _search needs utf8mb4, but we need to add it separately not to create db errors
		if ($board->myisam_search)
		{
			$tables[] = '_search';
		}

		foreach ($tables as $table)
		{
			if ($this->mysql_check_charset($board, $table))
			{
				cli_notice('notice', sprintf(__('Converting %s to utfmb4'), $this->get_table($board, $table)));
				\DB::query("ALTER TABLE ".$this->get_table($board, $table)." CONVERT TO CHARACTER SET utf8mb4")->execute();
			}
		}

		cli_notice('notice', __('The tables have all been converted to utf8mb4'));
		return TRUE;
	}

}

/* end of file radix.php */