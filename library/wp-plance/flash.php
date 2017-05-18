<?php

class Plance_Flash
{
	const KEY = 'plance_flash';
	
	/**
	 * Get Plance_Flash
	 *
	 * @return Plance_Flash
	 */
	public static function instance()
	{
		static $Instance;
		
		if(!is_object($Instance))
		{
			$Instance = new self;
		}
		return $Instance;
	}
	
	/**
	 * Init flash
	 */
	public function init()
	{
		if(session_id() == false)
		{
			session_start();
		}
		
		if(is_admin())
		{
			add_action('admin_notices', array($this, 'showMessage'));
		}
	}
	
	/**
	 * Show flash message in the CPanel
	 */
	public function showMessage()
	{
		if(isset($_SESSION[self::KEY])) 
		{
			$this -> show($_SESSION[self::KEY]['class'], $_SESSION[self::KEY]['message']);

			unset ($_SESSION[self::KEY]);
		}
	}
	
	/**
	 * Redirect user
	 * @param string $uri
	 * @param string $message
	 * @param bool $type
	 */
	public function redirect($uri, $message, $type = true)
	{
		$_SESSION[self::KEY] = array(
			'class'   => $type == true ? 'updated' : 'error',
			'message' => $message,
		);
		
		wp_redirect($uri);
		exit;
	}
	
	/**
	 * Get flash message
	 * @param string $class
	 * @param string|array $message
	 */
	public function get($class, $message)
	{
		$c = '<div id="message" class="'.$class.' notice is-dismissible">';
		if(is_admin())
		{
			$c .= '<button type="button" class="notice-dismiss"></button>';
		}
		if(is_array($message) == true)
		{
			foreach($message as $text)
			{
				$c .= '<p><strong>'.$text.'</strong></p>';
			}
		}
		else
		{
			$c .= '<p><strong>'.$message.'</strong></p>';
		}
		$c .= '</div>';
		
		return $c;
	}
	
	/**
	 * Show flash message
	 * @param string $class
	 * @param string|array $message
	 */
	public function show($class, $message)
	{
		echo $this -> get($class, $message);
	}
}
