<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

class Forum
{
	public $db;
	public $fluxbb;
	public $stage;

	public $db_config;
	public $charset;

	function __construct($db, $fluxbb)
	{
		$this->db = $db;
		$this->fluxbb = $fluxbb;
	}

	function init_config($db_config)
	{
		$this->db_config = $db_config;
		$this->charset = $db_config['charset'];

		$this->initialize();
	}

	function initialize()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_bans()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_categories()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_censoring()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_config()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_forums()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_forum_perms()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_groups()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_posts()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_ranks()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_reports()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_topic_subscriptions()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_forum_subscriptions()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_topics()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	function convert_users()
	{
		conv_message('Not implemented', __FUNCTION__);
	}

	/**
	 * Check whether current table has more rows - if yes, redirect to the next page of the current stage
	 *
	 * @param string $old_table
	 * @param string $old_field
	 * @param integer $start_at
	 */
	function redirect($old_table, $old_field, $start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 1,
			'FROM'		=> $old_table,
			'WHERE'		=> $old_field.' > '.$start_at,
			'LIMIT'		=> 1,
		)) or error('Unable to fetch num rows', __FILE__, __LINE__, $this->db->error());

		if ($this->db->num_rows($result))
			conv_redirect($this->stage, $start_at);
	}

	/**
	 * Convert specified data to the UTF-8 charset
	 *
	 * @param string $str
	 */
	function convert_to_utf8($str)
	{
		return convert_to_utf8($str, $this->charset);
	}

}
