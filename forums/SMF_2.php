<?php

define('FORUM_VERSION', '1.4');
define('FORUM_DB_REVISION', 2);

class SMF_2 extends Forum
{
	function initialize()
	{
		$this->db->set_names('utf8');

		if (!$this->db->table_exists('members'))
			error('Selected database does not contain valid SMF installation', __FILE__, __LINE__);
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.id_ban AS id, bg.name AS username, b.ip_low1, b.ip_low2, b.ip_low3, b.ip_low4, b.email_address AS email, bg.reason AS message, bg.expire_time AS expire',//, ban_creator',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'ban_groups AS bg',
					'ON'		=> 'bg.id_ban_group=b.id_ban_group'
				),
			),
			'FROM'		=> 'ban_items AS b',
		)) or error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		message('Processing %d bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$cur_ban['ip'] = implode('.', array($cur_ban['ip_low1'], $cur_ban['ip_low2'], $cur_ban['ip_low3'], $cur_ban['ip_low4']));
			unset ($cur_ban['ip_low1'], $cur_ban['ip_low2'], $cur_ban['ip_low3'], $cur_ban['ip_low4']);

			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_cat AS id, name AS cat_name, cat_order AS disp_position',
			'FROM'		=> 'categories',
		)) or error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		message('Processing %d categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring()
	{
		// Nothing to do. Censoring conversion is in conver_config()
	}

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'variable, value',
			'FROM'		=> 'settings',
		)) or error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		message('Processing config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$this->new_config['o_smtp_host'] 			= $old_config['smtp_host'].(!empty($old_config['smtp_host']) && !empty($old_config['smtp_port'])) ? ':'.$old_config['smtp_port'] : '';
		$this->new_config['o_smtp_user'] 			= $old_config['smtp_username'];
		$this->new_config['o_smtp_pass'] 			= $old_config['smtp_password'];

		foreach ($this->new_config as $key => $value)
		{
			$this->fluxbb->add_row('config', array(
				'conf_name'		=> $key,
				'conf_value'	=> $value,
			));
		}

		// Convert censoring
		$censor_words = array_combine(explode("\n", $old_config['censor_vulgar']), explode("\n", $old_config['censor_proper']));
		foreach ($censor_words as $vulgar => $valid)
		{
			$this->fluxbb->add_row('censoring', array(
				'search_for'	=> $vulgar,
				'replace_with'	=> $valid,
			));
		}
	}

	function convert_forums()
	{
		// TODO: last post/poster
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.id_board AS id, b.name AS forum_name, b.description AS forum_desc, b.num_topics AS num_topics, b.num_posts AS num_posts, b.board_order AS disp_position, u.member_name AS last_poster, m.poster_time AS last_post, b.id_last_msg AS last_post_id, b.id_cat AS cat_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.id_msg=b.id_last_msg'
				),
				array(
					'LEFT JOIN'	=> 'members AS u',
					'ON'		=> 'u.id_member=m.id_member'
				),
			),
			'FROM'		=> 'boards AS b',
		)) or error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		message('Processing %d forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

//	function convert_forum_perms()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
//			'FROM'		=> 'forum_perms',
//		)) or error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

//		message('Processing %d forum_perms', $this->db->num_rows($result));
//		while ($cur_perm = $this->db->fetch_assoc($result))
//		{
//			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

//			$this->fluxbb->add_row('forum_perms', $cur_perm);
//		}
//	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_group AS g_id, group_name AS g_title',//, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'min_posts = -1 AND id_group > 3'
		)) or error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		message('Processing %d groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);
			$cur_group['g_moderator'] = $cur_group['g_title'] == 'Moderator';

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_msg AS id, poster_name AS poster, id_member AS poster_id, poster_time AS posted, poster_ip AS poster_ip, body AS message, id_topic AS topic_id',
			'FROM'		=> 'messages',
		)) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		message('Processing %d posts', $this->db->num_rows($result));
		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$cur_post['message'] = $this->convert_message($cur_post['message']);
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);

			$this->fluxbb->add_row('posts', $cur_post);
		}
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_group AS id, group_name AS rank, min_posts',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'min_posts <> -1',
		)) or error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		message('Processing %d ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_report AS id, id_msg AS post_id, id_topic AS topic_id, id_board AS forum_id, membername AS reported_by, time_started AS created, body AS message, closed AS zapped',
			'FROM'		=> 'log_reported',
		)) or error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		message('Processing %d reports', $this->db->num_rows($result));
		while ($cur_report = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('reports', $cur_report);
		}
	}

	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS user_id, id_topic AS topic_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'id_topic > 0',
		)) or error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

		message('Processing %d topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS user_id, id_board AS forum_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'id_board > 0',
		)) or error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

		message('Processing %d forum subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forum_subscriptions', $cur_sub);
		}
	}

	function convert_topics()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 't.id_topic AS id, m.poster_name AS poster, t.num_views AS num_views, t.num_replies AS num_replies, t.is_sticky AS sticky, t.locked AS closed, t.id_board AS forum_id, m.subject AS subject, m.poster_time AS posted, m.id_msg AS first_post_id, lm.poster_time AS last_post, lm.poster_name AS last_poster, lm.id_msg AS last_post_id',
			'FROM'		=> 'topics AS t',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.id_msg=t.id_first_msg'
				),
				array(
					'LEFT JOIN'	=> 'messages AS lm',
					'ON'		=> 'lm.id_msg=t.id_last_msg'
				),
			)
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		message ('Processing %d topics', $this->db->num_rows($result));
		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topics', $cur_topic);
		}
	}

	function convert_users()
	{
		// Add salt field to the users table to allow login
		$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true);

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS id, id_group AS group_id, member_name AS username, passwd AS password, password_salt AS salt, website_url AS url, icq AS icq, msn AS msn, aim AS aim, yim AS yahoo, signature AS signature, time_offset AS timezone, posts AS num_posts, date_registered AS registered, last_login AS last_visit, location AS location, email_address AS email',
			'FROM'		=> 'members',
		)) or error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		message('Processing %d users', $this->db->num_rows($result));
		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
//			$cur_user['password'] = $this->fluxbb->pass_hash($this->fluxbb->random_pass(20));
			$cur_user['language'] = $this->default_lang;
			$cur_user['style'] = $this->default_style;
			$cur_user['id'] = $this->uid2uid($cur_user['id'], true);

			$result_post = $this->db->query_build(array(
				'SELECT'	=> 'poster_time',
				'FROM'		=> 'messages',
				'WHERE'		=> 'id_member='.$cur_user['id'],
				'ORDER BY'	=> 'poster_time DESC',
				'LIMIT'		=> 1
			)) or error('Unable to fetch last post', __FILE__, __LINE__, $this->db->error());

			if ($this->db->num_rows($result_post))
				$cur_user['last_post'] = $this->db->result($result_post);

			$this->fluxbb->add_row('users', $cur_user);
		}
	}

	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(0 => 4, 3 => 2, 5 => 4, 6 => 4, 7 => 4, 8 => 4);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}

	function uid2uid($id, $new_uid = false)
	{
		// id=0 is a SMF's guest user
		if ($id == 0)
			return 1;

		// id=1 is reserved for the guest user
		elseif ($id == 1)
		{
			$result = $this->db->query_build(array(
				'SELECT'	=> 'id_member',
				'FROM'		=> 'members',
				'ORDER BY'	=> 'id_member DESC',
				'LIMIT'		=> 1
			)) or error('Unable to fetch last user id', __FILE__, __LINE__, $this->db->error());

			$id = $this->db->result($result);
			if ($new_uid)
				return ++$id;
		}

		return $id;
	}

	// Convert posts BB-code
	function convert_message($message)
	{
		$pattern = array(
			// Other
			'#\\[quote author=(.*?) link(.*?)\](.*?)\[/QUOTE\]#is',
			'#\\[flash=(.*?)\](.*?)\[/flash\]#is',
			'#\\[ftp=(.*?)\](.*?)\[/ftp\]#is',
			'#\\[font=(.*?)\](.*?)\[/font\]#is',
			'#\\[size=(.*?)\](.*?)\[/size\]#is',
			'#\\[list=?.*?\](.*?)\[/list\]#is',
			'#\\[li\](.*?)\[/li\]#is',

			// Table
			'#\\[table\](.*?)\[/table\]#is',
			'#\\[tr\]#is',
			'#\\[/tr\]#is',
			'#\\[td\](.*?)\[/td\]#is',

			// Removed tags
			'#\\[glow=(.*?)\](.*?)\[/glow\]#is',
			'#\\[s\](.*?)\[/s\]#is',
			'#\\[shadow=(.*?)\](.*?)\[/shadow\]#is',
			'#\\[move\](.*?)\[/move\]#is',
			'#\\[pre\](.*?)\[/pre\]#is',

			'#\\[left\](.*?)\[/left\]#is',
			'#\\[right\](.*?)\[/right\]#is',
			'#\\[center\](.*?)\[/center\]#is',
			'#\\[sup\](.*?)\[/sup\]#is',
			'#\\[sub\](.*?)\[/sub\]#is',

			'#\\[hr\]#is',
			'#\\[tt\](.*?)\[/tt\]#is',
		);

		$replace = array(
			// Other
			'[quote=$1]$3[/quote]',
			'Flash: $2',
			'[url=$1]$2[/url]',
			'$2',
			'$2',
			'[list]$1[/list]',
			'[*]$1[/*]',

			// Table
			'$1',
			'------------------------------------------------------------------'."\n",
			'------------------------------------------------------------------'."\n",
			"* $1\n",

			// Removed tags
			'$2',
			'$1',
			'$2',
			'$1',
			'$1',

			'$1',
			'$1',
			'$1',
			'$1',
			'$1',

			'$1'."\n",
			'$1',
		);

		$message = str_replace('<br />', "\n", $message);
		$message = str_replace("&gt;:(", ':x', $message);
		$message = str_replace('::)', ':rolleyes:', $message);
		$message = str_replace('&nbsp;', ' ', $message);

		return preg_replace($pattern, $replace, $message);
	}
}