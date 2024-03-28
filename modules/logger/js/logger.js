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

OBModules.Logger = new Object();

OBModules.Logger.init = function()
{
  OB.Callbacks.add('ready',0,OBModules.Logger.initMenu);
}

OBModules.Logger.initMenu = function()
{
  OB.UI.addSubMenuItem('admin','Logger Module Log','view_logger_log',OBModules.Logger.logPage,100,'view_logger_log');
}

OBModules.Logger.logPage = function()
{
		OB.UI.replaceMain('modules/logger/logger.html');
		$('#logger_module-list').hide();

		OBModules.Logger.logLimit = 100;
		OBModules.Logger.logOffset = 0;

		OBModules.Logger.logEntriesLoad();
}

OBModules.Logger.logNext = function()
{
	OBModules.Logger.logOffset+=this.logLimit;
	OBModules.Logger.logEntriesLoad();
}

OBModules.Logger.logPrev = function()
{
	OBModules.Logger.logOffset-=OBModules.Logger.logLimit;
	if(OBModules.Logger.logOffset<0) OBModules.Logger.logOffset=0;
	OBModules.Logger.logEntriesLoad();
}

OBModules.Logger.logEntriesLoad = function()
{
	$('#logger_module-info').text('Loading log entries...');

	OB.API.post('logger','viewLog',{'limit': OBModules.Logger.logLimit, 'offset': OBModules.Logger.logOffset},function(response) {

		$('#logger_module-list tbody').html('');

		if(!response.status) { $('#logger_module-info').text('Error loading log entries.'); $('#logger_module-list').hide(); return; }

		var logTotal = response.data.total;
		var entries = response.data.entries;

		if(!entries.length) { $('#logger_module-info').text('No log entries found.'); $('#logger_module-list').hide(); return; }

		$.each(entries,function(index,entry) {

			var $html = $('<tr></tr>');
			$html.append('<td>'+format_timestamp(entry.datetime)+'</td>');
			$html.append('<td>'+htmlspecialchars(entry.user_name)+'</td>');
			$html.append('<td>'+htmlspecialchars(entry.controller)+'</td>');
			$html.append('<td>'+htmlspecialchars(entry.action)+'</td>');
			
			$('#logger_module-list tbody').append($html.outerHTML());

		});

		$('#logger_module-info').text('Core functionality controller access log.');
		$('#logger_module-list').show();

		// show/hide next link as appropriate
		if(logTotal > (OBModules.Logger.logOffset + OBModules.Logger.logLimit)) $('#logger_module-next').show();
		else $('#logger_module-next').hide();

		// show/hide prev link as appropriate
		if(OBModules.Logger.logOffset>0) $('#logger_module-prev').show();
		else $('#logger_module-prev').hide();

	});

}

OBModules.Logger.logClear = function(confirm)
{
  if(confirm)
  {
    OB.API.post('logger','clearLog',{},function(response)
	  {
		    if(!response.status) OB.UI.alert('Error clearing log.');
		    else {
			    OBModules.Logger.logOffset = 0;
			    OBModules.Logger.logEntriesLoad();
		    }
	  });
  } 
    
  else {
    OB.UI.confirm(
        'Are you sure you want to completely clear the log?',
        function () {
          OBModules.Logger.logClear(true);
        },
        'Yes, Clear',
        'No, Cancel',
        'delete'
    );
  }
}

