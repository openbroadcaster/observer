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

OB.Emergency = new Object();

OB.Emergency.init = function()
{
  OB.Callbacks.add('ready',-4,OB.Emergency.initMenu);
}

OB.Emergency.initMenu = function()
{
  //T Priority Broadcasts
  OB.UI.addSubMenuItem('schedules', 'Priority Broadcasts', 'emergency', OB.Emergency.emergency, 30, 'manage_emergency_broadcasts');
}

OB.Emergency.player_id = null;

OB.Emergency.emergency = function()
{

  OB.UI.replaceMain('emergency/emergency.html');

  OB.Emergency.player_id = null;

  OB.Emergency.emergencyInit();

  $('#emergency_list_container').droppable({
      drop: function(event, ui) {

        if($(ui.draggable).attr('data-mode')=='media')
        {
          //T You can schedule only one item at a time.
          if($('.sidebar_search_media_selected').length!=1) { OB.UI.alert('You can schedule only one item at a time.'); return; }

          var item_id = $('.sidebar_search_media_selected').first().attr('data-id');
          var item_name = $('.sidebar_search_media_selected').first().attr('data-artist')+' - '+$('.sidebar_search_media_selected').first().attr('data-title');
          var item_type = $('.sidebar_search_media_selected').first().attr('data-type');

          var item_duration = $('.sidebar_search_media_selected').first().attr('data-duration');

          OB.Emergency.addEmergency(item_id,item_name,item_type);

        }

        else if($(ui.draggable).attr('data-mode')=='playlist')
        {

          //T Priority broadcast playlists are not supported at this time.
          OB.UI.alert('Priority broadcast playlists are not supported at this time.');

        }



      }

  });

}

OB.Emergency.emergencyInit = function()
{

  var post = [];
  post.push(['players','search', {}]);
  post.push(['alerts','get_last_player', {}]);

  OB.API.multiPost(post, function(responses)
  {

    var players = responses[0].data;
    var last_player = responses[1];

    $.each(players,function(index,item) {

      if(item.use_parent_emergency=='1') return; // player uses parent emergency broadcasts, setting them here would not do anything.

      // make sure we have permission for this
      if(OB.Settings.permissions.indexOf('manage_emergency_broadcasts')==-1 && OB.Settings.permissions.indexOf('manage_emergency_broadcasts:'+item.id)==-1) return;


      if(OB.Emergency.player_id==null) OB.Emergency.player_id = item.id; // default to first player.
      $('#emergency_player_select').append('<option value="'+item.id+'">'+htmlspecialchars(item.name)+'</option>');

    });

    if(last_player.status && $('#emergency_player_select option[value='+last_player.data+']').length)
    {
      $('#emergency_player_select').val(last_player.data);
      OB.Emergency.player_id = last_player.data;
    }

    OB.Emergency.loadEmergencies();

  });

}

OB.Emergency.playerChange = function()
{

  OB.Emergency.player_id = $('#emergency_player_select').val();
  OB.Emergency.loadEmergencies();

}

OB.Emergency.loadEmergencies = function()
{

  var post = [];
  post.push(['alerts','search',{ 'player_id': OB.Emergency.player_id }]);
  post.push(['alerts','set_last_player', { 'player': OB.Emergency.player_id}]);

  OB.API.multiPost(post, function(responses)
  {

    if(responses[0].status==true)
    {

      var emergencies = responses[0].data;

      $('#emergency_list tbody').children().not('#emergency_table_empty').remove();

      if($(emergencies).length>0)
      {

        $('#emergency_table_empty').hide();

        $.each(emergencies,function(index,data)
        {

          if(data.duration) var duration = Math.round(data.duration)+' seconds';
          else var duration = '';

          $('#emergency_list tbody').append('<tr id="emergency_'+data.id+'"><td>'+htmlspecialchars(data.name)+'</td></td><td>'+format_timestamp(data.start)+'</td><td>'+format_timestamp(data.stop)+'</td><td>'+data.frequency+' '+OB.t("seconds")+'</td><td>'+secsToTime(data.duration)+'</td><td>'+htmlspecialchars(data.item_name)+'</td>');

          $('#emergency_'+data.id).dblclick(function(eventObj)
          {
            OB.Emergency.editEmergency(data.id);
          });

        });

      }

      else $('#emergency_table_empty').show();

    }

  });

}

OB.Emergency.saveEmergency = function()
{

  fields = new Object();

  fields.name = $('#emergency_name').val();

  fields.player_id = OB.Emergency.player_id;

  fields.frequency = $('#emergency_frequency').val();
  fields.duration = parseInt($('#emergency_duration_minutes').val()*60) + parseInt($('#emergency_duration_seconds').val());

  var start_date = new Date($('#emergency_start_datetime').val());
  if(!start_date) fields.start = '';
  else fields.start = Math.round(start_date.getTime()/1000)+'';

  var stop_date = new Date($('#emergency_stop_datetime').val());
  if(!stop_date) fields.stop = '';
  else fields.stop = Math.round(stop_date.getTime()/1000)+'';

  fields.id = $('#emergency_id').val();
  fields.item_id = $('#emergency_item_id').val();

  OB.API.post('alerts','save',fields,function(data)
  {

    if (data.status == true)
    {
      OB.UI.closeModalWindow();
      OB.Emergency.loadEmergencies();

    } else
    {
      $('#emergency_addedit_message').obWidget('error', data.msg);
    }

  });

}

OB.Emergency.addeditEmergencyWindow = function()
{
  OB.UI.openModalWindow('emergency/addedit.html');
}

OB.Emergency.editEmergency = function(id)
{

  OB.API.post('alerts','get',{ 'id': id }, function(data)
  {

    if(data.status==true)
    {

      emerg = data.data;

      OB.Emergency.addeditEmergencyWindow();
      $('.edit_only').show();

      if(emerg.item_type=='image')
      {
        $('#emergency_duration').show();

        var duration = Math.round(emerg.duration);
        var duration_seconds = duration%60;
        var duration_minutes = (duration - duration_seconds)/60;

        $('#emergency_duration_minutes').val(duration_minutes);
        $('#emergency_duration_seconds').val(duration_seconds);
      }
      else $('#emergency_duration').hide();

      $('#emergency_item_info').text(emerg.item_name);
      $('#emergency_name').val(emerg.name);
      $('#emergency_frequency').val(emerg.frequency);
      $('#emergency_item_id').val(emerg.item_id);
      $('#emergency_id').val(emerg.id);

      $('#emergency_start_datetime').val(new Date(parseInt(emerg.start)*1000));
      $('#emergency_stop_datetime').val(new Date(parseInt(emerg.stop)*1000));
    }

    else OB.UI.alert(data.msg);

  });

}

OB.Emergency.addEmergency = function(item_id,item_name,item_type)
{

  OB.Emergency.addeditEmergencyWindow();
  $('.edit_only').hide();

  $('#emergency_item_id').val(item_id);
  $('#emergency_item_info').text(item_name);

  if(item_type=='image') $('#emergency_duration').show();
  else $('#emergency_duration').hide();

}

OB.Emergency.deleteEmergency = function(confirm)
{

  if(confirm)
  {

    OB.API.post('alerts','delete',{ 'id': $('#emergency_id').val() }, function(data)
    {

      if(data.status==true)
      {
        OB.UI.closeModalWindow();
        OB.Emergency.loadEmergencies();
      }

      else
      {
        $('#emergency_addedit_message').obWidget('error',data.msg);
      }

    });

  }

  else
  {
    //T Are you sure you want to delete this priority broadcast?
    //T Yes, Delete
    //T No, Cancel
    OB.UI.confirm(
      'Are you sure you want to delete this priority broadcast?',
      function() { OB.Emergency.deleteEmergency(true); },
      'Yes, Delete',
      'No, Cancel',
      'delete'
    );
  }

}
