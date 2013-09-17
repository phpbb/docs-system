<?php
/**
*
* @package phpBB.com Documentation - Flash Tutorials
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

if (!defined('IN_PHPBB'))
{
	exit();
}

//
// Relative path to the main docs root
//
$path_to_docs_root = '../';

//
// Include the main Documentation common.php
//
require($path_to_docs_root . 'common.php');

//
// Absolute path to Flash
//
define('ABS_PATH_TO_DOCS_FLASH', ABS_PATH_TO_DOCS . 'flash/');
include($root_path . 'includes/support/docs/functions_flash.' . $phpEx);

// @Todo add in flash administrator option
//if ($auth->acl_get('s_flash_add'))
//{
	define('FLASH_ADMIN', TRUE);

	$tabs['manage'] = 'Manage Flash';

//}

//
// Absolute path to Flash
//




//
// Common UG template vars
//
$template->assign_vars(array(
	'S_IN_DOCS_FLASH'			=> true,
	'S_LISTING' => false,
	'S_BODY_CLASS'				=> 'support_docs flash support tutorials',
	'S_ABS_PATH_TO_DOCS_FLASH'	=> ABS_PATH_TO_DOCS_FLASH,
));
