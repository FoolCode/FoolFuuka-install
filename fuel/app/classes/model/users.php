<?php

namespace Model;

class UsersWrongIdException extends \FuelException {}

class Users extends \Model
{
	/**
	 * Gets the current user
	 *
	 * @param  int  $id
	 * @return object
	 */
	public static function get_user()
	{
		$id = \Auth::get_user_id();
		$id = $id[1];

		$query = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where('id', $id)
			->as_object()
			->execute(\Config::get('foolauth.db_connection'));

		if (!count($query))
		{
			throw new UsersWrongIdException;
		}

		return User::forge($query->current());
	}


	/**
	 * Gets single user database row by selected row
	 *
	 * @param  int  $id
	 * @return object
	 */
	public static function get_user_by($field, $id)
	{
		$query = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->where($field, $id)
			->as_object()
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! count($query))
		{
			throw new UsersWrongIdException;
		}

		return User::forge($query->current());
	}


	/**
	 * Gets all user limited with page and limit
	 *
	 * @param  int  $page
	 * @param  into $limit
	 * @return object
	 */
	public static function get_all($page = 1, $limit = 40)
	{
		$users = \DB::select()
			->from(\Config::get('foolauth.table_name'))
			->limit($limit)
			->offset(($page * $limit) - $limit)
			->execute(\Config::get('foolauth.db_connection'))
			->as_array();

		$users = User::forge($users);

		$count = \DB::select(\DB::expr('COUNT(*) as count'))
			->from(\Config::get('foolauth.table_name'))
			->as_object()
			->execute(\Config::get('foolauth.db_connection'))
			->current()->count;

		return array('result' => $users, 'count' => $count);
	}

}

/* end of file user.php */