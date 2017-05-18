<?php

class Plance_Request
{
	/**
	 * Return data from $_GET var
	 * @param string $index
	 * @param mixed $default
	 * @param string $type
	 * @return mided
	 */
	public static function get($index, $default = NULL, $type = 'string')
	{
		return isset($_GET[$index]) ? self::_toType($_GET[$index], $type) : $default;
	}
	
	/**
	 * Return data from $_POST var
	 * @param string $index
	 * @param mixed $default
	 * @param string $type
	 * @return mided
	 */
	public static function post($index, $default = NULL, $type = 'string')
	{
		return isset($_POST[$index]) ? self::_toType($_POST[$index], $type) : $default;
	}
	
	/**
	 * Check, REQUEST_METHOD is POST
	 *
	 * @return bool
	 */
	public static function isPost()
	{
		 return $_SERVER['REQUEST_METHOD'] == "POST";
	}

	/**
	 * Get Current URL
	 * @return string
	 */
	public static function currentURL()
	{
		 return $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ;
	}
	
	/**
	 * Set type for var
	 * @param mixed $var
	 * @param string $type
	 * @return mixed
	 */
	private static function _toType($var, $type)
	{
		switch($type)
		{
			case "string":
				return (string) $var;
			case "bool":
				return (bool)$var;
			case "array":
				return (array) $var;
			case "int":
			case "intval":
				return (int) $var;
			case "float":
				return (double) $var;
			default:
				if($type)
				{
					return call_user_func($type, $var);
				}
		}
		return $var;
	}
}