#!/usr/bin/php
<?php
// Absolute path to the public userguide dir
$abs_path_to_data = '/var/www/phpbb.com/htdocs/support/docs/ug/data/';

// Version - may be dynamic later
$version = '3.0';

// Localisation - likewise may be dynamic later
$localisation = 'en';

// This is used to clear out some redundant bits from the slugs
$replace = array(
	'quickstart' => 'quickstart/quick_',
	'upgradeguide' => 'upgradeguide/upgrade_',
	'adminguide' => 'adminguide/acp_',
);

// To be sereialised later
$tabs = array();
$nav_bar = array();

/*
* Takes the navigation array from docbook and processes it into the .com docs system
*/
function process_nav($nav_array, $level = 0, $under_tab = '')
{
	global $tabs, $nav_bar, $replace;
	global $version, $localisation, $abs_path_to_data;

	foreach($nav_array as $key => $value)
	{
		if ($level == 0)
		{
			// The slug of the tab
			$slug = substr($value[0], 0, -10);
		
			// Add to tabs array
			$tabs[$slug] = $value[1];

			// Add an entry to the $nav_bar array
			$nar_bar[$slug] = array();

			// Create a dir for this main section
			mkdir($abs_path_to_data . $version . '/' . $localisation . '/' . $slug, 0755, true);

			// Process the rest of this section
			process_nav($value[2], $level + 1, $slug);
		}
		else
		{
			// Some cleanup of the slug/filename
			$slug = isset($replace[$under_tab]) ? str_replace($replace[$under_tab], '', $value[0]) : $value[0];
			$slug = str_replace($under_tab . '/', '', $slug);
			$slug = substr($slug, 0, -4);

			// Add this section to the navigation
			$nav_bar[$under_tab][$slug] = array($value[1], $level);

			// Now get this sections's content
			include($abs_path_to_data . $version . '/_updater/build/' . $value[0]);

			// We only want the content
			$start = strpos($content, '<p>');
			$end = strrpos($content, '<div class="copyright">');
			$content = substr($content, $start, $end - $start - 6);

			// Fix the image path
			$content = str_replace('<img src="../images/', '<img src="/support/docs/ug/images/' . $version . '/', $content);

			// Write the content to a file and use the "clean" filename
			$fh = fopen($abs_path_to_data . $version . '/' . $localisation . '/' . $under_tab . '/' . $slug, 'w');
			fwrite($fh, "\xEF\xBB\xBF" . $content);
			fclose($fh);

			// Process any subsections
			if (isset($value[2][0]))
			{
				process_nav($value[2], $level + 1, $under_tab);
			}
		}
	}
}

// Grab the main navigation file and process the stuff within
include($abs_path_to_data . '3.0/_updater/build/index.php');
process_nav($navigation['toc'][2]);

// Serialize the navigation arrays above and write the contents into files
$fh = fopen($abs_path_to_data . $version . '/' . $localisation . '/_tabs', 'w');
fwrite($fh, "\xEF\xBB\xBF" . serialize($tabs));
fclose($fh);

foreach($nav_bar as $tab_name => $tab_content)
{
	$fh = fopen($abs_path_to_data . $version . '/' . $localisation . '/' . $tab_name . '/_navigation', 'w');
	fwrite($fh, "\xEF\xBB\xBF" . serialize($tab_content));
	fclose($fh);
}
?>