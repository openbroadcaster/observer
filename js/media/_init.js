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

OB.Media = new Object();

OB.Media.init = function()
{
  OB.Callbacks.add('ready',-5,OB.Media.initMenu);
  OB.Callbacks.add('ready',-4,OB.Media.initMenu2);
}

OB.Media.initMenu = function()
{
  OB.UI.addMenuItem('Media', 'media', 20);
  OB.UI.addSubMenuItem('media', 'Upload Media', 'upload', OB.Media.uploadPage, 10, 'create_own_media');
}

OB.Media.initMenu2 = function()
{
  OB.UI.addSubMenuItem('admin', 'Media Settings', 'media_settings', OB.Media.settings, 40, 'manage_media_settings');
}
