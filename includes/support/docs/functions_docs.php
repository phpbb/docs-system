<?php
/**
*
* @package phpBB Documentation
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

function docs_header_class($approved, $private)
{
	if ($private)
	{
		return 'private';
	}
	elseif (!$approved)
	{
		return 'unapproved';
	}
	else
	{
		return '';
	}
}

function can_edit_comment($user_id)
{
	global $user;
	if(DOCS_ADMIN)
	{
		return true;
	}
	if($user_id == $user->data['user_id'] && $user->data['user_id'] != ANONYMOUS)
	{
		return true;
	}

	return false;
}

function ug_report_error($message, $is_ajax_request)
{
	if ($is_ajax_request)
	{
		// @TODO Fix this
		echo '<br /><br /><div class="panel" id="message">
			<div class="inner"><span class="corners-top"><span></span></span>
			<h2>Information</h2>
			<p>' .  $message . '</p>

			<span class="corners-bottom"><span></span></span></div>
			</div>';
		exit;
	}
	else
	{
		trigger_error($message);
	}
}


function upload_comment_attachment(){
	
	global $_FILES;
	global $user, $config, $db;
	global $phpbb_root_path, $phpEx;
	
	// Upload file
	if (isset($_FILES['attachment']['error']) && $_FILES['attachment']['error'] != UPLOAD_ERR_NO_FILE)
	{
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
				'poster_id'			=> $user->data['user_id'],
				);

			$db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

			// Get the attachments ID
			// @TODO Use the dbal function here
			$sql = 'SELECT attach_id, real_filename,filesize
					FROM ' . ATTACHMENTS_TABLE . '
					WHERE physical_filename = \'' . $filedata['physical_filename'] . '\'
					AND poster_id = ' . $user->data['user_id'];
			$result = $db->sql_query($sql);

			$row = $db->sql_fetchrow($result);

			//$attachment_id = $row['attach_id'];
								
			// Free result
			$db->sql_freeresult($result);
										
			return $row;
		}
	}
}

