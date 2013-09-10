 /*
 * Not for redistribution
 */

var bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list]','[/list]','[list=]','[/list]','[img]','[/img]','[url]','[/url]','[flash=]', '[/flash]','[size=]','[/size]');
var form_name = 'comment-form';
var text_name = 'comment_text';

function comment_inline_edit(commentID)
{
	$("div[id='comment-body-" + commentID + "']").toggle();
	$("div[id='comment-edit-" + commentID + "']").toggle();
	$("div[id='comment-body-" + commentID + "']").focus();
}

function comment_edit(commentID)
{
	var comment = $("textarea#textarea-" + commentID + "").val();
	if (comment == '')
	{
		alert('Pleae enter your comment.');
		return;
	}
	
	$.ajax({
		type: "POST",
		url: window.location.pathname,
		data: "comment_action=edit" + "&comment-id=" + commentID + "&new_comment_text=" + comment,
		success: function(html)
		{
			// Reload the comments
			$('#display_comments_block').replaceWith(html);
		}
	});
}

/* Delete */
function comment_delete(commentID)
{
	if (confirm("Are you sure you wish to delete this comment?"))
	{
		$.ajax({
			type: "POST",
			url: window.location.pathname,
			data: "comment_action=delete" + "&comment_id=" + commentID,
			success: function(html){
				$('#display_comments_block').replaceWith(html);
				return true;
			}
		});
	}
}

/* Approve */
function comment_approve(commentID)
{
	if (confirm("Are you sure you wish to approve this comment?"))
	{
		$.ajax({
			type: "POST",
			url: window.location.pathname,
			data: "comment_action=approve" + "&comment_id=" + commentID,
			success: function(html){
				$('#display_comments_block').replaceWith(html);
				return true;
			}
		});
	}
}

function delete_attachment(attach_id)
{
	var form_data = $('#comment-form').serializeArray();
	form_data.push({ name: "delete_attach", value: attach_id });
	$.post (
		window.location.pathname,
		form_data,
		function (data)
		{
			$('#display_comments_block').replaceWith(data);
		}
	);
}

function hide_qr(show)
{
	dE('qr_editor_div');
	dE('qr_showeditor_div');
	/*if (show && document.getElementById('qr_editor_div').style.display != 'none')
	{
		document.getElementsByName('comment_text')[0].focus();
	}*/
	return true;
}


$(function(){
	//why so many parameters?
	//var a = window.location.pathname + "?mode=add" + "&version=3.0&lang=en&selected_tab=" + tab + "&selected_sec=" + section + "&comment_text=" + jQuery("#comment_text").val();

	$(document).delegate('input#comment_submit_button', 'click',function(e)
	{
		e.preventDefault();
		
		var comment = $('#comment_text').val();
		if (comment == '')
		{
			$('div.rules').find('div.inner').empty();
			$('div.rules').find('div.inner').html('<span class="corners-top"><span></span></span><strong>Please enter your comment</strong><span class="corners-bottom"><span></span></span>');
			return;
		}
		/*
		var a = window.location.pathname + "?comment_action=add&comment_text=" + comment;
		$.get(a, function(data)
		{
			$('#display_comments_block').replaceWith(data);
		});
		*/
		// Replaced with post to submit attachment
		$('#comment-form').ajaxForm
		({
			url: window.location.pathname,
			type: "post",
			success: function (data)
			{
				$('#display_comments_block').replaceWith(data);
			}
		});		
		/*
		$.post (
			window.location.pathname,
			$('#comment-form').serialize(),
			function (data)
			{
				$('#display_comments_block').replaceWith(data);
			}
		);*/
	});

	//$(document).delegate("input[name^='delete_']", 'click',function(e)

	
	$(document).delegate('input[name=add_attachment]', 'click',function()
	{
		$('#comment-form').ajaxForm
		({
			url: window.location.pathname,
			type: "post",
			success: function (data)
			{
				$('#display_comments_block').replaceWith(data);
			}
		});
	});	
	

	$("form[id^='new-form-']").submit(function(e){
		e.preventDefault();
		return false;
	});


	$(document).delegate("a[id^='delete-']", 'click',function(e)
	{
		e.preventDefault();
		
		var delID = $(this).attr('id').split('-')[1];
		comment_delete(delID);
	});
	

	$(document).delegate("a[id^='approve-']", 'click',function(e)
	{
		e.preventDefault();
		var appID = $(this).attr('id').split('-')[1];
		comment_approve(appID);
	});
});
