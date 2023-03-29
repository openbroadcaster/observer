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

OB.Player = new Object();

OB.Player.init = function()
{
  OB.Callbacks.add('ready',-4,OB.Player.initMenu);
}

OB.Player.initMenu = function()
{
  //T Player Manager
  OB.UI.addSubMenuItem('admin', 'Player Manager', 'player_settings', OB.Player.settings, 20, 'manage_players');
  //T Player Monitoring
  OB.UI.addSubMenuItem('admin', 'Player Monitoring', 'player_monitoring', OB.Player.monitor, 30, 'view_player_monitor');
}

/* ======================
   PLAYER MANAGER SECTION
====================== */

OB.Player.settings = function () {
  OB.UI.replaceMain('player/settings.html');
  OB.Player.playerOverview();
}

OB.Player.playerOverview = function (orderby = 'name', orderdesc = null) {
  var post = {};
  post.orderby = orderby;
  if (orderdesc != null) post.orderdesc = 'desc';

  OB.API.post('players', 'search', post, function (response) {
    if (!response.status) {
      $('#player_main_message').obWidget('error', response.msg);
      return false;
    }

    $.each(response.data, function (i, player) {
      var dev_version       = (player.version == '') ? '<em>N/A</em>': player.version;
      var dev_lastip        = player.last_ip_address;
      var dev_conn_all      = player.last_connect;
      var dev_conn_schedule = player.last_connect_schedule;
      var dev_conn_priority = player.last_connect_emergency;
      var dev_conn_media    = player.last_connect_media;
      var dev_conn_playlog  = player.last_connect_playlog;

      $html = $('<tr/>');
      $html.append($('<td/>').text(player.id));
      $html.append($('<td/>').text(player.name));
      $html.append($('<td/>').text(player.description));

      $error = $('<div/>');
      $html.append($('<td/>').append($error));

      $status = $('<div/>');

      //T Version
      $status.append($('<div/>').html('<span>' + OB.t('Version') + ': </span>' + dev_version));
      //T Last IP
      if (dev_lastip) $status.append($('<div/>').html('<span>' + OB.t('Last IP') + ': </span>' + dev_lastip));

      //T All
      if (dev_conn_all) $status.append(OB.Player.generateConnectionHTML('All', dev_conn_all));
      //T Schedule
      if (dev_conn_schedule) $status.append(OB.Player.generateConnectionHTML('Schedule', dev_conn_schedule));
      //T Priority
      if (dev_conn_priority) $status.append(OB.Player.generateConnectionHTML('Priority', dev_conn_priority));
      //T Media
      if (dev_conn_media) $status.append(OB.Player.generateConnectionHTML('Media', dev_conn_media));
      //T Playlog
      if (dev_conn_playlog) $status.append(OB.Player.generateConnectionHTML('Playlog', dev_conn_playlog));
      $html.append($('<td/>').append($status));

      //T Edit
      $html.append($('<td/>').html('<button class="edit" onclick="OB.Player.editPlayer(' + player.id + ');">Edit</button>'));

      $('#player_list tbody').append($html);
    });
  });
}

OB.Player.sortOverview = function (elem, field) {
    $('#player_list tbody tr:nth-child(n+2)').empty();

    if ($(elem).hasClass('player_order_asc')) {
      $(elem).removeClass('player_order_asc').addClass('player_order_desc');
      $(elem).find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
      OB.Player.playerOverview(field);
    } else {
      $(elem).removeClass('player_order_desc').addClass('player_order_asc');
      $(elem).find('i').removeClass('fa-angle-up').addClass('fa-angle-down');
      OB.Player.playerOverview(field, 'desc');
    }
    return false;
}

OB.Player.generateConnectionHTML = function (label, last_conn) {
  var now = new Date().getTime() / 1000;
  var alert_icon = ((now - last_conn) > 3600) ? '<i class="player_list_alert fas fa-exclamation-triangle"></i>' : '';

  return $('<div/>').html(alert_icon + '<span>' + label + ': </span>' + format_timestamp(last_conn));
}

OB.Player.newPlayer = function () {
  OB.UI.openModalWindow('player/settings_form.html');

  $('#player_settings_id').val('new');
  $('.player_existing').hide();

  $('#player_settings_timezone').html(OB.UI.getHTML('player/tzoptions.html'));
  OB.Player.loadParentPlayers('new');

  OB.Player.dynamicFormOptions();
}

OB.Player.editPlayer = function (player_id) {
  OB.UI.openModalWindow('player/settings_form.html');

  $('#player_settings_id').val(player_id);
  $('.player_existing').show();

  $('#player_settings_timezone').html(OB.UI.getHTML('player/tzoptions.html'));
  OB.Player.loadParentPlayers(player_id);

  var post = {};
  post.id = player_id;
  OB.API.post('players', 'get', post, function (response) {
    if (!response.status) {
      OB.UI.closeModalWindow();
      $('#player_main_message').obWidget('error', response.msg);
      return false;
    }

    $('#player_settings_name').val(response.data.name);
    $('#player_settings_description').val(response.data.description);

    $('#player_settings_parent_id').val(response.data.parent_player_id);
    if (response.data.use_parent_schedule == "1")  $('#player_settings_parent_schedule').prop('checked', true);
    if (response.data.use_parent_dynamic == "1")   $('#player_settings_parent_dynamic').prop('checked', true);
    if (response.data.use_parent_ids == "1")       $('#player_settings_parent_stations').prop('checked', true);
    if (response.data.use_parent_playlist == "1")  $('#player_settings_parent_playlist').prop('checked', true);
    if (response.data.use_parent_alert == "1") $('#player_settings_parent_priority').prop('checked', true);

    $('#player_settings_ip').val(response.data.ip_address);

    if (response.data.support_audio == "1")  $('#player_settings_support_audio').prop('checked', true);
    if (response.data.support_images == "1") $('#player_settings_support_image').prop('checked', true);
    if (response.data.support_video == "1")  $('#player_settings_support_video').prop('checked', true);
    if (response.data.support_linein == "1") $('#player_settings_support_linein').prop('checked', true);

    $('#player_settings_timezone').val(response.data.timezone);

    $('#player_settings_playlist').val([response.data.default_playlist_id]);
    $('#player_settings_station_ids').val(response.data.station_ids)
    $('#player_settings_image_duration').val(response.data.station_id_image_duration);

    OB.Player.dynamicFormOptions();
  });
}

OB.Player.loadParentPlayers = function (player_id) {
  $('#player_list tbody tr:gt(0)').each(function (i, e) {
    var $option = $('<option/>');
    var id      = $(e).find('td:nth-child(1)').text();
    var name    = $(e).find('td:nth-child(2)').text();

    if (id == player_id) return;

    $option.val(id);
    $option.text(name);
    $('#player_settings_parent_id').append($option);
  });
}

OB.Player.dynamicFormOptions = function () {
  if ($('#player_settings_parent_id').val() != "") {
    $('#player_settings_parent_options').show();
  } else {
    $('#player_settings_parent_options').hide();
    $('#player_settings_parent_schedule').prop('checked', false);
    $('#player_settings_parent_stations').prop('checked', false);
    $('#player_settings_parent_playlist').prop('checked', false);
    $('#player_settings_parent_priority').prop('checked', false);
  }

  if ($('#player_settings_parent_schedule').is(':checked')) {
    $('#player_settings_parent_dynamic').parent().show();
  } else {
    $('#player_settings_parent_dynamic').parent().hide();
    $('#player_settings_parent_dynamic').prop('checked', false);
  }

  if ($('#player_settings_parent_dynamic').is(':checked')) {
    $('.no_parent_dynamic').hide();
    $('#player_settings_support_audio').prop('checked', false);
    $('#player_settings_support_image').prop('checked', false);
    $('#player_settings_support_video').prop('checked', false);
  } else {
    $('.no_parent_dynamic').show();
  }

  if ($('#player_settings_parent_stations').is(':checked')) {
    $('.no_parent_stations').hide();
    $('#player_settings_station_ids').empty();
    $('#player_settings_image_duration').val(15);
  } else {
    $('.no_parent_stations').show();
  }

  if ($('#player_settings_parent_playlist').is(':checked')) {
    $('.no_parent_playlist').hide();
    $('#player_settings_playlist').empty();
  } else {
    $('.no_parent_playlist').show();
  }
}

OB.Player.savePlayer = function () {
  var post = {};
  if ($('#player_settings_id').val() != 'new') post.id = $('#player_settings_id').val();

  post.name = $('#player_settings_name').val();
  post.description = $('#player_settings_description').val();
  post.password = $('#player_settings_password').val();
  post.ip_address = $('#player_settings_ip').val();

  post.support_audio = $('#player_settings_support_audio').is(':checked');
  post.support_video = $('#player_settings_support_video').is(':checked');
  post.support_images = $('#player_settings_support_image').is(':checked');
  post.support_linein = $('#player_settings_support_linein').is(':checked');

  post.timezone = $('#player_settings_timezone').val();
  post.station_ids = $('#player_settings_station_ids').val();
  post.station_id_image_duration = $('#player_settings_image_duration').val();
  if ($('#player_settings_playlist').val().length != 0) post.default_playlist = $('#player_settings_playlist').val()[0];

  if ($('#player_settings_parent_id').val() != "") post.parent_player_id = $('#player_settings_parent_id').val();
  post.use_parent_dynamic = $('#player_settings_parent_dynamic').is(':checked');
  post.use_parent_schedule = $('#player_settings_parent_schedule').is(':checked');
  post.use_parent_ids = $('#player_settings_parent_stations').is(':checked');
  post.use_parent_playlist = $('#player_settings_parent_playlist').is(':checked');
  post.use_parent_alert = $('#player_settings_parent_priority').is(':checked');

  OB.API.post('players', 'save', post, function (response) {
    if (!response.status) {
      $('#player_edit_message').obWidget('error', response.msg);
    } else {
      OB.UI.closeModalWindow();
      OB.Player.settings();

      $('#player_main_message').obWidget('success', response.msg);
    }
  })
}

OB.Player.deletePlayer = function () {
  var player_id = $('#player_settings_id').val();

  //T Are you sure you want to remove this player?
  OB.UI.confirm({
    text: "Are you sure you want to remove this player?",
    okay_class: "delete",
    callback: function () {
      OB.Player.deletePlayerConfirm(player_id);
    }
  })
}

OB.Player.deletePlayerConfirm = function (player_id) {
  var post = {};
  post.id = player_id;

  OB.API.post('players', 'delete', post, function (response) {
    OB.UI.closeModalWindow();
    OB.Player.settings();

    if (!response.status) {
      $('#player_main_message').obWidget('error', response.msg);
    }
  });
}

/* =========================
   PLAYER MONITORING SECTION
========================= */

OB.Player.monitor = function()
{
  OB.UI.replaceMain('player/monitor.html');

  $('#monitor_date_start').attr('data-value',moment().subtract(1, 'days'));
  $('#monitor_date_end').attr('data-value',moment());

  OB.API.post('players','search', {}, function(data)
  {
    var players = data.data;
    $.each(players,function(index,item) {

      // make sure we have permission for this
      if(OB.Settings.permissions.indexOf('view_player_monitor')==-1 && OB.Settings.permissions.indexOf('view_player_monitor:'+item.id)==-1) return;
      $('#monitor_player_select').append('<option value="'+item.id+'">'+htmlspecialchars(item.name)+'</option>');
    });
  });

}

OB.Player.monitorFilterFieldChange = function()
{

  var val = $('#monitor_filter_field').val();

  if(val=='media_id')
  {
    $('#monitor_filter_operator option[value="like"]').hide();
    $('#monitor_filter_operator option[value="not_like"]').hide();
  }

  else
  {
    $('#monitor_filter_operator option[value="like"]').show();
    $('#monitor_filter_operator option[value="not_like"]').show();
  }

}

OB.Player.monitor_filter_id = 0;

OB.Player.monitorFilterAdd = function()
{

  OB.Player.monitor_filter_id++;

  var filter_column = $('#monitor_filter_field').val();
  var filter_operator = $('#monitor_filter_operator').val();
  var filter_value = $.trim($('#monitor_filter_value').val());

  if(filter_value=='') return;

  var filter_friendly_string = $('#monitor_filter_field option:selected').text()+' '+$('#monitor_filter_operator option:selected').text()+' '+$('#monitor_filter_value').val();

  var $html = $('<div><a href="javascript: OB.Player.monitorFilterDel('+OB.Player.monitor_filter_id+');">[x]</a> '+htmlspecialchars(filter_friendly_string)+'</div>');
  $html.attr('id','monitor_filter_'+OB.Player.monitor_filter_id);
  $html.attr('data-column',filter_column);
  $html.attr('data-operator',filter_operator);
  $html.attr('data-value',filter_value);

  $('#monitor_filter_list').append($html.outerHTML());

  // redo search if we have a visible container (i.e. if we're searched...)
  if($('#monitor_results_container').is(':visible')) OB.Player.monitorSearch();

}

OB.Player.monitorFilterDel = function(id)
{
  $('#monitor_filter_'+id).remove();
  if($('#monitor_results_container').is(':visible')) OB.Player.monitorSearch();
}

OB.Player.monitorSearch = function(result_type = "json")
{

  $('#monitor_results_container').hide();

  var date_start = $('#monitor_date_start').val();
  var date_end = $('#monitor_date_end').val();

  var date_start_array = date_start.split('-');
  var date_end_array = date_end.split('-');

  var date_start_object = new Date(date_start_array[0],(date_start_array[1]-1),date_start_array[2],0,0,0);
  var date_end_object = new Date(date_end_array[0],(date_end_array[1]-1),date_end_array[2],23,59,59);

  if(date_start_object > date_end_object)
  {
    //T The start date must be before the end date.
    $('#monitor_message').obWidget('error','The start date must be before the end date.');
    return;
  }

  var fields = new Object();
  fields.type = result_type;
  if (result_type === "json") fields.limit = 2500;
  fields.offset = 0;
  fields.orderby = $('#monitor_orderby').val();
  fields.orderdesc = $('#monitor_orderdesc').val();

  fields.player_id = $('#monitor_player_select').val();

  fields.date_start = date_start;
  fields.date_end = date_end;

  // get our filters...
  filters = new Array();
  $('#monitor_filter_list div').each(function(index,element)
  {

    var filter = new Object();
    filter.column = $(element).attr('data-column');
    filter.operator = $(element).attr('data-operator');
    filter.value = $(element).attr('data-value');

    filters.push(filter);

  });

  fields.filters = filters;

  $('#monitor_message').hide();

  OB.API.post('players','monitor_search',fields,function(data) {

    if(data.status==false)
    {

      $('#monitor_message').obWidget('error',data.msg);
      return;

    }

    var results = data.data.results;
    var total_rows = data.data.total_rows;

    if(total_rows==0)
    {
      $('#monitor_no_results').show();
      $('#monitor_results_table').hide();
    }

    else
    {
      if (result_type === "json") {
        $('#monitor_no_results').hide();
        $('#monitor_results_table tbody').html('');
        $('#monitor_results_table').show();

        // add results to table
        $.each(results,function(index,row) {

          var $tr = $('<tr/>');
          $tr.append( $('<td/>').text(row.media_id) );
          $tr.append( $('<td/>').text(row.artist) );
          $tr.append( $('<td/>').text(row.title) );
          $tr.append( $('<td/>').text(row.datetime) );
          $tr.append( $('<td/>').text(row.context) );
          $tr.append( $('<td/>').text(row.notes) );

          $('#monitor_results_table tbody').append($tr);

        });
      }

    }

    if (result_type === "json" || total_rows == 0) {
      $('#monitor_results_container').show();
    } else {
      $('#monitor_results_container').hide();
    }

    if(result_type === "csv" && total_rows > 0)
    {
      var data = new Blob([data.data.results], { type: 'application/octect-stream' });
      const url = URL.createObjectURL(data);
      const filename = 'player_monitor.csv';

      const linkElement = document.getElementById('csv_result_link');
      if (linkElement) {
        linkElement.setAttribute('href', url);
        linkElement.setAttribute('download', filename);
        linkElement.click();
      }

    }

  });

}
