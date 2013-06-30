<?php
/**
*
* @package phpBB3 Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

/*
* This gets included for administrative actions
* It's not a separate file in order to keep the path /adm/
* Permissions checks have already been performed in index.php
*/

// Get the article we're working on and what we're going to be doing with it
$id = request_var('id', 0);
$mode = (isset($_POST['mode'])) ? $_POST['mode'] : '';

if (!$id)
{
	display_message('Missing article id');
}

// Check that the article exists and get the minimum required data for the class
$sql = 'SELECT a.*, u.username, u.user_colour
	FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
	WHERE u.user_id = a.author_id
	AND a.id = ' . $id;
$result = $db->sql_query_limit($sql, 1);
$row = $db->sql_fetchrow($result);

if (!$row)
{
	display_message('The provided article id (' . $id . ') is invalid.');
}

// All redirects
switch ($mode)
{
	case 'edit':
		redirect(ABS_PATH_TO_DOCS_KB . 'edit/' . $row['slug'] . '/');
	break;

	case 'topic':
		redirect($phpbb_root_path . 'viewtopic.php?t=' . $row['topic_id']);
	break;
}

// Load the kbarticle class
require($root_path . 'includes/support/docs/class_kbarticle.' . $phpEx);

// author_username and author_colour are needed by the class
$row['author_username']	= $row['username'];
$row['author_colour']	= $row['user_colour'];

// Define some standard links to show on confirmation pages
$request_uri = request_var('request_uri', '');
$return_links = $request_uri ? '<br /><br /><a href="' . redirect($request_uri, true) . '"><< Back from whence ye came</a>' : '';
$return_links .= '<br /><br /><a href="' . append_sid(ABS_PATH_TO_DOCS_KB . 'article/' . $row['slug'] . '/') . '">>> Go to the article</a><br />';
$return_links .= !$row['is_redirect'] ? '<a href="' . append_sid($config['script_path'] . '/viewtopic.php?t=' . $row['topic_id']) . '">>> Go to the article\'s topic</a><br />' : '';
$return_links .= '<a href="' . append_sid(ABS_PATH_TO_DOCS_KB . 'adm/') . '">>> Go to Administration</a>';

// This handles reassignments to other teams (Support, MOD, Styles). Options configured in common.php
if (isset($assignments[$mode]))
{
	if ($row['assigned_to'] == $mode)
	{
		display_message('The article "' . $row['title'] . '" is already assigned to the ' . $assignments[$mode] . $return_links);
	}

	$row['assigned_to'] = $mode;

	$instance = new kbarticle('mcp_reassign', $row);
	$instance->run();

	display_message('Successfully reassigned "' . $row['title'] . '" to the ' . $assignments[$mode] . $return_links);
}

// Special case for 'phpBB Team'
if ($row['author_id'] == KB_BOT)
{
//	$row['author_username']	= 'phpBB Team';

	// Don't send PMs to this user
	unset($_POST['send_pm']);

	$_POST['skip'] = TRUE;
}

// If sending a PM was chosen, then the fields need to be validated
$errors = array();

if (isset($_POST['send_pm']))
{
	$row['pm_subject']	= request_var('pm_subject', '');
	$row['pm_text']		= request_var('pm_text', '');
	$row['pm_warning']	= request_var('pm_warning', '');
///	$row['send_pm']		= TRUE;

	if (!$row['pm_subject'])
	{
		$errors[] = 'The subject cannot be blank.';
	}

	if (!$row['pm_text'])
	{
		$errors[] = 'The PM cannot be blank.';
	}

	if (strpos($row['pm_text'], '{PROVIDE A REASON!}') !== FALSE)
	{
		$errors[] = '{PROVIDE A REASON!} must be replaced with a reason';
	}
}

// Assuming that there aren't any errors from above, take the necessary actions
if (empty($errors))
{
	// Decide whether to process the action or display a PM form
	$submit = (isset($_POST['send_pm']) || isset($_POST['skip'])) ? TRUE : FALSE;

	switch ($mode)
	{
		case 'activate':
			if ($row['status'] == ARTICLE_ACTIVE)
			{
				display_message('the article is already active');
			}
		
			if ($submit)
			{
				$instance = new kbarticle('mcp_activation', $row);
				$instance->run();
				
				display_message('The article has been successfully activated.' . $return_links);
			}

			$row['pm_text'] = 'The article has been activated and can be viewed by the public.';
			$row['pm_warning'] = 'You are about to activate the article "' . $row['title'] . '".';
		break;

		case 'deactivate':
			if ($submit)
			{
				$instance = new kbarticle('mcp_activation', $row);
				$instance->run();
				
				display_message('The article has been successfully deactivated.' . $return_links);
			}

			if ($row['status'] == ARTICLE_NEW)
			{
				$row['pm_text'] = 'The article has not been approved because {PROVIDE A REASON!}. Please make the necessary corrections and submit a revision.';
				$row['pm_warning'] = 'You are about to deny the article "' . $row['title'] . '".';
			}
			else if ($row['status'] == ARTICLE_ACTIVE)
			{
				$row['pm_text'] = 'The article has been deactivated because {PROVIDE A REASON!}.';
				$row['pm_warning'] = 'You are about to deactivate the article "' . $row['title'] . '".';

				if ($row['revision_status'] == REVISION_PENDING)
				{
					$row['pm_warning'] .= ' Please note that a pending revision exists for this article. If the revision is approved, the article will be reactivated';
				}
			}
			else
			{
				display_message('the article is already inactive');
			}
		break;

		case 'approve_revision':
			if ($submit)
			{
				
				$instance = new kbarticle('mcp_approve_rev' , $row);
				$instance->run();

				display_message('The revision has been approved.' . $return_links);
			}

			$row['pm_text'] = 'The revision you submitted for the article above has been approved.';
			$row['pm_warning'] = 'You are about to approve a revision for the article "' . $row['title'] . '". The existing article will be overwritten by the revision and set to active status.';
		break;

		case 'nuke':
			
		//break;

		default:
			display_message('The selected mode is invalid. Stop breaking things you!');
	}

	// Add the static bits to the dynamic message from above
	$row['pm_subject'] = 'Update regarding your KB Article';
	$row['pm_text'] = 'Hello ' . $row['author_username'] . ",\n\nThis message is regarding your Knowledge Base article: [url=" . SITE_URL . ABS_PATH_TO_DOCS_KB . 'article/' . $row['slug'] . '/]' . $row['title'] . "[/url]\n\n" . $row['pm_text'] . "\n\nTo check the status of your articles or submit a revision, please see the [url=" . SITE_URL . ABS_PATH_TO_DOCS_KB . 'manage/]Manage Your Articles Page[/url]. If you have any questions, please PM '. create_author_string($user->data['user_id'], $user->data['username'], $user->data['user_colour'], TRUE) . ".\n\n\nThank you.";
}

// Get the KB Bot's user data
$sql = 'SELECT username, user_colour
	FROM ' . USERS_TABLE . '
	WHERE user_id = ' . KB_BOT;
$result = $db->sql_query($sql);
$kb_bot = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if (!$kb_bot)
{
	display_message('The KB Bot user does not exist. The configured id is ' . KB_BOT);
}

$template->assign_vars(array(
	'ARTICLE_ID'	=> $id,
	'SUBJECT'		=> $row['pm_subject'],
	'TEXT'			=> $row['pm_text'],
	'WARNING'		=> $row['pm_warning'],
	'AUTHOR'		=> create_author_string($row['author_id'], $row['author_username'], $row['author_colour']),
	'KB_BOT'		=> create_author_string(KB_BOT, $kb_bot['username'], $kb_bot['user_colour']),

	'ERRORS'		=> empty($errors) ? FALSE : implode('<br />&#8250; ', $errors),

	'S_MODE'		=> $mode,
));

$template->set_filenames(array(
	'body' => DOCS_TEMPLATE_PATH . 'kb_adm_pm.html')
);

// Generate the tabs, load the page
process_page('Administration', 'adm');
