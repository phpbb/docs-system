/*
 * Not for redistribution
 */

var bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list]','[/list]','[list=]','[/list]','[img]','[/img]','[url]','[/url]','[flash=]', '[/flash]','[size=]','[/size]');
var form_name = 'add-comment';
var text_name = 'comment_text';

function add_comment_submit(tab, section)
{
	/*
	alert(tab + '.....' + section);
	
	var a = "https://www.phpbb.local/support/docs/en/3.0/ug/?comment_action=add" + "&version=3.1&lang=en&selected_tab=" + tab + "&selected_sec=" + section + "&comment_text=" + $("#comment_text").val();
	
	alert(a);

	$.get(a, function(data)
	{
		$('#display_comments_block').replaceWith(data);
	});
	
	 e.preventDefault();
	 return false;
	*/
	$.ajax({
		type: "POST",
		url: window.location.pathname,
		dataType: "html",
		data: "comment_action=add" + "&version=3.0&lang=en&selected_tab=" + tab + "&selected_sec=" + section + "&comment_text=" + $("#comment_text").val(),
		success: function(html)
		{
			//alert(html);
			// Reload the comments
			var htmlCommentsBlock = $(html).find("#display_comments_block");
			$('#display_comments_block').replaceWith(htmlCommentsBlock);

			//$('#qr_editor_div').css("display","none");
		}
	});
}

function comment_inline_edit(commentID)
{
	jQuery("div[id='comment-body-" + commentID + "']").toggle();
	jQuery("div[id='comment-edit-" + commentID + "']").toggle();
	jQuery("div[id='comment-body-" + commentID + "']").focus();
}

function comment_edit(commentID)
{
	jQuery.ajax({
		type: "POST",
		url: "index.php",
		data: "comment_action=edit" + "&comment-id=" + commentID + "&new_comment_text=" + jQuery("textarea#textarea-" + commentID + "").val(),
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
		jQuery.ajax({
			type: "POST",
			url: "index.php",
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
		jQuery.ajax({
			type: "POST",
			url: "index.php",
			data: "comment_action=approve" + "&comment_id=" + commentID,
			success: function(html){
				$('#display_comments_block').replaceWith(html);
				return true;
			}
		})
	}
}
$(document).ready(function()
{
	jQuery("form#add-comment").submit(function(e){
		e.preventDefault();
		return false;
	})

	jQuery("form[id^='new-form-']").submit(function(e){
		e.preventDefault();
		return false;
	})

	$(document).delegate("a[id^='delete-']", 'click', function(e)
	{
		e.preventDefault();
		var delID = $(this).attr('id').split('-')[1];
		comment_delete(delID);
		return false;
	})

	$(document).delegate("a[id^='approve-']", 'click', function(e)
	{
		e.preventDefault();
		var appID = $(this).attr('id').split('-')[1];
		comment_approve(appID);
		return false;
	})
})

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
