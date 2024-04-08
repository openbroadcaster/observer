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

OB.Playlist.deletePage = function(ids)
{

  // no playlist IDs specified, get IDs from sidebar selection
  if(typeof(ids)=='undefined')
  {
    ids = [];
    $('.sidebar_search_playlist_selected').each(function(index,element) { ids.push($(element).attr('data-id')); });
  }

  // ids is a single number, make array for consistency
  else if(typeof(ids)=='number' || typeof(ids)=='string')
  {
    ids = [parseInt(ids)];
  }

  // if we get this far, we require ids to be an object/array
  else if(typeof(ids)!='object')
  {
    return;
  }

  if(ids.length < 1) { return; }

  var post = [];
  ids.forEach(function(id) { post.push(['playlists','get',{'id':id,'where_used':true}]); });

  OB.API.multiPost(post, function(response) {

    OB.UI.replaceMain('playlist/delete.html');

    var append_html = '';

    var playlists = {};
    response.forEach(function(item) { if(!item.data.name) return true; playlists[item.data.id] = item.data; });

    $.each(playlists, function (index, playlist) {

      var used = playlist.where_used;

      if(used.can_delete)
      {
        $('#playlist_delete_list').append('<li data-id="'+playlist.id+'">'+htmlspecialchars(playlist.name)+'</li>');
      }
      else
      {
        $('#playlist_cannot_delete > ul').append('<li data-id="'+playlist.id+'">'+htmlspecialchars(playlist.name)+'</li>');
        $('#playlist_cannot_delete').show();
      }

      if(used.can_delete && used.used.length>0)
      {

        append_html = '<ul>';

        $.each(used.used,function(where_used_index,where_used) {

          //T Item will be removed from
          append_html += '<li>'+htmlspecialchars(OB.t('Item will be removed from'))+' '+htmlspecialchars(where_used.where)+' <i>'+htmlspecialchars(where_used.name)+'</i></li>';

        });

        append_html += '</ul>';

        $('#playlist_delete_list > li[data-id='+used.id+']').append(append_html);

        // if(used.can_delete) $('#playlist_delete_list > li[data-id='+used.id+']').append(append_html);
        // else $('#playlist_cannot_delete > ul > li[data-id='+used.id+']').append(append_html);

      }

    });

  });


}

OB.Playlist.delete = function()
{

  var delete_ids = new Array();

  $('#playlist_delete_list > li').each(function(index,element) {
    delete_ids.push($(element).attr('data-id'));
  });

  OB.API.post('playlists','delete',{ 'id': delete_ids },function(data) {

    if(data.status==true)
    {
      OB.Sidebar.playlistSearch();

      $('.playlist_delete_button').remove();
      $('#playlist_delete_list').remove();

      //T Playlists have been deleted.
      $('#playlist_top_message').text(OB.t('Playlists have been deleted.'));
    }

    else OB.UI.alert(data.msg);

  });

}
