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

			default:
				log_message('error', 'post.php/get_latest: invalid or missing type argument');
				return FALSE;
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
}