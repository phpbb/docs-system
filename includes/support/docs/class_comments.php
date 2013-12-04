<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

/*
* Mapping table for UG articles will be defined in support/docs/common.php
*/
class Comments
{
	var $_docs_section;
	var $_article_id;
	var $_mode;
	var $_comments_enabled;

	//Paging would be nice
	var $_limit;
	var $_page;

	function Comments($docs_section, $article_id, $mode, $limit, $page, $enabled)
	{
		//init everything
	}

	function run()
	{
		switch($_mode)
		{
			case 'add':
				//add_comment()
			break;

 			case 'delete':
			break;

			case 'edit':
			break;

			case 'hide':
			break;
		}
	}

	function print_comments()
        {
		//use generate_text_for_display()
		//use decode_message()
		//use $template->assign_block_vars to display comments from template
        }
       
	function add_comment($comment_text, $topic_id, $bbcode_bitfield, $bbcode_uid, $bbcode_flags)
	{
		global $db;
		//check if($user->data['user_id'] != ANONYMOUS)
	}

	function delete_comment($comment_id)
        {
		global $db;
		//check if(DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
	}

	function hide_comment($comment_id)
	{
		global $db;
		//check if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)

		//Because of this, do comments need the ability to be marked as private? Are they the same thing?
	}
       
	function edit_comment($comment_id)
	{
		global $db;
		//if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
		//Users should be able to edit their own comments
	}

	function get_topic_slug($comment_id)
	{
		//If you need the url of the topic from the comment id
		//could be used to get the selected tab of ug articles
	}
       
	function get_topic_id($comment_id)
	{
	       
	}

	//Could probably just move can_edit_comment from functions_docs.php to this class.
}
?>
