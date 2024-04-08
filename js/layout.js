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

OB.Layout = new Object();

OB.Layout.init = function()
{
	OB.Callbacks.add('ready',-40,OB.Layout.layoutInit);
}

OB.Layout.layoutInit = function()
{
  OB.Layout.home();
}

OB.Layout.home = function()
{
  OB.UI.replaceMain('main.html');

	OB.API.post('clientsettings', 'get_welcome_page', {}, function (response) {
		if (response.status) {
			$('#main_page_content').html(response.data);
		} else {
			$('#main_page_content').text('Welcome to OpenBroadcaster.');
		}
	});
}

OB.Layout.tableFixedHeaders = function($headers,$table)
{
  $headers.width($table.width())

  $headers.find('th:visible').each(function(index,element)
  {
    if(!$(element).attr('data-column')) return;

    $column = $table.find('td:visible[data-column='+$(element).attr('data-column')+']').first();
    if(!$column.length) return;

    // wrap out table heading if <div> so we can have it cut off if too long.
    if(!$(element).find('div').length)
      $(element).html('<div style="overflow: hidden; white-space: nowrap; width: '+$column.width()+'px;">'+$(element).html()+'</div>');

    $(element).width($column.width());
  });

}
