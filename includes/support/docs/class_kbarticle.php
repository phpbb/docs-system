<?php
/**
*
* @package phpBB3 Website
* @copyright (c) 2011 phpBB Group
* @license Not for redistribution
*
* Fair Warning:
* If you are here to maintain this file, please accept my sincerest apologies and
* understand that I was coerced into writing everything under the threat that
* the author of submit_post() would do it otherwise.
*
* If you plan on messing with anything attachment-related, I have found that a large
* scotch dulls the pain.
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

class kbarticle
{
	var $submitted_data;
	var $cached_category_entries;
	var $mode;

	var $new_log_entry;
	var $time;

	function kbarticle($mode, $article_data, $category_entries = '')
	{
		$this->submitted_data = $article_data;
		$this->cached_category_entries = $category_entries;
		$this->mode = $mode;

		$this->new_log_entry = array();
		$this->time = time();
	}

	function run()
	{
		// Decide where we go from here
		switch ($this->mode)
		{
			case 'submit':
				$this->add();
			break;

			case 'edit':
			case 'revision':
				return $this->update();
			break;

			case 'mcp_activation':
				$this->mcp_activation();
			break;

			case 'mcp_reassign':
				$this->mcp_reassign();
			break;

			case 'mcp_approve_rev':
				$this->mcp_approve_rev();
			break;

			case 'redirect':
				$this->submit_redirect();
			break;

			default:
				// @TODO Replace with a vague error message before live
				var_dump($article_data);
				die('The mode is:' . $this->mode);
		}
	}

	protected function submit_redirect()
	{
		global $db;

		// Submit the article
		$this->commit_content_changes();

		// Figure out the submitted article's id
		$sql = 'SELECT id FROM ' . KB_ARTICLES_TABLE . '
				WHERE slug = \'' . $this->submitted_data['slug'] . '\'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$this->submitted_data['id'] = $row['id'];

		// Submit the article's category entries
		$this->commit_category_changes();
	}

	protected function mcp_approve_rev()
	{
		global $db;

		// Get the revision's data
		$sql = 'SELECT * FROM ' . KB_REVISIONS_TABLE . '
				WHERE id = ' . $this->submitted_data['id'];
		$result = $db->sql_query($sql);
		$revision_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$revision_data)
		{
			trigger_error("Unrecoverable error 406THI");
		}

		$this->submitted_data['title']				= $revision_data['title'];
		$this->submitted_data['slug']				= generate_slug($revision_data['title']);
		$this->submitted_data['description']		= $revision_data['description'];
		$this->submitted_data['for_3_0']			= $revision_data['for_3_0'];
		$this->submitted_data['for_3_1']			= $revision_data['for_3_1'];
		$this->submitted_data['revision_status']	= REVISION_APPROVED;
		$this->submitted_data['revision_date']		= $this->time;
		$this->submitted_data['author_id']			= $revision_data['author_id'];
		$this->submitted_data['text']				= $revision_data['text'];
		$this->submitted_data['bbcode_flags']		= $revision_data['bbcode_flags'];

		// Use a trick to handle attachments. Move all the current attachments to the bump post
		// Then move the revision attachments back to the first post
		$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
				SET post_msg_id = ' . $revision_data['bump_post_id'] . '
				WHERE post_msg_id = ' . $this->submitted_data['post_id'];
		$db->sql_query($sql);

		$this->update_attachments(unserialize($revision_data['attachments']), $this->submitted_data['post_id']);

		$this->cached_category_entries = unserialize($revision_data['category_entries']);

		if (isset($current_article_data['pm_text']))
		{
			$this->mcp_send_pm($current_article_data['pm_subject'], $current_article_data['pm_text']);

			$this->new_log_entry = "Notified user of action via PM:[quote][b]Subject: [/b]" . $current_article_data['pm_subject'] . "\n\n" . $current_article_data['pm_text'] . '[/quote]';
		}
		else
		{
			$this->new_log_entry = 'User was not notified of this action via PM';
		}

		$this->new_log_entry = array('Approved revision. Article is now active. - ' . $this->new_log_entry);

		// Delete the entry from the revisions table
		$sql = 'DELETE FROM ' . KB_REVISIONS_TABLE . '
				WHERE id = ' . $this->submitted_data['id'];
		$db->sql_query($sql);

		$this->commit_session_changelog();
		$this->commit_content_changes();
		$this->commit_category_changes();
		
		// @TODO Delete revision data and mark the article as having no revision
		// Maybe "appoved and active" in Manage
	}

// @TODO Merge this into update()
	protected function get_article_data($condition)
	{
		global $db;

		$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
				FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
				WHERE u.user_id = a.author_id
				AND ' . $condition;
		$result = $db->sql_query_limit($sql, 1);
		$article_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$article_data)
		{
			trigger_error("Unrecoverable error 14686A");
		}

		return $article_data;
	}

	/*
	 * Handles all changes made to existing articles (revisions/edits)
	 */
	protected function update()
	{
		global /*$user, */$db, $categories;

		// Grab the existing article's data for diffing
		$stored_article_data = &$this->get_article_data('a.id = ' . $this->submitted_data['id']);

		// Cannot edit redirects
		if ($stored_article_data['is_redirect'])
		{
			trigger_error("Unrecoverable error 970HON");
		}

		// Cache article's post and topic ids
		$this->submitted_data['topic_id'] = $stored_article_data['topic_id'];
		$this->submitted_data['post_id'] = $stored_article_data['post_id'];

		decode_message($stored_article_data['text'], $stored_article_data['bbcode_uid']);

		// Grab the existing category entries for diffing
		$sql = 'SELECT category_slug
				FROM ' . KB_CATEGORY_ENTRIES_TABLE . '
				WHERE article_id = ' . $this->submitted_data['id'];
		$result = $db->sql_query($sql);

		$stored_category_entries = array();

		while ($row = $db->sql_fetchrow($result))
		{
			$stored_category_entries[$row['category_slug']] = $categories[$row['category_slug']];
		}

		// The stuff we want to log
		$this->new_log_entry = array();

		// Check if any actual changes have been made and add them to the log
		if ($stored_article_data['title'] != $this->submitted_data['title'])
		{
			$this->new_log_entry[] = 'Changed title from "' . $stored_article_data['title'] . '" to "' . $this->submitted_data['title'] . '"';
		}

		if ($stored_article_data['author_id'] != $this->submitted_data['author_id'])
		{
			$this->new_log_entry[] = 'Changed author from ' . create_author_string($stored_article_data['user_id'], $stored_article_data['username'], $stored_article_data['user_colour'], TRUE) . ' to ' . create_author_string($this->submitted_data['author_id'], $this->submitted_data['author_username'], $this->submitted_data['author_colour'], TRUE);
		}

		if ($stored_article_data['description'] != $this->submitted_data['description'])
		{
			$this->new_log_entry[] = 'Changed description from: [code]' . $stored_article_data['description'] . '[/code]To:[code]' . $this->submitted_data['description'] . '[/code]';
		}

		if ($stored_article_data['text'] != $this->submitted_data['text'])
		{
			$this->new_log_entry[] = 'Changed article content from: [code]' . $stored_article_data['text'] . '[/code]To:[code]' . $this->submitted_data['text'] . '[/code]';
		}

		if ($stored_article_data['for_3_0'] != $this->submitted_data['for_3_0'])
		{
			$this->new_log_entry[] = (($this->submitted_data['for_3_0']) ? "Removed article from" : "Added article to") . ' the [b]phpBB 3.0[/b] section';
		}

		if ($stored_article_data['for_3_1'] != $this->submitted_data['for_3_1'])
		{
			$this->new_log_entry[] = (($this->submitted_data['for_3_1']) ? "Removed article from" : "Added article to") . ' the [b]phpBB 3.1[/b] section';
		}

		// Has the article been added to any categories?
		$changed_categories = '';

		foreach($stored_category_entries as $key => $category)
		{
			if (!isset($this->cached_category_entries[$key]))
			{
				$changed_categories .= '[b]' . $category . '[/b], ';
			}
		}

		if ($changed_categories != '')
		{
			$this->new_log_entry[] = 'Added to: ' . substr($changed_categories, 0, -2);
		}

		// Has the article been removed from any categories?
		$changed_categories = '';

		foreach($this->cached_category_entries as $key => $category)
		{
			if (!isset($stored_category_entries[$key]))
			{
				$changed_categories .= '[b]' . $category . '[/b], ';
			}
		}

		if ($changed_categories != '')
		{
			$this->new_log_entry[] = 'Removed from: ' . substr($changed_categories, 0, -2);
		}

		// We need to perform some diffing ops on the attachments and store the results
		$attachments_in_revision = array();
		$attachments_added = array();
		$attachments_deleted = array();

		foreach ($this->submitted_data['attachments'] as $attachment_data)
		{
			$attachments_in_revision[$attachment_data['attach_id']] = false;
		}

		// Get the current attachments @TODO specify columns
		$sql = 'SELECT *
				FROM ' . ATTACHMENTS_TABLE. '
				WHERE post_msg_id = ' . $this->submitted_data['post_id'];
		$result = $db->sql_query($sql);

		// Iterate through the attachments in the current version of the article
		while ($row = $db->sql_fetchrow($result))
		{
			// Check whether the attachment has been deleted in this revision
			if (isset($attachments_in_revision[$row['attach_id']]))
			{
				$attachments_in_revision[$row['attach_id']] = true;
			}
			else
			{
				$attachments_deleted[] = $row['attach_id'];
			}
		}

		// Now identify any attachments that are new in this revision
		foreach($attachments_in_revision as $attachment_id => $existed_before)
		{
			if (!$existed_before)
			{
				$attachments_added[] = $attachment_id;
			}
		}

		if (!empty($attachments_added))
		{
			$this->new_log_entry[] = array(LOG_ATTACHMENT, 'The following attachments were added: ', $attachments_added);
		}

		if (!empty($attachments_deleted))
		{
			$this->new_log_entry[] = array(LOG_ATTACHMENT, 'The following attachments were deleted: ', $attachments_deleted);
		}

		// @TODO This seems to be the place to check for attachment changes.

		// This is used by process_log()
		// @TODO See if the above is true
		$this->submitted_data['revision_date'] = $stored_article_data['revision_date'];

		// If the log is empty, then no changes have been made, so there is nothing to update.
		if (!empty($this->new_log_entry))
		{
			// The changelog for this session is done. The queue post also will need updating
			$bump_post_id = $this->commit_session_changelog($this->submitted_data['edit_reason'], TRUE);

			// Link any newly submitted attachments to the bump post
			if ($this->mode == 'edit')
			{
				$this->update_attachments($attachments_deleted, $bump_post_id);

				$bump_post_id = $this->submitted_data['post_id'];
			}
			else
			{
				$this->submitted_data['bump_post_id'] = $bump_post_id;
			}

			$this->update_attachments($attachments_added, $bump_post_id, $this->submitted_data['topic_id']);

			// Submit content changes. This will also take care of running $this->commit_category_changes() for us
			$this->commit_content_changes();
		}
		else
		{
			return 'No changes were made';
		}
	}

	protected function update_attachments($attachment_ids, $post_id, $topic_id = false)
	{
		global $db;

		if (empty($attachment_ids))
		{
			return;
		}

		$sql_array = array('post_msg_id' => $post_id);

		if ($topic_id)
		{
			$sql_array['topic_id']	= $topic_id;
			$sql_array['is_orphan']	= 0;
			//'in_message'		=> 0,
		}

		$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
				WHERE attach_id IN (' . implode(', ', $attachment_ids) . ')';
		
		$db->sql_query($sql);
	}

	protected function mcp_activation()
	{
		global $db;

		// Switch the activation status
		if ($this->submitted_data['status'] == ARTICLE_ACTIVE)
		{
			$this->submitted_data['status'] = ARTICLE_INACTIVE;
			$this->new_log_entry = array('Deactivated article');
		}
		else
		{
			$this->submitted_data['status'] = ARTICLE_ACTIVE;
			$this->new_log_entry = array('Activated article');
		}

		$sql = 'UPDATE ' . KB_ARTICLES_TABLE . '
				SET status = ' . $this->submitted_data['status'] . '
				WHERE id = ' . $this->submitted_data['id'];
		$db->sql_query($sql);

		if (isset($this->submitted_data['pm_text']))
		{
			$this->mcp_send_pm($this->submitted_data['pm_subject'], $this->submitted_data['pm_text']);
		
			$this->new_log_entry[] = "Notified user of action via PM:[quote][b]Subject: [/b]" . $this->submitted_data['pm_subject'] . "\n\n" . $this->submitted_data['pm_text'] . '[/quote]';
		}
		else
		{
			$this->new_log_entry[] = 'User was not notified of this action via PM';
		}

		$this->commit_session_changelog();
	}

	protected function mcp_reassign()
	{
		global $db, $assignments;

		$sql = 'UPDATE ' . KB_ARTICLES_TABLE . '
				SET assigned_to = \'' . $this->submitted_data['assigned_to'] . '\'
				WHERE id = ' . $this->submitted_data['id'];
		$db->sql_query($sql);

		if (!$this->submitted_data['is_redirect'])
		{
			$this->new_log_entry = 'Reassigned to the ' . $assignments[$this->submitted_data['assigned_to']];
			$this->commit_session_changelog();
		}
	}

	protected function add()
	{
		global $db;

		// Create a temporary placeholder topic in the queue forum
		$this->submitted_data['post_id'] = $this->create_post($this->submitted_data['title'], 'Temporary placeholder');

		// Now that the post and topic ids are known, we can submit the actual article
		$this->commit_content_changes();

		$this->submitted_data['id'] = $db->sql_nextid();

		// Format the attachments
		$attachments = array();

		foreach ($this->submitted_data['attachments'] as $attachment)
		{
			$attachments[] = $attachment['attach_id'];
		}

		// Submit attachments
		$this->update_attachments($attachments, $this->submitted_data['post_id'], $this->submitted_data['topic_id']);

		/*
		* Now we need to submit the first log entry
		* This couldn't be done sooner because $this->submitted_data['id'] wasn't available
		* new_log_entry() will update the entry in the database
		*/
		$this->new_log_entry = 'Original article submitted';

		$this->commit_session_changelog();

		$this->commit_category_changes();
	}

	protected function mcp_send_pm($subject, $message)
	{
		global $phpbb_root_path, $phpEx, $db;

		if (!function_exists('submit_pm'))
		{
			include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		}

		$to = array('u' => array(), 'g' => array());
		$to['u'][$this->submitted_data['author_id']] = 'to';

		$pm_data = array(
			'from_user_id'			=> KB_BOT,
			'from_user_ip'			=> '127.0.0.1',
			'from_username'			=> 'Support Robot',
			'icon_id'				=> 0,
			'enable_sig'			=> 0,
			'enable_bbcode'			=> 1,
			'enable_smilies'		=> 1,
			'enable_urls'			=> 1,
			'bbcode_bitfield'		=> '',
			'bbcode_uid'			=> '',
			'bbcode_flags'			=> 7,
			'message'				=> $message,
			'address_list'			=> $to
		);

		generate_text_for_storage($pm_data['message'], $pm_data['bbcode_uid'], $pm_data['bbcode_bitfield'], $pm_data['bbcode_flags'], 1, 1, 1);

		submit_pm('post', $subject, $pm_data);
	}

	protected function create_post($title, $message)
	{
		global $phpbb_root_path, $db, $phpEx, $user, $config, $auth;

		// Load the posting stuff
		if (!function_exists('submit_post'))
		{
			include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		}

		// Get queue forum's name to check that it exists and for use in the array below
		$sql = 'SELECT forum_name, enable_indexing
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . KB_QUEUE_FORUM;
		$result = $db->sql_query($sql);
		$forum_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$forum_data)
		{
			trigger_error("Unrecoverable error 1B.");
		}

		/*
		* 1) Save old permissions
		* 2) Give user Support Bot's permissions
		* 3) Post message with new permissions
		* 4) Restore old permissions
		* 5) ???
		* 6) Profit
		*/
		$sql = 'SELECT username, user_colour, user_permissions, user_type
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . KB_BOT;
		$result = $db->sql_query($sql);
		$user_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$user_data)
		{
			return false;
		}

		$real_data = $user->data;
		$real_auth = $auth;

		$user->data['user_id']			= KB_BOT;
		$user->data['username']			= $user_data['username'];
		$user->data['user_colour']		= $user_data['user_colour'];
		$user->data['user_permissions']	= $user_data['user_permissions'];
		$user->data['user_type']		= $user_data['user_type'];
		$user->data['user_ip']			= '127.0.0.1';

		$auth = new auth();
		$auth->acl($user->data);

		// Poll? What poll?
		$poll = '';

		// Bots have no limits
		$config['max_post_urls'] = 0;

		// Is this a new post or a reply?
		$new_topic = isset($this->submitted_data['topic_id']) ? FALSE : TRUE;

// @ TODO		// Attachments
		//$attachments =

		// Standard stuff
		$data = array(
			'forum_id'				=> KB_QUEUE_FORUM,
			'forum_name'			=> $forum_data['forum_name'],

			'topic_title'			=> $title,
			'topic_time_limit'		=> 0,
			'topic_attachment'		=> 0,
			'topic_id'				=> ($new_topic) ? 0 : $this->submitted_data['topic_id'],
			'icon_id'				=> 1,

			'poster_id'				=> KB_BOT,
			'poster_ip'				=> '127.0.0.1',

			'post_id'				=> 0,
			'post_time'				=> $this->time,
			'post_edit_locked'		=> 0,
			'post_approved'			=> 1,
			'message'				=> $message,
			'bbcode_uid'			=> '',
			'bbcode_bitfield'		=> '',
			'bbcode_flags'			=> '',
			'enable_sig'			=> 0,
			'enable_bbcode'			=> 1,
			'enable_smilies'		=> 1,
			'enable_urls'			=> 1,
			'attachment_data'		=> '',
			'message_md5'			=> '',

			'notify'				=> FALSE,
			'notify_set'			=> FALSE,

			'enable_indexing'		=> ($new_topic) ? 0 : $forum_data['enable_indexing'],
			'post_mode'				=> ($new_topic) ? 'post' : 'reply',
		);

		// If this is a reply, then the BBCode needs to be processed
		// @TODO wtf?
		//if (!$new_topic)
		//{
			generate_text_for_storage($data['message'], $data['bbcode_uid'], $data['bbcode_bitfield'], $data['bbcode_flags'], 1, 1, 1);
		//}
		
		//die("test");

		// Submit the new post
		submit_post($data['post_mode'], $data['topic_title'], $user_data['username'], POST_NORMAL, $poll, $data, true);

		// Reset the user's proper permissions
		$user->data = $real_data;
		$auth = $real_auth;

		// If this is a new topic, then the topic id should be saved for later use
		if ($new_topic)
		{
			$this->submitted_data['topic_id'] = $data['topic_id'];
		}

		return $data['post_id'];
	}

	protected function commit_content_changes()
	{
		global $db;

		$this->submitted_data['bbcode_uid'] = $this->submitted_data['bbcode_bitfield'] = $new_article_data['bbcode_flags'] = '';

		if ($this->mode != 'redirect')
		{
			generate_text_for_storage($this->submitted_data['text'], $this->submitted_data['bbcode_uid'], $this->submitted_data['bbcode_bitfield'], $this->submitted_data['bbcode_flags'], 1, 1, 1);
		}

		$sql_array = array(
			'id'					=> $this->submitted_data['id'],
			'title'					=> $this->submitted_data['title'],
			'slug'					=> $this->submitted_data['slug'],
			'description'			=> $this->submitted_data['description'],
			'text'					=> $this->submitted_data['text'],

			'for_3_0'				=> $this->submitted_data['for_3_0'],
			'for_3_1'				=> $this->submitted_data['for_3_1'],

			'bbcode_uid'			=> $this->submitted_data['bbcode_uid'],
			'bbcode_bitfield'		=> $this->submitted_data['bbcode_bitfield'],
			'bbcode_flags'			=> 7,

			'author_id'				=> $this->submitted_data['author_id'],

			'date_last_modified'	=> $this->time,
		);

		switch ($this->mode)
		{
			case 'submit':
				$sql_array['date_submitted']	= $this->time;
				$sql_array['topic_id']			= $this->submitted_data['topic_id'];
				$sql_array['post_id']			= $this->submitted_data['post_id'];
				$sql_array['status']			= $this->submitted_data['status'];

				$sql = 'INSERT INTO ' . KB_ARTICLES_TABLE . $db->sql_build_array('INSERT', $sql_array);
				break;

			case 'redirect':
				$sql_array['date_submitted']	= $this->time;
				$sql_array['status']			= $this->submitted_data['status']; // @TODO ???
				$sql_array['is_redirect']		= 1;

				$sql = 'REPLACE INTO ' . KB_ARTICLES_TABLE . $db->sql_build_array('INSERT', $sql_array);
				break;

			case 'mcp_approve_rev':
				$sql_array['date_submitted']	= $this->time;
				$sql_array['revision_status']	= REVISION_APPROVED;
				$sql_array['status']			= ARTICLE_ACTIVE;

			case 'edit':
				$sql = 'UPDATE ' . KB_ARTICLES_TABLE . '
						SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
						WHERE id = ' . $this->submitted_data['id'];

				// Now is a good time to submit the category changes as well
				$this->commit_category_changes();
				break;

			case 'revision':
				// Update a few things in the main table as well
				$sql_array2 = array(
					'revision_status'	=> REVISION_PENDING,
					'revision_date'		=> $this->time,
				);

				$sql = 'UPDATE ' . KB_ARTICLES_TABLE . '
						SET ' . $db->sql_build_array('UPDATE', $sql_array2) . '
						WHERE id = ' . $this->submitted_data['id'];
				$db->sql_query($sql);

				// Attachments
				// @TODO Make this a function
				$revision_attachments = array();
				foreach ($this->submitted_data['attachments'] as $attachment_data)
				{
					$revision_attachments[] = $attachment_data['attach_id'];
				}

				$sql_array['attachments'] = serialize($revision_attachments);
				$sql_array['bump_post_id'] = $this->submitted_data['bump_post_id'];

				// These things should not be stored in the revisions table
				unset($sql_array['slug']);
				unset($sql_array['date_last_modified']);

				// Add the category entries in serialized form
				$sql_array['category_entries'] = serialize($this->cached_category_entries);

				// Using a REPLACE here because we can
				$sql = 'REPLACE INTO ' . KB_REVISIONS_TABLE . $db->sql_build_array('INSERT', $sql_array);
				break;
			default:
			// @TODO Throw generic error
		}

		$db->sql_query($sql);
	}

	protected function format_log_entry($log_entry)
	{
		$output = '';

		foreach ($log_entry as $single_line)
		{
			if (is_array($single_line))
			{
				switch ($single_line[0])
				{
					case LOG_NEW_POST:
						$link = '[url=' . BOARD_URL . '/viewtopic.php?p=' . $single_line[2] . '#p' . $single_line[2] . ']Post ' . $single_line[2] . '[/url]';
						$single_line = sprintf($single_line[1], $link);
					break;

					case LOG_ATTACHMENT:
						$attachment_links = '';

						foreach ($single_line[2] as $attachment_id)
						{
							$attachment_links .= ', [url=' . BOARD_URL . '/download/file.php?mode=view&id=' . $attachment_id . ']File #' .$attachment_id . '[/url]';
						}

						$single_line = $single_line[1] . substr($attachment_links, 2);
					break;
				}
			}

			$output .= "\n\t[*]" . $single_line;
		}

		return $output;
	}

	/*
	* Writes the current session's changelog to the KB's log table.
	*/
	protected function commit_session_changelog($edit_reason = '', $create_new_post = FALSE)
	{
		global $db, $user;

		// We should always have something to add
		if (empty($this->new_log_entry))
		{
			trigger_error("Unrecoverable error FTY834.");
		}

		// We need some additional information about the user who is generating this entry
		$sql = 'SELECT r.rank_title
				FROM ' . USERS_TABLE . ' u, ' . RANKS_TABLE . ' r
				WHERE u.user_rank = r.rank_id
				AND u.user_id = \'' . $user->data['user_id'] . '\'';
		$result = $db->sql_query_limit($sql, 1);
		$user_rank_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		// The log should be an array from here on out
		if (!is_array($this->new_log_entry))
		{
			$this->new_log_entry = array($this->new_log_entry);
		}

		if ($edit_reason)
		{
			$edit_reason = "\n[b]Edit Reason Provided[/b]: [code]" . $edit_reason. "[/code]";

			array_unshift($this->new_log_entry, $edit_reason);

			$edit_reason .= "\n[*]";
		}

		// Now decide whether everything goes in the first post, or if the topic gets bumped
		if ($create_new_post)
		{
			$message = ($this->mode == 'edit') ? 'made a direct edit. No further action is required' : 'submitted a revision. Validation is required';

			$create_new_post = $this->create_post("New revision submitted", create_author_string($user->data['user_id'], $user->data['username'], $user->data['user_colour'], TRUE) . ' (as ' . $user_rank_data['rank_title'] . ') ' . $message . ".\n\n[b]Changelog (from currently approved version)[/b]\n[list=a]" . $this->format_log_entry($this->new_log_entry ) . "\n[/list]");

			$this->new_log_entry = array(array(LOG_NEW_POST, $edit_reason . 'A comprehensive changelog is available here: %s', $create_new_post));
		}

		// Start off the final sql array
		$sql_array = array(
			'entry_time'			=> $this->time,
			'entry_article_id'		=> $this->submitted_data['id'],
			'entry_author_id'		=> $user->data['user_id'],
			'entry_author_rank'		=> $user_rank_data['rank_title'],
			'entry_author_colour'	=> $user->data['user_colour'],
			'entry_text'			=> serialize($this->new_log_entry),
		);

		// @TODO
		//$r_of_r = ($stored_article_data['revision_status'] == REVISION_PENDING) ? "\n\n[b]Note that a previous revision existed and was overwritten[/b]" : '';

		// Figure out which status image to use
		switch ($this->mode)
		{
			case 'revision':
				$sql_array['entry_action_image'] = KB_IMAGE_REVISION;
			break;

			case 'edit':
				$sql_array['entry_action_image'] = KB_IMAGE_EDIT;
			break;

			case 'submit':
				$sql_array['entry_action_image'] = KB_IMAGE_NEW;
			break;

			default:
				$sql_array['entry_action_image'] = KB_IMAGE_CHANGE;
			break;
		}

		$sql = 'INSERT INTO ' . KB_LOG_TABLE . $db->sql_build_array('INSERT', $sql_array);
		$db->sql_query($sql);

		$this->rebuild_first_post();

		return $create_new_post;
	}

	/*
	* The first post of each article's queue topic contains details about the article as well as a changelog.
	* This funtion queries the log table and formats the posts's content.
	*/
	protected function rebuild_first_post()
	{
		global $db, $topic_status_images;

		$status = '';

		switch ($this->submitted_data['status'])
		{
			case ARTICLE_NEW:
				$status = '[color=#FF8649]New[/color]';
			break;

			case ARTICLE_INACTIVE:
				$status = '[color=#FF0000]Inactive[/color]';
			break;

			default:
				$status = '[color=#007F0E]Active[/color]';
		}

		// Start off with the header
		$updated_queue_post = "[size=130][b]Details[/b][/size]\n" .
			'[b]Title:[/b] ' . $this->submitted_data['title'] . "\n" .
			'[b]ID:[/b] ' . $this->submitted_data['id'] . "\n" .
			'[b]Status:[/b] ' . $status . "\n" .
			'[b]Pending revision:[/b] ' . (isset($this->submitted_data['revision_date']) && $this->submitted_data['revision_date'] ? 'Yes' : 'No') . "\n" .
			'[b]Author:[/b] ' . create_author_string($this->submitted_data['author_id'], $this->submitted_data['author_username'], $this->submitted_data['author_colour'], TRUE) . "\n" .
			'[b]Link:[/b] [url=' . SITE_URL . ABS_PATH_TO_DOCS_KB . 'article/' . $this->submitted_data['slug'] . '/]Knowledge Base - ' . $this->submitted_data['title'] ."[/url]\n\n\n[size=130][b]Changelog[/b][/size]\n[list=1]";

		// Get the log entries for this article
		$sql = 'SELECT k.*, u.username
				FROM ' . KB_LOG_TABLE . ' k, ' . USERS_TABLE . ' u
				WHERE k.entry_article_id = ' . $this->submitted_data['id'] . '
				AND k.entry_author_id = u.user_id
				ORDER BY entry_time ASC';
		$result = $db->sql_query($sql);

		// Loop through the results and construct the log list
		while ($row = $db->sql_fetchrow($result))
		{
			$updated_queue_post .= "\n[*]" .
				create_author_string($row['entry_author_id'], $row['username'], $row['entry_author_colour'], TRUE) .
				' - ' . date('H:i F j, Y', $row['entry_time']) . ' UTC [img]' . SITE_URL . '/theme/images/support/docs/' .
				$topic_status_images[$row['entry_action_image']] . "[/img]\n[list=a]" .
				$this->format_log_entry(unserialize($row['entry_text'])) . "\n\t[/list]";
		}

		$db->sql_freeresult($result);

		// Close the main list. We're done.
		$updated_queue_post .= '[/list]';

		// Get the post ready for submission to the database
		$post_bbcode_uid = $post_bbcode_bitfield = $post_bbcode_flags = '';
		generate_text_for_storage($updated_queue_post, $post_bbcode_uid, $post_bbcode_bitfield, $post_bbcode_flags, 1, 1, 1);

		// WARNING: Be very careful when editing this, since it is directly altering core phpBB tables.
		$sql_array = array(
			'post_text'			=>	$updated_queue_post,
			'bbcode_uid'		=>	$post_bbcode_uid,
			'bbcode_bitfield'	=>	$post_bbcode_bitfield,
			'post_checksum'		=>	md5($updated_queue_post),
			// @TODO title
		);

		$sql = 'UPDATE ' . POSTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $sql_array) . '
				WHERE post_id = ' . $this->submitted_data['post_id'];
		$db->sql_query($sql);
	}

	/*
	*
	* Updates KB_CATEGORY_ENTRIES_TABLE with the new category selections.
	*
	*/
	protected function commit_category_changes()
	{
		global $db;

		// @TODO Check whether this gets run for revisions. Remove clear_old
		// Unless this is a new submission, the old categories need to be removed
		if ($this->mode != 'submit')
		{
			$sql = 'DELETE FROM ' . KB_CATEGORY_ENTRIES_TABLE . '
					WHERE article_id = ' . $this->submitted_data['id'];
			$db->sql_query($sql);
		}

		foreach ($this->cached_category_entries as $key => $new_category)
		{
			$sql = 'INSERT INTO ' . KB_CATEGORY_ENTRIES_TABLE . '
					VALUES (' . $this->submitted_data['id'] . ", '$key');";
			$db->sql_query($sql);
		}
	}
	
	function delete_redirect()
	{
		/*
		Delete entry in articles table	
		Delete entry in revisions table
		Delete all category entries
			
		if not a link
			
			
			
			
		*/
	}
}
