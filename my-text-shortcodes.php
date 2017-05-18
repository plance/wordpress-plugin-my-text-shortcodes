<?php
/*
Plugin Name: My Text Shortcodes
Plugin URI: http://wordpress.org/plugins/my-text-shortcodes/
Description: Creating text shortcodes, using friendly interface
Version: 1.0.2
Author: Pavel
Author URI: http://plance.in.ua/
*/

defined('ABSPATH') or die('No script kiddies please!');

/**
* Отображаем таблицу лишь в том случае, если пользователь администратор
*/
if(is_admin() == TRUE)
{
	/**
	* Подключаем базовый класс
	*/
	if(class_exists('WP_List_Table') == FALSE)
	{
		require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
	}
	if(class_exists('Plance_Flash') == FALSE)
	{
		require_once(plugin_dir_path(__FILE__).'library/wp-plance/flash.php');
	}
	if(class_exists('Plance_Validate') == FALSE)
	{
		require_once(plugin_dir_path(__FILE__).'library/plance/validate.php');
	}
	if(class_exists('Plance_View') == FALSE)
	{
		require_once(plugin_dir_path(__FILE__).'library/plance/view.php');
	}
	if(class_exists('Plance_Request') == FALSE)
	{
		require_once(plugin_dir_path(__FILE__).'library/plance/request.php');
	}

	require_once(plugin_dir_path(__FILE__).'app/class.db.php');
	require_once(plugin_dir_path(__FILE__).'app/class.table.php');

	register_activation_hook(__FILE__, 'Plance_MTSC_DB::activate');
	register_uninstall_hook(__FILE__, 'Plance_MTSC_DB::uninstall');

	new Plance_MTSC_INIT();
}
else
{
	$shs_ar = $wpdb -> get_results("SELECT `sh_code`
		FROM `{$wpdb -> prefix}plance_text_shortcodes`
		WHERE `sh_is_lock` = 0",
		ARRAY_A);

	foreach ($shs_ar as $sh_ar)
	{
		add_shortcode('mtsc-'.$sh_ar['sh_code'], function($attr, $content, $shortcode) {
			global $wpdb;

			$sh_ar = $wpdb -> get_row("SELECT `sh_description`
				FROM `{$wpdb -> prefix}plance_text_shortcodes`
				WHERE `sh_is_lock` = 0
				AND `sh_code` = '".$wpdb -> _real_escape(str_replace('mtsc-', '', $shortcode))."'
				LIMIT 1", ARRAY_A);
				return isset($sh_ar['sh_description']) ? $sh_ar['sh_description'] : '';
			});
		}
	}

/**
* Класс формирующий пункт меню и отображающий таблицу
*/
class Plance_MTSC_INIT
{
	const PAGE = __CLASS__;

	/**
	*
	* @var Plance_MTSC_Table_Shortcodes
	*/
	private $_Table;

	/**
	*
	* @var Plance_Validate
	*/
	private $_FormValidate;

	/**
	* Конструктор
	*/
	public function __construct()
	{
		Plance_Flash::instance() -> init();

		add_action('admin_menu', array($this, 'adminMenu'));
		add_filter('set-screen-option', array($this, 'setScreenOption'), 10, 3);
		add_action('admin_head', array($this, 'adminHead'));
		add_action('plugins_loaded', array($this, 'pluginsLoaded'));
	}

	/**
	* Create menu
	*/
	public function adminMenu()
	{
		/* Create main item menu */
		$hook_list = add_menu_page(
			__('List shortcodes', 'plance'),
			__('My shortcodes', 'plance'),
			'manage_options',
			Plance_MTSC_INIT::PAGE,
			array($this, 'listShortcodes')
		);

		/* Create submenu */
		add_submenu_page(
			Plance_MTSC_INIT::PAGE,
			__('List shortcodes', 'plance'),
			__('List shortcodes', 'plance'),
			'manage_options',
			Plance_MTSC_INIT::PAGE,
			array($this, 'listShortcodes')
		);

		$hook_form = add_submenu_page(
			Plance_MTSC_INIT::PAGE,
			__('Creating shortcode', 'plance'),
			__('Create shortcode', 'plance'),
			'manage_options',
			Plance_MTSC_INIT::PAGE.'-form',
			array($this, 'formShortcode')
		);

		add_action('load-'.$hook_list, array($this, 'screenOptionsList'));
		add_action('load-'.$hook_form, array($this, 'screenOptionsForm'));
	}

	/**
	* Save screen options
	*/
	public function setScreenOption($status, $option, $value)
	{
		if('plmsc_per_page' == $option )
		{
			return $value;
		}

		return $status;
	}

	/**
	* Create options
	*/
	public function adminHead()
	{
		if(Plance_MTSC_INIT::PAGE == (isset($_GET['page']) ? esc_attr($_GET['page']) : ''))
		{
			echo '<style type="text/css">';
			echo '.wp-list-table .column-cb { width: 4%; }';
			echo '.wp-list-table .column-sh_id { width: 5%; }';
			echo '.wp-list-table .column-sh_title { width: 50%; }';
			echo '.wp-list-table .column-sh_code { width: 30%; }';
			echo '.wp-list-table .column-sh_date_create { width: 11%; }';
			echo '.wp-list-table .pl-tr-important td {color: #EA6047; }';
			echo '</style>';
		}
	}

	/**
	* Include language
	*/
	public function pluginsLoaded()
	{
		load_plugin_textdomain('plance', false, basename(__DIR__).'/languages/');
	}

	/**
	* Create options for list shortcodes
	*/
	public function screenOptionsList()
	{
		global $wpdb;

		add_screen_option('per_page', array(
			'label'		=> __('Records', 'plance'),
			'default'	=> 10,
			'option'	=> 'plmsc_per_page'
		));

		//Sets
		$this -> _Table = new Plance_MTSC_Table_Shortcodes;
		$action = $this -> _Table -> current_action();

		if($action && isset($_GET['sh_id']))
		{
			$sh_id_ar = is_array($_GET['sh_id']) ? array_map('intval', $_GET['sh_id']) : array((int)$_GET['sh_id']);

			switch ($action)
			{
				case 'delete':
					$wpdb -> query("DELETE FROM `{$wpdb -> prefix}plance_text_shortcodes` WHERE `sh_id` IN (".join(', ', $sh_id_ar).")");
					Plance_Flash::instance() -> redirect('?page='.Plance_MTSC_INIT::PAGE, __('Shortcodes deleted', 'plance'));
				break;
				case 'lock':
					$wpdb -> query("UPDATE `{$wpdb -> prefix}plance_text_shortcodes` SET `sh_is_lock` = 1 WHERE `sh_id` IN (".join(', ', $sh_id_ar).")");
					Plance_Flash::instance() -> redirect('?page='.Plance_MTSC_INIT::PAGE, __('Shortcodes locked', 'plance'));
				break;
				case 'unlock':
					$wpdb -> query("UPDATE `{$wpdb -> prefix}plance_text_shortcodes` SET `sh_is_lock` = 0 WHERE `sh_id` IN (".join(', ', $sh_id_ar).")");
					Plance_Flash::instance() -> redirect('?page='.Plance_MTSC_INIT::PAGE, __('Shortcodes unlocked', 'plance'));
				break;
			}
			exit;
		}
	}

	/**
	* Create options form add/edit form
	*/
	public function screenOptionsForm()
	{
		global $wpdb;

		//Sets
		$sh_id = Plance_Request::get('sh_id', 0, 'int');

		$this -> _FormValidate = Plance_Validate::factory(wp_unslash($_POST))
		-> setLabels(array(
			'sh_title'		=> '"'.__('Title', 'plance').'"',
			'sh_code'		=> '"'.__('Code', 'plance').'"',
			'sh_is_lock'	=> '"'.__('Blocking', 'plance').'"',
			'sh_description'=> '"'.__('Description', 'plance').'"',
			))

			-> setFilters('*', array(
				'trim' => array(),
			))
			-> setFilters('sh_is_lock', array(
				'intval' => array()
			))

			-> setRules('*', array(
				'required' => array(),
			))
			-> setRules('sh_title', array(
				'max_length' => array(150),
			))
			-> setRules('sh_code', array(
				'max_length' => array(25),
				'regex' => array('/^[a-z0-9]+[a-z0-9\-]*[a-z0-9]+$/i'),
				'Plance_MTSC_INIT::validateShCode' => array($sh_id),
			))
			-> setRules('sh_is_lock', array(
				'in_array' => array(array(0, 1)),
			))

			-> setMessages(array(
				'required'					=> __('{field} must not be empty', 'plance'),
				'max_length'				=> __('{field} must not exceed {param1} characters long', 'plance'),
				'in_array'					=> __('{field} must be one of the available options', 'plance'),
				'regex'						=> __('{field} does not match the required format', 'plance'),
				'Plance_MTSC_INIT::validateShCode'=> __('This shortcode has already been taken, select another shortcod', 'plance'),
			));

			if(Plance_Request::isPost() && $this -> _FormValidate -> validate())
			{
				$data_ar = $this -> _FormValidate -> getData();

				if($sh_id == 0)
				{
					$wpdb -> insert(
					$wpdb -> prefix.'plance_text_shortcodes',
					array(
						'sh_title'		 => $data_ar['sh_title'],
						'sh_code'		 => $data_ar['sh_code'],
						'sh_description' => $data_ar['sh_description'],
						'sh_is_lock'	 => $data_ar['sh_is_lock'],
						'sh_date_create' => time(),
					),
					array('%s', '%s', '%s', '%d', '%s')
				);

				Plance_Flash::instance() -> redirect('?page='.Plance_MTSC_INIT::PAGE, __('Shortcode saved', 'plance'));
			}
			else
			{
				$wpdb -> update(
					$wpdb -> prefix.'plance_text_shortcodes',
					array(
						'sh_title'		 => $data_ar['sh_title'],
						'sh_code'		 => $data_ar['sh_code'],
						'sh_description' => $data_ar['sh_description'],
						'sh_is_lock'	 => $data_ar['sh_is_lock'],
					),
					array('sh_id' => $sh_id),
					array('%s', '%s', '%s', '%d'),
					array('%d')
				);

				Plance_Flash::instance() -> redirect('?page='.Plance_MTSC_INIT::PAGE.'-form&sh_id='.$sh_id, __('Shortcode updated', 'plance'));
			}
		}
		else if(Plance_Request::isPost() == false && $sh_id > 0)
		{
			$sql = "SELECT *
			FROM `{$wpdb -> prefix}plance_text_shortcodes`
			WHERE `sh_id` = ".$sh_id."
			LIMIT 1";

			$data_ar = $wpdb -> get_results($sql, ARRAY_A);

			if(isset($data_ar[0]) == false)
			{
				wp_die(__('Selected shortcode does not exists', 'plance'));
			}

			$this -> _FormValidate -> setData($data_ar[0]);
		}

		if($this -> _FormValidate -> isErrors())
		{
			Plance_Flash::instance() -> show('error', $this -> _FormValidate -> getErrors());
		}
	}

	/**
	* Show list shor
	*
	*/
	public function listShortcodes()
	{
		$this -> _Table -> prepare_items();
		?>
		<div class="wrap">
			<h2>
				<?php echo __('List shortcodes', 'plance') ?>
				<a href="?page=<?php echo Plance_MTSC_INIT::PAGE ?>-form" class="page-title-action"><?php echo __('Add shortcode', 'plance') ?></a>
			</h2>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo Plance_MTSC_INIT::PAGE ?>" />
				<?php $this -> _Table -> search_box(__('Search', 'plance'), 'search_id'); ?>
				<?php $this -> _Table -> display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	* Show form add/edit shortcode
	*/
	public function formShortcode()
	{
		$sh_id = Plance_Request::get('sh_id', 0, 'int');

		if($sh_id > 0)
		{
			echo Plance_View::get(plugin_dir_path(__FILE__).'app/view/form', array(
				'form_title' => __('Editing shortcode', 'plance'),
				'form_action'=> '?page='.Plance_MTSC_INIT::PAGE.'-form&sh_id='.$sh_id,
				'data_ar'	 => $this -> _FormValidate -> getData()
			));
		}
		else
		{
			echo Plance_View::get(plugin_dir_path(__FILE__).'app/view/form', array(
				'form_title' => __('Creating shortcode', 'plance'),
				'form_action'=> '?page='.Plance_MTSC_INIT::PAGE.'-form',
				'data_ar'	 => $this -> _FormValidate -> getData()
			));
		}
	}

	/**
	* Validate sh_code
	* @param string $sh_code
	* @param int $sh_id
	*/
	public static function validateShCode($sh_code, $sh_id)
	{
		global $wpdb;

		$sql = "
		SELECT COUNT(*)
		FROM `{$wpdb -> prefix}plance_text_shortcodes`
		WHERE `sh_code` = '".$wpdb -> _real_escape($sh_code)."'
		AND `sh_id` <> ".intval($sh_id);

		return $wpdb -> get_var($sql) > 0 ? false : true;
	}
}
