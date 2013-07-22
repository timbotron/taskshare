/*
JS ELEMENTS:
[ ] popup for tasklist rename / delete / ADD TASK
[ ] ADD TASK
	[ ] Input appears, top tr, has save / done. Also saves on enter.
	[ ] On save, pushes to REST, if success adds underneath and input clears for next todo.
	[ ] When done adding, hit done. Input row goes away
[ ] EDIT TASKLIST
	[ ] remove all popovers when clicked
	[ ] Input appears with text of tasklist name in it, and a done button
	[ ] on enter or clicking done, input goes disabled and done button replaced with whirly
	[ ] on success, destroy input and whirly and show tasklist name again

*/


$( document ).ready(function() {




/*
	TaskList Block
*/
$('.list_title').click(function () 
{
	$(this).popover({
				html: true,
				trigger: 'manual',
				placement: 'bottom',
				content: function () {
					var $buttons = $('#popover_template').html();
					$buttons = $buttons.replace("replace_me",$(this).attr('id'));

					return $buttons;
				}
	}).popover('toggle');
});

$(document).on('click', '.edit_listname', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	$('#'+$list_id).popover('toggle').hide();
	var $template = $('#tasklist_name_edit_template').html();
	$template = $template.replace(/replace_me/gim,$list_id);
	$('.'+$list_id).html($template);
	$('.edit-tasklist-'+$list_id).val($('#'+$list_id).html()).focus();
	console.log($list_id);
});

$(document).on('click', '.save_tasklist', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	save_tasklist_edit($list_id,$('.edit-tasklist-'+$list_id).val());
});

$(document).on('keyup','input[class*=edit-tasklist]',function(e)
{
	var $list_id = $(this).parent().prev().attr('class');
	if(e.keyCode==13) //is enter
	{
		save_tasklist_edit($list_id,$(this).val());
	}
});

function save_tasklist_edit($list_id,$new_value)
{
	console.log($list_id+" "+$new_value);
	$('#'+$list_id).html($new_value).show();
	$('.'+$list_id).empty();

	var $subtractme = window.location.hostname + "/b/";

	var $boardname = window.location.href.replace($subtractme, "").replace('http://','');

	console.log($boardname);

	// Now to work on pushing to API

	$list_id_num = $list_id.replace("list_","");

	var $json = '{ "listname": "'+$new_value+'" }';
	
	$.ajax({
		type: "PUT",
		url: "/tasklist/"+$list_id_num+"/"+$boardname,
		contentType: "json",
		data: $json
	});
	
}

}); //end document.ready