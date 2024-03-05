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

// This is the first js file to be outputted.

OB = new Object();
OBModules = new Object();

$(document).ready(function() 
{

	$.each(OB,function(name,item)
	{
		if(typeof(OB[name].init)=='function') OB[name].init();
	});

	$.each(OBModules,function(name,item)
	{
		if(typeof(OBModules[name].init)=='function') OBModules[name].init();
	});

	OB.Callbacks.callall('ready');

});

$(function(){
    /*
     * this swallows backspace keys on any non-input element.
     * stops backspace -> back
     */
    var rx = /INPUT|SELECT|TEXTAREA|OB-TAG-INPUT/i;

    $(document).bind("keydown keypress", function(e){
        if( e.which == 8 ){ // 8 == backspace
            if (customElements.get(e.target.tagName.toLowerCase()) && (e.target.getAttribute('data-edit') !== null)) {
                return;
            }
            if(!rx.test(e.target.tagName) || e.target.disabled || e.target.readOnly ){
                e.preventDefault();
            }
        }
    });
});

jQuery.fn.showFlex = function() {
  $(this).each(function(index, element)
  {
    $(element).css('display','flex');
    if($(element).css('display')!='flex') $(element).css('display','-webkit-flex');
  });
};
