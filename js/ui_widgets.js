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

OB.UI.Widgets = new Object();

OB.UI.widgetHTML = function($elements)
{

  // TODO some way of better dealing with widget code. (widgets.js?)
  $elements.find('obwidget').each(function(index,element)
  {
    var attributes = $(element).getAttributes();

    // we require an ID on widgets.
    // if(!attributes.id) return;

    // deal with widget type "message".
    if($(element).attr('type')=='message')
    {
      delete attributes['type'];

      $div = $('<div></div>');
      
      $.each(attributes,function(attribute,value) { $div.attr(attribute,value); });

      $div.addClass('obwidget');
      $div.addClass('message');
      $div.addClass('hidden');

      $div.attr('data-type','message');

      $(element).replaceWith($div);
    }
  });

}

OB.UI.Widgets.message = function($element,type,...message)
{
  // validate args.
  if(!type) return false;
  if(type!='hide' && !message.length) return false;
  if($.inArray(type,['hide','info','warning','error','success'])<0) return;

  if(type=='hide') $element.hide();

  else
  {
    $element.removeClass('info');
    $element.removeClass('success');
    $element.removeClass('warning');
    $element.removeClass('error');

    $element.addClass(type);

    $element.text(OB.t(...message));
    $element.show();

    OB.UI.scrollIntoView($element);
  }

  return true;
}

$.fn.obWidget = function(...args)
{
  if(!this.hasClass('obwidget') || !this.attr('data-type') || !OB.UI.Widgets[this.attr('data-type')]) return false;
  return OB.UI.Widgets[this.attr('data-type')](this,...args);
}