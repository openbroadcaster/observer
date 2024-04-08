/*     
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

OBModules.Tutorial = new function()
{

  this.init = function()
  {
    OB.Callbacks.add('ready',0,OBModules.Tutorial.initMenu);
  }

	this.initMenu = function()
	{
    OB.UI.addSubMenuItem('help','Run Tutorial','run_tutorial',OBModules.Tutorial.go,5);
	}


	this.currentStep = 0;

	this.steps = new Array();

	this.steps[0] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('Welcome to the OpenBroadcaster tutorial.  This tutorial will take you through uploading media, creating a playlist, and scheduling a show.\
					<br><br>After you have completed each step, the tutorial instructions will automatically advance.  Click-drag to move this window if it gets in your way.\
					<br><br>To begin, we will upload some media.  Under the media menu at the bottom of the screen, click "upload media".  You can also use the "new" button found in the media sidebar on the right.');
		}

		this.nextCondition = function()
		{
			// check conditions for new media page
			if($('#media_data').length && $('#media_top_message').length && !$('#media_data_middle div').length) return true;
			return false;
		}

	}

	this.steps[1] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('You have found the media upload page.<br><br>\
							Click the "upload a file" button to find one or more files on your computer to upload.\
							You can use shift-click or control-click to select multiple files.');
		}

		this.nextCondition = function()
		{
			if($('#media_data_middle div').length) return true;
			return false;
		}

	}

	this.steps[2] = new function()
	{

		this.init = function()
		{

			$('#tutorial_module-text').html('Well done. <br><br>Media files are being uploaded.  You may enter media information in the form fields.\
				To copy media information to all your uploads, use the "copy to all" buttons.  \
				For audio files, you can use the ID3 button to obtain information about the media automatically. \
				<br><br>Once the uploads are complete and the information has been provided, click "save data" to complete the new media process.');

			// tts needed I D 3 to say properly.
			OBModules.Tutorial.tts('Well done. Media files are being uploaded.  You may enter media information in the form fields.\
				To copy media information to all your uploads, use the "copy to all" buttons.  \
				For audio files, you can use the I D 3 button to obtain information about the media automatically. \
				Once the uploads are complete and the information has been provided, click "save data" to complete the new media process.');

		}

		this.nextCondition = function()
		{
			if($('#media_top_message').hasClass('success')) return true;
			return false;
		}

	}

	this.steps[3] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('Your media has been saved. Let\'s move on to creating a playlist. \
				<br><br>Under the playlists menu, select "new playlist".  You can also use the "new" button under the playlists sidebar on the right.');
		}		

		this.nextCondition = function()
		{
			if($('#playlist_edit_heading').length) return true;
			return false;
		}

	}

	this.steps[4] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('To begin, enter a playlist name and description.  Then using the playlist and media sidebar on the right, drag media or other playlists into your playlist items near the middle of the screen.');
		}

		this.nextCondition = function()
		{
			if($('.playlist_addedit_item').length && $('#playlist_name_input').val()!='') return true;
			return false;
		}

	}

	this.steps[5] = new function()
	{
		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('When you are finished creating your playlist, click "save" to complete the new playlist process.');
		}

		this.nextCondition = function()
		{
			if($('#playlist_addedit_message').hasClass('success')) return true;
			return false;
		}
	}

	this.steps[6] = new function()
	{
		this.init = function()
		{

			// make sure we are searching all content, not just my content.
			if(OB.Sidebar.media_search_filters.my) OB.Sidebar.mediaSearchFilter('my');
			if(OB.Sidebar.playlist_search_filters.my) OB.Sidebar.playlistSearchFilter('my');

			$('#sidebar_search_playlist_input').val('');
			$('#sidebar_search_media_input').val('');

			OBModules.Tutorial.giveInstructions('Let\s now search for our new media and playlist in the sidebar on the right.\
				<br><br>Click the media tab to search and browse media. Click the playlist tab to search and browse playlists.\
				<br><br>To search media by artist or title, or playlists by name or description, enter a search term into the search field.  The results will be automatically filtered below.\
				<br><br>Try searching for a playlist or media item now.');

		}

		this.nextCondition = function()
		{

			if($('#sidebar_search_playlist_input').val()!='') return true;
			if($('#sidebar_search_media_input').val()!='') return true;

			return false;

		}

	}

	this.steps[7] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('If you want to limit the results to content you have uploaded or created, select the "my" link.  To view all your content, make sure the search text field is clear.\
				<br><br>Try clicking the "my" link to view your media or playlists now.');
		}

		this.nextCondition = function()
		{
			
			if(OB.Sidebar.media_search_filters.my) return true;
			if(OB.Sidebar.playlist_search_filters.my) return true;

			return false;
		}

	}

	this.steps[8] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('Now that we can create and explore media and playlists, let\'s finally look at how to schedule a show. A show is a playlist that has been scheduled to play at a certain time.\
				<br><br>Begin by loading the scheduler using "schedule shows" in the "schedules" menu.');
		}

		this.nextCondition = function()
		{
			if($('#schedule_container').length && $('#schedule_container').hasClass('droppable_target_playlist')) return true;
			return false;
		}

	}

	this.steps[9] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('To schedule a show, you must already have time slots assigned to you, or have permission to schedule at any time.\
				<br><br>First select the week you want to schedule.  Use the next and previous links on the schedule table to change weeks.  Then, click the "playlist" tab to access the playlist sidebar and search for the playlist you want to schedule.\
				<br><br>Finally, click-drag that playlist and drop it on the schedule table.  It does not matter where on the schedule table you drop the playlist.');
		}

		this.nextCondition = function()
		{
			if($('#layout_modal_window #show_addedit_form').length) return true;
			return false;
		}

	}

	this.steps[10] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('Select a timeslot to schedule your show, then click save.\
				<br><br>If you have the ability to schedule anywhere on the player, you may alternatively provide a scheduling mode, start time, and duration.');
		}

		this.nextCondition = function()
		{
			if($('#layout_modal_window').is(':visible')==false) return true;
			return false;
		}

	}

	this.steps[11] = new function()
	{

		this.init = function()
		{
			OBModules.Tutorial.giveInstructions('This concludes the tutorial.  We hope that it has provided a good introduction to using OpenBroadcaster.\
				<br><br>Click the exit link on the tutorial window to resume normal OpenBroadcaster operation.');
		}

		this.nextCondition = function()
		{
			return false;
		}

	}

	this.giveInstructions = function(data)
	{
		$('#tutorial_module-text').html(data);
		OBModules.Tutorial.tts($('#tutorial_module-text').text());
	}


	this.nextIntervalId = null;

	this.go = function()
	{

		OB.Layout.home(); // return OB to welcome screen - the starting point to the tutorial.  steps might automatically advance otherwise.

		this.audioMuted = false;

		$('#tutorial_module-menuitem').html('<a href="javascript: OBModules.Tutorial.exit();">Stop Tutorial</a></li>');
		$('#main_container').append(OB.UI.getHTML('modules/tutorial/tutorial.html'));

		$('#tutorial_module-window').draggable({ containment: 'document' });

		$('#tutorial_module-prev').css('visibility','hidden');
		$('#tutorial_module-next').css('visibility','visible');

		this.currentStep=0;
		OBModules.Tutorial.steps[0].init();

		// set an interval to check next step condition.
		this.nextIntervalId = setInterval(function()
		{
			if(OBModules.Tutorial.steps[OBModules.Tutorial.currentStep].nextCondition()) OBModules.Tutorial.nextStep();
		},500);
	}

	this.exit = function()
	{
		$('#tutorial_module-menuitem').html('<a href="javascript: OBModules.Tutorial.go();">Run Tutorial</a></li>');
		this.audio.pause();
		$('#tutorial_module-window').remove();

		clearInterval(this.nextIntervalId);
	}

	this.nextStep = function()
	{
		if(this.currentStep == (this.steps.length - 1)) return;
		this.currentStep += 1;
		this.steps[this.currentStep].init();
	}

	this.prevStep = function()
	{
		if(this.currentStep == 0) return;
		this.currentStep -= 1;
		this.steps[this.currentStep].init();
	}

	this.restart = function()	
	{
		this.exit();
		this.go();
	}

	this.mute = function()
	{
		if(this.audioMuted)
		{
			$('#tutorial_module-mute a').html('mute audio');
			this.audioMuted = false;
			this.audio.play();
		}
		else
		{
			$('#tutorial_module-mute a').html('unmute audio');
			this.audioMuted = true;
			this.audio.pause();
		}

	}

	// TODO - build this into the core!
	this.tts = function(text)
	{
		this.audio.pause();
		this.audio.setAttribute('src','/tts.php?t='+escape(text));
		if(!this.audioMuted) this.audio.play();
	}

	this.audio = document.createElement('audio');

	this.audioMuted = false;

}

