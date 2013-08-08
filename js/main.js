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



$(document).on('click', '.list_title', function()
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

$(document).on('click', '.delete_listname', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	$('#'+$list_id).popover('toggle');

	delete_tasklist($list_id);	

});

$(document).on('click', '.edit_listname', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	$('#'+$list_id).popover('toggle').hide();
	var $template = $('#tasklist_name_edit_template').html();
	$template = $template.replace(/replace_me/gim,$list_id);
	$('span.'+$list_id).html($template);
	$('.edit-tasklist-'+$list_id).val($('#'+$list_id).html()).focus();
	console.log($list_id);
});

$(document).on('click', '.save_tasklist', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	save_tasklist_edit($list_id,$('.edit-tasklist-'+$list_id).val());
});

$('.add-list').click(function ()
{
	create_new_tasklist();
	//console.log('clicked!');
});

$(document).on('keyup','input[class*=edit-tasklist]',function(e)
{
	var $list_id = $(this).parent().prev().attr('class');
	if(e.keyCode==13) //is enter
	{
		save_tasklist_edit($list_id,$(this).val());
	}
});

function delete_tasklist($list_id)
{

	var $subtractme = window.location.hostname + "/b/";
	var $boardname = window.location.href.replace($subtractme, "").replace('http://','');

	$list_id_num = $list_id.replace("list_","");

	$.ajax({
		type: "DELETE",
		url: "/tasklist/"+$list_id_num+"/"+$boardname,
		contentType: "json",
		data: '',
		success: function (data)
		{
			$('#'+$list_id).parent().parent().parent().remove();
		}
	});
}

function create_new_tasklist()
{

	var $subtractme = window.location.hostname + "/b/";

	var $boardname = window.location.href.replace($subtractme, "").replace('http://','');

	var $list_template = '<li class="span5"> \
  <div class="thumbnail"> \
    <h3><span class="list_title" id="list_{list_id}">New List</span><span class="list_{list_id}"></span></h3> \
    <table class="table table-condensed list_{list_id}"><tbody> \
    </tbody></table> \
  </div> \
</li>';
console.log($list_template);

	var $json = '{ "listname": "New List" }';
	$.ajax({
		type: "POST",
		url: "/tasklist/1/"+$boardname,
		contentType: "json",
		dataType: "json",
		data: $json,
		success: function (data)
		{
			$list_template = $list_template.replace(/{list_id}/gim,data.id);
			$('ul.thumbnails').prepend($list_template);

		}
	});
}

function save_tasklist_edit($list_id,$new_value)
{
	console.log($list_id+" "+$new_value);
	$('#'+$list_id).html($new_value).show();
	$('span.'+$list_id).empty();

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

/*
// TASK BLOCK
*/

var $new_task_row = '<tr><td class="check_td"><button class="btn btn-small btn-success done_task" value="task-{taskid}"><i class="icon-ok icon-white"></i></button></td><td class="taskname">{taskname}</td></tr>';

$(document).on('click', 'button.done_task', function()
{
	var $task_id = $(this).val();
	var $list_id = $(this).parent().parent().parent().parent().prev().children('span[class=list_title]').attr('id');
	$(this).parent().parent().remove();
	console.log($task_id+" "+$list_id);
	complete_task($task_id,$list_id);
});

$(document).on('click', '.add_task', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	$('#'+$list_id).popover('toggle');
	var $template = $('#task_add_template').html();
	$template = $template.replace(/replace_me/gim,$list_id);
	$('span.'+$list_id).html($template);
	$('.add-task-'+$list_id).focus();
	console.log($list_id);
});

$(document).on('keyup','input[class*=add-task]',function(e)
{
	var $list_id = $(this).parent().prev().attr('class');
	if(e.keyCode==13) //is enter
	{
		save_new_task($list_id,$('.add-task-'+$list_id).val());
	}
});

$(document).on('click', '.save_task', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	save_new_task($list_id,$('.add-task-'+$list_id).val());
});

$(document).on('click', '.done_adding_task', function()
{
	var $list_id = $(this).parent().prev().attr('class');
	$('span.'+$list_id).empty();
});

function complete_task($task_id,$list_id)
{
	var $subtractme = window.location.hostname + "/b/";

	var $boardname = window.location.href.replace($subtractme, "").replace('http://','');

	$list_id_num = $list_id.replace("list_","");
	$task_id_num = $task_id.replace("task-","");

	var $json = '{ "taskid": '+$task_id_num+' }';

	$.ajax({
		type: "DELETE",
		url: "/task/"+$list_id_num+"/"+$boardname,
		contentType: "json",
		data: $json
	});


}

function save_new_task($list_id,$new_value)
{
	var $template = $new_task_row.replace("{taskname}",$new_value);

	var $subtractme = window.location.hostname + "/b/";

	var $boardname = window.location.href.replace($subtractme, "").replace('http://','');

	// Now to work on pushing to API

	$list_id_num = $list_id.replace("list_","");

	var $json = '{ "taskname": "'+$new_value+'" }';
	
	$.ajax({
		type: "POST",
		url: "/task/"+$list_id_num+"/"+$boardname,
		contentType: "json",
		dataType: "json",
		data: $json,
		success: function (data)
		{
			$template = $template.replace("{taskid}",data.id);
			$('table.'+$list_id+" tbody").prepend($template);

		}
	});

	$('.add-task-'+$list_id).val('').focus();
	
}

}); //end document.ready