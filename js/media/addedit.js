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


// media add/edit: copy field to all other media we are adding/editing.
OB.Media.copyField = function(button)
{

  var field_class = $(button).attr('data-field');
  var value = $(button).parent().find('.'+field_class).val();

  if(field_class == 'category_field')
  {
    $('.media_addedit').each(function(index,element)
    {
      var $field = $(element).find('.'+field_class);
      if($field.val()!=value)
      {
        $field.val(value);
        OB.Media.updateGenreList($(element).attr('data-id'));
      }
    });
  }

  else $('.media_addedit .'+field_class).val(value);

}

// media add/edit: get our data from EXIF/ID3 and copy it to our field values.
OB.Media.mediaInfoImport = function(button)
{

  var id = $(button).parents('.media_addedit').attr('data-id');

  if(typeof(OB.Media.media_info[id])!='undefined' && typeof(OB.Media.media_info[id].comments)!='undefined') {

    $form = $('#media_addedit_'+id);

    if(typeof OB.Media.media_info[id].comments.artist != 'undefined') $form.find('.artist_field').val(OB.Media.media_info[id].comments.artist[0]);
    if(typeof OB.Media.media_info[id].comments.album != 'undefined') $form.find('.album_field').val(OB.Media.media_info[id].comments.album[0]);
    if(typeof OB.Media.media_info[id].comments.title != 'undefined') $form.find('.title_field').val(OB.Media.media_info[id].comments.title[0]);
    if(typeof OB.Media.media_info[id].comments.comments != 'undefined') $form.find('.comments_field').val(OB.Media.media_info[id].comments.comments[0]);

    let id3Comments = OB.Media.media_info[id].comments;
    Object.keys(id3Comments).forEach(function(key) {
      const id3Element = document.querySelector('[data-id3-field="' + key + '"]');
      if (! id3Element) {
        return;
      }

      let value = id3Comments[key];
      if (Array.isArray(value) && id3Element.tagName !== 'OB-FIELD-TAGS') {
        value = value.join();
      }
      if (typeof value === 'string' && id3Element.tagName === 'OB-FIELD-TAGS') {
        value = value.split(',');
      }

      id3Element.value = value;
    });
  }

}

OB.Media.editCancel = function(button)
{

  var id = $(button).parents('.media_addedit').attr('data-id');

  $(button).parents('.media_addedit').remove();
  if(OB.Media.media_uploader_xhr[id]) OB.Media.media_uploader_xhr[id].abort();

  OB.Media.media_uploader_uploading = false;
  OB.Media.mediaUploaderUpload(); //process next in queue if available.

  if($('#media_data_middle').html()=='') $('#media_data').hide();
}

OB.Media.editToggle = function(button)
{

  $container = $(button).parents('.media_addedit').find('.addedit_form_container');

  if($container.is(':visible')==true)
  {
    $container.slideUp('fast');
    //T Expand
    $(button).text(OB.t('Expand'));
  }

  else
  {
    $container.slideDown('fast');
    //T Collapse
    $(button).text(OB.t('Collapse'));
  }

}

OB.Media.mediaAddeditForm = function(id,title,editing)
{

  var form       = OB.UI.getHTML('media/addedit_form.html');
  var req_fields = OB.Settings.media_required_fields;

  $('#media_data_middle').append('<div id="media_addedit_'+id+'" class="media_addedit" data-id="'+id+'">'+form+'</div>');

  var $form = $('#media_addedit_'+id);
  $form.find('.addedit_form_title').text(title);

  // fill category list
  for(var i in OB.Settings.categories)
  {
    $form.find('.category_field').append('<option value="'+OB.Settings.categories[i].id+'">'+htmlspecialchars(OB.t(OB.Settings.categories[i].name))+'</option>');
    if(OB.Settings.categories[i].is_default==1) $form.find('.category_field').val(OB.Settings.categories[i].id);
  }

  // fill country list
  for(var i in OB.Settings.countries)
  {
    $form.find('.country_field').append('<option value="'+OB.Settings.countries[i].country_id+'">'+htmlspecialchars(OB.t(OB.Settings.countries[i].name))+'</option>');
  }

  // tie together genre list with category list on change
  $form.find('.category_field').change(function() { OB.Media.updateGenreList(id); });
  OB.Media.updateGenreList(id);

  // add our custom metadata fields
  if(OB.Settings.media_metadata) $.each(OB.Settings.media_metadata, function(index, metadata)
  {
    const metadataField = document.querySelector('#media_metadata_template_' + metadata.type + ' .fieldrow').cloneNode(true);
    if (metadataField) {
      // add data value for appropriate id3 tag if one exists
      if (metadata.settings.id3_key) {
        metadataField.querySelector('.metadata_name_field').setAttribute('data-id3-field', metadata.settings.id3_key);
      }

      // set select field options
      if (metadata.type=='select' && metadata.settings && metadata.settings.options) {
        metadata.settings.options.forEach(function(option) {
          let selectField = metadataField.querySelector('.metadata_name_field');
          selectField.innerHTML = selectField.innerHTML + '<option>' + option + '</option>';
        });
      }

      // set default
      if (metadata.settings && metadata.settings.default) {
        metadataField.querySelector('.metadata_name_field').value = metadata.settings.default;
      }

      // change field name and description values
      metadataField.querySelector('label').innerText = metadata.description;
      metadataField.querySelector('.metadata_name_field').setAttribute('class', 'metadata_' + metadata.name + '_field');
      metadataField.querySelector('.copy_to_all').setAttribute('data-field', 'metadata_' + metadata.name + '_field');

      let form = document.querySelector('#media_addedit_' + id);
      let reference = form.querySelector('.addedit_form_container .copyright_field').parentElement;
      reference.before(metadataField);
    }
  });

  // process HTML widgets/strings
  OB.UI.widgetHTML( $('#media_data_middle') );

  // Modify elements based on whether or not their fields are required/enabled/disabled in the
  // global settings.
  if(req_fields)
  {
    OB.Media.update_required_field(req_fields.artist,      'artist_field');
    OB.Media.update_required_field(req_fields.album,       'album_field');
    OB.Media.update_required_field(req_fields.year,        'year_field');
    OB.Media.update_required_field(req_fields.category_id, 'category_field');
    OB.Media.update_required_field(req_fields.category_id, 'genre_field');
    OB.Media.update_required_field(req_fields.country,  'country_field');
    OB.Media.update_required_field(req_fields.language,    'language_field');
    OB.Media.update_required_field(req_fields.comments,    'comments_field');
  }

  // req field handling for dynamic content default/hidden
  if(req_fields)
  {
    if(!editing) $form.find('.dynamic_select_field').val(req_fields.dynamic_content_default=='enabled' ? 1 : 0);
    if(req_fields.dynamic_content_hidden) $form.find('.dynamic_select_field').parent().hide();
  }

  // one or more elements have visibility depending on permissions. call our update function to adjust this.
  OB.UI.permissionsUpdate();

  // hide copy to add buttons if there is only one form loaded
  $('.media_addedit button.copy_to_all').toggle( $('.media_addedit').length>1 );

}

/* Helper function that takes the global setting for a field (whether it's required,
 enabled, or disabled) and the class of the field in the form, then adjusts it in
 the HTML. */
OB.Media.update_required_field = function (field_setting, field_class) {
  if (field_setting == 'required') {
    $('.' + field_class).siblings('label').addClass('required');
  } else if (field_setting == 'enabled') {
    $('.' + field_class).siblings('label').removeClass('required');
  } else {
    $('.' + field_class).parent().hide();
  }
}

OB.Media.updateGenreList = function(id)
{

  var $form = $('#media_addedit_'+id);

  var selected_category = $form.find('.category_field').val();

  $form.find('.genre_field option').remove();

  // fill genre list
  for(var i in OB.Settings.genres)
  {
    if(OB.Settings.genres[i].media_category_id == selected_category)
    {
      $form.find('.genre_field').append('<option value="'+OB.Settings.genres[i].id+'">'+htmlspecialchars(OB.Settings.genres[i].name)+'</option>');
      if(OB.Settings.genres[i].is_default==1) $form.find('.genre_field').val(OB.Settings.genres[i].id);
    }
  }
}

OB.Media.media_uploader_count = 0;
OB.Media.media_uploader_queue = [];
OB.Media.media_uploader_uploading_count = 0;
OB.Media.media_uploader_uploading = false;
OB.Media.media_uploader_xhr = {};

OB.Media.mediaUploader = function()
{

  $('#media_upload_file_field').change(function () {

    $.each($(this)[0].files,function(index,file)
    {
      OB.Media.media_uploader_count++;
      OB.Media.media_uploader_queue.push(file);
      OB.Media.mediaAddeditForm(OB.Media.media_uploader_count,file.name);
    });

    OB.Media.mediaUploaderUpload();

  });

  $.event.props.push('dataTransfer');

  $('#media_upload_form').bind('dragenter', OB.Media.mediaUploaderNoop);
  $('#media_upload_form').bind('dragexit', OB.Media.mediaUploaderNoop);
  $('#media_upload_form').bind('dragover', OB.Media.mediaUploaderNoop);

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
      OB.Media.media_uploader_count++;
      OB.Media.media_uploader_queue.push(file);
      OB.Media.mediaAddeditForm(OB.Media.media_uploader_count,file.name);
    });

    OB.Media.mediaUploaderUpload();
  });

}

OB.Media.mediaUploaderNoop = function(event)
{
  event.stopPropagation();
  event.preventDefault();
}

OB.Media.mediaUploaderUpload = function()
{

  // already uploading? wait for last upload to finish (this will get called again).
  if(OB.Media.media_uploader_uploading) return;

  // nothing to upload?
  if(!OB.Media.media_uploader_queue.length) return;

  var file_data = OB.Media.media_uploader_queue.shift();

  OB.Media.media_uploader_uploading_count++;
  OB.Media.media_uploader_uploading = true;

  $.ajax({
      url: '/upload.php',
      type: 'POST',
      xhr: function() {
          myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload)
          {
              myXhr.upload.addEventListener('progress',OB.Media.mediaUploaderProgress, false);
          }

          OB.Media.media_uploader_xhr[OB.Media.media_uploader_uploading_count] = myXhr;

          return myXhr;
      },
      data: file_data,
      cache: false,
      contentType: false,
      processData: false,
      complete : OB.Media.mediaUploaderComplete
  });

  $('#media_data').show();

}

OB.Media.mediaUploaderProgress = function(progress)
{
  if(progress.loaded == progress.total)
  {
    //t Processing Media...
    $('#media_addedit_'+OB.Media.media_uploader_uploading_count).find('.new_media_status').text(OB.t('Processing Media...'));
  }

  else
  {
    var percent = Math.floor((progress.loaded/progress.total)*100).toString();
    //T Uploading: %1%
    $('#media_addedit_'+OB.Media.media_uploader_uploading_count).find('.new_media_status').text(OB.t('Uploading: %1%', percent));
  }
}

OB.Media.mediaUploaderComplete = function(xhr)
{

  // set our 'uploading' status to false
  OB.Media.media_uploader_uploading = false;

  if(!xhr.responseText) return; // no response? probably cancelled.

  var res = $.parseJSON(xhr.responseText);
  var id = OB.Media.media_uploader_uploading_count;
  var $form = $('#media_addedit_'+id);

  if(res.error)
  {
    var filename = $form.find('.addedit_form_title').text();
    OB.UI.alert(filename+': '+res.error);
    OB.Media.editCancel(id);
  }

  else
  {
    OB.Media.media_info[id] = res.info;

    $form.find('.upload_file_id').val(res.file_id);
    $form.find('.upload_file_key').val(res.file_key);

    if(typeof(res.info.comments) != 'undefined' && res.info.comments)
    {
      $form.find('.new_media_status').hide();
      $form.find('.use_id3_button').show();
    }

    //T ID3/EXIF Data Unavailable
    else if(res.media_supported) $form.find('.new_media_status').text(OB.t('ID3/EXIF Data Unavailable'));
  }

  if(!res.media_supported) {
    //T This file format is not supported.
    $form.find('.addedit_form_message').obWidget('warning','This file format is not supported.');
    $form.find('.new_media_only').hide();
  }

  // call the uploader again in case we have something left in the queue.
  OB.Media.mediaUploaderUpload();
}

// media add/edit: save media
OB.Media.save = function()
{

  //T Unable to save, please wait until all uploads are complete.
  if(OB.Media.media_uploader_uploading) { OB.UI.alert('Unable to save, please wait until all uploads are complete.'); return; }

  var media_array = new Array();

  $('.media_addedit').each( function(index, element) {

    var item = new Object();
    item.local_id = $(element).attr('data-id');
    var local_id = item.local_id;

    if($(element).attr('data-edit')) item.id = $(element).attr('data-id');

    item.thumbnail = $(element).find('.thumbnail_field').val();

    item.artist = $(element).find('.artist_field').val();
    item.title = $(element).find('.title_field').val();
    item.album = $(element).find('.album_field').val();
    item.year = $(element).find('.year_field').val();

    item.country = $(element).find('.country_field').val();
    item.category_id = $(element).find('.category_field').val();
    item.language = $(element).find('.language_field').val();
    item.genre_id = $(element).find('.genre_field').val();

    item.comments = $(element).find('.comments_field').val();

    item.is_copyright_owner = $(element).find('.copyright_field').val();
    item.is_approved = $(element).find('.approved_field').val();
    item.status = $(element).find('.status_field').val();
    item.dynamic_select = $(element).find('.dynamic_select_field').val();

    item.file_id = $(element).find('.upload_file_id').val();
    item.file_key = $(element).find('.upload_file_key').val();

    $.each(OB.Settings.media_metadata, function(index,metadata)
    {
      item['metadata_'+metadata.name] = $(element).find('.metadata_'+metadata.name+'_field').val();
    });

    // add permissions if fields visible
    if( $(element).find('.advanced_permissions_users_field').is(':visible') )
    {
      item.advanced_permissions_users = $(element).find('.advanced_permissions_users_field:visible').val();
    }
    if( $(element).find('.advanced_permissions_groups_field').is(':visible') )
    {
      item.advanced_permissions_groups = $(element).find('.advanced_permissions_groups_field:visible').val();
    }

    media_array.push(item);

  });

  $('#media_top_message').hide();
  $('#media_data .addedit_form_message').hide();

  OB.API.post('media','save',{ 'media': media_array }, function(data) {

    // one or more validation errors.
    if(data.status==false)
    {

      var validation_errors = data.data;

      // single error (not array), no specific item.
      if(!validation_errors)
      {
        $('#media_top_message').obWidget('error',data.msg);
      }

      else
      {
        for(var i in validation_errors)
        {
          $('#media_addedit_'+validation_errors[i][1]).find('.addedit_form_message').obWidget('error', OB.t(validation_errors[i][2]));
        }
      }

    }

    // update/new complete, no errors.
    else
    {
      $('#media_data_middle').html('');
      $('#media_upload_form').hide();
      $('#media_data').hide();
      $('#media_top_instructions').hide();
      //T Media has been saved.
      $('#media_top_message').obWidget('success', 'Media has been saved.');
      OB.Sidebar.mediaSearch(); // reload our sidebar media search - maybe it needs updating.
    }
  });

}

// media add/edit: edit page
OB.Media.editPage = function(ids)
{
  // no media IDs specified, get IDs from sidebar selection
  if(typeof(ids)=='undefined')
  {
    ids = [];
    $('.sidebar_search_media_selected').each(function(index,element) { ids.push($(element).attr('data-id')); });
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

  // get our media metadata
  var post = [];
  ids.forEach(function(id) { post.push(['media','get',{'id':id}]); });

  OB.API.multiPost(post, function(response)
  {

    var items_selected = false;

    OB.Media.media_uploading = 0; // reset media upload counter.
    OB.Media.media_info = new Array(); // reset

    OB.UI.replaceMain('media/addedit.html');
    $('#media_data').after( OB.UI.getHTML('media/addedit_metadata.html') );
    //T Edit Media
    $('#media_heading').text(OB.t('Edit Media'));
    //T Edit the following as required, then click the save button to commit your changes.
    $('#media_top_instructions').text(OB.t('Edit the following as required, then click the save button to commit your changes.'));

    $('#media_upload_container').hide();
    $('#media_data').show();

    $(ids).each(function(index,id) {

      var media = response[index].data;

      OB.Media.mediaAddeditForm(id,media.artist+' - '+media.title,true);
      $('#upload_'+id+'_data_container').attr('data-id',id); // id is ID in database, it being set means we are editing existing data.

      $form = $('.media_addedit').last();

      $form.attr('data-edit',1);

      $form.find('.thumbnail_field').val( media.thumbnail );

      $form.find('.artist_field').val( media.artist );
      $form.find('.title_field').val( media.title );
      $form.find('.album_field').val( media.album );
      $form.find('.year_field').val( media.year );

      $form.find('.category_field').val( media.category_id );
      OB.Media.updateGenreList(id);

      $form.find('.country_field').val( media.country );
      $form.find('.language_field').val( media.language );
      $form.find('.genre_field').val( media.genre_id);

      $form.find('.comments_field').val( media.comments );

      $form.find('.copyright_field').val( media.is_copyright_owner );
      $form.find('.status_field').val( media.status );
      $form.find('.dynamic_select_field').val( media.dynamic_select );
      $form.find('.approved_field').val( media.is_approved );

      // advanced permissions values if we have them
      if(media.permissions_groups && $form.find('.advanced_permissions_groups_field').length) $form.find('.advanced_permissions_groups_field').val(media.permissions_groups);
      if(media.permissions_users && $form.find('.advanced_permissions_users_field').length) $form.find('.advanced_permissions_users_field').val(media.permissions_users);

      // set values for custom metadata fields
      $.each(OB.Settings.media_metadata, function(index, metadata)
      {
        if(metadata.type=='tags') {
          // TODO: Tags not currently working.
          // $form.find('.metadata_'+metadata.name+'_field').val( media['metadata_'+metadata.name].split(',') );
        }
        else $form.find('.metadata_'+metadata.name+'_field').val( media['metadata_'+metadata.name] );
      });

      items_selected = true;

    });

    $('.new_media_only').hide();

  });

}

// media add/edit: upload page
OB.Media.uploadPage = function()
{

  OB.Media.media_uploading = 0; // reset media upload counter.
  OB.Media.media_info = new Array(); // reset

  OB.UI.replaceMain('media/addedit.html');
  $('#media_data').after( OB.UI.getHTML('media/addedit_metadata.html') );
  OB.Media.mediaUploader();

}
