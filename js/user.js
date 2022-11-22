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

OB.User = new Object();

OB.User.init = function()
{
  OB.Callbacks.add('ready',-4,OB.User.initMenu);
}

OB.User.initMenu = function()
{
  //T Permissions
  OB.UI.addSubMenuItem('admin', 'Permissions', 'user_permissions', OB.User.managePermissions, 60, 'manage_permissions');
  //T User Management
  OB.UI.addSubMenuItem('admin', 'User Management', 'user_manage', OB.User.manageUsers, 70, 'manage_users');
}

OB.User.manageUsers = function()
{
  OB.UI.replaceMain('user/manage_users.html');

  OB.API.post('users','user_registration_get',{},function(response) {
    if(response.data==true) $('#users_allow_registration_checkbox').prop('checked',true);
  });

  $('#users_allow_registration_checkbox').change(OB.User.allowRegistrationToggle);
  OB.User.manageUsersList();
}

OB.User.allowRegistrationToggle = function()
{
  var checked = $('#users_allow_registration_checkbox').is(':checked');
  OB.API.post('users','user_registration_set',{'user_registration': checked}, function(response) { });
}

OB.User.sort_cache = {
  'sort_col': false,
  'sort_desc': false
}

OB.User.manageUsersSort = function(column)
{
    if (!OB.User.sort_cache.sort_col) {
        OB.API.post('account', 'store', {name: 'manage-users-sort'}, function (result) {
            OB.User.sort_cache = {
                'sort_col': column,
                'sort_desc': false
            };
            if (result.status) {
                OB.User.sort_cache.sort_desc = !result.data.sort_desc;
            }
            OB.API.post('account', 'store', {name: 'manage-users-sort', value: OB.User.sort_cache}, function (result) {
                // Updated user sort settings.
                OB.User.manageUsersList();
            })
        });
    } else {
        // Use cached data
        OB.User.sort_cache = {
            'sort_col': column,
            'sort_desc': (OB.User.sort_cache.sort_col == column) ? !OB.User.sort_cache.sort_desc : false
		    };

        OB.API.post('account', 'store', {name: 'manage-users-sort', value: OB.User.sort_cache}, function (result) {
            // Updated user sort settings.
            OB.User.manageUsersList();
	      });
    }
}

OB.User.manageUsersList = function()
{
    // Load from user storage first if data hasn't been cached yet.
    if (!OB.User.sort_cache.sort_col) {
        OB.API.post('account', 'store', {name: 'manage-users-sort'}, function (result) {
            if (result.status) {
                OB.User.sort_cache.sort_col = result.data.sort_col;
                OB.User.sort_cache.sort_desc = result.data.sort_desc;
            }

            OB.User.manageUsersListByColumn(OB.User.sort_cache);
        });
    } else {
        OB.User.manageUsersListByColumn(OB.User.sort_cache);
    }
}

OB.User.manageUsersListByColumn = function(sort_vals)
{
    OB.API.post('users', 'user_manage_list', sort_vals, function(data)
    {

      $('#user_list_table tbody').html('');

      if(data.status!=true) return false;

      OB.User.manage_users_sort_col = data.data[1];
      OB.User.manage_users_sort_desc = data.data[2];

      $.each(data.data[0],function(index,userdata)
      {

        var $html = $('<tr></tr>');

        $html.append('<td>'+htmlspecialchars(userdata.display_name)+'</td>');
        $html.append('<td><a href="mailto:'+htmlspecialchars(userdata.email)+'">'+htmlspecialchars(userdata.email)+'</a></td>');

        $html.append('<td>'+format_timestamp(userdata.created)+'</td>');
        $html.append('<td>'+format_timestamp(userdata.last_access)+'</td>');

        $html.append('<td class="user_groups"></td>');
        //T Edit
        $html.append('<td><button onclick="OB.User.manageUsersEdit('+userdata.id+');" >'+OB.t("Edit")+'</button></td>');

        $html.attr('id','user_'+userdata.id);
        $html.attr('data-id',userdata.id);
        $html.attr('data-username',userdata.username);
        $html.attr('data-display_name',userdata.display_name);
        $html.attr('data-name',userdata.name);
        $html.attr('data-email',userdata.email);
        $html.attr('data-enabled',userdata.enabled);

        $('#user_list_table tbody').append($html);

        // $('#user_'+userdata.id).dblclick(function() { OB.User.manageUsersEdit(userdata.id); });

        $.each(userdata.groups,function(index,group) {
          var $groupname = $('#user_'+userdata.id+' .user_groups');
          $groupname.append('<span data-group_id="'+group.id+'">'+htmlspecialchars(group.name));
          if (typeof(userdata.groups[index+1]) != 'undefined') $groupname.append(', ');
          $groupname.append('</span>');
        });

      });

    });
}

OB.User.manageUsersGroupList = function(callback)
{

  OB.API.post('users','group_list',{},function(data)
  {

    groups = data.data;

    $.each(groups,function(index,group) {

      var html = '<div><input type="checkbox" value="'+group.id+'"> '+htmlspecialchars(group.name)+'</div>';
      $('#user_addedit_group_list').append(html);

    });

    if(callback) callback();

  });

}

OB.User.manageUsersNew = function()
{
  OB.UI.openModalWindow('user/manage_users_addedit.html');
  OB.User.manageUsersGroupList();

  $('#user_name_input').val('');
  $('#user_username_input').val('');
  $('#user_display_name_input').val('');
  $('#user_email_input').val('');
  $('#user_enabled_input').val(1);
  $('#user_addedit_id').val('');

  $('.edit_only').hide();

}

OB.User.manageUsersEdit = function(id)
{
  OB.UI.openModalWindow('user/manage_users_addedit.html');

  var $user = $('#user_'+id);

  $('#user_name_input').val($user.attr('data-name'));
  $('#user_username_input').val($user.attr('data-username'));
  $('#user_display_name_input').val($user.attr('data-display_name'));
  $('#user_email_input').val($user.attr('data-email'));
  $('#user_enabled_input').val($user.attr('data-enabled'));
  $('#user_addedit_id').val($user.attr('data-id'));

  $('.edit_only').show();

  OB.User.manageUsersGroupList(function() {

    $user.find('.user_groups').children().each(function(index,element)
    {
      $('#user_addedit_group_list input[value='+$(element).attr('data-group_id')+']').attr('checked',true);
    });

  });

  OB.User.manageUsersKeyLoad(id);

}

OB.User.manageUsersSave = function()
{

//  $('#users_addedit_messagebox').hide();

  var fields = new Object();

  fields.name = $('#user_name_input').val();
  fields.username = $('#user_username_input').val();
  fields.display_name = $('#user_display_name_input').val();
  fields.email = $('#user_email_input').val();
  fields.enabled = $('#user_enabled_input').val();
  fields.id = $('#user_addedit_id').val();

  fields.password = $('#user_password_input').val();
  fields.password_confirm = $('#user_password_confirm_input').val();

  fields.group_ids = new Array();

  $('#user_addedit_group_list input:checked').each(function(index,element)
  {

    fields.group_ids.push($(element).val());

  });

  fields.appkeys = new Array();
  $('#user_appkey_table tbody tr').each(function (index, row) {
    fields.appkeys.push([
      $(row).attr('data-id'),
      $(row).find('.user_appkey_name').val()
    ]);
  });


  OB.API.post('users','user_manage_addedit',fields,function(data)
  {

    if(data.status==true)
    {
      OB.UI.closeModalWindow();
      OB.User.manageUsersList();
    }

    else
    {
      $('#user_addedit_message').obWidget('error',data.msg);
      return;
    }

  });

}

OB.User.manageUsersDelete = function(confirm)
{

  if(!confirm)
  {
/*
    $('#users_addedit_messagebox').html('<p>Delete this user?</p>' +
    '<p><input type="button" value="Yes, Delete" onclick="OB.User.manageUsersDelete(true);"> &nbsp; &nbsp; ' +
    '<input type="button" value="No, Cancel" onclick="$(\'#users_addedit_messagebox\').hide();"></p>').show();

    $('#user_addedit_message').obWidget('error',data.msg);
deletemeifworks
*/

    //T Are you sure you want to delete this user?
    //T Yes, Delete
    //T No, Cancel
    OB.UI.confirm(
        'Are you sure you want to delete this user?',
        function() { OB.User.manageUsersDelete(true); },
        'Yes, Delete',
        'No, Cancel',
        'delete'
    );
  }

  else
  {

    OB.API.post('users','user_manage_delete',{'id': $('#user_addedit_id').val()},function(data)
    {

      if(data.status==true)
      {
        OB.UI.closeModalWindow();
        OB.User.manageUsersList();
      }

      else
      {
        $('#user_addedit_message').obWidget('error',data.msg);
        return;
      }

    });

  }

}

OB.User.manageUsersKeyAdd = function () {
  OB.API.post('users', 'user_manage_key_new', {'id': $('#user_addedit_id').val()}, function (response) {
    if (!response.status) {
      $('#user_addedit_message').obWidget('error', response.msg);
      return;
    }

    $('#user_appkey_newkeyinfo').show().html(
      "A new App Key has been created on " +
      format_timestamp(response.data.created) +
      ". The secret key to use with your App key requests is:<br><br><code>" +
      response.data.key +
      "</code><br><br>Please save this key in a secure place."
    );

    $tr = $('<tr/>').attr('data-id', response.data.id);
    $tr.append($('<td/>').html('<input type="text" class="user_appkey_name" value="' + response.data.name + '">'));
    $tr.append($('<td/>').text(format_timestamp(response.data.created)));
    $tr.append($('<td/>').text(format_timestamp(response.data.last_access)));
    $tr.append($('<td/>').html('<button class="delete" class="user_appkey_delete" onclick="OB.User.manageUsersKeyDelete(this);">Delete</button>'));

    $('#user_appkey_table tbody').append($tr);
  });
}

OB.User.manageUsersKeyDelete = function (elem) {
  OB.API.post('users', 'user_manage_key_delete', {
    'user_id': $('#user_addedit_id').val(),
    'id': $(elem).closest('tr').attr('data-id')
  }, function (response) {
    if (!response.status) {
      $('#user_addedit_message').obWidget('error', response.msg);
      return;
    }

    $(elem).closest('tr').remove();
    $('#user_appkey_newkeyinfo').hide();
  });
}

OB.User.manageUsersKeyLoad = function (id) {
  OB.API.post('users', 'user_manage_key_load', {id: id}, function (response) {
    if (!response.status) {
      $('#user_addedit_message').obWidget('error', response.msg);
      return;
    }

    $.each(response.data, function (index, row) {
      $tr = $('<tr/>').attr('data-id', row.id);
      $tr.append($('<td/>').html('<input type="text" class="user_appkey_name" value="' + row.name + '">'));
      $tr.append($('<td/>').text(format_timestamp(row.created)));
      $tr.append($('<td/>').text(format_timestamp(row.last_access)));
      $tr.append($('<td/>').html('<button class="delete" class="user_appkey_delete" onclick="OB.User.manageUsersKeyDelete(this);">Delete</button>'));

      $('#user_appkey_table tbody').append($tr);
    });
  });
}

OB.User.manage_permissions_list = null;

OB.User.managePermissions = function()
{
  OB.UI.replaceMain('user/manage_permissions.html');

  OB.API.post('users','permissions_manage_list',{}, function(data) {
  OB.API.post('users','group_list',{},function(groups)
  {

    OB.User.manage_permissions_list = data.data; // this is used later (when adding/editing a group).

    var group_ids = new Array();

    groups = groups.data;
    $thead = $('<thead></thead>');
    $thead.append('<th>&nbsp;</th>');

    $.each(groups,function(index,group)
    {
      $thead.append('<th id="group_permissions_'+group.id+'" ' +
        'data-name="'+htmlspecialchars(group.name)+'"> '+
        (group.id!=1 ? '<button onclick="OB.User.managePermissionsEdit('+group.id+');">' +
        //T Edit
        OB.t('Edit') +
        '</button>' : '')+
        '<br>'+htmlspecialchars(group.name)+
        '</th>');

      group_ids.push(group.id);

    });

    $('#permissions_table').prepend($thead);

    // attach group data to th for later use.
    $.each(groups,function(index,group)
    {
      if(group.permissions) {
        $('#group_permissions_'+group.id).data('permissions',group.permissions);
      }
    });

    if(data.status!=false)
    {

      var categories = data.data;

      $.each(categories,function(category,permissions)
      {

        //T player
        if(category.match(/^player: /)) var category_translated = category.replace(/^player: /,OB.t('player')+': ');
        else var category_translated = category; // no dynamic variable translation for now

        $('#permissions_table tbody').append('<tr class="permission-category" ><th colspan="1000">'+ htmlspecialchars(category_translated)+'</th></tr>');

        $.each(permissions,function(index,permission)
        {
          $('#permissions_table tbody').append('<tr data-permission="'+htmlspecialchars(permission.name)+'" ><td>'+ htmlspecialchars(permission.description)+'</td><td class="center"><span class="checkmark">&#10003;</span></td></tr>');


          $.each(groups,function(index,group)
          {

            if(group.id==1) return;

            if(group.permissions.indexOf(permission.name)==-1) var check = '';
            else var check = '<span class="checkmark">&#10003;</span>';

            $('#permissions_table tbody tr[data-permission="'+htmlspecialchars(permission.name)+'"]').append('<td >'+check+'</td>');

          });

        });

      });

    }


  }); });

}

OB.User.managePermissionsDelete = function(confirm)
{

  if(confirm)
  {

    OB.API.post('users','permissions_manage_delete',{'id': $('#group_addedit_id').val()},function(data)
    {

      if(data.status==true)
      {
        OB.UI.closeModalWindow();
        OB.User.managePermissions();
      }

      else
      {
        $('permissions_addedit_message').obWidget(data.msg);
      }

    });
  }

  else
  {
    //T Are you sure you want to delete this group?
    //T Yes, Delete
    //T No, Cancel
    OB.UI.confirm(
        'Are you sure you want to delete this group?',
        function() { OB.User.managePermissionsDelete(true); },
        'Yes, Delete',
        'No, Cancel',
        'delete'
    );
  }

}

OB.User.managePermissionsSave = function()
{

  fields = new Object();

  fields.id = $('#group_addedit_id').val();
  fields.name = $('#group_name_input').val();

  fields.permissions = new Array();

  $('#permissions_addedit_form .permission_checkbox').each(function(index,element)
  {
    if($(element).prop('checked')==true)
      fields.permissions.push($(element).attr('data-name'));
  });

  OB.API.post('users','permissions_manage_addedit',fields,function(data)
  {

    if(data.status==true)
    {
      OB.UI.closeModalWindow();
      OB.User.managePermissions();
    }

    else
    {
      $('#permissions_addedit_message').obWidget('error', data.msg)

    }

  });

}

OB.User.managePermissionsNew = function()
{
  OB.UI.openModalWindow('user/manage_permissions_addedit.html');

  //T New Group
  $('#permissions_addedit_heading').text(OB.t('New Group'));

  $('.edit_only').hide();

  OB.User.managePermissionsForm();

}

OB.User.managePermissionsEdit = function(id)
{
  OB.UI.openModalWindow('user/manage_permissions_addedit.html');

  //T Edit Group/Permissions
  $('#permissions_addedit_heading').text(OB.t('Edit Group/Permissions'));
  $('#group_addedit_id').val(id);
  $('#group_name_input').val($('#group_permissions_'+id).attr('data-name'));

  $('.edit_only').show();

  OB.User.managePermissionsForm(id);

}

OB.User.managePermissionsForm = function(id)
{

  var category = null;

  if(id) var group_permissions = $('#group_permissions_'+id).data('permissions');

  $.each(OB.User.manage_permissions_list,function(category,permissions)
  {
    //T player
    if(category.match(/^player: /)) var category_translated = category.replace(/^player: /,OB.t('player')+': ');
    else var category_translated = category;

    var $fieldset = $('<fieldset><legend data-t >'+htmlspecialchars(category_translated)+'</legend></fieldset>');

    $.each(permissions,function(index,permission)
    {
      var $fieldrow = $('<div class="fieldrow" ></div>');

      $fieldrow.append(
        '<label data-t>'+
        htmlspecialchars(permission.description) +
        '</label>'+
        '<input class="permission_checkbox" data-name="'+permission.name+'" type="checkbox"> '
      );

      if(id && group_permissions.indexOf(permission.name)!=-1) {
        $fieldrow.find('.permission_checkbox[data-name="'+permission.name+'"]').attr('checked',true);
      }

      $fieldset.append($fieldrow);

    });


    $('#permissions_addedit_form').append($fieldset);

  });


}
