// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

now_playing_ajax_running = false;
now_playing_data = false;

// http://papermashup.com/read-url-get-variables-withjavascript/
function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}

function now_playing_timepad(val)
{

	while(val.toString().length<2) val = '0'+val;
	return val;

}

// http://snipplr.com/view/20348/time-in-seconds-to-hmmss/http://snipplr.com/view/20348/time-in-seconds-to-hmmss/
now_playing_hms = function(d)
{
	d = Number(d);
	d = Math.round(d/1000);
	var h = Math.floor(d / 3600);
	var m = Math.floor(d % 3600 / 60);
	var s = Math.floor(d % 3600 % 60);

	seph = 'h';
	sepm = 'm';
	seps = 's';

	var v = '';
		
	if(h>0) { m = now_playing_timepad(m); v = v+h+seph; }
	if(m>0) { s = now_playing_timepad(s); v = v+m+sepm; }
	v = v+s+seps;

	return v;
}

now_playing_update_tick = function()
{

	// make sure we're not already processing an ajax request.
	if(now_playing_ajax_running) return;

	var now = new Date().getTime();

	// if we need to update, make our update
	if(!now_playing_data || now>=now_playing_data.show_end || now>=now_playing_data.track_end)
	{

		var vars = new Object();
		vars.i = getUrlVars()['i'];
		vars.json = 1;

		$.get('/modules/now_playing/now_playing.php',vars,function(response)
		{

			now_playing_data = response;
			now_playing_data.show_end = new Date().getTime() + response.show_time_left*1000;
			now_playing_data.track_end = new Date().getTime() + response.media.time_left*1000;

			if(response.show_time_left < -10 || response.media.time_left < -10) 
			{
				clearInterval(now_playing_tick_id);
				$('#now_playing').replaceWith('<div id="now_playing" class="error">An error occurred while trying to determine what\'s playing.  Perhaps nothing is playing.</p>');
				now_playing_center();
				return;
			}
			
			$('#now_playing_show_countdown').text('time loading...');
			$('#now_playing_track_countdown').text('time loading...');

			$('#now_playing_show_name').text(now_playing_data.show_name);
			$('#now_playing_track_name').text(now_playing_data.media.artist+' - '+now_playing_data.media.title);

			if(now_playing_data.media.thumbnail)
			{
				$('#now_playing_thumbnail').html('<img src="/modules/now_playing/now_playing.php?i='+vars.i+'&thumbnail=1"/>');
			}


		},'json');

		return;

	}

	// no update required or pending, so just tick the countdowns.
	$('#now_playing_show_countdown').text(now_playing_hms(now_playing_data.show_end - now));
	$('#now_playing_track_countdown').text(now_playing_hms(now_playing_data.track_end - now));

}

$(document).ready(function()
{

	$(document).ajaxStart(function() { now_playing_ajax_running = true; });
	$(document).ajaxStop(function() { now_playing_ajax_running = false; });

	now_playing_tick_id = setInterval(now_playing_update_tick,1000);
});