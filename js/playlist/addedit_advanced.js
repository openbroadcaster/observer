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

OB.Playlist.advancedKeypress = function(e)
{
  if(!$('#playlist_edit_advanced:visible').length) return; // make sure we are actually editing advanced playlist.
  if(!$('#playlist_edit_advanced .playlist_addedit_advanced_item.selected').length) return; // make sure we have something selected.

  var selected_id = $('#playlist_edit_advanced .playlist_addedit_advanced_item.selected').attr('data-id');

  if(e.keyCode == 8 || e.keyCode == 46)
  {
    OB.Playlist.advancedRemoveItem(selected_id);
  }

  else if(e.keyCode == 38) OB.Playlist.advancedItemUp(selected_id);
  else if(e.keyCode == 40) OB.Playlist.advancedItemDown(selected_id);
}

OB.Playlist.advancedInit = function()
{

  $('#playlist_edit_advanced').bind('mousewheel',OB.Playlist.advancedZoomWheel);

  // establish drop target for media (advanced playlist)
  $('#playlist_edit_advanced').droppable({
      drop: function(event, ui) {

        var private_media_alert = false;
        var playlist_owner_id = $('#playlist_owner_id').val();

        if($(ui.draggable).attr('data-mode')=='media')
        {
          $('.sidebar_search_media_selected').each(function(index,element) {

            if($(element).attr('data-visibility')=='private' && ($(element).attr('data-owner_id')!=playlist_owner_id))
            {
              private_media_alert = true;
              return true;
            }

            if($(element).attr('data-type')=='image') var insert_duration = 15;
            else var insert_duration = parseFloat($(element).attr('data-duration'));

            var item = {};
            item.id = $(element).attr('data-id');
            item.artist = $(element).attr('data-artist');
            item.title = $(element).attr('data-title');
            item.duration = insert_duration;
            item.type = $(element).attr('data-type');

            OB.Playlist.advancedAddItem(item);

          });

          //T A media item is marked as private. It can only be used in playlists created by the same owner.
          if(private_media_alert) OB.UI.alert('A media item is marked as private. It can only be used in playlists created by the same owner.');

          // unselect our media from our sidebar
          OB.Sidebar.mediaSelectNone();

        }


        else if($(ui.draggable).attr('data-mode')=='playlist')
        {

          $('.sidebar_search_playlist_selected').each(function(index,element) {

            OB.API.post('playlists','get', { 'id': $(element).attr('data-id') }, function(data) {

              if(data.status==false) return;

              var playlist_data = data.data;
              var not_supported_error = false;

              $.each(playlist_data['items'], function(index, item) {
                if(item.type=='dynamic') { not_supported_error = true; }
                else if(item.type=='station_id') { not_supported_error = true;  }
                else if(item.status=='private' && item.owner_id!=playlist_owner_id)
                {
                  private_media_alert = true;
                  return true;
                }
                else OB.Playlist.advancedAddItem(item);
              });

              //T Dynamic selections and station IDs are not supported by advanced playlists. These items have been ignored.
              //T A media item is marked as private. It can only be used in playlists created by the same owner.
              if(not_supported_error && private_media_alert) OB.UI.alert(OB.t('Dynamic selections and station IDs are not supported by advanced playlists. These items have been ignored.')+"\n\n"+OB.t('A media item is marked as private. It can only be used in playlists created by the same owner.'));

              else
              {
                //T Dynamic selections and station IDs are not supported by advanced playlists. These items have been ignored.
                //T A media item is marked as private. It can only be used in playlists created by the same owner.
                if(not_supported_error) OB.UI.alert('Dynamic selections and station IDs are not supported by advanced playlists. These items have been ignored.');
                if(private_media_alert) OB.UI.alert('A media item is marked as private. It can only be used in playlists created by the same owner.');
              }

            });

          });

          // get the content of this playlist, add it to our playlist we are editing.


          // unselect our playlists from our sidebar
          OB.Sidebar.playlistSelectNone();
        }
      }
  });

  OB.Playlist.advancedItemsDisplay();
}

OB.Playlist.advanced_items = [];

// get items for saving
OB.Playlist.advancedGetItems = function()
{
  var items = [];

  $.each(OB.Playlist.advanced_items, function(index,item)
  {
    var new_item = {};
    new_item.type = 'media';
    new_item.id = item.id;
    new_item.duration = item.duration;
    
    if (item.voicetrack) {
      new_item.voicetrack = item.voicetrack;
    }
    console.log(new_item);

    items.push(new_item);
  });

  return items;
}

// add item. we can skip display refresh if adding a bunch and not wanting to refresh until the end.
OB.Playlist.advancedAddItem = function(item,skip_display)
{
  if (item.properties) {
    item.voicetrack = item.properties.voicetrack;
    item.crossfade = item.properties.crossfade;

    delete item.properties;
  }

  OB.Playlist.advanced_items.push(item);
  if(!skip_display) OB.Playlist.advancedItemsDisplay();
}

OB.Playlist.advancedItemUp = function(index)
{
  index=parseInt(index);
  if(index==0) return;

  var top = $('#playlist_addedit_advanced_item_'+(index)).css('top');

  var item = OB.Playlist.advanced_items.splice(index,1);
  OB.Playlist.advanced_items.splice(index-1,0,item[0]);
  OB.Playlist.advancedItemsDisplay();

  $('#playlist_addedit_advanced_item_'+(index-1)).addClass('selected');

  // if it didn't appear to move, move it up again. (might change position in array but not actually move)
  if( $('#playlist_addedit_advanced_item_'+(index-1)).css('top') == top ) OB.Playlist.advancedItemUp(index-1);
}

OB.Playlist.advancedItemDown = function(index)
{
  index=parseInt(index);
  if(index==(OB.Playlist.advanced_items.length-1)) return;

  var top = $('#playlist_addedit_advanced_item_'+(index)).css('top');

  var item = OB.Playlist.advanced_items.splice(index,1);
  OB.Playlist.advanced_items.splice(index+1,0,item[0]);
  OB.Playlist.advancedItemsDisplay();

  $('#playlist_addedit_advanced_item_'+(index+1)).addClass('selected');

  // if it didn't appear to move, move it down again. (might change position in array but not actually move)
  if( $('#playlist_addedit_advanced_item_'+(index+1)).css('top') == top ) OB.Playlist.advancedItemDown(index+1);
}

OB.Playlist.advancedRemoveItem = function(index)
{
  OB.Playlist.advanced_items.splice(index,1);
  OB.Playlist.advancedItemsDisplay();
}

OB.Playlist.advancedRemoveAll = function(skip_confirm)
{
  //T Clear all items from the playlist?
  if(OB.Playlist.advanced_items.length && (skip_confirm || confirm( OB.t('Clear all items from the playlist?') )))
  {
    OB.Playlist.advanced_items = [];
    OB.Playlist.advancedItemsDisplay();
  }
}

OB.Playlist.advancedItemUnselect = function(e)
{
  if(e && $(e.target).hasClass('playlist_addedit_advanced_item')) return;
  $('#playlist_edit_advanced_items .playlist_addedit_advanced_item').removeClass('selected');
}

OB.Playlist.advancedItemSelect = function()
{
  OB.Playlist.advancedItemUnselect();
  $(this).addClass('selected');
}

OB.Playlist.advanced_last_position = 0;

OB.Playlist.advancedItemsDisplay = function()
{

  if(!OB.Playlist.advanced_items.length) { $('#playlist_edit_advanced_help').show(); $('#playlist_edit_advanced_times').hide(); }
  else { $('#playlist_edit_advanced_help').hide(); $('#playlist_edit_advanced_times').show(); }

  $('#playlist_edit_advanced_items').html('');

  var last_audio_position = 0.0;
  var last_image_position = 0.0;

  var last_audio_px_position = 0;
  var last_image_px_position = 0;

  var total_width = $('#playlist_edit_advanced_items').width();
  var left_width = Math.ceil(total_width/2);
  var right_width = total_width - left_width;

  $.each(OB.Playlist.advanced_items, function(index,item)
  {

    item.duration = parseFloat(item.duration);

    if(item.type=='audio')
    {
      var position = last_audio_position;
      var left = '0';
      var width = left_width-1;

      var start_px_pos = last_audio_px_position + 1;
      var end_px_pos = OB.Playlist.advancedItemOffset(position + item.duration);

      last_audio_position += item.duration;
      last_audio_px_position = end_px_pos;
    }
    else if(item.type=='image')
    {
      var position = last_image_position;
      var left = left_width;
      var width = right_width;

      var start_px_pos = last_image_px_position + 1;
      var end_px_pos = OB.Playlist.advancedItemOffset(position + item.duration);

      last_image_position += item.duration;
      last_image_px_position = end_px_pos;
    }
    else
    {
      var position = Math.max(last_audio_position, last_image_position);
      var left = '0';
      var width = total_width;

      var start_px_pos = Math.max(last_audio_px_position, last_image_px_position) + 1;
      var end_px_pos = OB.Playlist.advancedItemOffset(position + item.duration);

      last_audio_position = position + item.duration;
      last_image_position = position + item.duration;

      last_audio_px_position = end_px_pos;
      last_image_px_position = end_px_pos;
    }

    height = end_px_pos - start_px_pos;

    if(height > 0)
    {
      $('#playlist_edit_advanced_items').append('<div class="playlist_addedit_advanced_item '+item.type+'" data-id="'+index+'" id="playlist_addedit_advanced_item_'+index+'" style="left: '+left+'px; width: '+width+'px; top: '+start_px_pos+'px; height: '+height+'px; line-height: '+height+'px;">'+htmlspecialchars(item.artist+' - '+item.title)+'</div>');
      $('#playlist_addedit_advanced_item_'+index).dblclick(function(e) { OB.Playlist.addeditItemProperties(index,item.type); });
      $('#playlist_addedit_advanced_item_'+index).click(OB.Playlist.advancedItemSelect);
    }

  });

  OB.Playlist.advanced_last_position = Math.max(last_audio_position, last_image_position);
  OB.Playlist.advancedTimes();

}

// see OB.Playlist.advancedTimeIncrement();
OB.Playlist.advanced_zoom = 3;

OB.Playlist.advancedZoomOut = function()
{
  if(OB.Playlist.advanced_zoom==5) return;

  var selected_id = $('#playlist_edit_advanced .playlist_addedit_advanced_item.selected').attr('data-id');

  OB.Playlist.advanced_zoom++;
  OB.Playlist.advancedItemsDisplay();

  if(selected_id) $('#playlist_edit_advanced .playlist_addedit_advanced_item[data-id='+selected_id+']').addClass('selected');
}

OB.Playlist.advancedZoomIn = function()
{
  if(OB.Playlist.advanced_zoom==1) return; // not using the 0.1 zoom at this point.

  var selected_id = $('#playlist_edit_advanced .playlist_addedit_advanced_item.selected').attr('data-id');

  OB.Playlist.advanced_zoom--;
  OB.Playlist.advancedItemsDisplay();

  if(selected_id) $('#playlist_edit_advanced .playlist_addedit_advanced_item[data-id='+selected_id+']').addClass('selected');
}

OB.Playlist.advancedZoomWheel = function(event,delta)
{
  if(delta>0) { OB.Playlist.advancedZoomIn(); event.preventDefault(); return false; }
  else if(delta<0) { OB.Playlist.advancedZoomOut(); event.preventDefault(); return false; }
}

// calculate pixel offset per second. uses 25 pixels per increment value.
OB.Playlist.advancedItemOffset = function(seconds)
{
  return Math.floor(seconds / OB.Playlist.advancedTimeIncrement() * 25);
}

OB.Playlist.advancedTimeIncrement = function()
{
  switch(OB.Playlist.advanced_zoom)
  {
    case 0:
      var time_increment = 0.1;
      break;

    case 1:
      var time_increment = 1.0;
      break;

    case 2:
      var time_increment = 5.0;
      break;

    case 3:
      var time_increment = 10.0;
      break;

    case 4:
      var time_increment = 30.0;
      break;

    case 5:
      var time_increment = 60.0;
      break;

    case 6:
      var time_increment = 300.0;
      break;

    case 7:
      var time_increment = 600.0;
      break;

    case 8:
      var time_increment = 1800.0;
      break;

    default:
      var time_increment = 3600.0;
      break;
  }

  return time_increment;
}

OB.Playlist.advancedTimes = function()
{
  var present_time = 0.00;

  // figure out time increment based on zoom setting.
  time_increment = OB.Playlist.advancedTimeIncrement();

  // clear out times
  $('#playlist_edit_advanced_times').html('');

  // loop until we reach our max time (or over)
  while(present_time<=OB.Playlist.advanced_last_position)
  {

    $('#playlist_edit_advanced_times').append('<div class="playlist_edit_advanced_time" style="top: '+OB.Playlist.advancedItemOffset(present_time)+'px">'+secsToTime(present_time)+'</div>');
    present_time+=time_increment;

  }

  var required_cell_height = $('#playlist_edit_advanced_times div').last().position().top + $('#playlist_edit_advanced_times div').last().height();
  var required_cell_width = $('#playlist_edit_advanced_times div').last().width();

  $('#playlist_edit_advanced_times').css('height',required_cell_height+'px');
  $('#playlist_edit_advanced_times').css('width',required_cell_width+'px');
}
