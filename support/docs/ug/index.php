<?php
/**
*
* @package phpBB.com Documentation - User Guide
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

// The page title @TODO
$page_title = 'phpBB 3.0 User Guide';
define('IN_PHPBB', true);

include('./common.php');
require($root_path . 'includes/support/docs/functions_docs.php');

$user->add_lang('posting');

// Grab all the tabs that should be displayed
$tabs = unserialize(str_replace("\xEF\xBB\xBF", '', file_get_contents('./data/' . DOCS_VERSION . '/' . DOCS_LANG . '/_tabs')));

// Now see what the user asked to view
$selected_tab = request_var('selected_tab', '');
$selected_sec = request_var('selected_sec', '');

$comment_action = request_var('comment_action', '', true);

//$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ? true : false;
$is_ajax_request = isset($_SERVER['HTTP_X_PJAX']) ? true : false;
$is_data_submission = false;

// Check for administrators
// @TODO Fix the docs permissions
if (DOCS_ADMIN)
{
	// If a comment id was specified without a tab (and thus without a section) see
	// if it can be extrapolated. This will happen when deleting/approving/etc.
	$comment_id = request_var('comment_id', 0, true);

	if ($selected_tab == '' && $comment_id)
	{
		$sql = 'SELECT topic_id FROM ' . DOC_COMMENTS_TABLE . ' WHERE comment_id = ' . $comment_id;
		$result = $db->sql_query_limit($sql, 1);
		$topic_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($topic_data['topic_id']))
		{
			// Chop off the 'ug_'
			$topic_data['topic_id'] = substr($topic_data['topic_id'], 3);

			// Find the first '_' and use that as the delimiter to split
			// the tab from the section.
			$delimiter = strpos($topic_data['topic_id'], '_');
			$selected_tab = substr($topic_data['topic_id'], 0, $delimiter);
			$selected_sec = substr($topic_data['topic_id'], $delimiter + 1);
		}
	}

	// Display the administration tab
	$tabs['adm'] = '[Administration]';
}

if (DOCS_ADMIN && $selected_tab == 'adm')
{
	$nav_bar = array(
		'update_docs'	=> array('Update Documentation', 1),
		'log'			=> array('Activity Log', 1),
	);

	// Yay for hardcoded HTML
	// @TODO Fix this
	switch($selected_sec)
	{
		case '':
		case 'update_docs':
			$selected_sec = 'update_docs';
			$update_file = $root_path . '/' . 'community/cache/.docs_update';

			// Check whether the file exists...
			
			if (isset($_POST['submit']) || file_exists($update_file))
			{
				// Write a file to the happy place
				$fh = fopen($update_file, 'w');
				fwrite($fh, $user->data['user_id'] . "\n" . $user->data['username']);
				fclose($fh);

				$section_content = '
					<div align="center"><h2>Reprocessing Documentation From SVN</h2>
						<p><strong>Request to update submitted.<br />Please wait up to 2 minutes for the process to complete.<br /><br />The results will show up in the logs after completion (coming soon).</strong></p>
						<br /><br />
					</div>';
			}
			else
			{
				$section_content = '
					<div align="center"><h2>Reprocess Documentation From SVN</h2>
						<p><strong>Current Revision:</strong> [to be determined]</p>

						<form action="' . ABS_PATH_TO_DOCS_UG . 'adm/update_docs/" method="post">
							<input type="submit" id="submit" name="submit" value="UPDATE WEBSITE" class="button1" />
						</form>
						<br /><br />
					</div>';
			}
		break;

		case 'log':
			$section_content = '<div align="center"><h2>Coming soon to a theater near you</h2></div><br /><br />';
		break;
		default:
			die("moo");
	}
}
else
{
	// Check if selected tab is valid, or set the first tab @TODO
	if (!isset($tabs[$selected_tab]))
	{
		if ($selected_tab == '')
		{
			$selected_tab = key($tabs);
		}
		else
		{
			ug_report_error('Invalid tab selected', $is_ajax_request);
		}
	}

	// Grab the navigation menu for this tab
	$nav_bar = unserialize(str_replace("\xEF\xBB\xBF", '', file_get_contents('./data/' . DOCS_VERSION . '/' . DOCS_LANG . '/' . $selected_tab . '/_navigation')));

	// Check if selected section is valid, or set the first tab @TODO
	if (!isset($nav_bar[$selected_sec]))
	{
		if ($selected_sec == '')
		{
			$selected_sec = key($nav_bar);
		}
		else
		{
			ug_report_error('Invalid tab selected', $is_ajax_request);
		}
	}

	// Set the content being displayed, depending on whether the selected section is valid
	//@TODO Handle errors properly, maybe move this past the comments block below
	$section_content = isset($nav_bar[$selected_sec]) ? file_get_contents('./data/' . DOCS_VERSION . '/' . DOCS_LANG . '/' . $selected_tab . '/' . $selected_sec) : '';

	//$section_content = str_replace('<span xmlns="" id="quick_requirements"/>', '', $section_content);

//ug_report_error('You are not authorized to access this feature.', $is_ajax_request);

	//
	// COMMENTS BLOCK
	//
	

	if ($comment_action != '')
	{
		// @TODO Sanity checks!!!
		switch($comment_action)
		{
			case 'add':
				if ($user->data['user_id'] != ANONYMOUS)
				{
					// @TODO Check that the comments section is valid by always submitting requests to the full static path
					$comment_text = utf8_normalize_nfc(request_var('comment_text', '', true));
					$comment_section = utf8_normalize_nfc('ug_' . $selected_tab . '_' . $selected_sec);

					$bbcode_uid = $bitfield = $flags = '';
					$enable_bbcode = $enable_magic_url = $enable_smilies = true;
					generate_text_for_storage($comment_text, $bbcode_uid, $bitfield, $flags, $enable_bbcode, $enable_magic_url, $enable_smilies);

					$sql_array = array(
						'topic_id'			=> $comment_section,
						'user_id'			=> $user->data['user_id'],
						'comment_approved'	=> DOCS_ADMIN ? 1 : 0,
						'comment_private'	=> 0,
						'comment_text'		=> $comment_text,
						'comment_timestamp'	=> time(),
						'bbcode_bitfield'	=> $bitfield,
						'bbcode_uid'		=> $bbcode_uid,
						'bbcode_flags'		=> (int) $flags,
					);

					$sql = 'INSERT INTO ' . DOC_COMMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_array);
					$db->sql_query($sql);
				}
				else
				{
					ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
				}
			break;

			case 'approve':
				if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
				{
					$comment_id = request_var('comment_id', '', true);
					$sql_array = array(
						'comment_approved' => 1,
					);
					$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_array) . " WHERE comment_id = '" . $comment_id . "'";
					$db->sql_query($sql);
				}
				else
				{
					ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
				}
			break;

			case 'delete':
				if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
				{
					$comment_id = request_var('comment_id', 0);
					$sql = 'DELETE FROM ' . DOC_COMMENTS_TABLE . ' WHERE comment_id = ' . $comment_id;
					$db->sql_query($sql);
				}
				else
				{
					ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
				}
			break;

			case 'edit':
				if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
				{
					$comment_text = utf8_normalize_nfc(request_var('new_comment_text', '', true));
					$comment_id = request_var('comment-id', 0);

					$bbcode_uid = $bitfield = $flags = '';
					$enable_bbcode = $enable_magic_url = $enable_smilies = true;
					generate_text_for_storage($comment_text, $bbcode_uid, $bitfield, $flags, $enable_bbcode, $enable_magic_url, $enable_smilies);

					$sql_array = array(
						'comment_text' 		=> $comment_text,
						'bbcode_bitfield'	=> $bitfield,
						'bbcode_uid'		=> $bbcode_uid,
						'bbcode_flags'		=> (int) $flags,
					);

					$sql = 'UPDATE ' . DOC_COMMENTS_TABLE . '
						SET ' . $db->sql_build_array('UPDATE', $sql_array) . "
						WHERE comment_id = '" . $comment_id . "'";
					$db->sql_query($sql);
				}
				else
				{
					ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
				}
			break;
		}
	}

	$topic_slug = 'ug' . '_' . $selected_tab . '_' . $selected_sec;

	// @TODO Potentially combine
	if (!DOCS_ADMIN)
	{
		$approved_sql = " AND c.comment_approved = 1";
		$private_sql = " AND c.comment_private = 0";
	}
	else
	{
		$approved_sql = "";
		$private_sql = "";
	}

	$sql_array = array
	(
		'SELECT' => '*',
		'FROM' => array(
			DOC_COMMENTS_TABLE => 'c',
			USERS_TABLE => 'u',
		),
		'WHERE' => 'c.topic_id = "' . $topic_slug .
			'" AND u.user_id = c.user_id' .
			$approved_sql .
			$private_sql,
		'ORDER_BY' => 'c.comment_timestamp ASC',
	);

	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);

	while ($comment_data = $db->sql_fetchrow($result))
	{
		$comment_text = generate_text_for_display($comment_data['comment_text'], $comment_data['bbcode_uid'], $comment_data['bbcode_bitfield'], $comment_data['bbcode_flags']);

		decode_message($comment_data['comment_text'], $comment_data['bbcode_uid']);

		$template->assign_block_vars('comment', array(
			'COMMENT_ID' 	=> $comment_data['comment_id'],
			'AUTHOR'		=> get_username_string('full', $comment_data['user_id'], $comment_data['username'], $comment_data['user_colour']),
			'TIMESTAMP' 	=> $user->format_date($comment_data['comment_timestamp']),
			'TEXT'			=> $comment_text,
			'RAW_TEXT'		=> $comment_data['comment_text'],
			'HEADER_CLASS'	=> docs_header_class($comment_data['comment_approved'], $comment_data['comment_private']),
			'IS_UNAPPROVED'	=> $comment_data['comment_approved'] ? false : true,
			'IS_ADMIN'		=> DOCS_ADMIN ? true : false,
			'CAN_EDIT'		=> can_edit_comment($comment_data['user_id']),
		));
	}

	$template->assign_vars(array(
		'S_COMMENTS_ENABLED'		=> true,
		'COMMENT_ERROR'				=> true,		// Just a test @TODO Remove
	));
}

if ($comment_action != '' && $is_ajax_request)
{
	$template->set_filenames(array(
		'body'	=> DOCS_TEMPLATE_PATH . 'ug_display_comments.html')
	);
}
else
{
	$template->set_filenames(array(
		'body'	=> DOCS_TEMPLATE_PATH . 'ug_index.html')
	);

	foreach ($tabs as $tab_slug => $tab_title)
	{
		
		$template->assign_block_vars('tabs', array(
			//@TODO AJAX fix
			//'U_TAB'			=> $is_ajax_request ? append_sid(ABS_PATH_TO_DOCS_UG) . '#/' . $tab_slug . '/' : append_sid(ABS_PATH_TO_DOCS_UG . $tab_slug . '/'),
			'U_TAB'			=> append_sid(ABS_PATH_TO_DOCS_UG . $tab_slug . '/'),
			'TAB'			=> $tab_title,
			'S_TAB_ACTIVE'	=> ($tab_slug == $selected_tab) ? true : false,
		));
	}

	foreach ($nav_bar as $section_slug => $section_data)
	{
		$template->assign_block_vars('sections', array(
			//'U_SECTION'			=> $is_ajax_request ? append_sid(ABS_PATH_TO_DOCS_UG) . '' . $selected_tab . '/' . $section_slug . '/' : append_sid(ABS_PATH_TO_DOCS_UG . $selected_tab . '/' . $section_slug . '/'),
			'U_SECTION'			=> append_sid(ABS_PATH_TO_DOCS_UG . $selected_tab . '/' . $section_slug . '/'),
			'SECTION'			=> $section_data[0],
			'LEVEL'				=> $section_data[1],
			'S_SECTION_ACTIVE'	=> ($section_slug == $selected_sec) ? true : false,
		));
	}
}

$template->assign_vars(array(
//	'KB_USER'					=> KB_USER,
	'DOCS_ADMIN'				=> DOCS_ADMIN,
	'SECTION_CONTENT'			=> $section_content,
	'SECTION_TITLE'				=> $nav_bar[$selected_sec][0],
	'S_AJAX_REQUEST'			=> $is_ajax_request,
	'SELECTED_SEC'				=> $selected_sec,
	'SELECTED_TAB'				=> $selected_tab,
	'DOCS_UG_PATH'				=> ABS_PATH_TO_DOCS_UG,

//	'S_PATH_TO_TEMPLATE'		=> 'support/docs/kb/'
));

page_header($page_title, false);
page_footer(false);
