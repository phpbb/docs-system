<?php
/**
*
* @package phpBB.com Documentation - User Guide
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
// Absolute path to UG
//
define('ABS_PATH_TO_DOCS_UG', ABS_PATH_TO_DOCS . 'ug/');


define('UG_COMMENTS_FORUM_ID', 3);

//
// Common UG template vars
//
$template->assign_vars(array(
	'S_IN_DOCS_UG'			=> true,
	'S_BODY_CLASS'			=> 'support_docs ug support',
	'S_ABS_PATH_TO_DOCS_UG'		=> ABS_PATH_TO_DOCS_UG,
));
