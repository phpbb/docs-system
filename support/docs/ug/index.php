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

//testing admin, remove after test
define('DOCS_ADMIN', TRUE);

$user->add_lang('posting');

// Grab all the tabs that should be displayed
$tabs = unserialize(str_replace("\xEF\xBB\xBF", '', file_get_contents('./data/' . DOCS_VERSION . '/' . DOCS_LANG . '/_tabs')));

// Now see what the user asked to view
// Tab selected by user
$selected_tab = request_var('selected_tab', '');
// Section in each tab selected by user
$selected_sec = request_var('selected_sec', '');

// Comment actions submitted by the user, include admin actions such as edit, delete and approve
//$comment_action = request_var('comment_action', '', true);
$comment_action = request_var('comment_action','',true);

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

function comment_check_attachment($comment_id)
{
	global $db;
	// Get comment attachments 
	$sql = 'SELECT a.* 
			FROM '.	DOC_COMMENTS_ATTACHMENTS_TABLE.' d
			INNER JOIN '.ATTACHMENTS_TABLE.' a ON d.attach_id=a.attach_id 
			WHERE d.comment_id='. intval($comment_id).' and d.module=\'ug\'';
		
	$attachment_data = $db->sql_query($sql);
	
	$result=array();
	
	while ($attach = $db->sql_fetchrow($attachment_data))
	{
		$result[]=$attach;
	}
	
	return $result;
}

if (DOCS_ADMIN && $selected_tab == 'adm')
{

	// Grab the navigation menu for admin tab
	//$nav_bar = unserialize(str_replace("\xEF\xBB\xBF", '', file_get_contents('./data/' . DOCS_VERSION . '/' . DOCS_LANG . '/' . $selected_tab . '/_navigation')));

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
	
	$submit = ($_POST) ? true : false;

	$attached_ids=array();
	
	//uploading attachment
	if($submit)
	{	
		if($_FILES)
		{	echo "cry";
			exit;
			$attachment = upload_comment_attachment();
			/*$template->assign_block_vars('attachments', array(
					'ID' 	=> $attachment['attach_id'],
					'TITLE'	=> $attachment['real_filename'],
					'SIZE' 	=> $attachment['filesize']
			));*/
			// Grab attachment id for later add post use
			$attachment_id = $attachment['attach_id'];
			array_push($attached_ids,$attachment_id);
			echo "i am uploaded";
		}
		
		
		//$attachments = request_var('attachments', '');
		$attachments=$_POST['attachments'];
		
		// delete attachment is submit delete action
		// why cannot use request_var here
		$del_attachment_id = $_POST['delete_attach'];

		if(isset($del_attachment_id)&&intval($del_attachment_id)>0)
		{

			$sql = 'DELETE FROM ' . ATTACHMENTS_TABLE . ' WHERE attach_id = ' . $del_attachment_id;
			$result=$db->sql_query($sql);
			
			// After delete pop deleted id
			if($result&&count($attachments)>0)
			{
				$temp = array();
				array_push($temp,$del_attachment_id);
				$attachments = array_diff($attachments, $temp);
			}		
		}
		
		if($attachments&&count($attachments)>0)
		{
			foreach($attachments AS $k=>$v) array_push($attached_ids,$v);
		}
					
	}
	
	
	//
	// COMMENTS BLOCK
	//
	// Comment actions - delete, add and approve

	if ($comment_action != '')
	{
		// @TODO Sanity checks!!!
		// updated sanity with $db->sql_escape
		switch($comment_action)
		{
			case 'add':
				if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
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
						'comment_text'		=> $db->sql_escape($comment_text),
						'comment_timestamp'	=> time(),
						'bbcode_bitfield'	=> $bitfield,
						'bbcode_uid'		=> $bbcode_uid,
						'bbcode_flags'		=> (int) $flags,
					);
					
					if(trim($comment_text)!=""&&$user->data['user_id']){
						$sql = 'INSERT INTO ' . DOC_COMMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_array);
						$db->sql_query($sql);
						$comment_id = $db->sql_nextid();
					}

					// link comment table and attachment table together	
					if(count($attached_ids)>0&&intval($comment_id)>0){
						// Insert the attachment id into the docs_comment_attachment table for reference
						$i=0;
						
						while($i<count($attached_ids))
						{
							$sql_ary = array(
								'attach_id'	=> $attached_ids[$i],
								'comment_id' => $comment_id,
								'module'=> 'ug'
							);							

							$sql = 'INSERT INTO ' . DOC_COMMENTS_ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
							
							$db->sql_query($sql);
							
							$i++;
						}
						
						// Reset attachment arrays
						$attached_ids=array();
					}
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
					
					$attach_array= comment_check_attachment($comment_id);
					
					// Get rid of records related to attachments
					if(count($attach_array)>0)
					{
						foreach($attach_array as $attach)
						{
							$sql = 'DELETE FROM ' . ATTACHMENTS_TABLE . ' WHERE attach_id = ' . $attach['attach_id'];
							$db->sql_query($sql);
							
							$sql = 'DELETE FROM ' . DOC_COMMENTS_ATTACHMENTS_TABLE . ' WHERE comment_id = ' . $comment_id . ' AND attach_id = ' .$attac['attach_id'] .' AND module = \'ug\'';
							$db->sql_query($sql);
						}
					}
					
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
					
					if (isset($_FILES['attachment']['error']) && $_FILES['attachment']['error'] != UPLOAD_ERR_NO_FILE)
					{	
						$attach_id= comment_check_attachment($comment_id);
						
						// Get rid of records related to attachments
						if($attach_id)
						{	
							$sql = 'DELETE FROM ' . ATTACHMENTS_TABLE . ' WHERE attach_id = ' . $attach_id;
							$db->sql_query($sql);
							
							// @TODO There may be a direct functions file to include
							require($phpbb_root_path . 'includes/functions_posting.' . $phpEx);

							$filedata = upload_attachment('attachment', 0);

							if (!sizeof($filedata['error']))
							{
								$sql_ary = array(
									'physical_filename'	=> $filedata['physical_filename'],
									'attach_comment'	=> '',
									'real_filename'		=> $filedata['real_filename'],
									'extension'			=> $filedata['extension'],
									'mimetype'			=> $filedata['mimetype'],
									'filesize'			=> $filedata['filesize'],
									'filetime'			=> $filedata['filetime'],
									'thumbnail'			=> $filedata['thumbnail'],
									'is_orphan'			=> 0,
									'in_message'		=> 0,
									'',
									'poster_id'			=> $user->data['user_id'],
									);

								$db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

								// Get the attachments ID
								// @TODO Use the dbal function here
								$sql = 'SELECT attach_id, real_filename
										FROM ' . ATTACHMENTS_TABLE . '
										WHERE physical_filename = \'' . $filedata['physical_filename'] . '\'
										AND poster_id = ' . $user->data['user_id'];
								$result = $db->sql_query($sql);

								$row = $db->sql_fetchrow($result);

								$attachment_id = $row['attach_id'];
													
								// Free result
								$db->sql_freeresult($result);
													
								// Update document attachment table
								$sql_array = array(
									'attach_id' => $attachment_id,
								);
								$sql = 'UPDATE ' . DOC_COMMENTS_ATTACHMENTS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_array) . " WHERE comment_id = '" . $comment_id . "' AND module='ug'";
								$db->sql_query($sql);
							}							

						}
						else
						{
							comment_upload_attachment($comment_id);
						}
					}
				}
				else
				{
					ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
				}
			break;
		}
	}
	// Rending uploaded attachment, must put this function below comment_action to ensure if the form is uploaded then the form is resetted
	
	if(count($attached_ids)>0)
	{
		$sql = 'SELECT *
					FROM ' . ATTACHMENTS_TABLE . '
					WHERE attach_id IN (' . implode(',', $attached_ids) . ')';

		$attach_result = $db->sql_query($sql);

		while ($result = $db->sql_fetchrow($attach_result))
		{
			$template->assign_block_vars('attachments', array(
						'ID' 	=> $result['attach_id'],
						'TITLE'	=> $result['real_filename'],
						'SIZE' 	=> $result['filesize']
			));
		}
	}
	
	
	$topic_slug = 'ug' . '_' . $selected_tab . '_' . $selected_sec;

	// Getting comments
	// @TODO Potentially combine
	
	$start = request_var('start', 0);
	//$limit = request_var('limit', (int) $config['topics_per_page']);
	$limit = request_var('limit', 5);
	
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
	
	// Total number of comments
	/*$sql = 'SELECT COUNT(c.comment_id) AS total_comments
			FROM ' . DOC_COMMENTS_TABLE . ' c
			WHERE c.topic_id = "'. $topic_slug .'"'.
				$approved_sql .
				$private_sql;*/
	$sql_array = array
	(
		'SELECT' => 'count(c.comment_id) AS total_comments',
		'FROM' => array(
			DOC_COMMENTS_TABLE => 'c',
			USERS_TABLE => 'u',
		),
		'WHERE' => 'c.topic_id = "' . $topic_slug .
			'" AND u.user_id = c.user_id' .
			$approved_sql .
			$private_sql
	);

	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	$total_comments = (int) $db->sql_fetchfield('total_comments');
	$db->sql_freeresult($result);

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
		'ORDER_BY' => 'c.comment_timestamp DESC',
	);

	$sql = $db->sql_build_query('SELECT', $sql_array);
	//$result = $db->sql_query($sql);
	$result = $db->sql_query_limit($sql, $limit, intval($start));
	
	$page_url = '';//ABS_PATH_TO_DOCS_UG
	
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
		
		// Get comment attachments 

		$sql = 'SELECT a.* 
			FROM '.	DOC_COMMENTS_ATTACHMENTS_TABLE.' d
			INNER JOIN '.ATTACHMENTS_TABLE.' a ON d.attach_id=a.attach_id 
			WHERE d.comment_id='. intval($comment_data['comment_id']).' and d.module=\'ug\'';
		
		$attachment_data = $db->sql_query($sql);
		
		while ($attach = $db->sql_fetchrow($attachment_data))
		{
			$template->assign_block_vars('comment.saved_attachment', array(
						'ID' 	=> $attach['attach_id'],
						'TITLE'	=> $attach['real_filename'],
						'SIZE' 	=> $attach['filesize'],
			));
		}
	}

	
	$template->assign_vars(array(
		'S_COMMENTS_ENABLED'		=> true,
		'COMMENT_ERROR'				=> true,		// Just a test @TODO Remove
		'PAGINATION'     => generate_pagination(append_sid(ABS_PATH_TO_DOCS_UG . $selected_tab . '/' . $section_slug ), $total_comments, $limit, $start),
		'PAGE_NUMBER'     => on_page($total_comments, $limit, $start)
	));
	
}

//if ($comment_action != '' && $is_ajax_request)
if ($comment_action != '')
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
