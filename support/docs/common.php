<?php
/**
*
* @package phpBB.com Documentation
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

if (!defined('IN_PHPBB'))
{
	exit();
}

// Relative path to root
$root_path = '../../../../' . (isset($path_to_docs_root) ? $path_to_docs_root : '');
// Include the main website common.php
require($root_path . 'common.php');
include($phpbb_root_path . 'includes/functions_upload.php');

// Path for doc template files with trailing slash
define('DOCS_TEMPLATE_PATH', 'support/docs/');

// Which language shall we display?
define('DOCS_LANG', request_var('lang', ''));

// Which version of phpBB?
define('DOCS_VERSION', request_var('version', ''));

// @TODO Everything should use this
define('ABS_PATH_TO_DOCS', $base_path . 'support/docs/' . DOCS_LANG . '/' . DOCS_VERSION . '/');

// Table to associate docs/kb/etc comments to a topic id
define('DOC_COMMENTS_TABLE', 'docs_comments');

// Table to to store attachment id for ug/kib/etc
define('DOC_COMMENTS_ATTACHMENTS_TABLE', 'docs_comments_attachments');

// Table to to store flash
define('DOC_FLASH_TABLE', 'docs_flash');

// Domain name for posted links
// No trailing slashes
define('SITE_URL', generate_board_url(true));
define('BOARD_URL', SITE_URL . $config['script_path']);

// A user has an account and is logged in
// An administrator has full access to the documentation management system
// @TODO Might need another level later for "moderators"
if ($auth->acl_get('s_kb_add'))
{
	define('DOCS_USER', TRUE);

	if ($auth->acl_get('s_kb_approve'))
	{
		//remove comment after testdefine('DOCS_ADMIN', TRUE);
	}
	else
	{
		//remove comment after testdefine('DOCS_ADMIN', FALSE);
	}
}
else
{
	define('DOCS_USER', FALSE);
	//remove comment after test define('DOCS_ADMIN', FALSE);
}

// Login/Logout Data
$request_uri = $_SERVER['REQUEST_URI'];

if ($user->data['user_id'] != ANONYMOUS)
{
	$u_docs_login_logout = append_sid($base_path . substr($config['script_path'], 1) . '/ucp.' . $phpEx, 'mode=logout', true, $user->session_id);
}
else
{
	// @TODO Needs a rewrite to work with all 3 docs sections
	$u_docs_login_logout = append_sid(ABS_PATH_TO_DOCS . 'login/');
}

// Common template vars
$template->assign_vars(array(
	'DOCS_USER'				=> DOCS_USER,
	'DOCS_ADMIN'			=> DOCS_ADMIN,

	'VIEWER_USERNAME'		=> $user->data['username'],

	'U_DOCS_LOGIN_LOGOUT'	=> $u_docs_login_logout,
));

// @TODO Figure out why this is necessary
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);



