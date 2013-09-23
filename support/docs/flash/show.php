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

$tid=request_var('tid',0);

// Retrieving flash records
$sql = 'SELECT * 
		FROM '.	DOC_FLASH_TABLE.' where flash_id= ' . $tid;
		
$flash_data = $db->sql_query($sql);

$tutorials=array();

$flash=array();
		
while ($flash = $db->sql_fetchrow($flash_data))
{	
	$template->assign_vars(array(
		'FLASH'	=> $flash['flash'],
		'SERVER_NAME' =>$config['server_name'],
		'ID' => $flash['flash_id'],
	));
}	


$template->set_filenames(array(
	'body' => DOCS_TEMPLATE_PATH . 'flash_popup.html',
));
$template->display('body');
