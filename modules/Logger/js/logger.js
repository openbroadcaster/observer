// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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

