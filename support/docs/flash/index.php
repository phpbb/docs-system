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
// please dump docs_flash.sql into database first

// Inserting flash post

if($_POST)
{
	$action = request_var('action', '');
	
	$submission = ($action=="add"||$action=="edit")? true: false;
	
	if($submission)
	{
		$title = request_var('title', '');
		$unique_name = request_var('unique_name', '');
		$flash = request_var('flash', '');
		$thumbnail = request_var('thumbnail', '');
		$flash_version = request_var('flash_version', '');	
	}
	
	switch($action)
	{
		case 'add':
			if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
			{
				$sql_array = array(
							'title'			=> $title,
							'unique_name'	=> $unique_name,
							'flash'			=> $flash,
							'thumb'			=> $thumbnail,
							'version'		=> $flash_version,
							'user_id'		=> $user->data['user_id'],
						);
								
				$sql = 'INSERT INTO ' . DOC_FLASH_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_array);

				$db->sql_query($sql);
				$template->assign_vars(array(
					'S_AJAX_REQUEST'=>true,
				));
			}
			else
			{
				ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
			}
		break;
		case 'edit':
			if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
			{
				$id = request_var('id',0);
				
				$sql_array = array(
					'title'			=> $title,
					'unique_name'	=> $unique_name,
					'version'		=> $flash_version,
				);
				
				if(isset($flash)&&!empty($flash))
				{
					$sql_array['flash'] = $flash;
				}

				if(isset($thumbnail)&&!empty($thumbnail))
				{
					$sql_array['thumb'] = $thumbnail;
				}				
				
				$sql = 'UPDATE ' . DOC_FLASH_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $sql_array) . "
					WHERE flash_id = '" . $id . "'";
				$db->sql_query($sql);
				$template->assign_vars(array(
					'S_AJAX_REQUEST'=>true,
				));
			}
			else
			{
				ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
			}
		break;
		case 'delete':
			if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
			{
				$id = intval(request_var('id',''));
					
				$sql = 'DELETE FROM ' . DOC_FLASH_TABLE . ' WHERE flash_id = ' . $id;
				$db->sql_query($sql);
				$template->assign_vars(array(
					'S_AJAX_REQUEST'=>true,
				));
			}
			else
			{
				ug_report_error('You are not authorized to access this feature.', $is_ajax_request);
			}
		break;
		case 'fetch':
			if (DOCS_ADMIN && $user->data['user_id'] != ANONYMOUS)
			{
				$id = intval(request_var('id',''));
					
				$sql = 'SELECT *
						FROM '.	DOC_FLASH_TABLE.' WHERE flash_id='. $id;
				
				$flash_data = $db->sql_query($sql);
				
				$flash = $db->sql_fetchrow($flash_data);
				
				echo json_encode($flash);
				exit;
			}
		break;
	}
}

// Retrieving flash records
$sql = 'SELECT f.*,u.username 
		FROM '.	DOC_FLASH_TABLE.' f
		INNER JOIN '.USERS_TABLE.' u ON f.user_id=u.user_id ';
		
$flash_data = $db->sql_query($sql);

$tutorials=array();
		
while ($flash = $db->sql_fetchrow($flash_data))
{
	$template->assign_block_vars('flash', array(
				'TITLE' 	=> $flash['title'],
				'UNIQUE_NAME'	=> $flash['unique_name'],
				'PATH' 	=> $flash['flash'],
				'THUMB'		=> $flash['thumb'],
				'VERSION'	=> $flash['version'],
				'USER_NAME' => $flash['username'],
				'ID'		=> $flash['flash_id'],
	));
	
	$tutorials[$flash['flash_id']]=$flash;
}	

$template->assign_block_Vars('flash_data', array());


// Retrieve the version the user would like to see
$version = request_var('version', '');
$version = in_array($version, array('3.0')) ? $version : '3.0';

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
		'V_NAME'	=> $tutorials[$tutorial_id]['flash'],
		'V_TITLE'	=> $tutorials[$tutorial_id]['title'])
	);
}
else/* if (!empty($version))*/
{
	// list tutorials
	$template->set_filenames(array(
		'body' => DOCS_TEMPLATE_PATH . 'flash_body.html')
	);
}

$template->assign_vars(array(
	//'S_BODY_CLASS'		=> 'support tutorials',
));

process_page($page_title, $tab);
page_footer(false);
