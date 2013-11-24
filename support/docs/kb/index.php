<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

// @TODO Get rid of $mode and just use $selected_version

// Standard definitions/includes
define('IN_PHPBB', true);
require('./common.php');
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// We have to interpret the mod_rewrite results
$selected_version = request_var('var1', '');

switch ($selected_version)
{
	case 'adm':
		// Only KB Admins can proceed
		if (!KB_ADMIN)
		{
			if (!KB_USER)
			{
				display_message('DISPLAY_LOGIN');
			}
			else
			{
				display_message('This is a restricted area');
			}
		}

		// If an administrative action was requested, then we do that
		if (isset($_POST['mode']))
		{
			include($root_path . 'includes/' . DOCS_TEMPLATE_PATH . 'includes_acp.' . $phpEx);
		}

		$adm_option = request_var('var2', '');

		switch ($adm_option)
		{
			case 'redirects':
				$adm_narrow = 'AND is_redirect = 1';
			break;

			case 'inactive':
				$adm_narrow = 'AND status = ' . ARTICLE_INACTIVE;
			break;

			case 'support':
			case 'styles':
			case 'mod':
				$adm_narrow = 'AND (status <> ' . ARTICLE_ACTIVE . ' OR revision_date > 0)
					AND assigned_to = \'' . $adm_option . '\'
					ORDER BY revision_date DESC';
			break;

			case 'support-active':
			case 'styles-active':
			case 'mod-active':			
				$adm_narrow = 'AND assigned_to = \'' . substr($adm_option, 0, strpos($adm_option, '-')) . '\'
					ORDER BY revision_date DESC';
			break;

			case 'viewall':
				$adm_narrow = '';
			break;

			default:
				$adm_narrow = 'AND (a.status = ' . ARTICLE_NEW . ' OR a.revision_status = ' . REVISION_PENDING . ')
					ORDER BY date DESC';
		}

		// Grab the articles that still have not been validated
		$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
				FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
				WHERE u.user_id = a.author_id ' .
		$adm_narrow;

		$page_title = 'Administration';
		$mode = 'adm';

		$template->set_filenames(array(
			'body'	=> DOCS_TEMPLATE_PATH . 'kb_adm.html')
		);
	break;

	case 'manage':
		if (!KB_USER)
		{
			display_message('DISPLAY_LOGIN');
		}

		// Grab the articles that were written by the viewer
		$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
				FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
				WHERE u.user_id = ' . $user->data['user_id'] . '
				AND u.user_id = a.author_id
				ORDER BY title ASC';
//		AND a.is_redirect = 0

		$page_title = 'Manage Your Articles';
		$mode = 'manage';

		$template->set_filenames(array(
			'body'	=> DOCS_TEMPLATE_PATH . 'kb_manage.html')
		);
	break;

	default:
		$selected_category = request_var('var2', '');

		// If the version is invalid, then assume it's actually the category
		if ($selected_version != '' && $selected_version != '3.0' && $selected_version != '3.1')
		{
			$selected_category = $selected_version;
			$selected_version = '';	
		}

		// If the category is invalid, throw an error
		if ($selected_category != '' && !isset($categories[$selected_category]))
		{
			display_message('Invalid category or version.<br /><br /><br /><a href="' . ABS_PATH_TO_DOCS_KB . '">Go back to the index</a>');
		}

		// Build the category dropdown
		foreach($categories as $key => $category)
		{
			$template->assign_block_vars('category', array(
				'CATEGORY' => $category,
				'KEY' => $key,
				'ACTIVE' => ($selected_category == $key) ? TRUE : FALSE,
			));
		}

		// Build the query
		$narrow_version = ($selected_version != '') ? 'AND for_' . str_replace('.', '_', $selected_version) . ' = 1 ' : '';

		if ($selected_category != '')
		{
			$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
					FROM ' . KB_ARTICLES_TABLE . ' a, ' . KB_CATEGORY_ENTRIES_TABLE. ' c, ' . USERS_TABLE . ' u
					WHERE a.status = ' . ARTICLE_ACTIVE . '
					AND c.article_id = a.id
					AND u.user_id = a.author_id
					AND c.category_slug = \'' . $selected_category . '\'' .
					$narrow_version . '
					ORDER BY title ASC';
		}
		else
		{
			$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
					FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
					WHERE a.status = ' . ARTICLE_ACTIVE . '
					AND u.user_id = a.author_id ' .
					$narrow_version . '
					ORDER BY title ASC';
		}

		$page_title = 'Index' . ($selected_version ? ' > ' . $versions[$selected_version] : '') . ($selected_category ? ' > ' . $categories[$selected_category] : '');
		$mode = '';

		$template->set_filenames(array(
			'body'	=> DOCS_TEMPLATE_PATH . 'kb_index.html')
		);
}

$result = $db->sql_query($sql);

// Off we go
while ($row = $db->sql_fetchrow($result))
{
	$type_image = $status_image = FALSE;

	// Figures out which status image to use
	switch ($mode)
	{
		case 'adm':
			$assigned_to = '';
			foreach($assignments as $key => $value)
			{
				$assigned_to .= '<option ' . (($row['assigned_to'] == $key) ? 'selected="selected" ' : '') . 'value="' . $key . '">&nbsp; &nbsp; &nbsp; &nbsp;' . (($row['assigned_to'] == $key) ? '✔ ' : '') . $value . '</option>';
			}

		case 'manage':
			if ($row['is_redirect'])
			{
				$type_image = 'redirect';
			}
			else if ($row['revision_status'] == REVISION_PENDING)
			{
				$type_image = 'revision';
			}

			switch ($row['status'])
			{
				case ARTICLE_ACTIVE:
					$status_image = 'active';
				break;

				case ARTICLE_NEW:
					// The author will see "article reviewed" after 12 hours :P
					$status_image = (KB_ADMIN || /**/time() - $row['date'] < /*43200*/ 120) ? 'new' : 'reviewed';
				break;

				case ARTICLE_INACTIVE:
					$status_image = 'inactive';
				break;
			}
		break;
	}

	// Special case for 'phpBB Team'
	$row['username'] = ($row['user_id'] == KB_BOT) ? 'phpBB Team' : $row['username'];

	$template->assign_block_vars('article_row', array(
		'ID'					=> $row['id'],
		'ARTICLE_TITLE'			=> $row['title'],
		'SLUG'					=> $row['slug'],
		'DESCRIPTION'			=> $row['description'],
		'AUTHOR'				=> create_author_string($row['user_id'], $row['username'], $row['user_colour']),
		'VIEWS'					=> $row['is_redirect'] ? 'N/A' : $row['views'],
		'REVISION_STATUS'		=> $row['revision_status'],
		'REVISION_DATE'			=> ($row['revision_date']) ? date('M dS H:i', $row['revision_date']) : 0,
		'STATUS_IMAGE'			=> $status_image,
		'TYPE_IMAGE'			=> $type_image,

		'DATE_SUBMITTED'		=> $user->format_date($row['date_submitted'], 'M d, Y g:i a'),
		'DATE_LAST_MODIFIED'	=> $user->format_date(($row['date_last_modified'] ? $row['date_last_modified'] : $row['date_submitted']), 'M d, Y g:i a'),

		'ASSIGNED_TO'			=> isset($assigned_to) ? $assigned_to : '',
		'U_ARTICLE'				=> $row['is_redirect'] ? $row['text'] : append_sid(ABS_PATH_TO_DOCS_KB . 'article/' . urlencode($row['slug']) . '/'),
		'U_EDIT'				=> append_sid(ABS_PATH_TO_DOCS_KB . 'edit/' . $row['slug'] . '/'),

		'S_IS_REDIRECT'		=> $row['is_redirect'],
		'S_IS_ACTIVE'		=> $row['status'] == ARTICLE_ACTIVE ? TRUE : FALSE,
	));
}

$db->sql_freeresult($result);

$template->assign_vars(array(
	'SELECTED_VERSION'	=> $selected_version,
	'ADM_OPTION'	=> isset($adm_option) ? $adm_option : FALSE,
	'REQUEST_URI'	=> isset($request_uri) ? $request_uri : FALSE,

	// Revision statuses
	'S_REVISION_NONE'		=> REVISION_NONE,
	'S_REVISION_PENDING'	=> REVISION_PENDING,
	'S_REVISION_APPROVED'	=> REVISION_APPROVED,
	'S_REVISION_DENIED'		=> REVISION_DENIED,

	'S_LISTING'			=> TRUE,
));

// Generate the tabs, load the page
process_page($page_title, $mode);
