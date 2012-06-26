<?php

namespace Model;

/**
 * FoOlFuuka Post Model
 *
 * The Post Model deals with all the data in the board tables and
 * the media folders. It also processes the post for display.
 *
 * @package        	FoOlFrame
 * @subpackage    	FoOlFuuka
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Comment extends \Model
{
	// store all relavent data regarding posts displayed

	/**
	 * Array of post numbers found in the database
	 *
	 * @var array
	 */
	private static $posts_arr = array();

	/**
	 * Array of backlinks found in the posts
	 *
	 * @var type
	 */
	private static $backlinks = array();

	// global variables used for processing due to callbacks

	/**
	 * If the backlinks must be full URLs or just the hash
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var bool
	 */
	private $backlinks_hash_only_url = FALSE;

	/**
	 * Sets the callbacks so they return URLs good for realtime updates
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var type
	 */
	private $realtime = FALSE;

	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = Plugins::run_hook('model/comment/call/before/'.$name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = Plugins::run_hook('model/comment/call/replace/'.$name, $parameters, array($parameters));

		if($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters)) {
				case 0:
					$return = $this->{'p_' . $name}();
					break;
				case 1:
					$return = $this->{'p_' . $name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
				break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = Plugins::run_hook('model/comment/call/after/'.$name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * Puts in the $posts_arr class variable the number of the posts that
	 * we for sure know exist since we've fetched them once during processing
	 *
	 * @param array|object $posts
	 */
	private function p_populate_posts_arr($post)
	{
		if (is_array($post))
		{
			foreach ($post as $p)
			{
				$this->populate_posts_arr($p);
			}
		}

		if (is_object($post))
		{
			if ($post->op == 1)
			{
				$this->posts_arr[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->posts_arr[$post->thread_num][] = $post->num;
				else
					$this->posts_arr[$post->thread_num][] = $post->num . ',' . $post->subnum;
			}
		}
	}


	/**
	 * Get the path to the media
	 *
	 * @param object $board
	 * @param object $post the database object for the post
	 * @param bool $thumbnail if we're looking for a thumbnail
	 * @return bool|string FALSE if it has no image in database, string for the path
	 */
	private function p_get_media_dir($board, $post, $thumbnail = FALSE)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		if ($thumbnail === TRUE)
		{
			if (isset($post->op) && $post->op == 1)
			{
				$image = $post->preview_op ? $post->preview_op : $post->preview_reply;
			}
			else
			{
				$image = $post->preview_reply ? $post->preview_reply : $post->preview_op;
			}
		}
		else
		{
			$image = $post->media;
		}

		// if we don't check, the return will return a valid folder that will evaluate file_exists() as TRUE
		if(is_null($image))
		{
			return FALSE;
		}

		return Preferences::get('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/'
			. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
	}


	/**
	 * Get the full URL to the media, and in case switch between multiple CDNs
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @param bool $thumbnail if it's a thumbnail we're looking for
	 * @return bool|string FALSE on not found, a fallback image if not found for thumbnails, or the URL on success
	 */
	private function p_get_media_link($board, &$post, $thumbnail = FALSE)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		$post->media_status = 'available';

		// these features will only affect guest users
		if ($board->hide_thumbnails && !$this->auth->is_mod_admin())
		{
			// hide all thumbnails for the board
			if (!$board->hide_thumbnails)
			{
				$post->media_status = 'forbidden';
				return FALSE;
			}

			// add a delay of 1 day to all thumbnails
			if ($board->delay_thumbnails && isset($post->timestamp) && ($post->timestamp + 86400) > time())
			{
				$post->media_status = 'forbidden-24h';
				return FALSE;
			}
		}

		// this post contain's a banned media, do not display
		if ($post->banned == 1)
		{
			$post->media_status = 'banned';
			return FALSE;
		}

		// locate the image
		if ($thumbnail && file_exists($this->get_media_dir($board, $post, $thumbnail)) !== FALSE)
		{
			if (isset($post->op) && $post->op == 1)
			{
				$image = $post->preview_op ? : $post->preview_reply;
			}
			else
			{
				$image = $post->preview_reply ? : $post->preview_op;
			}
		}

		// full image
		if (!$thumbnail && file_exists($this->get_media_dir($board, $post, FALSE)))
		{
			$image = $post->media;
		}

		// fallback if we have the full image but not the thumbnail
		if ($thumbnail && !isset($image) && file_exists($this->get_media_dir($board, $post, FALSE)))
		{
			$thumbnail = FALSE;
			$image = $post->media;
		}

		if(isset($image))
		{
			$media_cdn = array();
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && Preferences::get('fs_fuuka_boards_media_balancers_https'))
			{
				$balancers = Preferences::get('fs_fuuka_boards_media_balancers_https');
			}

			if (!isset($balancers) && Preferences::get('fs_fuuka_boards_media_balancers'))
			{
				$balancers = Preferences::get('fs_fuuka_boards_media_balancers');
			}

			if(isset($balancers))
			{
				$media_cdn = array_filter(preg_split('/\r\n|\r|\n/', $balancers));
			}

			if(!empty($media_cdn) && $post->media_id > 0)
			{
				return $media_cdn[($post->media_id % count($media_cdn))] . '/' . $board->shortname . '/'
					. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
			}

			return Preferences::get('fs_fuuka_boards_url', site_url()) . '/' . $board->shortname . '/'
				. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
		}

		$post->media_status = 'not-available';
		return FALSE;
	}


	/**
	 * Get the remote link for media if it's not local
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @return bool|string FALSE if there's no media, local URL if it's not remote, or the remote URL
	 */
	private function p_get_remote_media_link($board, $post)
	{
		if (!$post->media_hash)
		{
			return FALSE;
		}

		if ($board->archive && $board->images_url != "")
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $board->images_url . $post->media_orig;
			}

			return site_url(array($board->shortname, 'redirect')) . $post->media_orig;
		}
		else
		{
			if (file_exists($this->get_media_dir($board, $post)) !== FALSE)
			{
				return $this->get_media_link($board, $post);
			}
			else
			{
				return FALSE;
			}
		}
	}


	/**
	 * Get the post's media hash
	 *
	 * @param mixed $media
	 * @param bool $urlsafe if TRUE it will return a modified base64 compatible with URL
	 * @return bool|string FALSE if media_hash not found, or the base64 string
	 */
	private function p_get_media_hash($media, $urlsafe = FALSE)
	{
		if (is_object($media) || is_array($media))
		{
			if (!$media->media_hash)
			{
				return FALSE;
			}

			$media = $media->media_hash;
		}
		else
		{
			if (strlen(trim($media)) == 0)
			{
				return FALSE;
			}
		}

		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === TRUE)
		{
			return urlsafe_b64encode(urlsafe_b64decode($media));
		}
		else
		{
			return base64_encode(urlsafe_b64decode($media));
		}
	}


	/**
	 * Processes the name with unprocessed tripcode and returns name and processed tripcode
	 *
	 * @param string $name name and unprocessed tripcode and unprocessed secure tripcode
	 * @return array name without tripcode and processed tripcode concatenated with processed secure tripcode
	 */
	private function p_process_name($name)
	{
		// define variables
		$matches = array();
		$normal_trip = '';
		$secure_trip = '';

		if (preg_match("'^(.*?)(#)(.*)$'", $name, $matches))
		{
			$matches_trip = array();
			$name = trim($matches[1]);

			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

			if (count($matches_trip) > 1)
			{
				$normal_trip = $this->process_tripcode($matches_trip[1]);
				$normal_trip = $normal_trip ? '!' . $normal_trip : '';
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . $this->process_secure_tripcode($matches_trip[2]);
			}
		}

		return array($name, $normal_trip . $secure_trip);
	}


	/**
	 * Processes the tripcode
	 *
	 * @param string $plain the word to generate the tripcode from
	 * @return string the processed tripcode
	 */
	private function p_process_tripcode($plain)
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
	 * Process the entirety of the post so it can be safely used in views.
	 * New variables with _processed are created for all the data to be displayed.
	 *
	 * @param object $board
	 * @param object $post object from the database
	 * @param bool $clean remove sensible data from the $post object
	 * @param bool $build build the post box according to the selected theme
	 */
	private function p_process_post($board, &$post, $clean = TRUE, $build = FALSE)
	{
		$this->load->helper('text');
		$this->current_p = $post;

		$post->safe_media_hash = $this->get_media_hash($post, TRUE);
		$post->remote_media_link = $this->get_remote_media_link($board, $post);
		$post->media_link = $this->get_media_link($board, $post);
		$post->thumb_link = $this->get_media_link($board, $post, TRUE);
		$post->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment($board, $post));
		$post->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);

		// gotta change the timestamp of the archives to GMT and fix images
		if ($board->archive)
		{
			// fallback if the preview size because spoiler thumbnails in archive may not have sizes
			if ($post->spoiler && $post->preview_w == 0)
			{
				if(!$imgsize = $this->cache->get('foolfuuka_' .
					config_item('random_id') . '_board_' .
					$board->id . '_spoiler_size_' . $post->media_orig)
				)
				{
					$imgpath = $this->get_media_dir($board, $post, TRUE);
					$imgsize = FALSE;

					if(file_exists($imgpath))
					{
						$imgsize = @getimagesize($imgpath);
					}
					$this->cache->save('foolfuuka_' .
						config_item('random_id') . '_board_' .
						$board->id . '_spoiler_size_' . $post->media_orig, $imgsize, 86400);
				}

				if($imgsize !== FALSE)
				{
					$post->preview_h = $imgsize[1];
					$post->preview_w = $imgsize[0];
				}
			}

			$post->original_timestamp = $post->timestamp;
			$newyork = new DateTime(date('Y-m-d H:i:s', $post->timestamp), new DateTimeZone('America/New_York'));
			$utc = new DateTime(date('Y-m-d H:i:s', $post->timestamp), new DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$post->timestamp = $post->timestamp + ($diff * 60 * 60);
			$post->fourchan_date = gmdate('n/j/y(D)G:i', $post->original_timestamp);
		}
		else
		{
			$post->original_timestamp = $post->timestamp;
		}

		// Asagi currently inserts the media_filename in DB with 4chan escaping, we decode it and reencode in case
		if($board->archive)
		{
			$post->media_filename = html_entity_decode($post->media_filename, ENT_QUOTES, 'UTF-8');
		}

		$elements = array('title', 'name', 'email', 'trip', 'media_orig',
			'preview_orig', 'media_filename', 'media_hash', 'poster_hash');

		if ($this->auth->is_mod_admin() && isset($post->report_reason))
		{
			array_push($elements, 'report_reason');
		}

		foreach($elements as $element)
		{
			$post->{$element . '_processed'} = @iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = @iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
		}

		// remove both ip and delpass from public view
		if ($clean === TRUE)
		{
			if (!$this->auth->is_mod_admin())
			{
				unset($post->poster_ip);
			}

			unset($post->delpass);
		}

		if ($build === TRUE && $this->theme->get_selected_theme())
		{
			$post->formatted = $this->build_board_comment($board, $post);
		}
	}


	/**
	 * Manipulate the sent media and store it if there is no same media in the database
	 * Notice: currently works only with images
	 * Notice: this is not a view output function, this is a function to insert in database!
	 *
	 * @param object $board
	 * @param object $post database row for the post
	 * @param array $file the file array from the CodeIgniter upload class
	 * @param string $media_hash the media_hash for the media
	 * @param null|object used in recursion if the media is found in the database
	 * @return array|bool An array of data necessary for the board database insert
	 */
	private function p_process_media($board, $post_id, $file, $media_hash, $duplicate = NULL)
	{
		// only allow media on internal boards
		if ($board->archive)
		{
			return FALSE;
		}

		$preliminary_check = @getimagesize($file['full_path']);

		if(!$preliminary_check)
		{
			return array('error' => __('The file you submitted doesn\'t seem to be an image.'));
		}

		// if width and height are lower than 25 reject the image
		if($preliminary_check[0] < 25 || $preliminary_check[1] < 25)
		{
			return array('error' => __('The image you submitted is too small.'));
		}


		// default variables
		$media_exists = FALSE;
		$thumb_exists = FALSE;

		// only run the check when iterated with duplicate
		if ($duplicate === NULL)
		{
			// check *_images table for media hash
			$check = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE media_hash = ?
				LIMIT 0, 1
			',
				array($media_hash)
			);

			// if exists, re-run process with duplicate set
			if ($check->num_rows() > 0)
			{
				$check_row = $check->row();

				// do we have some image reposting constraint?
				if($board->min_image_repost_hours == 0 || $this->auth->is_mod_admin())
				{
					// do nothing, 0 means that there's no time constraint
					// also admins and mods can repost however mich they want
				}
				else if($board->min_image_repost_hours == -1)
				{
					// don't allow reposting, ever
					return array('error' =>
						__('This image has already been posted once. This board doesn\'t allow image reposting'));
				}
				else
				{
					// check if there's a recent image with the same media_id
					$constraint = $this->db->query('
						SELECT *
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_id = ? AND timestamp > ?
					', array($check_row->media_id, time() - $board->min_image_repost_hours * 60 * 60));

					if($constraint->num_rows() > 0)
					{
						return array('error' => sprintf(
							__('You must wait up to %s hours to repost this image.'),
							$board->min_image_repost_hours)
						);
					}
				}

				return $this->process_media($board, $post_id, $file, $media_hash, $check_row);
			}
		}

		// generate unique filename with timestamp, this will be stored with the post
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($file['file_ext']);
		$thumb_filename = $media_unixtime . 's' . strtolower($file['file_ext']);

		// set default locations of media directories and image directory structure
		$board_directory = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/';
		$thumb_filepath = $board_directory . 'thumb/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';
		$media_filepath = $board_directory . 'image/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';

		// PHP must be compiled with --enable-exif
		// exif can be grabbed only from jpg and tiff
		if(function_exists('exif_read_data')
			&& in_array(strtolower(trim($file['file_ext'], '.')), array('jpg', 'jpeg', 'tiff')))
		{
			$exif = exif_read_data($file['full_path']);

			if($exif === FALSE)
			{
				$exif = NULL;
			}
		}
		else
		{
			$exif = NULL;
		}

		// check for any type of duplicate records or information and override default locations
		if ($duplicate !== NULL)
		{
			// handle full media
			if ($duplicate->media !== NULL)
			{
				$media_exists = TRUE;

				$media_existing = $duplicate->media;
				$media_filepath = $board_directory . 'image/'
					. substr($duplicate->media, 0, 4) . '/' . substr($duplicate->media, 4, 2) . '/';
			}

			// generate full file paths for missing files only
			if ($duplicate->media === NULL || file_exists($media_filepath . $duplicate->media) === FALSE)
			{
				if(!file_exists($media_filepath))
					mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			}

			// handle thumbs
			if ($post_id == 0)
			{
				// thumb op
				if ($duplicate->preview_op !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_op;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_op, 0, 4) . '/' . substr($duplicate->preview_op, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_op === NULL || file_exists($media_filepath . $duplicate->preview_op) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
			else
			{
				// thumb re
				if ($duplicate->preview_reply !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_reply;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_reply, 0, 4) . '/' . substr($duplicate->preview_reply, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_reply === NULL || file_exists($media_filepath . $duplicate->preview_reply) === FALSE)
				{
					if(!file_exists($thumb_filepath))
						mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
				}
			}
		}
		else
		{
			// generate full file paths for everything
			if(!file_exists($media_filepath))
				mkdir($media_filepath, FOOL_FILES_DIR_MODE, TRUE);
			if(!file_exists($thumb_filepath))
				mkdir($thumb_filepath, FOOL_FILES_DIR_MODE, TRUE);
		}

		// relocate the media file to proper location
		if (!copy($file['full_path'], $media_filepath . (($media_exists) ? $media_existing : $media_filename)))
		{
			log_message('error', 'post.php/process_media: failed to move media file');
			return FALSE;
		}

		// remove the media file
		if (!unlink($file['full_path']))
		{
			log_message('error', 'post.php/process_media: failed to remove media file from cache directory');
		}

		// determine the correct thumbnail dimensions
		if ($post_id == 0)
		{
			$thumb_width = $board->thumbnail_op_width;
			$thumb_height = $board->thumbnail_op_height;
		}
		else
		{
			$thumb_width = $board->thumbnail_reply_width;
			$thumb_height = $board->thumbnail_reply_height;
		}

		// generate thumbnail
		$imagemagick = locate_imagemagick();
		$media_config = array(
			'image_library' => ($imagemagick) ? 'ImageMagick' : 'GD2',
			'library_path'  => ($imagemagick) ? $this->ff_imagemagick->path : '',
			'source_image'  => $media_filepath . (($media_exists) ? $media_existing : $media_filename),
			'new_image'     => $thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename),
			'width'         => ($file['image_width'] > $thumb_width) ? $thumb_width : $file['image_width'],
			'height'        => ($file['image_height'] > $thumb_height) ? $thumb_height : $file['image_height'],
		);

		// leave this NULL so it processes normally
		$switch = Plugins::run_hook('fu_post_model_process_media_switch_resize', array($media_config));

		// if plugin returns false, error
		if(isset($switch['return']) && $switch['return'] === FALSE)
		{
			log_message('error', 'post.php/process_media: failed to generate thumbnail');
			return FALSE;
		}

		if(is_null($switch) || is_null($switch['return']))
		{
			$this->load->library('image_lib');

			$this->image_lib->initialize($media_config);
			if (!$this->image_lib->resize())
			{
				log_message('error', 'post.php/process_media: failed to generate thumbnail');
				return FALSE;
			}

			$this->image_lib->clear();
		}

		$thumb_dimensions = @getimagesize($thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename));

		return array(
			'preview_orig' => $thumb_filename,
			'thumb_width' => $thumb_dimensions[0],
			'thumb_height'=> $thumb_dimensions[1],
			'media_filename' => $file['file_name'],
			'width' => $file['image_width'],
			'height'=> $file['image_height'],
			'size' => floor($file['file_size'] * 1024),
			'media_hash' => $media_hash,
			'media_orig' => $media_filename,
			'exif' => !is_null($exif)?json_encode($exif):NULL,
			'unixtime' => $media_unixtime,
		);
	}


	/**
	 * Processes the comment, strips annoying data from moot, converts BBCode,
	 * converts > to greentext, >> to internal link, and >>> to crossboard link
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @return string the processed comment
	 */
	private function p_process_comment($board, $post)
	{
		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		$html = Plugins::run_hook('fu_post_model_process_comment_greentext_result', array($html), 'simple');

		$comment = $post->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($post->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
			}
		}

		if ($post->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
			}
		}

		$comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', FALSE);

		// preg_replace_callback handle
		$this->current_board_for_prc = $board;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_crossboard_links'), $comment);

		$comment = auto_linkify($comment, 'url', TRUE);
		$comment = preg_replace($find, $html, $comment);
		$comment = parse_bbcode($comment, ($board->archive && !$post->subnum));

		// additional formatting
		if ($board->archive && !$post->subnum)
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

		return nl2br(trim($comment));
	}


	/**
	 * A callback function for preg_replace_callback for internal links (>>)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	private function p_process_internal_links($matches)
	{
		// this is a patch not to have the comment() check spouting errors
		// since this is already a fairly bad (but unavoidable) solution, let's keep the dirt in this function
		if(!isset($this->current_p))
		{
			return $matches[0];
		}

		$num = $matches[2];

		// create link object with all relevant information
		$data = new stdClass();
		$data->num = str_replace(',', '_', $matches[2]);
		$data->board = $this->current_board_for_prc;
		$data->post = $this->current_p;

		$current_p_num_c = $this->current_p->num . (($this->current_p->subnum > 0) ? ',' . $this->current_p->subnum : '');
		$current_p_num_u = $this->current_p->num . (($this->current_p->subnum > 0) ? '_' . $this->current_p->subnum : '');

		$build_url = array(
			'tags' => array('', ''),
			'hash' => '',
			'attr' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_op' => 'class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $current_p_num_u . '"',
		);

		$build_url = Plugins::run_hook('fu_post_model_process_internal_links_html_result', array($data, $build_url), 'simple');

		$this->backlinks[$data->num][$this->current_p->num] = implode(
			'<a href="' . site_url(array($data->board->shortname, 'thread', $data->post->thread_num)) . '#' . $build_url['hash'] . $current_p_num_u . '" ' .
			$build_url['attr_backlink'] . '>&gt;&gt;' . $current_p_num_c . '</a>'
		, $build_url['tags']);

		if (array_key_exists($num, $this->posts_arr))
		{
			if ($this->backlinks_hash_only_url)
			{
				return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}

			return implode('<a href="' . site_url(array($data->board->shortname, 'thread', $num)) . '#' . $data->num . '" '
				. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		foreach ($this->posts_arr as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->backlinks_hash_only_url)
				{
					return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
						. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
				}

				return implode('<a href="' . site_url(array($data->board->shortname, 'thread', $key)) . '#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}
		}

		if ($this->realtime === TRUE)
		{
			return implode('<a href="' . site_url(array($data->board->shortname, 'thread', $key)) . '#' . $build_url['hash'] . $data->num . '" '
				. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . site_url(array($data->board->shortname, 'post', $data->num)) . '" '
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
	private function p_process_crossboard_links($matches)
	{
		// create link object with all relevant information
		$data = new stdClass();
		$data->url = $matches[2];
		$data->num = $matches[4];
		$data->shortname = $matches[3];
		$data->board = $this->radix->get_by_shortname($data->shortname);

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
			return implode('<a href="' . site_url(array($data->board->shortname, 'post', $data->num)) . '" ' . $build_url['backlink'] . '>&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . site_url($data->board->shortname) . '">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);

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
	private function p_build_board_comment($board, $post)
	{
		return $this->theme->build('board_comment', array('p' => $post), TRUE, TRUE);
	}


	/**
	 * Delete the post and eventually the entire thread if it's OP
	 * Also deletes the images when it's the only post with that image
	 *
	 * @param object $board
	 * @param array $post the post data necessary for deletion (password, doc_id)
	 * @return array|bool
	 */
	private function p_delete($board, $post)
	{
		// $post => [doc_id, password, type]
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($post['doc_id'])
		);

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'post.php/delete: invalid doc_id for post or thread');
			return array('error' => __('There\'s no such a post to be deleted.'));
		}

		// store query results
		$row = $query->row();

		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);

		// validate password
		if ($phpass->CheckPassword($post['password'], $row->delpass) !== TRUE && !$this->auth->is_mod_admin())
		{
			log_message('debug', 'post.php/delete: invalid password');
			return array('error' => __('The password you inserted did not match the post\'s deletion password.'));
		}

		// delete media file for post
		if ($row->total == 1 && !$this->delete_media($board, $row))
		{
			log_message('error', 'post.php/delete: unable to delete media from post');
			return array('error' => __('Unable to delete thumbnail for post.'));
		}

		// remove the thread
		$this->db->query('
				DELETE
				FROM ' . $this->radix->get_table($board) . '
				WHERE doc_id = ?
			',
			array($row->doc_id)
		);

		// get rid of the entry from the myisam _search table
		if($board->myisam_search)
		{
			$this->db->query("
				DELETE
				FROM " . $this->radix->get_table($board, '_search') . "
				WHERE doc_id = ?
			", array($row->doc_id));
		}

		// purge existing reports for post
		$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $row->doc_id));

		// purge thread replies if thread_num
		if ($row->op == 1) // delete: thread
		{
			$thread = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				WHERE thread_num = ?
			',array($row->num));

			// thread replies found
			if ($thread->num_rows() > 0)
			{
				// remove all media files
				foreach ($thread->result() as $p)
				{
					if (!$this->delete_media($board, $p))
					{
						log_message('error', 'post.php/delete: unable to delete media from thread op');
						return array('error' => __('Unable to delete thumbnail for thread replies.'));
					}

					// purge associated reports
					$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $p->doc_id));
				}

				// remove all replies
				$this->db->query('
					DELETE FROM ' . $this->radix->get_table($board) . '
					WHERE thread_num = ?
				', array($row->num));

				// get rid of the replies from the myisam _search table
				if($board->myisam_search)
				{
					$this->db->query("
						DELETE
						FROM " . $this->radix->get_table($board, '_search') . "
						WHERE thread_num = ?
					", array($row->num));
				}
			}
		}

		return TRUE;
	}


	/**
	 * Delete media for the selected post
	 *
	 * @param object $board
	 * @param object $post the post choosen
	 * @param bool $media if full media should be deleted
	 * @param bool $thumb if thumbnail should be deleted
	 * @return bool TRUE on success or if it didn't exist in first place, FALSE on failure
	 */
	private function p_delete_media($board, $post, $media = TRUE, $thumb = TRUE)
	{
		if (!$post->media_hash)
		{
			// if there's no media, it's all OK
			return TRUE;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($post->total == 1 || $post->banned == 1 || $this->auth->is_mod_admin())
		{
			if ($media === TRUE)
			{
				$media_file = $this->get_media_dir($board, $post);
				if (file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $media_file);
						return FALSE;
					}
				}
			}

			if ($thumb === TRUE)
			{
				// remove OP thumbnail
				$post->op = 1;
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}

				// remove reply thumbnail
				$post->op = 0;
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}


	/**
	 * Sets the media hash to banned through all boards
	 *
	 * @param string $hash the hash to ban
	 * @param bool $delete if it should delete the media through all the boards
	 * @return bool
	 */
	private function p_ban_media($media_hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . '
			(
				md5
			)
			VALUES
			(
				?
			)
		',
			array($media_hash)
		);

		// update all local _images table
		foreach ($this->radix->get_all() as $board)
		{
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board, '_images') . '
				(
					media_hash, media, preview_op, preview_reply, total, banned
				)
				VALUES
				(
					?, ?, ?, ?, ?, ?
				)
				ON DUPLICATE KEY UPDATE banned = 1
			',
				array($media_hash, NULL, NULL, NULL, 0, 1)
			);
		}

		// delete media files if TRUE
		if ($delete === TRUE)
		{
			$posts = array();

			foreach ($this->radix->get_all() as $board)
			{
				$posts[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($board->id) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_hash = ' . $this->db->escape($media_hash) . '
					)
				';
			}

			$query = $this->db->query(implode('UNION', $posts));
			if ($query->num_rows() == 0)
			{
				log_message('error', 'post.php/ban_media: unable to locate posts containing media_hash');
				return FALSE;
			}

			foreach ($query->result() as $post)
			{
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
			}
		}

		return TRUE;
	}

}

/* End of file post_model.php */
/* Location: ./application/models/post_model.php */
