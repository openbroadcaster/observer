$(document).ready(function() {

	$('#login_password').add('#forgotpass_email').add('#newaccount_email').keypress(function (e) {
		if (e.which == 13) {
		  $('input[type=button]:visible').click();
		}
	});

});

var OB = new Object();
OB.Welcome = new Object();

OB.Welcome.show = function(what)
{
	$('.section').hide();
	$('.section#'+what).show();
}

OB.Welcome.login = function()
{
	var data = new Object();
	data.username = $('#login_username').val();
	data.password = $('#login_password').val();

	$('#login_message').text('');
	$('#login_submit').val('Logging in...').prop('disabled',true);

  $.post('../api.php', {'c': 'account', 'a': 'login', 'd': $.toJSON(data) },function(response) {

    if(response.status==false)
    {
      $('#login_message').text(response.msg);
			$('#login_submit').val('Log In').prop('disabled',false);
      $('#login_username').focus();
      $('#login_username').select();
    }

    else
    {
			window.location.href = '/';
    }

  },'json');
}

OB.Welcome.forgotpass = function()
{

	var data = new Object();
	data.email = $('#forgotpass_email').val();

	$('#forgotpass_message').text('');
	$('#forgotpass_submit').val('Sending...').prop('disabled',true);

	$.post('../api.php', {'c': 'account', 'a': 'forgotpass', 'd': $.toJSON(data)}, function(response)
	{
		$('#forgotpass_message').text(response.msg);
		if(response.status==true) $('#forgotpass_form input').remove();
		$('#forgotpass_submit').val('Submit').prop('disabled',false);
	},'json');

}

OB.Welcome.newaccount = function()
{
	var data = new Object();
	data.name = $('#newaccount_name').val();
	data.username = $('#newaccount_username').val();
	data.email = $('#newaccount_email').val();

	$('#newaccount_message').text('');
	$('#newaccount_submit').val('Sending...').prop('disabled',true);

	$.post('../api.php', {'c': 'account', 'a': 'newaccount', 'd': $.toJSON(data)},function(response)
	{
		$('#newaccount_message').html(response.msg);
		if(response.status==true) $('#newaccount_form input').remove();
		$('#newaccount_submit').val('Submit').prop('disabled',false);
	},'json');

}

OB.Welcome.appkeyPost = function (appkey, controller, action, sdata, callback_function, mode) {
  var async = (mode == 'sync') ? false : true;

  var xhr = $.ajax( {
    'async': async,
    'type': 'POST',
    'url': '/api.php',
    'dataType': 'json',
    'data': {
      "c": controller,
      "a": action,
      "d": $.toJSON(sdata),
      "appkey": appkey
    },
    'success': function (response) {
      callback_function(response);
    }
  });
}
