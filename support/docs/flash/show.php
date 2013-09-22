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

$flash=request_var('flash','');
$template->assign_vars(array(
		'FLASH'	=> $flash,
	));

$template->set_filenames(array(
	'body' => DOCS_TEMPLATE_PATH . 'flash_popup.html',
));
$template->display('body');
