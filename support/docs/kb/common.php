<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
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
// Absolute path to KB
//
define('ABS_PATH_TO_DOCS_KB', ABS_PATH_TO_DOCS . 'kb/');

include($root_path . 'includes/support/docs/functions_knowldgebase.' . $phpEx);

//
// Sets permission constants and figures out which tabs should be displayed
// All team members are KB Admins
// @TODO These should be replaced with DOCS_USER and DOCS_ADMIN
//
$tabs = array('' => 'View Article Index');

/*
if ($auth->acl_get('s_kb_add'))
{
	define('KB_USER', TRUE);

	$tabs['manage'] = 'Manage Your Articles';
	$tabs['submit'] = 'Submit an Article';

	if ($auth->acl_get('s_kb_approve'))
	//if (FALSE)
	{
		define('KB_ADMIN', TRUE);

		$tabs['redirect'] = 'Submit a Redirect';
		$tabs['adm'] = 'Administration';
	}
	else
	{
		define('KB_ADMIN', FALSE);
	}
}
else
{
	define('KB_USER', FALSE);
	define('KB_ADMIN', FALSE);

	$tabs['login'] = 'Login for more options';
}
*/
// test account remove after test
	define('KB_USER', TRUE);
	define('KB_ADMIN', TRUE);
		$tabs['redirect'] = 'Submit a Redirect';
		$tabs['adm'] = 'Administration';
	$tabs['manage'] = 'Manage Your Articles';
	$tabs['submit'] = 'Submit an Article';
// end test
	

//
// Table names
//
define('KB_ARTICLES_TABLE', 'docs_kb_articles');
define('KB_CATEGORY_ENTRIES_TABLE', 'docs_kb_category_entries');
define('KB_REVISIONS_TABLE', 'docs_kb_revisions');
define('KB_REVISIONS_ATTACH_TABLE', 'docs_kb_revisions_attach');
define('KB_LOG_TABLE', 'docs_kb_log');

//
// KB Queue Forum and Bot IDs
//
define('KB_QUEUE_FORUM',	265);
define('KB_BOT',			750575);

//
// Article statuses
//
define('ARTICLE_NEW',		0);
define('ARTICLE_INACTIVE',	1);
define('ARTICLE_ACTIVE',	2);

//
// Revision statuses
//
define('REVISION_NONE',		0);
define('REVISION_PENDING',	1);
define('REVISION_APPROVED',	2);
define('REVISION_DENIED',	3);

//
// Different types of log entries
//
define('LOG_NEW_POST',		0);
define('LOG_ATTACHMENT',	1);

//
// Images used in queue topics
// Stored in /theme/images/support/docs/
//
define('KB_IMAGE_CHANGE',	0);
define('KB_IMAGE_EDIT',		1);
define('KB_IMAGE_NEW',		2);
define('KB_IMAGE_REVISION',	3);

$topic_status_images = array(
	KB_IMAGE_CHANGE		=>	'kb_topic_change.gif',
	KB_IMAGE_EDIT		=>	'kb_topic_edit.gif',
	KB_IMAGE_NEW		=>	'kb_topic_new.gif',
	KB_IMAGE_REVISION	=>	'kb_topic_revision.gif',
);

//
// Categories
// WARNING: Consult Marshalrusty before adding or changing categories
//
$categories = array(
	'administration'					=> 'Administration',
	'styles'							=> 'Styles',
	'improvements'						=> 'Improvements',
	'miscellanea'						=> 'Miscellanea',
	'incident-investigation-team'		=> 'Incident Investigation Team',
	'information-and-notices'			=> 'Information and Notices',
	'installing-upgrading-converting'	=> 'Installing/Upgrading/Converting',
);

//
// Versions
// WARNING: Consult Marshalrusty before adding or changing versions
//
$versions = array(
	'3.0'	=> 'phpBB 3.0',
	'3.1'	=> 'phpBB 3.1',
);

//
// Assigned To
//
$assignments = array(
	'support'	=> 'Support Team',
	'styles'	=> 'Styles Team',
	'mod'		=> 'MOD Team'
);

//
// Common template vars
//
$template->assign_vars(array(
	'KB_USER'				=> KB_USER,
	'KB_ADMIN'				=> KB_ADMIN,

	'S_IN_KB'				=> true,
	'S_BODY_CLASS'			=> 'support_docs kb support',
));
