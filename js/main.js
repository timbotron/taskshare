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
				placement: 'right',
				content: function () {
					var $buttons = $('#popover_template').html();
					$buttons = $buttons.replace("replace_me",$(this).attr('id'));

					return $buttons;
				}
	}).popover('toggle');
});

$(document).on('click', '.edit_listname', function()
{
	//$(this).popover('hide');
	var $list_id = $(this).parent().attr('id');
	$('#'+$list_id).popover('toggle');
	console.log($(this).parent().attr('id'));
	//console.log('boom!');
});

}); //end document.ready