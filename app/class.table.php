<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
* Create table
*/
class Plance_MTSC_Table_Shortcodes extends WP_List_Table
{
	/**
	* Подготавливаем колонки таблицы для их отображения
	*
	*/
	public function prepare_items()
	{
		global $wpdb;

		/* Определяем общее количество записей в БД */
		$total_items = $wpdb -> get_var("
		SELECT COUNT(`sh_id`)
		FROM `{$wpdb -> prefix}plance_text_shortcodes`
		{$this -> _getSqlWhere()}
		");

		//Sets
		$per_page = $this -> get_items_per_page('plmsc_per_page', 10);

		/* Устанавливаем данные для пагинации */
		$this -> set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		));

		/* Получаем данные для формирования таблицы */
		$data = $this -> table_data();

		$this -> _column_headers = $this -> get_column_info();

		/* Устанавливаем данные таблицы */
		$this -> items = $data;
	}

	/**
	* Название колонок таблицы
	*
	* @return array
	*/
	public function get_columns()
	{
		return array(
			'cb'			=> '<input type="checkbox" />',
			'sh_id'			=> __('ID', 'plance'),
			'sh_title'		=> __('Title', 'plance'),
			'sh_code'		=> __('Code', 'plance'),
			'sh_date_create'=> __('Date create', 'plance'),
		);
	}

	/**
	* Массив названий колонок по которым выполняется сортировка
	*
	* @return array
	*/
	public function get_sortable_columns()
	{
		return array(
			'sh_id'			=> array('sh_id', false),
			'sh_title'		=> array('sh_title', false),
			'sh_code'		=> array('sh_code', false),
			'sh_date_create'=> array('sh_date_create', false),
		);
	}

	/**
	* Данные таблицы
	*
	* @return array
	*/
	private function table_data()
	{
		global $wpdb;

		//Sets
		$per_page = $this -> get_pagination_arg('per_page');
		$order_ar = $this -> get_sortable_columns();
		$orderby = 'sh_title';
		$order = 'ASC';

		if(isset($_GET['order']) && isset($order_ar[$_GET['order']]))
		{
			$orderby = $_GET['order'];
		}

		if(isset($_GET['order']))
		{
			$order = $_GET['order'] == 'asc' ? 'asc' : 'desc';
		}

		$sql = "SELECT *
		FROM `{$wpdb -> prefix}plance_text_shortcodes`
		{$this -> _getSqlWhere()}
		ORDER BY `{$orderby}` {$order}
		LIMIT ".(($this -> get_pagenum() - 1) * $per_page).", {$per_page}
		";

		return $wpdb -> get_results($sql, ARRAY_A);
	}

	/**
	* Отображается в случае отсутствии данных
	*/
	public function no_items()
	{
		echo __('Data not found', 'plance');
	}

	/**
	* Формируем строку таблицы
	* @param array $item
	*/
	public function single_row($item)
	{
		echo '<tr class="'.($item['sh_is_lock'] ? 'pl-tr-important' : '').'">';
		$this -> single_row_columns($item);
		echo '</tr>';
	}

	/**
	* Возвращает содержимое колонки
	*
	* @param  array $item массив данных таблицы
	* @param  string $column_name название текущей колонки
	*
	* @return mixed
	*/
	public function column_default($item, $column_name )
	{
		switch($column_name)
		{
			case 'sh_id':
			case 'sh_title':
			return $item[$column_name];
			case 'sh_code':
			return '[mtsc-'.$item['sh_code'].']';
			case 'sh_date_create':
			return '<abbr title="'.date('d.m.Y H:i', $item['sh_date_create']).'">'.date('d.m.Y', $item['sh_date_create']).'</abbr>';
			default:
			return print_r($item, true);
		}
	}

	/**
	* Создает чекбокс
	* @param array $item
	* @return string
	*/
	function column_cb($item)
	{
		return '<input type="checkbox" name="sh_id[]" value="'.$item['sh_id'].'" />';
	}

	/**
	* Формируем для записей колонки "title" дополнительные ссылки
	* @param array $item
	* @return string
	*/
	function column_sh_title($item)
	{
		return $item['sh_title'].' '.$this -> row_actions(array(
			'edit'	 => '<a href="?page='.Plance_MTSC_INIT::PAGE.'-form&sh_id='.$item['sh_id'].'">'.__('edit', 'plance').'</a>',
			'delete' => '<a href="?page='.Plance_MTSC_INIT::PAGE.'&action=delete&sh_id='.$item['sh_id'].'">'.__('delete', 'plance').'</a>',
		));
	}

	/**
	* Возвращает массив опций для групповых действий
	* @return array
	*/
	function get_bulk_actions()
	{
		return array(
			'delete'=> __('Delete', 'plance'),
			'lock'	=> __('Lock', 'plance'),
			'unlock'=> __('Unlock', 'plance'),
		);
	}

	/********************************************************************************************************************/
	/************************************************* PRIVATE METHODS **************************************************/
	/********************************************************************************************************************/

	/**
	* Get "where" for sql
	* @global wpdb $wpdb
	* @return string
	*/
	private function _getSqlWhere()
	{
		global $wpdb;

		$where = '';

		if(isset($_GET['s']) && $_GET['s'])
		{
			$where = 'WHERE '.join(' OR ', array(
				"`sh_title` LIKE  '%".$wpdb -> _real_escape($_GET['s'])."%'",
				"`sh_code` LIKE  '%".$wpdb -> _real_escape($_GET['s'])."%'",
				"`sh_description` LIKE  '%".$wpdb -> _real_escape($_GET['s'])."%'",
			));
		}

		return $where;
	}
}
