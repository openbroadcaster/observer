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

OB.Media.versionPage = function(id, title)
{
  OB.UI.replaceMain('media/versions.html');
  $('#media_versions').attr('data-media_id',id);
  $('#media_versions_title').text(title);
  OB.Media.versionUploader();
  OB.Media.versionsGet();
}

OB.Media.versionUploader = function()
{

  $('#media_upload_file_field').change(function () {
    $.each($(this)[0].files,function(index,file)
    {
      OB.Media.versionUploaderUpload(file);
      return false; // just one file
    });
  });

  $.event.props.push('dataTransfer');

  $('#media_upload_form').bind('dragenter', OB.Media.versionUploaderNoop);
  $('#media_upload_form').bind('dragexit', OB.Media.versionUploaderNoop);
  $('#media_upload_form').bind('dragover', OB.Media.versionUploaderNoop);

  $('#media_upload_form').bind('drop',function(event)
  {
    event.stopPropagation();
    event.preventDefault();

    // not sure why 'files' is empty in firefox?
    var files = event.dataTransfer.files;

    //T Could not get file information. Try clicking to select files instead.
    if(files.length==0) OB.UI.alert('Could not get file information. Try clicking to select files instead.');

    $.each(files,function(index,file)
    {
      OB.Media.versionUploaderUpload(file);
      return false; // just one file
    });
  });

}

OB.Media.versionUploaderNoop = function(event)
{
  event.stopPropagation();
  event.preventDefault();
}

OB.Media.versionUploaderUpload = function(file)
{

  $('#media_upload_form').hide();
  $('#media_upload_progress').show();

  $.ajax({
      url: '/upload.php',
      type: 'POST',
      xhr: function() {
          myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload)
          {
              myXhr.upload.addEventListener('progress',OB.Media.versionUploaderProgress, false);
          }
          return myXhr;
      },
      data: file,
      cache: false,
      contentType: false,
      processData: false,
      complete : OB.Media.versionUploaderComplete
  });
}

OB.Media.versionUploaderProgress = function(progress)
{
  var percent = Math.floor((progress.loaded/progress.total)*100);
  //T Uploading: %1%
  $('#media_upload_progress').text(OB.t('Uploading: %1%', percent.toString()));
}

OB.Media.versionUploaderComplete = function(xhr)
{
  $('#media_upload_form input').val('');
  $('#media_upload_form').show();
  $('#media_upload_progress').hide();

  if(!xhr.responseText) return; // no response? probably cancelled.

  var res = $.parseJSON(xhr.responseText);

  if(res.error)
  {
    OB.UI.alert(res.error);
    return;
  }

  if(!res.media_supported)
  {
    //T This file format is not supported.
    OB.UI.alert('This file format is not supported.');
    return;
  }

  var data = {};
  data.file_id = res.file_id;
  data.file_key = res.file_key;
  data.media_id = $('#media_versions').attr('data-media_id');

  OB.API.post('media', 'version_add', data, function(response)
  {
    if(!response.status)
    {
      OB.UI.alert(response.msg);
      return;
    }

    $('#media_versions_message').obWidget('success',response.msg);

    OB.Media.versionsGet();
  });

}

OB.Media.versionsGet = function()
{
  var data = {};
  data.media_id = $('#media_versions').attr('data-media_id');

  OB.API.post('media','versions', data, function(response)
  {
    if(!response.status) $('#media_versions_message').obWidget('error',response.msg);

    var media = response.data.media;

    $('#media_versions').empty();

    $.each(response.data.versions, function(index, version)
    {
      var $tr = $('<tr />').attr('data-id',version.created);
      $tr.append( $('<td />').html(version.active==0 ? '' : '&#x2714;') );
      //T Original Version
      $tr.append( $('<td />').html(version.created==0 ? OB.t('Original Version') + '<br />'+format_timestamp(media.created) : format_timestamp(version.created)) );
      $tr.append( $('<td />').text(version.format) );
      $tr.append( $('<td />').text(version.duration ? secsToTime(version.duration) : '') );
      $tr.append( $('<td />').html(nl2br(htmlspecialchars(version.notes))) );

      $td = $('<td />');
      //T Activate
      if(version.active==0) $td.append( $('<button data-t class="add">Activate</button>').click(function() { OB.Media.versionSet(data.media_id, version.created); }) );
      //T Edit
      $td.append( $('<button class="edit" data-t>Edit</button>').click(function() { OB.Media.versionEdit(data.media_id, version); }) );
      //T Delete
      if(version.created!=0 && version.active==0) $td.append( $('<button class="delete" data-t>Delete</button>').click(function() { OB.Media.versionDelete(data.media_id, version.created); }) );
      //T Download
      $td.append( $('<button data-t>Download</button>').click(function() { OB.Media.download(data.media_id, version.created); }) );
      $tr.append($td);

      $('#media_versions').append($tr);
    });
  });
}

OB.Media.versionSet = function(media_id, created, confirm)
{
  if(!confirm)
  {
    //T Are you sure you want to activate this media version?
    //T Activate
    //T Cancel
    OB.UI.confirm('Are you sure you want to activate this media version?', function() { OB.Media.versionSet(media_id, created, 1); },'Activate','Cancel','add');
    return;
  }

  OB.API.post('media','version_set',{'media_id':media_id, 'created':created},function(response)
  {
    OB.Media.versionsGet();
    $('#media_versions_message').obWidget(response.status ? 'success' : 'error',response.msg);
  });
}

OB.Media.versionEdit = function(media_id, version)
{
  OB.UI.openModalWindow('media/version_edit.html');

  $('#version_edit_media_id').val(media_id);
  $('#version_edit_created').val(version.created);
  $('#version_edit_notes').val(version.notes);
}

OB.Media.versionEditSave = function()
{
  var data = {};
  data.media_id = $('#version_edit_media_id').val();
  data.created = $('#version_edit_created').val();
  data.notes = $('#version_edit_notes').val();

  OB.API.post('media','version_edit',data,function(response)
  {
    if(!response.status) $('#version_edit_message').obWidget('error',response.msg);

    else
    {
      OB.UI.closeModalWindow();
      OB.Media.versionsGet();
    }

  });
}

OB.Media.versionDelete = function(media_id, created, confirm)
{

  if(!confirm)
  {
    //T Are you sure you want to delete this media version?
    //T Delete
    //T Cancel
    OB.UI.confirm('Are you sure you want to delete this media version?', function() { OB.Media.versionDelete(media_id, created, 1); },'Delete','Cancel','delete');
    return;
  }

  OB.API.post('media','version_delete',{'media_id':media_id, 'created':created},function(response)
  {
    OB.Media.versionsGet();
  });

}
