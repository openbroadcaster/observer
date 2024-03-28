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

OB.Modules = new Object();

OB.Modules.init = function()
{
  OB.Callbacks.add('ready',-4,OB.Modules.initMenu);
}

OB.Modules.initMenu = function()
{
  //T Modules
  OB.UI.addSubMenuItem('admin', 'Modules', 'manage_modules', OB.Modules.modulesPage, 50, 'manage_modules');
}

OB.Modules.modulesPage = function()
{
//  $('#layout_main').html(OB.UI.getHTML('admin/modules.html'));
  OB.UI.replaceMain('admin/modules.html');
  OB.Modules.modulesGet();
}

OB.Modules.modulesGet = function()
{

  //T Loading modules...
  $('#modules_installed_info').text(OB.t('Loading modules...'));
  $('#modules_available_info').text(OB.t('Loading modules...'));

  $('#modules_installed_list tbody').html('');
  $('#modules_available_list tbody').html('');

  OB.API.post('modules','search', {}, function(response) {

    var installed_modules = response.data.installed;
    var available_modules = response.data.available;

    //T There are no modules installed.
    var no_modules_text = OB.t('There are no modules installed.');
    //T The following modules are installed:
    var following_modules_text = OB.t('The following modules are installed:');
    //T There are no modules available to install.
    var no_available_text = OB.t('There are no modules available to install.');
    //T The following modules are available to install:
    var available_modules_text = OB.t('The following modules are available to install:');

    if(installed_modules.length==0) {
      $('#modules_installed_info').text(no_modules_text);
      $('#modules_installed_list').hide();
    }
    else {
      $('#modules_installed_info').text(following_modules_text);
      $('#modules_installed_list').show();
    }

    if(available_modules.length==0) {
      $('#modules_available_info').text(no_available_text);
      $('#modules_available_list').hide();
    }
    else {
      $('#modules_available_info').text(available_modules_text);
      $('#modules_available_list').show();
    }

    $.each(installed_modules,function(index,module)
    {
      $('#modules_installed_list tbody').append(
          '<tr><td>'+htmlspecialchars(module.name)+'</td>'+
          '<td>'+htmlspecialchars(module.description)+'</td>'+
          '<td><button onclick="OB.Modules.moduleUninstall(false, \''+module.dir+'\');" >' +
          OB.t('uninstall') +
          '</td>'+
          '</tr>');
    });


    $.each(available_modules,function(index,module)
    {
      $('#modules_available_list tbody').append('<tr>'+
      '<td>'+htmlspecialchars(module.name)+'</td>'+
      '<td>'+htmlspecialchars(module.description)+'</td>'+
      '<td><button onclick="OB.Modules.moduleInstall(false, \''+module.dir+'\');" >' +
      OB.t('install') +
      '</td></tr>');
    });


  });

}

OB.Modules.moduleInstall = function(confirm, module_name) {

  if (confirm)
  {
    OB.API.post('modules','install', {'name': module_name}, function(response) {
      if (response.status) {
        OB.Modules.modulesGet();
        $('#modules_message').obWidget('success', response.msg)
      } else {
        $('#modules_message').obWidget('error', response.msg);
      }
    });
  } else {

    //T Are you sure you wish to install the module "%1"?
    //T Yes, Install the Module
    //T No, Cancel
    OB.UI.confirm(
        ['Are you sure you wish to install the module "%1"?', module_name],
        function () {
          OB.Modules.moduleInstall(true, module_name);
        },
        'Yes, Install the Module',
        'No, Cancel',
        'delete'
    )
  }
}

OB.Modules.moduleUninstall = function(confirm, module_name) {

  if (confirm) {

    OB.API.post('modules', 'uninstall', {'name': module_name}, function (response) {
      if (!response.status) {
        $('#modules_message').obWidget('error', response.msg);
      } else {
        OB.Modules.modulesGet();
        $('#modules_message').obWidget('success', response.msg);
      }
    });

  } else {

    //T Are you sure you wish to uninstall the module "%1"?
    //T Yes, Uninstall the Module
    //T No, Cancel
    OB.UI.confirm(
        ['Are you sure you wish to uninstall the module "%1"?', module_name],
        function () {
          OB.Modules.moduleUninstall(true, module_name);
        },
        'Yes, Uninstall the Module',
        'No, Cancel',
        'delete'
    );

  }
}
