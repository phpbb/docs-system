<?php
/**
*
* @package phpBB3 Website
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

// The page title
$page_title = 'Flash tutorials';

define('IN_PHPBB', true);
require('./common.php');

// Define the 3.0 flash tutorials
// dumping this into a database

$tutorials_3 = array(
	array('title' => 'Add a moderator',			'name' => '3.0_add_moderator'),
	array('title' => 'Manage attachments',		'name' => '3.0_attachments'),
	array('title' => 'Backup and restore',		'name' => '3.0_backup'),
	array('title' => 'Add custom BBCodes',		'name' => '3.0_bbcode'),
	array('title' => 'Custom usergroups',		'name' => '3.0_custom_groups'),
	array('title' => 'Custom profile fields',	'name' => '3.0_fields'),
	array('title' => 'Managing forums', 		'name' => '3.0_forums'),
	array('title' => 'Mass email',				'name' => '3.0_mass_email'),
	array('title' => 'Managing ranks',			'name' => '3.0_ranks'),
	array('title' => 'Report reasons',			'name' => '3.0_report_reasons'),
	array('title' => 'User security',			'name' => '3.0_user_security'),
	array('title' => 'Word censor',				'name' => '3.0_word_censor'),
);


// Retrieve the version the user would like to see
$version = request_var('version', '');
$version = in_array($version, array('3.0')) ? $version : '3.0';
$tutorials = ($version == '3.0') ? $tutorials_3 : /*$tutorials_3_2*/'';

$template->assign_var('V_VERSION', $version);

// Add in admin console
$console = request_var('console','');
$tutorial_id = request_var('tid', -1);

if (isset($console) && $console=="adm")
{
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'flash_adm.html',
	));
	$tab= 'manage';
}
else if (!empty($version) && $tutorial_id >= 0 && isset($tutorials[$tutorial_id]))
{
	// show tutorial detail
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'flash_swf.html')
	);
	
	$template->assign_vars(array(
		'V_NAME'	=> $tutorials[$tutorial_id]['name'],
		'V_TITLE'	=> $tutorials[$tutorial_id]['title'])
	);
}
else/* if (!empty($version))*/
{
	// list tutorials
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'flash_body.html')
	);

	foreach ($tutorials as $tutorial_id => $tutorial_data)
	{
		$template->assign_block_vars('tutorials', array(
			'U_TUTORIAL'	=> append_sid($base_path . 'support/docs/flash/' . $version . '/index.' . $phpEx, 'tid=' . $tutorial_id),
			'V_NAME'		=> $tutorial_data['name'],
			'V_TITLE'		=> $tutorial_data['title'])
		);
	}
}

$template->assign_vars(array(
	//'S_BODY_CLASS'		=> 'support tutorials',
));

process_page($page_title, $tab);
page_footer(false);
