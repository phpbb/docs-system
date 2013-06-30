<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

// Standard definitions/includes
define('IN_PHPBB', true);

require('./common.php');
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Either the slug or article id will work
$slug = request_var('slug', '');
$slug = (is_numeric($slug)) ? 'AND a.id = ' . (int) $slug . ' ' : "AND a.slug = '$slug' ";

// Get the article's data
$sql = 'SELECT a.*, u.user_id, u.user_colour, u.username
	FROM ' . KB_ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
	WHERE u.user_id = a.author_id
	AND is_redirect = 0 ' .
	$slug;
$result = $db->sql_query_limit($sql, 1);
$display_data = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

/*if (is_numeric($slug))
{
	$path = append_sid($base_path . 'kb/article/' . $row['slug'] . '/', (request_var('redirected', 0) ? 'redirected=1' : ''));
	header("Location: $path");
	trigger_error('You may view the article here: <a href="' . htmlspecialchars($path) . '">Knowledge Base - ' . $row['title'] . '</a>');
}*/

if (!$display_data || ($display_data['status'] != ARTICLE_ACTIVE && !KB_ADMIN && $display_data['author_id'] != $user->data['user_id']))
{
	display_message('You are requesting to view an invalid article.<br /><br />Please search the <a href="' . append_sid(ABS_PATH_TO_DOCS_KB) . '">Knowledge Base</a> for an article that answers your question.');
}

// Only run the following for admins and the article's author
if (KB_ADMIN || $display_data['author_id'] == $user->data['user_id'])
{
	// Assignments
	$assigned_to = '';
	foreach($assignments as $key => $value)
	{
		$assigned_to .= '<option ' . (($display_data['assigned_to'] == $key) ? 'selected="selected" ' : '') . 'value="' . $key . '">&nbsp; &nbsp; &nbsp; &nbsp;' . (($display_data['assigned_to'] == $key) ? '✔ ' : '') . $value . '</option>';
	}

	// Figure out which status image to display
	switch ($display_data['status'])
	{
		case ARTICLE_ACTIVE:
			$status_image = 'active';
			$is_active = TRUE;
			break;
		case ARTICLE_NEW:
			// The author will see "article reviewed" after 12 hours :P
			$status_image = (KB_ADMIN || time() - $display_data['date'] < 43200) ? 'newly_added' : 'reviewed';
			break;
		case ARTICLE_INACTIVE:
			$status_image = 'inactive';
			break;
	}

	// Are we displaying the article or the article's revision?
	if (isset($_GET['rev']))
	{
		if ($display_data['revision_date'])
		{
			$sql = 'SELECT *
				FROM ' . KB_REVISIONS_TABLE . '
				WHERE id = ' . $display_data['id'];
			$result = $db->sql_query_limit($sql, 1);
			$revision_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$revision_data)
			{
				trigger_error("Unrecoverable error 960TDI");
			}

			$category_entries = unserialize($revision_data['category_entries']);
		}
		else
		{
			display_message('No revision exists for this article.');
		}

		// Build the category dropdown
		foreach ($category_entries as $category)
		{
				$template->assign_block_vars('category', array(
					'CATEGORY' => $category,
				));
		}

		$cats_set = true;

		//@TODO Add all the stuff that gets updated here.
	}
}
else
{
	// Don't display a status image
	$status_image = FALSE;
}

// Only do this if the categories were not already set above from the revision table
if (!isset($cats_set))
{
	// Since the categories are stored in another table, we need to grab them
	$sql = 'SELECT category_slug FROM ' . KB_CATEGORY_ENTRIES_TABLE. ' WHERE article_id = ' . $display_data['id'];
	$result = $db->sql_query($sql);

	// Build the category dropdown
	while ($row = $db->sql_fetchrow($result))
	{
		$template->assign_block_vars('category', array(
			'CATEGORY' => $categories[$row['category_slug']],
		));
	}

	$db->sql_freeresult($result);

	// Update article views
	$sql = 'UPDATE ' . KB_ARTICLES_TABLE . ' SET views = views + 1 WHERE id = ' . (int) $display_data['id'];
	$db->sql_query_limit($sql, 1);
}

$text = generate_text_for_display($display_data['text'], $display_data['bbcode_uid'], $display_data['bbcode_bitfield'], $display_data['bbcode_flags']);
$text = str_replace('<img src="' . $phpbb_root_path, '<img src="' . substr_replace($phpbb_root_path, './../../../..', 0, 2), $text);
$text = add_title_headers($text);

//include($phpbb_root_path . 'includes/bbcode.' . $phpEx);


$template->set_filenames(array(
	'body'				=> DOCS_TEMPLATE_PATH . 'kb_article.html',

	// @TODO If there's a cleaner way to do this, I haven't found it
	'attachment_tpl'	=> '../community/styles/prosilver/template/attachment.html',
));

$bbcode = new bbcode();

$bbcode->bbcode_second_pass($text, 'drr9yh7v', 'AAg=');
	$moo = unserialize('a:1:{i:0;a:15:{s:9:"attach_id";s:6:"134195";s:11:"post_msg_id";s:8:"12816927";s:8:"topic_id";s:7:"2096450";s:10:"in_message";s:1:"0";s:9:"poster_id";s:6:"750575";s:9:"is_orphan";s:1:"0";s:17:"physical_filename";s:39:"151944_11f4631db88f915b0d845476c9c1a962";s:13:"real_filename";s:14:"2girls1cup.jpg";s:14:"download_count";s:1:"2";s:14:"attach_comment";s:0:"";s:9:"extension";s:3:"jpg";s:8:"mimetype";s:10:"image/jpeg";s:8:"filesize";s:6:"207399";s:8:"filetime";s:10:"1278826526";s:9:"thumbnail";s:1:"1";}}
');

	parse_attachments(265, $text, $moo, $update_count);
	
	//echo $text;
	
	$text = str_replace('../../../community/download', '../../../../../community/download', $text);


	//$attachments[$row['post_id']] = $moo;




// Special case for 'phpBB Team'
$display_data['username'] = ($display_data['user_id'] == KB_BOT) ? 'phpBB Team' : $display_data['username'];

// Assign some vars
$template->assign_vars(array(
	'ID'					=> $display_data['id'],
	'TITLE'					=> $display_data['title'],
	'SLUG'					=> $display_data['slug'],
	'DESCRIPTION'			=> $display_data['description'],
	'TEXT'					=> str_replace(array('<ul>', '<ol style="'), array('<ul style="font-size:1em;">', '<ol style="font-size:1em; '), $text),
	'STATUS_IMG'			=> $status_image,
	'DATE_FORMATTED'		=> $user->format_date($display_data['date']),
	'AUTHOR_USERNAME'		=> create_author_string($display_data['user_id'], $display_data['username'], $display_data['user_colour']),
	'LINK_TO_ARTICLE'		=> '[url=' . SITE_URL . ABS_PATH_TO_DOCS_KB . 'article/' . $display_data['slug'] . '/]Knowledge Base - ' . $display_data['title'] . '[/url]',

	'DATE_SUBMITTED'		=> $user->format_date($display_data['date_submitted'], 'M d, Y g:i a'),
	'DATE_LAST_MODIFIED'	=> $user->format_date($display_data['date_last_modified'], 'M d, Y g:i a'),


	'ASSIGNED_TO'			=> $assigned_to,

	'S_IS_ACTIVE'			=> isset($is_active) ? TRUE : FALSE,
	'S_REDIRECTED'			=> request_var('redirected', 0),
	'S_HAS_REVISION'		=> isset($cats_set)? TRUE : FALSE,
	'REQUEST_URI'	=> isset($request_uri) ? $request_uri : FALSE,
));

// Generate the tabs, load the page
process_page($display_data['title'], 'Reading Article ' . $display_data['id'], TRUE);

