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
			'U_TAB' => append_sid(ABS_PATH_TO_DOCS_FLASH . ($slug ? $slug . '/' : '')),
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


