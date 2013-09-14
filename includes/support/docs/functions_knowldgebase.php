<?php
/**
*
* @package phpBB3 Website
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}
/**
 * Generates the tabs and calls the header/footer
 *
 * @param string $page_title the specified title of the page
 * @param string $active_tab defined which tab should be active
 */
function process_page($page_title, $active_tab)
{
	global $template, $tabs, $categories;

	// Loop through all the tags that the user should see (set in common.php)
	foreach($tabs as $slug => $title)
	{
		$template->assign_block_vars('tabs', array(
			'TAB' => $title,
			'S_TAB_ACTIVE' => ($slug == $active_tab) ? TRUE : FALSE,
			'U_TAB' => append_sid(ABS_PATH_TO_DOCS_KB . ($slug ? $slug . '/' : '')),
		));
	}

	// Add an additional tab to the end when in a dynamic section
	// Also very useful for displaying notices
	if ($active_tab && !isset($tabs[$active_tab]))
	{
		$template->assign_block_vars('tabs', array(
			'TAB' => $active_tab,
			'S_TAB_ACTIVE' => TRUE,
			'U_TAB' => append_sid(''),
		));
	}

	page_header('Knowledge Base > ' . $page_title, false);
	page_footer(false);
}


/**
 * Displays a message and kills the page
 * Just like trigger_error, except that the tabs are still generated
 *
 * @param string $message the message to be displayed
 */
function display_message($message)
{
	global $template;

	$template->set_filenames(array(
		'body'	=> DOCS_TEMPLATE_PATH . 'kb_display_message.html')
	);

	if ($message == 'DISPLAY_LOGIN')
	{
		process_page('Please login with your phpBB.com account', 'login');
	}
	else
	{
		$template->assign_var('MESSAGE', $message);

		process_page('Notice', 'Notice', TRUE);
	}

	page_footer(false);
}

/**
 * Generates a slug from a category or article title.
 *
 * @param string $title contains the title of the article or category
 * @return slug
 */
function generate_slug($title)
{
	// Only lowercase chars please
	$slug = strtolower($title);

	//Replace ',' '&', '/', ' ' and '_' with '-'
	$search = array(',', '/', '&amp;', ' ', '_');
	$slug = str_replace($search, '-', $slug);

	// No freaky chars
	$slug = preg_replace('/[^a-z0-9+-]/', '', $slug);

	// Remove multiple conjunctions. Pluses are dominant.
	while (strpos($slug, '--') !== FALSE)
	{
		$slug = str_replace('--', '-', $slug);
	}

	return $slug;
}

/**
 * Adds title headers to the text
 *
 * @param string $text contains the article text
 * @return article text, with headers
 */
function add_title_headers($text)
{
	// This is quick and dirty, but also very simple
	$text = str_replace('[h1]','<span style="color: #800040; font-size: 180%; font-weight: bold;">', $text);
	$text = str_replace('[h2]','<span style="color: #0000BF; font-size: 160%; font-weight: bold;">', $text);
	$text = str_replace('[h3]','<span style="color: #008000; font-size: 145%; font-weight: bold;">', $text);
	$text = str_replace('[h4]','<span style="color: #408080; font-size: 135%; font-weight: bold;">', $text);

	$text = str_replace('[/h1]','</span>', $text);
	$text = str_replace('[/h2]','</span>', $text);
	$text = str_replace('[/h3]','</span>', $text);
	$text = str_replace('[/h4]','</span>', $text);

	return $text;
}

/**
 * Creates a formatted string linking to the user's profile
 *
 * @param int $user_id provided user id
 * @param string $username provided username
 * @param string $user_color provided color code
 * @param boolean $bbcode return html or bbcode
 * @return colored hyperlink to user profile
 */
function create_author_string($user_id, $username, $user_color, $bbcode = FALSE)
{
	global $base_path, $config;

	if ($bbcode)
	{
		// Special case for displaying 'phpBB Team'
		if ($username == 'phpBB Team')
		{
			$user = '[url=' . BOARD_URL . '/memberlist.php?mode=leaders][b][color=#AA0000]phpBB Team[/color][/b][/url]';
		}
		else
		{
			$user = '[url=' . BOARD_URL . '/memberlist.php?mode=viewprofile&u=' . $user_id . '][b]' . ($user_color ? '[color=#' . $user_color . ']' : '') . $username . ($user_color ? '[/color]' : '') . '[/b][/url]';
		}
	}
	else
	{
		// Special case for displaying 'phpBB Team'
		if ($username == 'phpBB Team')
		{
			$user = '<a href="' . append_sid($base_path . substr($config['script_path'], 1) . '/memberlist.php', 'mode=leaders') . '" style="color: #AA0000;" class="username-coloured">phpBB Team</a>';
		}
		else
		{
			$user = get_username_string('full', $user_id, $username, $user_color, FALSE, BOARD_URL . '/memberlist.php?mode=viewprofile');
		}
	}
	
	return $user;
}
