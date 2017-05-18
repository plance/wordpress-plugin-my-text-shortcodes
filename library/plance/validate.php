<?php

class Plance_Validate
{
	/**
	 * Data
	 * @var array
	 */
	private $_data = array();
	
	/**
	 * Labels
	 * @var array
	 */
	private $_labels = array();
	
	/**
	 * Filters
	 * @var array
	 */
	private $_filters = array();
	
	/**
	 * Rules
	 * @var array
	 */
	private $_rules = array();
	
	/**
	 * Errors
	 * @var array
	 */
	private $_errors = array();

	/**
	 * Is valide
	 * @var bool
	 */
	private $is_valid = false;
	
	/**
	 * Error messages
	 * @var array
	 */
	private $_messages = array(
		'in_array'      => '{field} must be one of the available options',
		'min_length'    => '{field} must be at least {param1} characters long',
		'max_length'    => '{field} must not exceed {param1} characters long',
		'required'		=> '{field} must not be empty',
		'regex'         => '{field} does not match the required format',
		'email'         => '{field} must be a email',
	);

	/**
	 * Create Validate object
	 * @param array $data
	 * @param string $type
	 * @return Plance_Validate
	 */
	private function __construct($data)
	{
		$this -> _data = $data;
	}
	
	/**
	 * Create Validate object
	 * @param array $data
	 * @param string $type
	 * @return Plance_Validate
	 */
	public static function factory(array $data)
	{
		return new self($data);
	}
	
	/**
	 * Set labels
	 * @param array $labels labels
	 * @return \Plance_Validate
	 */
	public function setLabels(array $labels)
	{
		foreach($labels as $k => $v)
		{
			$this -> _labels[$k] = $v;
		}

		if(sizeof($this -> _data) == 0)
		{
			$this -> _data = array_map(function()
			{
				return '';
			}, $this -> _labels);
		}
		
		return $this;
	}
	
	/**
	 * Get label
	 * @param string $key
	 * @return string
	 */
	public function getLabel($key = '')
	{
		if($key == '')
		{
			return $this -> _labels;
		}
		return $this -> _labels[$key];
	}
	
	/**
	 * Set filters
	 * @param string $field
	 * @param array $filters filters
	 * @return \Plance_Validate
	 */
	public function setFilters($field, array $filters)
	{
		foreach($filters as $k => $v)
		{
			$this -> _filters[$field][$k] = $v;
		}

		return $this;
	}
	
	/**
	 * Set rulles
	 * @param string $field field
	 * @param array $rules rules
	 * @return \Plance_Validate
	 */
	public function setRules($field, array $rules)
	{
		foreach($rules as $k => $v)
		{
			$this -> _rules[$field][$k] = $v;
		}

		return $this;
	}
	
	/**
	 * Set error messages
	 * @param array $messages
	 * @return \Plance_Validate
	 */
	public function setMessages(array $messages)
	{
		foreach($messages as $k => $v)
		{
			$this -> _messages[$k] = $v;
		}

		return $this;
	}

	/**
	 * Set data
	 * @param array|string $k
	 * @param string $v
	 */
	public function setData($k, $v = '')
	{
		if(is_array($k) == true || is_object($k) == true)
		{
			$this -> _data = $k;
		}
		else
		{
			$this -> _data[$k] = $v;
		}
	}
	
	/**
	 * Get data
	 * @param string $key
	 * @return array
	 */
	public function getData($key = '')
	{
		if($key == '')
		{
			return $this -> _data;
		}
		return $this -> _data[$key];
	}
	
	/**
	 * Get errors
	 * @return array
	 */
	public function getErrors()
	{
		return $this -> _errors;
	}
	
	/**
	 * Add errors
	 * @param string $rule rule
	 * @param string $label label
	 * @param string $params
	 */
	public function addError($rule, $label, $params)
	{
		$a = array('{field}' => $label);

		foreach ($params as $i => $value)
		{
			$a['{param'.$i.'}'] = is_array($value) || is_object($value) ? '' : (string)$value;
		}
			
		if(isset($this -> _messages[$rule]))
		{
			$message = $this -> _messages[$rule];
		}
		else
		{
			$message = $rule;
		}

		$this -> _errors[] = strtr($message, $a);
	}
	
	/**
	 * Check exists error or not
	 * @return bool
	 */
	public function isErrors()
	{
		return sizeof($this -> _errors) ? true : false;
	}

	/**
	 * Vilid data or not
	 * @return bool
	 */
	public function isValid()
	{
		return $this -> is_valid;
	}
	
	/**
	 * Run validator
	 * @return boolean
	 */
	public function validate()
	{
		//Sets
		$labels_ar = array_keys($this -> _labels);
		$data_ar   = array();
		
		foreach($labels_ar as $label)
		{
			if(isset($this -> _data[$label]))
			{
				$data_ar[$label] = $this -> _data[$label];
			}
			else
			{
				$data_ar[$label] = '';
			}
		}
		
		$filters_ar = $this -> _mergeFunction($labels_ar, $this -> _filters);
		foreach($filters_ar as $field => $filters)
		{
			foreach ($filters as $function => $params)
			{
				array_unshift($params, $data_ar[$field]);

				if(method_exists($this, $function))
				{
					$Object = new ReflectionMethod($this, $function);
					if($Object -> isStatic())
					{
						$data_ar[$field] = $Object -> invokeArgs(NULL, $params);
					}
					else
					{
						$data_ar[$field] = call_user_func_array(array($this, $function), $params);
					}
				}
				else if(strpos($function, '::') === FALSE)
				{
					$Object = new ReflectionFunction($function);

					$data_ar[$field] = $Object -> invokeArgs($params);
				}
				else
				{
					list($class, $method) = explode('::', $function, 2);

					$Object = new ReflectionMethod($class, $method);

					$data_ar[$field] = $Object -> invokeArgs(NULL, $params);
				}
			}
		}
		
		$rules_ar = $this -> _mergeFunction($labels_ar, $this -> _rules);
		foreach ($rules_ar as $field => $rules)
		{
			foreach ($rules as $function => $params)
			{
				array_unshift($params, $data_ar[$field]);

				if(method_exists($this, $function))
				{
					$Object = new ReflectionMethod($this, $function);
					if($Object -> isStatic())
					{
						$result = $Object -> invokeArgs(NULL, $params);
					}
					else
					{
						$result = call_user_func_array(array($this, $function), $params);
					}
				}
				else if(strpos($function, '::') === FALSE)
				{
					$Object = new ReflectionFunction($function);

					$result = $Object -> invokeArgs($params);
				}
				else
				{
					list($class, $method) = explode('::', $function, 2);

					$Object = new ReflectionMethod($class, $method);

					$result = $Object -> invokeArgs(NULL, $params);
				}
				
				if($result === false)
				{
					$this -> addError($function, $this -> _labels[$field], $params);
				}
			}
		}
		
		$this -> _data = $data_ar;
		$this -> is_valid = !$this -> isErrors();
		
		return $this -> is_valid;
	}
	
	/**
	 * Megre functions array
	 * @param array $labels_ar labal fields
	 * @param array $function_ar functions
	 * @return array
	 */
	private function _mergeFunction($labels_ar, $function_ar)
	{
		if(isset($function_ar['*']))
		{
			$common_ar = $function_ar['*'];
			unset($function_ar['*']);
			
			foreach($labels_ar as $label)
			{
				if(isset($function_ar[$label]))
				{
					$function_ar[$label] = array_merge($common_ar, $function_ar[$label]);
				}
				else
				{
					$function_ar[$label] = $common_ar;
				}
			}
		}
		
		return $function_ar;
	}
	
	//===========================================================
	
	/**
	 * Checks a field is empty or not
	 * @param string $t
	 * @return bool
	 */
	public static function required($t)
	{
		return ! in_array($t, array(NULL, FALSE, '', array()), TRUE);
	}

	/**
	 * Checks a field using regular expression
	 * @param string $t
	 * @param string $expression
	 * @return bool
	 */
	public static function regex($t, $expression)
	{
		return (bool) preg_match($expression, (string) $t);
	}

	/**
	 * Checks a field on the min length
	 * @param type $t
	 * @param int $l
	 * @return bool
	 */
	public static function min_length($t, $l)
	{
		return self::_strlen($t) >= $l;
	}

	/**
	 * Checks a field on the max length
	 * @param type $t
	 * @param int $l
	 * @return bool
	 */
	public static function max_length($t, $l)
	{
		return self::_strlen($t) <= $l;
	}

	/**
	 * Checks a field on email
	 * @param string $t
	 * @return bool
	 */
	public static function email($t)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $t)) ? FALSE : TRUE;
	}
	
	/********************************************************************************************************************/
	/************************************************* PRIVATE METHODS **************************************************/
	/********************************************************************************************************************/
	
	/**
	 * Get string length
	 *
	 * @param string $str string
	 */
	private static function _strlen($str)
	{
		if(function_exists('mb_strlen'))
		{
			return mb_strlen($str);
		}

		return strlen(utf8_decode($str));
	}	
}