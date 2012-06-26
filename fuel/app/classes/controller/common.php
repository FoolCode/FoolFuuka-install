<?php


class Controller_Common extends Controller
{


	public function before()
	{
		$hash = false;
		if (Session::get('login_hash') !== null)
		{
			$hash = Session::get('login_hash');
		}
		else if (Cookie::get('autologin') !== null)
		{
			$hash = Cookie::get('autologin');
		}

		if ($hash !== false)
		{
			$query = DB::select('*')->from('user_autologin')
					->where('login_hash', $hash)->and_where('expiration', '>', time())->execute();

			if (count($query))
			{
				Auth::force_login($query[0]->user_id);
			}
		}

		// login garbage collection
		if (time() % 25 == 0)
		{
			DB::delete('user_autologin')->where('expiration', '<', time())->execute();
		}
	}

}