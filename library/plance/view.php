<?php

class Plance_View
{
	/**
	 * Возвращает содержимое вида
	 *
	 * @param string $path путь к файлу вида
	 * @param array $array массив данных
	 * @param string $ext расширение
	 * @return string
	 */
	public static function get($path, $array = array(), $ext = 'php')
	{
		if(is_array($array) == TRUE)
		{
			extract($array, EXTR_SKIP);
		}

		ob_start();

		try
		{
			include $path.'.'.$ext;
		}
		catch (Exception $e)
		{
			ob_end_clean();

			throw $e;
		}

		return ob_get_clean();
	}
}
