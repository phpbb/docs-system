<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

// Standard definitions/includes
$page_title = 'Knowledge Base';
define('IN_PHPBB', true);

require('./common.php');

if ($user->data['username'] == 'Marshalrusty')
{
	$sql = 'TRUNCATE TABLE ' . KB_ARTICLES_TABLE;
	$db->sql_query($sql);

	$sql = 'TRUNCATE TABLE ' . KB_CATEGORY_ENTRIES_TABLE;
	$db->sql_query($sql);

	$sql = 'TRUNCATE TABLE ' . KB_REVISIONS_TABLE;
	$db->sql_query($sql);

	$sql = 'TRUNCATE TABLE ' . KB_LOG_TABLE;
	$db->sql_query($sql);
}

echo 'Cleared';
