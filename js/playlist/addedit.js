/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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

OB.Playlist.newPage = function()
{

  OB.Playlist.advanced_items = [];

  OB.API.post('players','station_id_avg_duration', {}, function(data) {
    OB.Playlist.station_id_avg_duration=data.data;

    OB.UI.replaceMain('playlist/addedit.html');
    OB.Playlist.addCustomTypeButtons();
    OB.UI.permissionsUpdate();

    //T New Playlist
    $('#playlist_edit_heading').text( OB.t('New Playlist') );
    //T Fill out the following playlist information, then drag media and playlist items into the playlist area below.
    $('#playlist_edit_instructions').text( OB.t('Fill out the following playlist information, then drag media and playlist items into the playlist area below.') );

    // need this to prevent adding other user's private media to this playlist.
    $('#playlist_owner_id').val(OB.Account.user_id);

    OB.Playlist.addeditTypeChange();

    OB.Playlist.addeditInit();
    OB.Playlist.advancedInit();
  });

}


OB.Playlist.editPage = function(id)
{

  OB.Playlist.advanced_items = [];

  // if not defined, get our ID from the sidebar
  if(typeof(id)=='undefined')
  {
    //T Select one playlist only.
    if($('.sidebar_search_playlist_selected').size() > 1) { OB.UI.alert( 'Select one playlist only.' ); return; }
    id = $('.sidebar_search_playlist_selected').first().attr('data-id');
  }

  // otherwise make sure it's a number or string
  else if(typeof(id)!='number' && typeof(id)!='string')
  {
    return;
  }

  var post = [];
  post.push(['players','station_id_avg_duration', {}]);
  post.push(['playlists','get', { 'id': id }]);

  OB.API.multiPost(post, function(response)
  {

    OB.Playlist.station_id_avg_duration=response[0].data;

    // if we didn't get our playlist we are trying to edit, just direct to create new playlist instead.
    if(response[1].status==false) { OB.Playlist.newPage(); return; }

    OB.UI.replaceMain('playlist/addedit.html');
    OB.Playlist.addCustomTypeButtons();
    OB.UI.permissionsUpdate();

    //T Edit Playlist
    $('#playlist_edit_heading').text( OB.t('Edit Playlist') );
    //T Edit the following playlist as required.
    $('#playlist_edit_instructions').text( OB.t('Edit the following playlist as required.') );

    OB.Playlist.addeditInit();
    OB.Playlist.advancedInit();

    // playlist data from the db (up to date, at this point anyway...)
    playlist_data = response[1].data;

    $('#playlist_name_input').val(playlist_data['name']);
    $('#playlist_thumbnail_input').val(playlist_data['thumbnail']);
    $('#playlist_description_input').val(playlist_data['description']);
    $('#playlist_id').val(playlist_data['id']);
    $('#playlist_owner_id').val(playlist_data['owner_id']);

    $('#playlist_status_input').val(playlist_data['status']);
    $('#playlist_type_input').val(playlist_data['type']);

    OB.Playlist.addeditTypeChange();

    $.each(playlist_data['items'], function(index, item) {

      if(playlist_data['type']=='advanced')
      {
        OB.Playlist.advancedAddItem(item,true);
      }

      else
      {
        if(item['type']=='dynamic') OB.Playlist.addeditInsertDynamic(false,item['properties']['query'],item['duration'],item['properties']['name'],item['properties']['num_items'],item['properties']['image_duration'],item['properties']['crossfade'] ?? 0,item['properties']['crossfade_last'] ?? 0);
        else if(item['type']=='station_id') OB.Playlist.addeditInsertStationId();
        else if(item['type']=='breakpoint') OB.Playlist.addeditInsertBreakpoint();
        else if(item['type']=='custom')
        {
          var custom_item_name = item['properties']['name'];
          var custom_item_description = null;
          var custom_item_duration = null;
          $.each(OB.Settings.playlist_item_types, function(playlist_item_type_index, playlist_item_type)
          {
            if(playlist_item_type['name']==custom_item_name)
            {
              custom_item_duration = playlist_item_type['duration'];
              custom_item_description = playlist_item_type['description'];
            }
          });
          if(custom_item_duration!==null) OB.Playlist.addeditInsertCustom(custom_item_name,custom_item_description,custom_item_duration);
        }
        else OB.Playlist.addeditInsertItem(item['id'],item['artist']+' - '+item['title'],item['duration'],item['type'],item['properties']);
      }

    });

    if(playlist_data['type']=='advanced') OB.Playlist.advancedItemsDisplay();
    else OB.Playlist.addeditTotalDuration();

    if(playlist_data['type']=='live_assist')
    {
      $.each(playlist_data['liveassist_button_items'], function(index,item) { OB.Playlist.addeditInsertLiveassistItem(item); });
    }

    // advanced permissions values if we have them
    if(playlist_data.permissions_groups) $('#playlist_groups_permissions_input').val(playlist_data.permissions_groups);
    if(playlist_data.permissions_users) $('#playlist_users_permissions_input').val(playlist_data.permissions_users);
  });

}

OB.Playlist.addCustomTypeButtons = function()
{
  $.each(OB.Settings.playlist_item_types, function(index, type)
  {
    $('#playlist_edit_standard_container .playlist_data_save').prepend( $('<button></button>').text(type.description).attr('data-name',type.name).attr('data-duration',type.duration).click(function() { OB.Playlist.addeditInsertCustom(type.name,type.description,type.duration); }) );
  });
}

OB.Playlist.addeditInsertCustom = function(name, description, duration)
{
  OB.Playlist.addedit_item_last_id += 1;

  var $button = $('<div data-type="custom" class="playlist_addedit_item" id="playlist_addedit_item_'+OB.Playlist.addedit_item_last_id+'"></div>');
  $button.attr('data-name',name);
  $button.attr('data-duration',duration);
  $button.append( $('<span class="playlist_addedit_thumbnail"></span>') );
  $button.append( $('<i class="playlist_addedit_description"></i>').text(description) );
  $button.append( $('<span class="playlist_addedit_duration"></span>').text(secsToTime(duration)) );
  // $('#playlist_items').append( $('<div data-type="custom" class="playlist_addedit_item" id="playlist_addedit_item_'+OB.Playlist.addedit_item_last_id+'"><span class="playlist_addedit_duration">*'+secsToTime(duration)+'</span><i></i></div>').text(name) );
  $('#playlist_items').append($button);

  // item select
  $('#playlist_addedit_item_'+OB.Playlist.addedit_item_last_id).click(OB.Playlist.addeditItemSelect);

  // hide our 'drag items here' help.
  $('#playlist_items_drag_help').hide();

  OB.Playlist.addeditTotalDuration();
  $('#playlist_items').sortable({ start: OB.Playlist.addeditSortStart, stop: OB.Playlist.addeditSortStop });
}

// remove all playlist items from all playlist types/containers.
OB.Playlist.addeditRemoveAllFromAll = function()
{
  OB.Playlist.addeditRemoveAll(true);
  OB.Playlist.advancedRemoveAll(true);
}

OB.Playlist.addedit_type = false;

OB.Playlist.addeditTypeChange = function()
{
  var change_to = $('#playlist_type_input').val();
  if(change_to=='live_assist') change_to='standard';

  if(OB.Playlist.addedit_type == 'advanced') var has_items = OB.Playlist.advanced_items.length>0;
  else var has_items = $('.playlist_addedit_item').length>0;

  if(!has_items || OB.Playlist.addeditTypeChangeConfirm())
  {
    OB.Playlist.addedit_type = $('#playlist_type_input').val();

    OB.Playlist.addeditRemoveAllFromAll();
    $('.playlist_edit_container').hide();
    $('#playlist_edit_'+change_to+'_container').show();

    if(change_to=='standard' && $('#playlist_type_input').val()=='live_assist') {
      $('#playlist_insert_breakpoint_button').show();
      $('#playlist_liveassist_buttons').show();
    }
    else if(change_to=='standard') {
      $('#playlist_insert_breakpoint_button').hide();
      $('#playlist_liveassist_buttons').hide();
    }
  }
  else $('#playlist_type_input').val(OB.Playlist.addedit_type);
}

OB.Playlist.addeditTypeChangeConfirm = function()
{
  //T Changing playlist type will clear the existing playlist.  Are you sure you want to do this?
  return confirm( OB.t('Changing playlist type will clear the existing playlist.  Are you sure you want to do this?') );
}


// modal window for item settings (like start/stop time, image duration, dynamic selection name...)
OB.Playlist.addeditItemProperties = function(id,type,required)
{

  OB.UI.openModalWindow('playlist/'+type+'_properties.html');

  // initialize properties window for dynamic item.
  if(type=='dynamic')
  {
    $('#dynamic_name').val($('#playlist_addedit_item_'+id).attr('data-name'));
    $('#dynamic_num_items').val( $('#playlist_addedit_item_'+id).attr('data-num_items') ? $('#playlist_addedit_item_'+id).attr('data-num_items') : 10); // 10 is default.
    $('#dynamic_image_duration').val( $('#playlist_addedit_item_'+id).attr('data-image_duration') ? $('#playlist_addedit_item_'+id).attr('data-image_duration') : 15); // 15 is default.
    $('#dynamic_crossfade').val( $('#playlist_addedit_item_'+id).attr('data-crossfade') ? $('#playlist_addedit_item_'+id).attr('data-crossfade') : 0 );
    $('#dynamic_crossfade_last').val( $('#playlist_addedit_item_'+id).attr('data-crossfade_last') ? $('#playlist_addedit_item_'+id).attr('data-crossfade_last') : 0 );

    $('#dynamic_num_items_all').change(function()
    {
      if($('#dynamic_num_items_all').is(':checked')) $('#dynamic_num_items').hide();
      else $('#dynamic_num_items').show();
    });


    if($('#playlist_addedit_item_'+id).attr('data-num_items_all')=="true") $('#dynamic_num_items_all').attr('checked','checked');
    $('#dynamic_num_items_all').change();

    // determine how this dynamic item works (and provide information)
    var search_query = $.parseJSON($('#playlist_addedit_item_'+id).attr('data-query'));

    if(search_query.mode=='advanced')
    {
      //T Advanced Search Type
      var search_type = 'Advanced Search Type';
      var search_string = '';

      $.each(search_query.filters,function(index,filter)
      {
        search_string += '&bull; '+htmlspecialchars(filter.description)+'<br>';
      });
    }

    else if(search_query.string=='')
    {
      //T All Media Search Type
      var search_type = 'All Media Search Type';
      var search_string = null;
    }

    else
    {
      //T Standard Search Type
      var search_type = 'Standard Search Type';
      var search_string = htmlspecialchars(search_query.string);
    }

    //T Type
    $('#dynamic_item_description').append('<div class="fieldrow"><label data-t>Type</label><span>'+htmlspecialchars(OB.t(search_type))+'</span></div>');
    //T Query
    if(search_string!=null) $('#dynamic_item_description').append('<div class="fieldrow"><label data-t>Query</label><span>'+search_string+'</span></div>');

  }

  // initialize properties window for audio item.
  else if(type=='audio')
  {
    if($('#playlist_type_input').val()=='standard')
    {
      $('#audio_properties_crossfade').val($('#playlist_addedit_item_'+id).attr('data-crossfade'));
    }

    else // advanced
    {
      $('#audio_properties_crossfade').val(OB.Playlist.advanced_items[id].crossfade);
    }
  }

  // initialize properties window for image item.
  else if(type=='image')
  {
    if($('#playlist_type_input').val()=='standard')
    {
      $('#image_properties_duration').val( Math.round($('#playlist_addedit_item_'+id).attr('data-duration')) );
    }

    else // advanced
    {
      $('#image_properties_duration').val(OB.Playlist.advanced_items[id].duration);
    }
  }

  // if our this is required (new dynamic itemof rexample), we remove the item if this is cancelled.
  if(required)
  {
    $('#item_properties_cancel').click(function() {
      OB.UI.closeModalWindow();
      OB.Playlist.addeditRemoveItem(id);
    });
  }

  $('#item_properties_save').click(function() {

    // dynamic used only for standard playlist right now.
    if(type=='dynamic')
    {
      var selection_name = $('#dynamic_name').val();
      var num_items = $('#dynamic_num_items').val();
      var num_items_all = $('#dynamic_num_items_all').is(':checked');
      var image_duration = $('#dynamic_image_duration').val();
      var search_query = $('#playlist_addedit_item_'+id).attr('data-query');
      var crossfade = $('#dynamic_crossfade').val();
      var crossfade_last = $('#dynamic_crossfade_last').val();

      $('#item_properties_message').hide();

      OB.API.post('playlists','validate_dynamic_properties', { 'selection_name': selection_name, 'num_items': num_items, 'num_items_all': num_items_all, 'image_duration': image_duration, 'search_query': $.parseJSON(search_query) }, function(data) {

        if(data.status==false)
        {
          $('#item_properties_message').obWidget('error',data.msg);
        }

        else
        {
          OB.Playlist.addeditSetDynamicItemProperties( id, data.data.duration, selection_name, num_items, num_items_all, image_duration, crossfade, crossfade_last );
          OB.Playlist.addeditTotalDuration();
          OB.UI.closeModalWindow();
        }

      });
    }

    if(type=="audio")
    {
      // okay to save, standard playlist.
      if($('#playlist_type_input').val()=='standard')
      {
        $('#playlist_addedit_item_'+id).attr('data-crossfade', $('#audio_properties_crossfade').val());
        OB.UI.closeModalWindow();
      }

      // okay to save, advanced playlist.
      else
      {
        OB.Playlist.advanced_items[id].crossfade = $('#audio_properties_crossfade').val();
        OB.UI.closeModalWindow();
      }
    }

    // image properties could be for standard or advanced playlist.
    if(type=='image')
    {

      // make sure image properties are valid.
      if(!$('#image_properties_duration').val().match(/^[0-9]+$/) || $('#image_properties_duration')=='0')
      {
        //T A valid image duration is required.
        $('#item_properties_message').obWidget('error', 'A valid image duration is required.');
      }

      // okay to save, standard playlist.
      else if($('#playlist_type_input').val()=='standard')
      {
        $('#playlist_addedit_item_'+id).attr('data-duration', $('#image_properties_duration').val());
        OB.Playlist.addeditImageDurationUpdate(id);
        OB.UI.closeModalWindow();
      }

      // okay to save, advanced playlist.
      else
      {
        OB.Playlist.advanced_items[id].duration = $('#image_properties_duration').val();
        OB.Playlist.advancedItemsDisplay();
        OB.UI.closeModalWindow();
      }

    }

  });

}

OB.Playlist.save = function()
{

  var id = $('#playlist_id').val();
  var playlist_name = $('#playlist_name_input').val();
  var thumbnail = $('#playlist_thumbnail_input').val();
  var description = $('#playlist_description_input').val();
  var status = $('#playlist_status_input').val();
  var type = $('#playlist_type_input').val();

  var permissions_users = null;
  var permissions_groups = null;

  // add permissions if fields visible
  if( $('#playlist_users_permissions_input').is(':visible') )
  {
    permissions_users = $('#playlist_users_permissions_input:visible').val();
  }
  if( $('#playlist_groups_permissions_input').is(':visible') )
  {
    permissions_groups = $('#playlist_groups_permissions_input:visible').val();
  }

  if(type=='advanced') var items = OB.Playlist.advancedGetItems();
  else var items = OB.Playlist.addeditGetItems();

  if(type=='live_assist') var liveassist_button_items = OB.Playlist.liveassistButtonItems();
  else var liveassist_button_items = false;

  $('#playlist_addedit_message').hide();

  OB.API.post('playlists','save', {
    'id': id,
    'name': playlist_name,
    'thumbnail': thumbnail,
    'description': description,
    'status': status,
    'type': type,
    'items': items,
    'liveassist_button_items': liveassist_button_items,
    'permissions_users': permissions_users,
    'permissions_groups' : permissions_groups
  }, function(data) {

    $('#playlist_addedit_message').obWidget(data.status ? 'success' : 'error', data.msg);

    if(data.status == true)
    {
      $('#playlist_id').val(data.data);
      OB.Sidebar.playlistSearch(); // update sidebar search entries.
    }

  });

}
