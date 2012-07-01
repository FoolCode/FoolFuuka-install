<?php

namespace Model;


class CommentDeleteWrongPass extends \FuelException {}


class Comment extends \Model\Model_Base
{

	/**
	 * Array of post numbers found in the database
	 *
	 * @var array
	 */
	private static $_posts = array();

	/**
	 * Array of backlinks found in the posts
	 *
	 * @var type
	 */
	private static $_backlinks = array();

	// global variables used for processing due to callbacks

	/**
	 * If the backlinks must be full URLs or just the hash
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var bool
	 */
	private $_backlinks_hash_only_url = FALSE;

	/**
	 * Sets the callbacks so they return URLs good for realtime updates
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var type
	 */
	private $_realtime = FALSE;
	private $_force_entries = FALSE;
	private $_forced_entries = array(
		'title_processed', 'name_processed', 'email_processed', 'trip_processed', 'media_orig_processed',
		'preview_orig_processed', 'media_filename_processed', 'media_hash_processed', 'poster_hash_processed',
		'original_timestamp', 'fourchan_date', 'comment', 'comment_processed'
	);

	public $board = null;

	public $doc_id = 0;
	public $poster_ip = null;
	public $num = 0;
	public $subnum = 0;
	public $thread_num = 0;
	public $op = 0;
	public $timestamp = 0;
	public $timestamp_expired = 0;
	public $capcode = 'N';
	public $email = null;
	public $name = null;
	public $trip = null;
	public $title = null;
	public $comment = null;
	public $delpass = null;
	public $poster_hash = null;

	public $media = null;


	public function __get($name)
	{
		if ($name != 'comment_processed' && substr($name, -10) === '_processed')
		{
			$processing_name = substr($name, 0, strlen($name) - 10);
			return $this->$name = e(@iconv('UTF-8', 'UTF-8//IGNORE', $this->$processing_name));
		}

		switch ($name)
		{
			case 'original_timestamp':
				$this->original_timestamp = $this->timestamp;
				$newyork = new \DateTime(date('Y-m-d H:i:s', $this->timestamp), new \DateTimeZone('America/New_York'));
				$utc = new \DateTime(date('Y-m-d H:i:s', $this->timestamp), new \DateTimeZone('UTC'));
				$diff = $newyork->diff($utc)->h;
				$this->timestamp = $this->timestamp + ($diff * 60 * 60);
				return $this->original_timestamp;
			case 'fourchan_date':
				return $this->fourchan_date = gmdate('n/j/y(D)G:i', $this->original_timestamp);
			case 'comment':
				return $this->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $this->comment);
			case 'comment_processed':

				return $this->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment());
		}



		return null;
	}


	public static function forge($post, &$board, $options = array())
	{
		if (is_array($post))
		{
			$array = array();
			foreach ($post as $p)
			{
				$array[] = static::forge($p, $board, $options);
			}

			return $array;
		}

		return new Comment($post, $board, $options);
	}


	public function __construct($post, $board, $options = array())
	{
		//parent::__construct();

		$this->board = & $board;

		if (\Auth::has_access('comment.reports'))
		{
			$this->_forced_entries[] = 'report_reason_processed';
		}

		$media_fields = Media::get_fields();
		$media = new \stdClass();

		foreach ($post as $key => $value)
		{
			if(!in_array($key, $media_fields))
			{
				$this->$key = $value;
			}
			else
			{
				$media->$key = $value;
			}
		}

		$this->media = Media::forge_from_comment($media, $this->board);

		foreach ($options as $key => $value)
		{
			$this->{'_'.$key} = $value;
		}

		$this->clean_fields();

		$num = $this->thread_num.($this->subnum ? ',' - $this->subnum : '');
		static::$_posts[$this->thread_num][] = $num;
	}


	/**
	 * Processes the comment, strips annoying data from moot, converts BBCode,
	 * converts > to greentext, >> to internal link, and >>> to crossboard link
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @return string the processed comment
	 */
	public function p_process_comment()
	{
		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		$html = Plugins::run_hook('fu_post_model_process_comment_greentext_result', array($html), 'simple');

		$comment = $this->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($this->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
			}
		}

		if ($this->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
			}
		}

		$comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', FALSE);

		// preg_replace_callback handle
		$this->current_board_for_prc = $this->board;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_crossboard_links'), $comment);

		$comment = static::auto_linkify($comment, 'url', TRUE);
		$comment = preg_replace($find, $html, $comment);
		//$comment = parse_bbcode($comment, ($this->board->archive && !$this->subnum));

		// additional formatting
		if ($this->board->archive && !$this->subnum)
		{
			// admin bbcode
			$admin_find = "'\[banned\](.*?)\[/banned\]'i";
			$admin_html = '<span class="banned">\\1</span>';

			$comment = preg_replace($admin_find, $admin_html, $comment);

			// literal bbcode
			$lit_find = array(
				"'\[banned:lit\]'i", "'\[/banned:lit\]'i",
				"'\[moot:lit\]'i", "'\[/moot:lit\]'i"
			);

			$lit_html = array(
				'[banned]', '[/banned]',
				'[moot]', '[/moot]'
			);

			$comment = preg_replace($lit_find, $lit_html, $comment);
		}

		return $this->comment_processed = nl2br(trim($comment));
	}


	/**
	 * A callback function for preg_replace_callback for internal links (>>)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	public function p_process_internal_links($matches)
	{
		$num = $matches[2];

		// create link object with all relevant information
		$data = new \stdClass();
		$data->num = str_replace(',', '_', $matches[2]);
		$data->board = $this->board;
		$data->post = $this;

		$current_p_num_c = $this->num . ($this->subnum > 0 ? ',' . $this->subnum : '');
		$current_p_num_u = $this->num . ($this->subnum > 0 ? '_' . $this->subnum : '');

		$build_url = array(
			'tags' => array('', ''),
			'hash' => '',
			'attr' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_op' => 'class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $current_p_num_u . '"',
		);

		$build_url = Plugins::run_hook('fu_post_model_process_internal_links_html_result', array($data, $build_url), 'simple');

		static::$_backlinks[$data->num][$this->num] = implode(
			'<a href="' . \Uri::create(array($data->board->shortname, 'thread', $data->post->thread_num)) . '#' . $build_url['hash'] . $current_p_num_u . '" ' .
			$build_url['attr_backlink'] . '>&gt;&gt;' . $current_p_num_c . '</a>'
		, $build_url['tags']);

		if (array_key_exists($num, static::$_posts))
		{
			if ($this->_backlinks_hash_only_url)
			{
				return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}

			return implode('<a href="' . \Uri::create(array($data->board->shortname, 'thread', $num)) . '#' . $data->num . '" '
				. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		foreach (static::$_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->_backlinks_hash_only_url)
				{
					return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
						. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
				}

				return implode('<a href="' . \Uri::create(array($data->board->shortname, 'thread', $key)) . '#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}
		}

		if ($this->realtime === TRUE)
		{
			return implode('<a href="' . \Uri::create(array($data->board->shortname, 'thread', $key)) . '#' . $build_url['hash'] . $data->num . '" '
				. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . (array($data->board->shortname, 'post', $data->num)) . '" '
			. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);

		// return un-altered
		return $matches[0];
	}


	/**
	 * A callback function for preg_replace_callback for crossboard links (>>>//)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	public function p_process_crossboard_links($matches)
	{
		// create link object with all relevant information
		$data = new \stdClass();
		$data->url = $matches[2];
		$data->num = $matches[4];
		$data->shortname = $matches[3];
		$data->board = $this->board;

		$build_url = array(
			'tags' => array('', ''),
			'backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . (($data->board) ? $data->board->shortname : $data->shortname) . '" data-post="' . $data->num . '"'
		);

		$build_url = Plugins::run_hook('fu_post_model_process_crossboard_links_html_result', array($data, $build_url), 'simple');

		if (!$data->board)
		{
			if ($data->num)
			{
				return implode('<a href="//boards.4chan.org/' . $data->shortname . '/res/' . $data->num . '">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
			}

			return implode('<a href="//boards.4chan.org/' . $data->shortname . '/">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
		}

		if ($data->num)
		{
			return implode('<a href="' . \Uri::create(array($data->board->shortname, 'post', $data->num)) . '" ' . $build_url['backlink'] . '>&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . \Uri::create($data->board->shortname) . '">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);

		// return un-altered
		return $matches[0];
	}


	/**
	 * Returns the HTML for the post with the currently selected theme
	 *
	 * @param object $board
	 * @param object $post database row for the post
	 * @return string the post box HTML with the selected theme
	 */
	private function p_build_board_comment()
	{
		return \Theme::build('board_comment', array('p' => $post), TRUE, TRUE);
	}



	/**
	 * This function is grabbed from Codeigniter Framework on which
	 * the original FoOlFuuka was coded on: http://codeigniter.com
	 * The function is modified tu support multiple subdomains and https
	 *
	 * @param type $str
	 * @param type $type
	 * @param type $popup
	 * @return type
	 */
	public static function auto_linkify($str, $type = 'both', $popup = FALSE)
	{
		if ($type != 'email')
		{
			if (preg_match_all("#(^|\s|\(|\])((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
			{
				$pop = ($popup == TRUE) ? " target=\"_blank\" " : "";

				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['6'][$i]))
					{
						$period = '.';
						$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
					}

					$internal = (strpos($matches['6'][$i], $_SERVER['HTTP_HOST']) === 0);

					if (!$internal && defined('FOOL_SUBDOMAINS_ENABLED') && FOOL_SUBDOMAINS_ENABLED == TRUE)
					{
						$subdomains = array(
							FOOL_SUBDOMAINS_SYSTEM,
							FOOL_SUBDOMAINS_BOARD,
							FOOL_SUBDOMAINS_ARCHIVE,
							FOOL_SUBDOMAINS_DEFAULT
						);

						foreach ($subdomains as $subdomain)
						{
							if (strpos($matches['6'][$i], rtrim($subdomain, '.')) === 0)
							{
								$host_array = explode('.', $_SERVER['HTTP_HOST']);
								array_shift($host_array);
								array_unshift($host_array, $subdomain);
								$host = implode('.', $host_array);
								if (strpos($matches['6'][$i], $host) === 0)
								{
									$internal = TRUE;
									break;
								}
							}
						}
					}

					if ($internal)
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="//' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
					else
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
				}
			}
		}

		if ($type != 'url')
		{
			if (preg_match_all("/([a-zA-Z0-9_\.\-\+]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['3'][$i]))
					{
						$period = '.';
						$matches['3'][$i] = substr($matches['3'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i],
						safe_mailto($matches['1'][$i] . '@' . $matches['2'][$i] . '.' . $matches['3'][$i]) . $period, $str);
				}
			}
		}

		return $str;
	}


	public function clean_fields()
	{
		if (!\Auth::has_access('maccess.mod'))
			unset($this->poster_ip);

		unset($this->delpass);
	}


	/**
	 * Delete the post and eventually the entire thread if it's OP
	 * Also deletes the images when it's the only post with that image
	 *
	 * @return array|bool
	 */
	private function p_delete($password = null, $force)
	{
		if(!\Auth::has_access('comment.passwordless_deletion'))
		{
			if ( ! class_exists('PHPSecLib\\Crypt_Hash', false))
			{
				import('phpseclib/Crypt/Hash', 'vendor');
			}

			$hasher = new \PHPSecLib\Crypt_Hash();

			$hashed = base64_encode($hasher->hasher()->pbkdf2($password, \Config::get('auth.salt'), 10000, 32));

			if($this->delpass != $hashed)
			{
				throw new \CommentDeleteWrongPass;
			}
		}

		// remove message
		\DB::delete(\DB::expr(Radix::get_table($this->board)))->where('doc_id', $this->doc_id)->execute();

		// remove message search entry
		if($this->board->myisam_search)
		{
			\DB::delete(\DB::expr(Radix::get_table($this->board, '_search')))->where('doc_id', $this->doc_id)->execute();
		}

		// remove message reports
		\DB::delete('reports')->where('board_id', $this->board->id)->where('doc_id', $this->doc_id)->execute();

		// remove its image file
		$this->delete_media();

		// if it's OP delete all other comments
		if ($this->op)
		{
			$to_delete = \DB::select()->from(\DB::expr(Radix::get_table($this->board)));
			\Board::sql_media_join($this->board, $to_delete);
			$to_delete_arr = $to_delete->where('thread_num', $this->thread_num)->as_object()->execute()->as_array();
			$posts = Comment::forge($to_delete_arr);

			foreach($posts as $post)
				$post->delete(null, true);
		}
	}


	/**
	 * Processes the name with unprocessed tripcode and returns name and processed tripcode
	 *
	 * @return array name without tripcode and processed tripcode concatenated with processed secure tripcode
	 */
	private function p_process_name()
	{
		$name = $this->name;

		// define variables
		$matches = array();
		$normal_trip = '';
		$secure_trip = '';

		if (preg_match("'^(.*?)(#)(.*)$'", $this->name, $matches))
		{
			$matches_trip = array();
			$name = trim($matches[1]);

			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

			if (count($matches_trip) > 1)
			{
				$normal_trip = static::process_tripcode($matches_trip[1]);
				$normal_trip = $normal_trip ? '!' . $normal_trip : '';
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . static::process_secure_tripcode($matches_trip[2]);
			}
		}

		$this->name = $name;
		$this->trip = $normal_trip . $secure_trip;

		return array('name' => $name, 'trip' => $normal_trip . $secure_trip);
	}


	/**
	 * Processes the tripcode
	 *
	 * @param string $plain the word to generate the tripcode from
	 * @return string the processed tripcode
	 */
	private static function p_process_tripcode($plain)
	{
		if (trim($plain) == '')
		{
			return '';
		}

		$trip = mb_convert_encoding($plain, 'SJIS', 'UTF-8');

		$salt = substr($trip . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);
		$salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

		return substr(crypt($trip, $salt), -10);
	}


	/**
	 * Process the secure tripcode
	 *
	 * @param string $plain the word to generate the secure tripcode from
	 * @return string the processed secure tripcode
	 */
	private function p_process_secure_tripcode($plain)
	{
		return substr(base64_encode(sha1($plain . base64_decode(FOOLFUUKA_SECURE_TRIPCODE_SALT), TRUE)), 0, 11);
	}


	/**
	 * Send the comment and attached media to database
	 *
	 * @param object $board
	 * @param array $data the comment data
	 * @param array $options modifiers
	 * @return array error key with explanation to show to user, or success and post row
	 */
	private function p_comment()
	{
		// check: if passed stopforumspam, check if banned internally
		$check = \DB::select()->from('posters')->where('ip', \Input::ip_decimal())
			->limit(1)->as_object()->execute();

		if (count($check))
		{
			$row = $check->current();

			if ($row->banned && !\Auth::has_access('comment.limitless_comment'))
			{
				if ($this->media)
				{
					if (!unlink($this->media['full_path']))
					{
						throw new \CommentImpossibleDeletingTempImage;
					}
				}

				throw new \CommentSendingWhileBanned;
			}
		}

		if($data['num'] == 0 && !\Auth::has_access('comment.limitless_comment'))
		{
			// one can create a new thread only once every 5 minutes
			$check_op = \DB::select()->from(DB::expr(Radix::get_table($board)))
				->where('poster_ip', Input::ip_decimal())->where('timestamp', '>', time() - 300)
				->where('op', 1)->limit(1);

			if(count($check_op))
			{
				throw new \CommentSendingNewThreadTimeLimit;
			}
		}

		// check the latest posts by the user to see if he's posting the same message or if he's posting too fast
		$check = \DB::select()->from(DB::expr(Radix::get_table($this->board)))
			->where('poster_ip', Input::ip_decimal())
			->order_by('timestamp', 'desc')->limit(1)
			->as_object()->execute();

		if (count($check))
		{
			$row = $check->current();

			if ($this->comment && $row->comment == $this->comment && \Auth::has_access('comment.limitless_comment'))
			{
				throw new \CommentSendingSameComment;
			}

			if (time() - $row->timestamp < 10 && time() - $row->timestamp > 0 && !\Auth::has_access('comment.limitless_comment'))
			{
				throw new \CommentSendingNewCommentTimeLimit;
			}

		}

		// hook entire comment data to alter in plugin
		Plugins::run_hook('fu.comment.comment.input', array(&$this), 'simple');

		// process comment name+trip
		if ($this->name === FALSE || $this->name == '')
		{
			\Cookie::set('reply_name', '', 0);
			$this->name = $this->board->anonymous_default_name;
			$this->trip = '';
		}
		else
		{
			// store name in cookie to repopulate forms
			\Cookie::set('reply_name', $data['name'], 60 * 60 * 24 * 30);
			$this->process_name();
		}

		// process comment email
		if ($this->email === FALSE || $this->email == '')
		{
			\Cookie::set('reply_email', '', 0);
			$this->email = '';
		}
		else
		{
			// store email in cookie to repopulate forms
			if ($this->email != 'sage')
			{
				\Cookie::set('reply_email', $this->email, 60 * 60 * 24 * 30);
			}

		}

		// process comment password
		if ($this->delpass === FALSE || $this->delpass == '')
		{
			throw new \CommentSendingNoDelPass;
		}
		else
		{
			// store password in cookie to repopulate forms
			\Cookie::set('reply_password', $data['password'], 60 * 60 * 24 * 30);
		}

		// load the spam list and check comment, name, subject and email
		$spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents(DOCROOT.'assets/anti-spam/databases')));
		foreach($spam as $s)
		{
			if(strpos($comment, $s) !== FALSE || strpos($name, $s) !== FALSE
				|| strpos($subject, $s) !== FALSE || strpos($email, $s) !== FALSE)
			{
				throw new \CommentSendingSpam;
			}
		}

		// process comment ghost+spoiler
		$ghost = isset($this->ghost) && $this->ghost === TRUE;

		// we want to know if the comment will display empty, and in case we won't let it pass
		if($this->comment !== '')
		{
			$comment_parsed = $this->process_comment();
			if(!$comment_parsed)
			{
				throw new \CommentSendingDisplaysEmpty;
			}

		}

		// process comment media
		if (!isset($this->media))
		{
			// if no media is present, remove spoiler setting
			$this->spoiler = 0;

			// if no media is present and post is op, stop processing
			if ($data['num'] == 0)
			{
				throw new \CommentSendingThreadWithoutMedia;
			}
		}
		else
		{
			// check other media errors
			if (isset($this->media['media_error']))
			{
				// invalid file type
				if (strlen($this->media['media_error']) == 64)
				{
					throw new \CommentSendingMimeNotAllowed;
				}

				// media file is too large
				if (strlen($this->media['media_error']) == 79)
				{
					throw new \CommentSendingFileTooLarge;
				}
			}

			// check for valid media dimensions
			if ($this->media['image_width'] > 25 || $this->media['image_height'] > 25)
			{
				throw new \CommentSendingImageTooSmall;
			}

			// generate media hash
			$media_hash = base64_encode(pack("H*", md5(file_get_contents($this->media['full_path']))));


			// check if media is banned
			$media_banned_check = \DB::select()->from('banned_md5')->where('md5', $this->media['media_hash'])->execute();

			if (count($media_banned_check))
			{
				throw new \CommentSendingBannedMedia;
			}
		}

		// check entire length of comment
		if (mb_strlen($comment) > 4096)
		{
			throw new \CommentSendingTooManyCharacters;
		}

		// check total numbers of lines in comment
		if (count(explode("\n", $comment)) > 20)
		{
			throw new \CommentSendingTooManyLines;
		}

		if ( ! class_exists('PHPSecLib\\Crypt_Hash', false))
		{
			import('phpseclib/Crypt/Hash', 'vendor');
		}

		$hasher = new \PHPSecLib\Crypt_Hash();
		$this->delpass = base64_encode($hasher->hasher()->pbkdf2($password, \Config::get('auth.salt'), 10000, 32));

		$this->timestamp = time();

		// 2ch-style codes, only if enabled
		if($this->board->enable_poster_hash)
		{
			$this->poster_hash = substr(substr(crypt(md5(\Input::ip().'id'.$num),'id'),+3), 0, 8);
		}

		if($this->board->archive)
		{
			// archives are in new york time
			$newyork = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('America/New_York'));
			$utc = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$timestamp = time() - ($diff * 60 * 60);
		}




		$media_file = $this->process_media($board, $num, $media, $media_hash);
		$default_post_arr[4] = substr($media_file['unixtime'],0,10);
				unset($media_file['unixtime']);
				$default_post_arr = array_merge($default_post_arr, array_values($media_file));





		$this->db->trans_begin();

		// being processing insert...

		if($ghost)
		{
			$num = \DB::expr('
				(SELECT MAX(num)
				FROM
				(
					SELECT num
					FROM '.Radix::get_table($this->board).'
					WHERE thread_num = '.intval($this->thread_num).'
				) AS x)
			');

			$subnum = \DB::expr('
				(SELECT MAX(subnum)+1
				FROM
				(
					SELECT subnum
					FROM ' . Radix::get_table($board) . '
					WHERE
						num = (
							SELECT MAX(num)
							FROM ' . Radix::get_table($board) . '
							WHERE thread_num = '.intval($this->thread_num).'
						)
				) AS x)
			');

			$thread_num = $this->thread_num;
		}
		else
		{
			$num = \DB::expr('
				(SELECT COALESCE(MAX(num), 0)+1 AS num
				FROM
				(
					SELECT num
					FROM '.Radix::get_table($this->board).'
				) AS x)
			');

			$subnum = 0;

			$thread_num = \DB::expr('
				(IF(?, (
				SELECT COALESCE(MAX(num), 0)+1 AS num
				FROM
				(
					SELECT num
					FROM '.Radix::get_table($this->board).'
				) AS x)
			');
		}



		list($last_id, $num_affected) =
			\DB::insert(\DB::expr(Radix::get_table($this->board)))
			->set(array(
				'num' => $num,
				'subnum' => $subnum,
				'thread_num' => $thread_num,
				'op' => $this->op,
				'timestamp' => $this->timestamp,
				'capcode' => $this->capcode,
				'email' => $this->email,
				'name' => $this->name,
				'trip' => $this->trip,
				'title' => $this->title,
				'comment' => $this->comment,
				'delpass' => $this->delpass,
				'spoiler' => $this->spoiler,
				'poster_ip' => $this->poster_ip,
				'poster_hash' => $this->poster_hash,
				'preview_orig' => $this->media->preview_orig,
				'preview_w' => $this->media->preview_w,
				'preview_h' => $this->media->preview_h,
				'media_filename' => $this->media->media_filename,
				'media_w' => $this->media->media_w,
				'media_h' => $this->media->media_h,
				'media_size' => $this->media->media_size,
				'media_hash' => $this->media->media_hash,
				'media_orig' => $this->media->media_orig,
				'exif' => $this->media->exif
			))->execute();

		// check that it wasn't posted multiple times
		$check_duplicate = \DB::select()->from(\DB::expr(Radix::get_table($this->board)))
			->where('poster_ip', Input::ip_decimal())->where('comment', $this->comment)
			->where('timestamp', '>=', $this->timestamp)->as_object()->execute();

		if(count($check_duplicate) > 1)
		{
			$this->db->trans_rollback();
			throw new \CommentSendingDuplicate;
		}

		$comment = $check_duplicate->current();

		// update poster_hash for non-ghost posts
		if (!$ghost && $this->op && $this->board->enable_poster_hash)
		{
			$hash = substr(substr(crypt(md5(Input::ip().'id'.$comment->thread_num),'id'),+3), 0, 8);

			\DB::update(\DB::exec(Radix::get_table($this->board)))
				->value('poster_hash', $hash)->where('doc_id', $comment->doc_id)->execute();
		}

		$this->db->trans_commit();

		// success, now check if there's extra work to do

		// we might be using the local MyISAM search table which doesn't support transactions
		// so we must be really careful with the insertion
		if($this->board->myisam_search)
		{
			\DB::insert(Radix::get_table($this->board, '_search'))
				->set(array(
					'doc_id' => $comment->doc_id,
					'num' => $comment->num,
					'subnum' => $comment->subnum,
					'thread_num' => $comment->thread_num,
					'media_filename' => $comment->media_filename,
					'comment' => $comment->comment
				))->execute();
		}

		return array('success' => TRUE, 'posted' => $comment);
	}

}