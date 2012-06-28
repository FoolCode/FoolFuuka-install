<?php

namespace Model;

\Autoloader::add_classes(array(
	'Model\\BoardMessagesNotFound' => APPPATH.'classes/model/board/error.php'
));

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
class Board extends \Model
{

	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = Plugins::run_hook('model/board/call/before/'.$name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = Plugins::run_hook('model/board/call/replace/'.$name, $parameters, array($parameters));

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
		$after = Plugins::run_hook('model/board/call/after/'.$name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * Returns the SQL string to append to queries to be able to
	 * get the filenames required to create the path to media
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append to retrieve image filenames
	 */
	private function p_sql_media_join($board, $query, $join_on = FALSE)
	{
		$query->join(\DB::expr(Radix::get_table($board, '_images') . ' AS `mg`'), 'LEFT')
			->on(
				\DB::expr(Radix::get_table($board) . '.`media_id`'), '=', \DB::expr('`mg`.`media_id`')
			);
	}


	/**
	 * If the user is an admin, this will return SQL to add reports to the
	 * query output
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append reports to the rows
	 */
	private function p_sql_report_join($board, $query, $join_on = FALSE)
	{
		// only show report notifications to certain users
		if(\Auth::has_access('comment.reports'))
		{
			$query->join(\DB::expr('
					SELECT
						id AS report_id, doc_id AS report_doc_id, reason AS report_reason, ip_reporter as report_ip_reporter,
						status AS report_status, created AS report_created
					FROM ' . \DB::quote_identifier('reports') . '
					WHERE `board_id` = ' . $board->id), 'LEFT'
			)->on(
				Radix::get_board($board). '.`doc_id`', '=', \DB::expr('`r`.`report_doc_id`')
			);
		}
	}


	/**
	 * Get the latest
	 *
	 * @param object $board
	 * @param int $page the page to determine the offset
	 * @param array $options modifiers
	 * @return array|bool FALSE on error (likely from faulty $options), or the list of threads with 5 replies attached
	 */
	private function p_get_latest($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 20;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_post';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_post':

				$query = \DB::select('*', \DB::expr('thread_num as unq_thread_num'))
					->from(\DB::expr(Radix::get_table($board, '_threads')))
					->order_by('time_bump', 'desc')
					->limit(intval($per_page))->offset(intval(($page * $per_page) - $per_page));
				break;

			case 'by_thread':

				$query = \DB::select('*', 'thread_num as unq_thread_num')
					->from(\DB::expr(Radix::get_table($board, '_threads')))
					->order_by('thread_num', 'desc')
					->limit(intval($per_page))->offset(intval(($page * $per_page) - $per_page));
				break;

			case 'ghost':

				$query = \DB::select('*', 'thread_num as unq_thread_num')
					->from(\DB::expr(Radix::get_table($board, '_threads')))
					->where('time_ghost_bump', \DB::expr('IS NOT NULL'))
					->order_by('time_ghost_bump', 'desc')
					->limit(intval($per_page))->offset(intval(($page * $per_page) - $per_page));
				break;
		}

		$threads = $query->as_object()->execute()->as_array();

		// cache the count or get the cached count
		if($type == 'ghost')
		{
			$type_cache = 'ghost_num';
		}
		else
		{
			$type_cache = 'thread_num';
		}


		switch ($type)
		{
			// these two are the same
			case 'by_post':
			case 'by_thread':
				$query_threads = \DB::select(\DB::expr('COUNT(thread_num) AS threads'))
					->from(\DB::expr(Radix::get_table($board, '_threads')))->cached(1800);
				break;

			case 'ghost':
				$query_threads = \DB::select(\DB::expr('COUNT(thread_num) AS threads'))
					->from(\DB::expr(Radix::get_table($board, '_threads')))
					->where('time_ghost_bump', \DB::expr('IS NOT NULL'))->cached(1800);
				break;
		}

		$threads_count = $query_threads->as_object()->execute()->current()->threads;

		// set total pages found
		if ($threads_count <= $per_page)
		{
			$pages = NULL;
		}
		else
		{
			$pages = floor($threads_count/$per_page)+1;
		}

		// populate arrays with posts
		$threads_arr = array();
		$sql_arr = array();

		foreach ($threads as $thread)
		{
			$threads_arr[$thread->unq_thread_num] = array('replies' => $thread->nreplies, 'images' => $thread->nimages);

			$temp = \DB::select()->from(\DB::expr(Radix::get_table($board)));
			static::sql_media_join($board, $temp);
			static::sql_report_join($board, $temp);
			$temp->where('thread_num', $thread->unq_thread_num)
				->order_by('op', 'desc')->order_by('num', 'desc')->order_by('subnum', 'desc')
				->limit(6)->offset(0);

			$sql_arr[] = '('.$temp.')';
		}

		$query_posts = \DB::query(implode(' UNION ', $sql_arr), \DB::SELECT)->as_object()->execute()->as_array();
		// populate posts_arr array
		$posts = Comment::forge($query_posts, $board);
		$results = array();

		foreach ($threads as $thread)
		{
			$results[$thread->thread_num] = array(
				'omitted' => ($thread->nreplies - 6),
				'images_omitted' => ($thread->nimages - 1)
			);
		}

		// populate results array and order posts
		foreach ($posts as $post)
		{
			if ($post->op == 0)
			{
				if ($post->preview_orig)
				{
					$results[$post->thread_num]['images_omitted']--;
				}

				if(!isset($results[$post->thread_num]['posts']))
					$results[$post->thread_num]['posts'] = array();

				array_unshift($results[$post->thread_num]['posts'], $post);
			}
			else
			{
				$results[$post->thread_num]['op'] = $post;
			}
		}

		return array('result' => $results, 'pages' => $pages);
	}


	/**
	 * Get the thread
	 * Deals also with "last_x", and "from_doc_id" for realtime updates
	 *
	 * @param object $board
	 * @param int $num thread number
	 * @param array $options modifiers
	 * @return array|bool FALSE on failure (probably caused by faulty $options) or the thread array
	 */
	private function p_get_thread($board, $num, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;
		$type = 'thread';
		$type_extra = array();
		$realtime = FALSE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'from_doc_id':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($board)));
				static::sql_media_join($board, $query);
				static::sql_report_join($board, $query);
				$query->where('thread_num', $num)->where('doc_id', '>', $type_extra['latest_doc_id'])
					->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'ghosts':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($board)));
				static::sql_media_join($board, $query);
				static::sql_report_join($board, $query);
				$query->where('thread_num', $num)->where('subnum', '<>', 0)
					->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'last_x':
				$query = \DB::select()->from(\DB::expr('
					(
						('.\DB::select()->from(\DB::expr(Radix::get_table($board)))->where('num', $num)->limit(1).')
						UNION
						('.\DB::select()->from(\DB::expr(Radix::get_table($board)))->where('thread_num', $num)
							->order_by('num', 'desc')->order_by('subnum', 'desc')->limit($type_extra['last_limit']).')
					) AS x
				'));
				static::sql_media_join($board, $query);
				static::sql_report_join($board, $query);
				$query->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'thread':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($board)));
				static::sql_media_join($board, $query);
				static::sql_report_join($board, $query);
				$query->where('thread_num', $num)->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;
		}

		$query_result = $query->as_object()->execute()->as_array();

		if (!count($query_result))
		{
			throw new \BoardResultEmpty;
		}

		$posts = Comment::forge($query_result, $board, array('realtime' => $realtime, 'backlinks_hash_only_url' => true));

		// populate posts_arr array
		$thread_check = $this->check_thread($board, $posts);

		// process entire thread and store in $result array
		$result = array();

		foreach ($posts as $post)
		{

			if ($post->op == 0)
			{
				$result[$post->thread_num]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}

		/*

		// populate results with backlinks
		foreach ($this->backlinks as $key => $backlinks)
		{
			if (isset($result[$num]['op']) && $result[$num]['op']->num == $key)
			{
				$result[$num]['op']->backlinks = array_unique($backlinks);
			}
			else if (isset($result[$num]['posts'][$key]))
			{
				$result[$num]['posts'][$key]->backlinks = array_unique($backlinks);
			}
		}
		 *
		 *
		 */

		return array('result' => $result, 'thread_check' => $thread_check);
	}


	/**
	 * Return the status of the thread to determine if it can be posted in, or if images can be posted
	 * or if it's a ghost thread...
	 *
	 * @param object $board
	 * @param mixed $num if you send a $query->result() of a thread it will avoid another query
	 * @return array statuses of the thread
	 */
	private function p_check_thread($board, $num)
	{
		if ($num == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		// of $num is an array it means we've sent a $query->result()
		if (is_array($num))
		{
			$query_result = $num;
		}
		else
		{
			// grab the entire thread
			$query_result = \DB::select()->from(\DB::expr(Radix::get_table($board)))
				->where('thread_num', $num)->as_object()->execute()->as_array();

			// thread was not found
			if (!count($query_result))
			{
				return array('invalid_thread' => TRUE);
			}
		}

		// define variables
		$thread_op_present = FALSE;
		$ghost_post_present = FALSE;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($query_result as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->op == 1)
			{
				$thread_op_present = TRUE;
			}

			if($post->subnum > 0)
			{
				$ghost_post_present = TRUE;
			}

			if($post->subnum == 0 && $thread_last_bump < $post->timestamp)
			{
				$thread_last_bump = $post->timestamp;
			}

			if ($post->media_filename)
			{
				$counter['images']++;
			}

			$counter['posts']++;
		}

		// we didn't point to the thread OP, this is not a thread
		if (!$thread_op_present)
		{
			return array('invalid_thread' => TRUE);
		}

		// time check
		if(time() - $thread_last_bump > 432000 || $ghost_post_present)
		{
			return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
		}

		if ($counter['posts'] > $board->max_posts_count)
		{
			if ($counter['images'] > $board->max_images_count)
			{
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
			else
			{
				return array('thread_dead' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
		}
		else if ($counter['images'] > $board->max_images_count)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}
}