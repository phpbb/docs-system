<?php
/**
*
* @package phpBB Documentation
* @copyright (c) 2006 phpBB Group
* @license Not for redistribution
*
*/

function docs_header_class($approved, $private)
{
	if ($private)
	{
		return 'private';
	}
	elseif (!$approved)
	{
		return 'unapproved';
	}
	else
	{
		return '';
	}
}

function can_edit_comment($user_id)
{
	global $user;
	if(DOCS_ADMIN)
	{
		return true;
	}
	if($user_id == $user->data['user_id'] && $user->data['user_id'] != ANONYMOUS)
	{
		return true;
	}

	return false;
}

function ug_report_error($message, $is_ajax_request)
{
	if ($is_ajax_request)
	{
		// @TODO Fix this
		echo '<br /><br /><div class="panel" id="message">
			<div class="inner"><span class="corners-top"><span></span></span>
			<h2>Information</h2>
			<p>' .  $message . '</p>

			<span class="corners-bottom"><span></span></span></div>
			</div>';
		exit;
	}
	else
	{
		trigger_error($message);
	}
}
