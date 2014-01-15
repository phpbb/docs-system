<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

// Mapping table for UG articles will be defined in support/docs/common.php
class comments
{
	private $db;
	private $comment_author;
	private $is_admin;
	private $location_id;
	private $article_id;
	private $comments_enabled;
	private $page_limit;
	private $current_page;

	public function __construct($db, $is_admin, $user, $location_id, $article_id, $comments_enabled, $page_limit, $current_page)
	{
		$this->db = $db;
		$this->is_admin = $is_admin;
		$this->comment_author = $user;
		$this->location_id = $location_id;
		$this->article_id = $article_id;
		$this->comments_enabled = $comments_enabled;
		$this->page_limit = $page_limit;
		$this->current_page = $current_page;
	}

	public function get_comments()
	{
		// @TODO Potentially combine
		if (!$this->is_admin)
		{
			$private_sql = " AND c.comment_private = 0";
		}
		else
		{
			$private_sql = "";
		}

		$sql_array = array
		(
			'SELECT' => '*',
			'FROM' => array(
				DOC_COMMENTS_TABLE => 'c',
				USERS_TABLE => 'u',
			),
			'WHERE' => 'c.location_id = ' . $this->location_id .
				' AND u.user_id = c.user_id' .
				$private_sql,
			'ORDER_BY' => 'c.comment_timestamp ASC',
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		return $result;
	}

	public function add_comment($comment_text, $bbcode_bitfield, $bbcode_uid, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies)
	{
		// @TODO catch and report errors
		if ($comment_author->data['user_id'] != ANONYMOUS)
		{
			$comment_text = utf8_normalize_nfc($comment_text);
			$bbcode_uid = $bbcode_bitfield = $bbcode_flags = '';
			generate_text_for_storage($comment_text, $bbcode_uid, $bbcode_bitfield, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies);

			$sql_array = array(
				'article_id'		=> $this->article_id,
				'location_id'		=> $this->location_id,
				'user_id'			=> $comment_author->data['user_id'],
				'comment_approved'	=> $this->is_admin ? 1 : 0,
				'comment_private'	=> 0,
				'comment_text'		=> $comment_text,
				'comment_timestamp'	=> time(),
				'bbcode_bitfield'	=> $bbcode_bitfield,
				'bbcode_uid'		=> $bbcode_uid,
				'bbcode_flags'		=> (int) $bbcode_flags,
			);

			$sql = 'INSERT INTO ' . DOC_COMMENTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_array);
			$this->db->sql_query($sql);
		}
	}

	public function delete_comment($comment_id)
	{
		if ($this->is_admin && $comment_author->data['user_id'] != ANONYMOUS)
		{
			$sql = 'DELETE FROM ' . DOC_COMMENTS_TABLE . ' 
					WHERE comment_id = ' . $comment_id;

			$db->sql_query($sql);
		}
		else
		{
			// @TODO Return 'not authorized'
			return -1;
		}
	}

	public function hide_comment($comment_id)
	{
		if ($this->is_admin && $comment_author->data['user_id'] != ANONYMOUS)
		{
			$sql_array = array(
				'comment_private'	=> 1,
			);

			$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
				WHERE comment_id = ' . $comment_id;

			$db->sql_query($sql);
		}
	}

	public function unhide_comment($comment_id)
	{
		if ($this->is_admin && $comment_author->data['user_id'] != ANONYMOUS)
		{
			$sql_array = array(
				'comment_private'	=> 0,
			);

			$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
				WHERE comment_id = ' . $comment_id;
			$db->sql_query($sql);
		}
	}

	public function edit_comment($comment_id, $comment_text, $bbcode_bitfield, $bbcode_uid, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies)
	{
		if ($comment_author->data['user_id'] != ANONYMOUS)
		{
			$comment_text = utf8_normalize_nfc($comment_text);
			$bbcode_uid = $bbcode_bitfield = $bbcode_flags = '';
			generate_text_for_storage($comment_text, $bbcode_uid, $bbcode_bitfield, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies);

			$sql_array = array(
				'comment_text'		=> $comment_text,
				'bbcode_bitfield'	=> $bbcode_bitfield,
				'bbcode_uid'		=> $bbcode_uid,
				'bbcode_flags'		=> (int) $bbcode_flags,
			);

			$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
				WHERE comment_id = ' . $comment_id;
			$db->sql_query($sql);
		}
	}

	public function get_article_id($comment_id)
	{
		$sql = 'SELECT article_id 
				FROM ' . DOC_COMMENTS_TABLE . ' 
				WHERE comment_id = ' . $comment_id . ' 
					AND location_id = ' . $this->location_id;

		$result = $db->sql_query_limit($sql, 1);
		$article_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($article_data['article_id']))
		{
			return $article_data['article_id'];
		}

		return null;
	}

	public function count_comments()
	{
		$sql = 'SELECT COUNT(*) AS num_comments 
				FROM ' . DOC_COMMENTS_TABLE . ' 
				WHERE article_id = ' . $this->article_id . ' 
					AND location_id = ' . $this->location_id;

		$result = $db->sql_query_limit($sql, 1);
		$comment_count = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($comment_count['num_comments']))
		{
			return $comment_count['num_comments'];
		}

		return 0;
	}

	// @TODO Finish paging, might want to just use a paginator class instead
	public function has_next()
	{
		if ($this->count_pages() > $this->current_page && $this->current_page > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function has_prev()
	{
		if ($this->current_page > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function count_pages()
	{
		return ($this->count_comments() + $this->page_limit - 1) / $this->page_limit;
	}

	// @TODO See if can_edit_comment from functions_docs.php will be required in this class
}

