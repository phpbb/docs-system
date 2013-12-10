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

	function __construct($db, $is_admin, $user, $location_id, $article_id, $comments_enabled, $page_limit, $current_page)
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

	function print_comments()
	{
		//use generate_text_for_display()
		//use decode_message()
		//use $template->assign_block_vars to display comments from template
	}

	function add_comment($comment_text, $bbcode_bitfield, $bbcode_uid, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies)
	{
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

			$sql = 'INSERT INTO ' . DOC_COMMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_array);
			$db->sql_query($sql);
		}
	}

	function delete_comment($comment_id)
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

	function hide_comment($comment_id)
	{
		if ($this->is_admin && $comment_author->data['user_id'] != ANONYMOUS)
		{
			$sql_array = array(
				'comment_private'	=> 1,
			);

			$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . "
				WHERE comment_id = '" . $comment_id . "'";

			$db->sql_query($sql);
		}
	}

	function unhide_comment($comment_id)
	{
		if ($this->is_admin && $comment_author->data['user_id'] != ANONYMOUS)
		{
			$sql_array = array(
				'comment_private'	=> 0,
			);

			$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . "
				WHERE comment_id = '" . $comment_id . "'";
			$db->sql_query($sql);
		}
	}

	function edit_comment($comment_id, $comment_text, $bbcode_bitfield, $bbcode_uid, $bbcode_flags, $enable_bbcode, $enable_magic_url, $enable_smilies)
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
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . "
				WHERE comment_id = '" . $comment_id . "'";
			$db->sql_query($sql);
		}
	}

	function get_article_id($comment_id)
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

	function count_comments()
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
	function has_next()
	{
		if (count_pages() > $this->current_page && $this->current_page > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function has_prev()
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

	function count_pages()
	{
		return (count_comments() + $this->page_limit - 1) / $this->page_limit;
	}

	// @TODO See if can_edit_comment from functions_docs.php will be required in this class
}

