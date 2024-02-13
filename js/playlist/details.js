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

// playlist details page
OB.Playlist.detailsPage = function(id)
{
  var post = [];
  post.push(['players','station_id_avg_duration', {}]);
  post.push(['playlists', 'get', {'id': id, 'where_used': true}]);

  OB.API.multiPost(post, function(response)
  {
    OB.UI.replaceMain('playlist/details.html');

    OB.Playlist.station_id_avg_duration=response[0].data;

    if(response[1].status==false) return;
    var pldata = response[1].data;
    var used = response[1].data.where_used.used;

    // if we have permission, show edit/delete buttons.
    if(pldata.can_edit)
    {
      $('#playlist_details_edit').show().click(function() { OB.Playlist.editPage(pldata.id); });
      $('#playlist_details_delete').show().click(function() { OB.Playlist.deletePage(pldata.id); });
    }

    $('#playlist_details_id').text(id);

    $('#playlist_details_name').text(pldata.name);
    $('#playlist_details_thumbnail').val(pldata.thumbnail);
    $('#playlist_details_description').text(pldata.description);

    //T Private
    if(pldata.status=='private') $('#playlist_details_visibility').text(OB.t('Private'));
    //T Visible
    else if(pldata.status=='visible') $('#playlist_details_visibility').text(OB.t('Visible'));
    //T Public
    else $('#playlist_details_visibility').text(OB.t('Public'));

    $('#playlist_details_created').text(format_timestamp(pldata.created));
    $('#playlist_details_updated').text(format_timestamp(pldata.updated));

    $('#playlist_details_owner').text(pldata.owner_name);

    // handle playlist items
    //T No playlist items found
    if(typeof(pldata.items)=='undefined' || pldata.items.length==0) $('#playlist_details_items_table').replaceWith(htmlspecialchars(OB.t('No playlist items found')));

    else {

      var pl_item_time_estimated = false;
      var pl_item_time_total = 0;

      $.each(pldata.items, function(index,item) {

        if(item.type=='station_id')
        {
          //T Station ID
          //T estimated
          $('#playlist_details_items_table').append('<tr><td>'+htmlspecialchars(OB.t('Station ID'))+'</td><td>'+secsToTime(OB.Playlist.station_id_avg_duration)+' ('+htmlspecialchars(OB.t('estimated'))+')</td></tr>');
          pl_item_time_estimated = true;
          pl_item_time_total += parseFloat(OB.Playlist.station_id_avg_duration);
        }

        else if(item.type=='breakpoint')
        {
          //T Breakpoint
          $('#playlist_details_items_table').append('<tr><td>'+htmlspecialchars(OB.t('Breakpoint'))+'</td><td>00:00</td></tr>');
        }

        else if(item.type=='dynamic')
        {
          //T Dynamic Selection
          //T estimated
          $('#playlist_details_items_table').append('<tr><td>'+htmlspecialchars(OB.t('Dynamic Selection'))+': '+htmlspecialchars(item.properties.name)+'</td><td>'+secsToTime(item.duration)+' ('+htmlspecialchars(OB.t('estimated'))+')</td></tr>');
          pl_item_time_estimated = true;
          pl_item_time_total += parseFloat(item.duration);
        }

        else
        {
          $('#playlist_details_items_table').append('<tr><td>'+htmlspecialchars(item.artist+' - '+item.title)+'</td><td>'+secsToTime(item.duration)+'</td></tr>');
          pl_item_time_total += parseFloat(item.duration);
        }

      });

      //T Total Duration
      //T estimated
      $('#playlist_details_items_table').append('<tr><td colspan="2" ><span>'+htmlspecialchars(OB.t('Total Duration'))+':</span> '+secsToTime(pl_item_time_total)+(pl_item_time_estimated ? ' ('+htmlspecialchars(OB.t('estimated'))+')' : '')+'</td></tr>');

    }



    // handle 'where used';
    //T Playlist is not in use.
    if(used.length==0) $('#playlist_details_used').append(OB.t('Playlist is not in use.'));

    else
    {
      $.each(used,function(index,used_detail) {
        $('#playlist_details_used ul').append('<li>'+htmlspecialchars(used_detail.where)+': '+htmlspecialchars(used_detail.name)+'</li>');
      });
    }

    if (pldata['properties'] && pldata['properties']['last_track_fadeout']) {
      $('#playlist_details_last_fadeout').text(pldata['properties']['last_track_fadeout'] + 's');
    }

    $('#playlist_details').show();

  });
}
