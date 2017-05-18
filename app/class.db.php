<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Работа с БД
 */
class Plance_MTSC_DB
{
    /**
	 * Активация плагина
	 */
    public static function activate()
    {
		global $wpdb;

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		
		/**
		 * Создаем таблицу шорткодов
		 */
		dbDelta("CREATE TABLE IF NOT EXISTS `{$wpdb -> prefix}plance_text_shortcodes` (
			`sh_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
			`sh_title` VARCHAR(150) NOT NULL,
			`sh_code` VARCHAR(25) NOT NULL,
			`sh_description` text NOT NULL,
			`sh_is_lock` TINYINT(1) UNSIGNED NOT NULL,
			`sh_date_create` INT(10) UNSIGNED NOT NULL
		) {$wpdb -> get_charset_collate()};");
		
        return TRUE;
    }
	
    /**
	 * Удаление плагина
	 */
    public static function uninstall()
    {
		global $wpdb;
		
		$wpdb -> query("DROP TABLE IF EXISTS `{$wpdb -> prefix}plance_text_shortcodes`");
		
		return TRUE;
    }
}