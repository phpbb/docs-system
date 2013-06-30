<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

/*
@TODO 
- Overwrite 'new' articles with submitted revisions
- You may not submit an update for an article that you did not write through this system. Please contact a support team member directly with your proposed changes.
- Add form token (surprised they're not already present)

// form_new
// form_edit
// form_new_redirect
// form_edit_redirect
// form_edit_preview

*/

// Standard definitions/includes
define('IN_PHPBB', true);

require('./common.php');

if (!KB_USER)
{
	display_message('DISPLAY_LOGIN');
}

// Other includes
require($phpbb_root_path . 'includes/bbcode.' . $phpEx);
require($root_path . 'includes/support/docs/class_kbarticle.' . $phpEx);

// Figure out what we're doing
if (isset($_POST['submit']))
{
	// Validate submitted details for passing to the class
	$mode = 'submit';
}
else if (isset($_POST['preview']))
{
	// Display a preview using submitted details
	$mode = 'preview';
}
else if (isset($_GET['edit']))
{
	// Display an edit form with a specified article's details filled in
	$mode = 'form_edit';
}
else if (isset($_POST['edit_preview']))
{
	// Display an edit form to modify submitted details (having just come from a preview)
	$mode = 'edit_preview';
}
else if (isset($_POST['add_file']))
{
	// Attach a new file. No field error checking
	$mode = 'attach_file';
}
else if (isset($_POST['delete_file']))
{
	// Delete an attached file. No field error checking
	$mode = 'delete_file';
}
else if (isset($_POST['redirect']))
{
	// Submit/modify a redirect
	if (!KB_ADMIN)
	{
		display_message('Unrecoverable error KLR406');
	}

	$mode = 'redirect';
}
else if (isset($_GET['new_redirect']))
{
	if (!KB_ADMIN)
	{
		display_message('Unrecoverable error REI579');
	}

	$mode = 'new_redirect';
}
else
{
	$mode = '';
}

$print_success = false;

if ($mode)
{
	if ($mode == 'form_edit')
	{
		// Try to find the article using the provided slug
		$sql = 'SELECT a.*, u.username, u.user_colour
			FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u 
			WHERE u.user_id = a.author_id
			AND a.slug = \'' . $db->sql_escape(request_var('edit', '')) . '\'';
		$result = $db->sql_query($sql);
		$new_article_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$new_article_data)
		{
			display_message('Invalid article slug.');
		}

		// Check whether the user has permission to edit this article
		if (!KB_ADMIN && (!$new_article_data['type'] || $new_article_data['author_id'] != $user->data['user_id']))
		{
			display_message('Unrecoverable error BPH176');
		}

		// Work from the previously submitted revision
		if ($new_article_data['revision_status'] == REVISION_PENDING && !request_var('work_from', FALSE))
		{
			// @TODO Check that the revision actually exists, as this doesn't seem to happen

			$sql = 'SELECT r.*, u.username, u.user_colour
				FROM ' . KB_REVISIONS_TABLE . ' r, ' . USERS_TABLE . ' u 
				WHERE u.user_id = r.author_id 
				AND id = ' . $new_article_data['id'];
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);

			$new_article_data['title']				= $row['title'];
			$new_article_data['description'] 		= $row['description'];
			$new_article_data['text']				= $row['text'];
			$new_article_data['for_3_0']			= $row['for_3_0'];
			$new_article_data['for_3_1']			= $row['for_3_1'];
			$new_article_data['author_id']			= $row['author_id'];
			$new_article_data['author_username']	= $row['username'];
			$new_article_data['author_colour']		= $row['user_colour'];

			$new_article_categories = unserialize($row['category_entries']);

			// Load the attachments specified in the revision
			// @TODO This is where the new table comes into play
			/*$sql = 'SELECT attach_id, is_orphan, attach_comment, real_filename
				FROM ' . DOCS_KB_ATTACHMENTS_REVISION_TABLE . '
				WHERE post_msg_id = ' . $new_article_data['post_id'];
			$result = $db->sql_query($sql);
			$new_article_data['attachments'] = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);*/
		}
		// Work from the main article
		else
		{
			// This is used to determine the selected drop down setting
			$s_selected_current = TRUE;

			decode_message($new_article_data['text'], $new_article_data['bbcode_uid']);

			// This is used later
			$new_article_data['author_username'] = $new_article_data['username'];
			$new_article_data['author_colour'] = $new_article_data['user_colour'];

			// Grab which categories this article is in and store them
			$sql = 'SELECT category_slug
				FROM ' . KB_CATEGORY_ENTRIES_TABLE . '
				WHERE article_id = ' . $new_article_data['id'];
			$result = $db->sql_query($sql);

			$new_article_categories = array();

			while ($row = $db->sql_fetchrow($result))
			{
				$new_article_categories[$row['category_slug']] = TRUE;
			}

			$db->sql_freeresult($result);

			// Load the currently associated attachments
			$sql = 'SELECT attach_id, is_orphan, attach_comment, real_filename
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE post_msg_id = ' . $new_article_data['post_id'];
			$result = $db->sql_query($sql);
			$new_article_data['attachments'] = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
		}
	}
	else if ($mode != 'new_redirect')
	{
		/*
		* The following runs if a form has been submitted for any reason,
		* including preview, final submit, attachments, etc.
		*/

		// Prepare for errors
		$errors = array();

		// Was an article id submitted?
		$id = request_var('id', 0);

		$title = utf8_normalize_nfc(request_var('title', '', true));

		$new_article_data = array(
			'id'					=>	$id,
			'title'					=>	$title,
			'slug'					=>	generate_slug($title),
			'description'			=>	utf8_normalize_nfc(request_var('description', '', true)),
			'text'					=>	utf8_normalize_nfc(request_var('text', '', true)),

			'for_3_0'				=>	(isset($_POST['for_3_0'])) ? 1 : 0,
			'for_3_1'				=>	(isset($_POST['for_3_1'])) ? 1 : 0,
		);

		// This takes care of validating the author's information
		// More complicated for admins since they can modify the author
		if (KB_ADMIN)
		{
			$author_username = utf8_normalize_nfc(request_var('author_username', '', true));

			// Handle the special 'phpBB Team' case
			if ($author_username == 'phpBB Team')
			{
				$new_article_data['author_id'] = KB_BOT;
				$new_article_data['author_username'] = 'phpBB Team';
				$new_article_data['author_colour'] = '';
			}
			else
			{
				if ($author_username)
				{
					$sql = 'SELECT user_id, username, user_colour
						FROM ' . USERS_TABLE . "
						WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($author_username)) . "'";
					$result = $db->sql_query($sql);
					$row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);

					if (!$row)
					{
						$errors[] = 'Invalid author.';
						$new_article_data['author_username'] = '';
					}
					else
					{
						$new_article_data['author_id'] = $row['user_id'];
						$new_article_data['author_username'] = $row['username'];
						$new_article_data['author_colour'] = $row['user_colour'];
					}
				}
				else
				{
					$errors[] = 'The author cannot be left blank.';
					$new_article_data['author_username'] = '';
				}
			}

			// Was an edit reason provided?
			$new_article_data['edit_reason'] = request_var('edit_reason', '');
		}
		else
		{
			$new_article_data['author_id'] = $user->data['user_id'];
			$new_article_data['author_username'] = $user->data['username'];
			$new_article_data['author_colour'] = $user->data['user_colour'];
		}

		// Process the categories that this article should be filed under
		$new_article_categories = array();
		foreach ($categories as $key => $category)
		{
			if (isset($_POST['in_' . $key]))
			{
				$new_article_categories[$key] = $category;
			}
		}

		// Validation of the form fields. This doesn't run when attaching or deleting files
		if ($mode != 'attach_file' && $mode != 'delete_file')
		{
			// Set the real $mode and check permissions
			// Also establishes whether a new article will go in the queue or be activated
			if (KB_ADMIN)
			{
				if (isset($_POST['as_moderator']))
				{
					// Don't change the mode if a preview was requested
					if ($mode != 'preview')
					{
						if ($_POST['as_moderator'] == 2)
						{
							$new_article_data['status'] = ARTICLE_ACTIVE;
							$mode = ($id) ? 'edit' : $mode;
						}
						else if ($_POST['as_moderator'] == 1)
						{
							$new_article_data['status'] = ARTICLE_NEW;
							$mode = ($id) ? 'revision' : $mode;
						}
					}
				}
				else if ($mode != 'redirect')
				{
					$errors[] = 'Please select whether you wish to submit the form with user or moderator permissions';
				}
			}
			else
			{
				if ($id)
				{
					$sql = 'SELECT author_id, post_id FROM ' . KB_ARTICLES_TABLE . ' WHERE id = ' . $id;
					$result = $db->sql_query($sql);
					$row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);

					if ($row['author_id'] != $user->data['user_id'])
					{
						display_message('Unrecoverable error HWI643');
					}

					$revision_post_id = (int) $row['post_id'];
					$mode = 'revision';
				}
				else
				{
					$mode = 'submit';
				}

				$new_article_data['status'] = ARTICLE_NEW;
			}

			// Check title length
			$temp_length = utf8_strlen($new_article_data['title']);

			if (!$temp_length)
			{
				$errors[] = 'The title cannot be blank.';
			}
			else if ($temp_length < 10)
			{
				$errors[] = 'The title is too short';
			}
			else if ($temp_length > 80)
			{
				$errors[] = 'The title cannot exceed 80 characters';	
			}

			// Check if title is numeric
			if (is_numeric($new_article_data['title']))
			{
				$errors[] = 'The title must not be a number';	
			}

			// Check description length
			if (!$new_article_data['description'])
			{
				$errors[] = 'The description cannot be blank';
			}
			else if (utf8_strlen($new_article_data['description']) > 200)
			{
				$errors[] = 'The description cannot exceed 200 characters';	
			}

			// Check article body length
			$temp_length = utf8_strlen($new_article_data['text']);

			if (!$temp_length)
			{
				$errors[] = 'The ' . ($mode != 'redirect' ? 'article contents' : 'link') . ' cannot be blank';
			}
			else if ($temp_length < 20 && $mode != 'redirect')
			{
				$errors[] = 'The article is too short';
			}

			// Check for at least one version choice
			if (!$new_article_data['for_3_0'] && !$new_article_data['for_3_1'])
			{
				$errors[] = 'Please select the version(s) that this article applies to.';
			}

			// Check for at least one category choice
			if (empty($new_article_categories))
			{
				$errors[] = 'Please select at least one category that this article should be filed under.';
			}

			// Check for articles with the same slug
			$not_including_id = ($id) ? 'AND id <> ' . $id : '';

			$sql = 'SELECT count(id) AS duplicate_count
				FROM ' . KB_ARTICLES_TABLE . " 
				WHERE slug = '" . $db->sql_escape($new_article_data['slug']) . "'" . 
				$not_including_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult();

			if ($row['duplicate_count'] > 0)
			{
				$errors[] = 'The title you specified is too similar to the title of another article.';
			}
		}

		// Get ready to process attachments
		$attachments = array();
		$new_article_data['attachments'] = array();

		// Get any attachments that were previously uploaded
		for ($i = 0; ; $i++)
		{
			// @TODO '' or 0?
			if ($attachment = request_var('attachment' . $i, ''))
			{
				$attachments[] = $attachment;
			}
			else
			{
				break;
			}
		}

		// If file deletion was requestion, do that
		$delete_file_request = request_var('delete_file', array('' => 0));
		$delete_file_request_id = key($delete_file_request);

		if ($delete_file_request_id)
		{
			$delete_file_request_key = array_search($delete_file_request_id, $attachments);

			// @TODO Delete the file and db entry only when it's not linked to an existing topic

			// The user chose the file, so don't display it anymore
			if ($delete_file_request_key !== FALSE)
			{
				unset($attachments[$delete_file_request_key]);
			}
		}

		// @TODO [phpBB Debug] PHP Notice: in file /includes/functions_upload.php on line 590: Undefined index: EMPTY_FILEUPLOAD
		// @TODO Delete the hard file if it's a newly submitted one
		// If there is a new attachment, it should be uploaded.
		if (isset($_FILES['fileupload']['error']) && $_FILES['fileupload']['error'] != UPLOAD_ERR_NO_FILE)
		{
			// @TODO There may be a direct functions file to include
			require($phpbb_root_path . 'includes/functions_posting.' . $phpEx);

			$filedata = upload_attachment('fileupload', 0);

			// Merge the attachment error into the existing errors array
			$errors = array_merge($errors, $filedata['error']);

			if (!sizeof($filedata['error']))
			{
				$sql_ary = array(
					'physical_filename'	=> $filedata['physical_filename'],
					'attach_comment'	=> 'this is delicious cake',
					'real_filename'		=> $filedata['real_filename'],
					'extension'			=> $filedata['extension'],
					'mimetype'			=> $filedata['mimetype'],
					'filesize'			=> $filedata['filesize'],
					'filetime'			=> $filedata['filetime'],
					'thumbnail'			=> $filedata['thumbnail'],
					'is_orphan'			=> 1,
					'in_message'		=> 0,
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

				$attachments[] = $row['attach_id'];
			}
		}

		// Only allow attachments that were either uploaded by the current user or are already linked to this article.
		// We don't want people attaching things from private forums or something.
		// @TODO Check for ids not already linked to something
		// @TODO Prevent someone from attaching things from another article
		if (!empty($attachments))
		{
			//$new_article_data['attachments'] = array();

			$sql = 'SELECT attach_id, is_orphan, attach_comment, real_filename
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE attach_id IN (' . implode(', ', $attachments) . ')
				AND (poster_id = ' . $user->data['user_id'] . (isset($revision_post_id) ? ' OR post_msg_id = ' . $revision_post_id : '') . ')';
			$result = $db->sql_query($sql);
			
		//			$message_parser->attachment_data = array_merge($message_parser->attachment_data, $db->sql_fetchrowset($result));
	//	$db->sql_freeresult($result);

	// @TODO Make while, now
			for ($i = 0; $row = $db->sql_fetchrow($result); $i++)
			{
				$new_article_data['attachments'][] = $row;
				
				/*[] = array(
					'attach_id'			=> $row['attach_id'],
					'is_orphan'			=> $row['real_filename'],
					'attach_comment'	=> $row['attach_comment'],
					'real_filename'		=> $row['real_filename'],
				);

				$template->assign_block_vars('attachments', array(
					'ID'		=> $row['attach_id'],
					'FILENAME'	=> $row['real_filename'],
					'FIELD'		=> 'attachment' . $i,
				));*/
			}
		}

		// @TODO
		// All of this stuf needs to be rewritten to work with the new attachments 
		/*$new_entry = array(
			'attach_id'		=> $db->sql_nextid(),
			'is_orphan'		=> 1,
			'real_filename'	=> $filedata['real_filename'],
			'attach_comment'=> $this->filename_data['filecomment'],
		);

		$this->attachment_data = array_merge(array(0 => $new_entry), $this->attachment_data);
		$this->message = preg_replace('#\[attachment=([0-9]+)\](.*?)\[\/attachment\]#e', "'[attachment='.(\\1 + 1).']\\2[/attachment]'", $this->message);
		$this->filename_data['filecomment'] = '';*/

		// Format for a preview or submit to the class.
		if ($mode == 'preview')
		{
			$new_article_data['unedited_text'] = $new_article_data['text'];
			$new_article_data['bbcode_uid'] = $new_article_data['bbcode_bitfield'] = $new_article_data['bbcode_flags'] = '';
			generate_text_for_storage($new_article_data['text'], $new_article_data['bbcode_uid'], $new_article_data['bbcode_bitfield'], $new_article_data['bbcode_flags'], 1, 1, 1);
			$new_article_data['text'] = generate_text_for_display($new_article_data['text'], $new_article_data['bbcode_uid'], $new_article_data['bbcode_bitfield'], $new_article_data['bbcode_flags']);
			$new_article_data['text'] = add_title_headers($new_article_data['text']);
		}
		else if ($mode != 'edit_preview' && $mode != 'delete_file' && $mode != 'attach_file' && empty($errors))
		//else if (($mode == 'submit' || $mode == 'redirect') && empty($errors))
		{
			$instance = new kbarticle($mode, $new_article_data, $new_article_categories);
			$error = $instance->run();

			// @TODO Selecting "preview, then "edit" causes a submit
			if ($error)
			{
				$errors[] = $error;
			}
			else
			{
				$print_success = true;
			}
		}
	}
}

// @TODO See if this can be moved into the condition below
if ($mode != 'preview')
{
	foreach ($categories as $key => $category)
	{
		$template->assign_block_vars('category', array(
			'CATEGORY' => $category,
			'KEY' => $key,
			'CHECKED' => isset($new_article_categories[$key]) ? TRUE : FALSE,
		));
	}
}
else
{
	foreach ($new_article_categories as $key => $category)
	{
		$template->assign_block_vars('category', array(
			'CATEGORY' => $category,
			'KEY' => $key,
		));
	}
}

if (isset($new_article_data))
{
	// The 'phpBB Team' special case
	$new_article_data['author_username'] = ($new_article_data['author_id'] == KB_BOT) ? 'phpBB Team' : $new_article_data['author_username'];

	$template->assign_vars(array(
		'ID'					=> $new_article_data['id'],
		'TITLE'					=> $new_article_data['title'],
		'SLUG'					=> $new_article_data['slug'],
		'DESCRIPTION'			=> $new_article_data['description'],
		'TEXT'					=> str_replace(array('<ul>', '<ol style="'), array('<ul style="font-size:1em;">', '<ol style="font-size:1em; '), $new_article_data['text']),
		'UNEDITED_TEXT'			=> $mode == 'preview' ? $new_article_data['unedited_text'] : '',

		'AS_MODERATOR'			=> isset($_POST['as_moderator']) ? $_POST['as_moderator'] : FALSE,
		'EDIT_REASON'			=> isset($new_article_data['edit_reason']) ? $new_article_data['edit_reason'] : FALSE,

		'FOR_3_0'				=> $new_article_data['for_3_0'],
		'FOR_3_1'				=> $new_article_data['for_3_1'],

		'AUTHOR_USERNAME'		=> $new_article_data['author_username'],
		'AUTHOR_USERNAME_FORMATTED'	=> ($new_article_data['author_username']) ? create_author_string($new_article_data['author_id'], $new_article_data['author_username'], $new_article_data['author_colour']) : FALSE,
		'IS_REDIRECT'			=> ($mode == 'redirect') ? 1 : 0,
		'LINK_TO_ARTICLE'		=> '[url=' . SITE_URL . ABS_PATH_TO_DOCS_KB . 'article/' . $new_article_data['slug'] . '/]Knowledge Base - ' . $new_article_data['title'] . '[/url]',
		'ERRORS'				=> empty($errors) ? FALSE : implode('<br />&#8250; ', $errors),

		'S_MODE_EDIT'			=> isset($new_article_data['id']) && $new_article_data['id'] ? TRUE: FALSE,
		'S_HAS_REVISION'		=> isset($new_article_data['revision_status']) && $new_article_data['revision_status'] == REVISION_PENDING ? TRUE : FALSE,
		'S_SELECTED_CURRENT'	=> isset($s_selected_current) ? TRUE : FALSE,

		// @TODO Nuke this
		'S_PRINT_SUCCESS'		=> $print_success,

		'U_ACTION'				=> append_sid(ABS_PATH_TO_DOCS_KB . 'submit/'),
	));

	// Pass along attachments
	if (!empty($new_article_data['attachments']))
	{
		$i = 0;

		foreach ($new_article_data['attachments'] as $attachment)
		{
			$template->assign_block_vars('attachments', array(
				'ID'		=> $attachment['attach_id'],
				'FILENAME'	=> $attachment['real_filename'],
				'FIELD'		=> 'attachment' . $i,
			));

			$i++;
		}
	}
}
else
{
	$template->assign_vars(array(
		'TITLE'						=> 'The number is \'randomly\' generated for quick testing ' . rand(0, 3000),
		'DESCRIPTION'				=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur id nunc. Phasellus lacinia. Pellentesque massa nibh, ultrices semper, fermentum sed, porta eget, est.',
		'TEXT'						=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur id nunc. Phasellus lacinia. Pellentesque massa nibh, ultrices semper, fermentum sed, porta eget, est. Fusce pretium, nulla non facilisis imperdiet, arcu mauris congue urna, ut sagittis sapien eros tempor arcu. Sed justo nunc, semper ut, eleifend nec, semper non, nibh. Nam sollicitudin, mi nec pulvinar mollis, neque dolor venenatis risus, sed lobortis nisl metus eget sapien. Ut ligula. Nullam imperdiet molestie lacus. Nulla rhoncus felis quis ipsum. Fusce in lectus sit amet lectus dictum fermentum. Aenean malesuada lobortis felis. Sed hendrerit odio ut leo. In vulputate ultricies nisl. Integer gravida sapien at nisi. Aliquam at quam. Fusce ante leo, volutpat at, malesuada at, ultricies eget, arcu. In consequat sollicitudin magna. Vestibulum nisi turpis, sagittis nec, rutrum nec, consequat id, risus. Sed sapien. ',

		'IS_REDIRECT'				=> ($mode == 'new_redirect') ? 1 : 0,

		'AUTHOR_USERNAME'			=> $user->data['username'],
		'AUTHOR_USERNAME_FORMATTED'	=> create_author_string($user->data['user_id'], $user->data['username'], $user->data['user_colour']))
	);
}

// The 'phpBB Team' special case demo
$template->assign_var('U_PHPBB_TEAM' , create_author_string('', 'phpBB Team', ''));

// @TODO Revisit which modes still apply here


if ($mode == 'preview')
{
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'kb_article.html')
	);

	$template->assign_var('S_PREVIEW' , TRUE);
}
else
{
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'kb_submit.html')
	);
}

// Generate tabs
if ($mode == 'new_redirect' || $mode == 'redirect')
{
	$tab = 'redirect';
}
else if (isset($new_article_data) && $new_article_data['id'])
{
	$tab = 'Editing Article ' . $new_article_data['id'];
}
else
{
	$tab = 'submit';
}

// Generate the tabs, load the page
process_page('Submit', $tab);
