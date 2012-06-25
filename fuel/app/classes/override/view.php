<?php

class View extends Fuel\Core\View
{

	protected function process_file($file_override = false)
	{
		$clean_room = function($__file_name, array $__data)
		{
			extract($__data, EXTR_REFS);

			// Capture the view output
			ob_start();

			try
			{
				// Load the view within the current scope
				if (version_compare(phpversion(), '5.4.0') < 0 && (bool) @ini_get('short_open_tag') === FALSE)
				{
					echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($__file_name))));
				}
				else
				{
					include $__file_name;
				}

			}
			catch (\Exception $e)
			{
				// Delete the output buffer
				ob_end_clean();

				// Re-throw the exception
				throw $e;
			}

			// Get the captured output and close the buffer
			return ob_get_clean();
		};
		return $clean_room($file_override ?: $this->file_name, $this->get_data());
	}

}