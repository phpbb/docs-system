<?php
/**
*
* @package phpBB.com Documentation - Knowledge Base
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

$page_title = 'phpBB 3.0 Olympus Documentation';

define('IN_PHPBB', true);

require('./common.php');

$template->set_filenames(array(
	'body'	=> DOCS_TEMPLATE_PATH . 'docs_index.html'
));

page_header($page_title, false);
page_footer(false);
