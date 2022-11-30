# OBServer UI Development

All core JS code goes in 'OB' object:

~~~~
     OB.ClassName (class)
~~~~

- OB.ClassName.init() is automatically called if available in $(document).ready. This is the ideal place to register callbacks and do any init that doesn't doesn't have to be negotiated in a particular order with order classes (see 'ready' callback below).

- The callbacks for 'ready' are called immediately after all init functions are called. The difference between using 'init' and the 'ready' callback is that the 'ready' callbacks are called using the specified order number (from lowest to highest), whereas this information isn't available for 'init'.

To add a callback: 

~~~~
OB.Callbacks.add(callbackname,ordernumber,function)
~~~~

Currently available callbacks: 

~~~~
"account_login"
"ready"
"permissions_update"
"groups_update"
~~~~

All OB core registered callbacks use negative order numbers. (Thinking about attaching callbacks to methods instead, then you can register before/after any method.)

__API requests__

~~~~
OB.API.post(controller_name,action_name,{ post data object },callback_function)
~~~~

Add "sync" as last argument to do a synchronous request.

- Multiple API requests in one transaction: 

~~~~
OB.API.multiPost (array of arrays containing controller/action/data, callback_function)
~~~~

(optional sync as last argument) See settings.js (getMediaSettings) for an example of this in use. Responses then come in as array with same indices as controller/action/data array.

OB.API methods: post( controller (string),action (string letter),sdata,callback_function (function),mode)

__Permissions to control item visibility__

In the HTML markup, set the attribute "data-­permissions" to a space­ separated list of permissions which allow the item to be visible.

o update visibility of items, call:

~~~~
OB.UI.permissionsUpdate(context)
~~~~

"context" is an optional area of consideration (i.e., '#obmenu').

__Minification__

Use uglifyjs for JS minification. Compile all JS files together (init files should appear first). then execute:

~~~~
uglifyjs -c ob.js > ob.min.js
~~~~~

## OBWidgets

Widgets are reusable OB UI elements are known as obWidgets. obWidgets are created in HTML files using an <obwidget> tag. This tag requires, at minimum, a "type" attribute which specifies what kind of widget it is. Other attributes are required or optional depending on the widget type.

The OB core JS will convert the obWidget element into standard HTML appropriate for the widget type. The widget can be interacted with via js and the obWidget function:

~~~~
$('#widget_id').obWidget(arg1,arg2,arg3...). 
~~~~

The arguments will depend on the widget type and interaction required.

__Message Widget__

Display a message box to provide info to the user (Playlist saved, error saving playlist, etc.)

- arg1: message action ('hide','info','error','warning','success'). (hides or sets a message with a particular style)

- arg2: message text (for 'info','error','warning','success' actions, set the text of the box). string or array (use array for translation, as specified above with 2 or 3 length). 

__Alert Box__

OB UI has a custom alert box to match the OB theme. Call:

OB.UI.alert(text)

where text is a string, or array for translation (length 2 or 3 as specified in Translation Guide). 

__Confirm Box__

OB UI has a custom confirm box to match the OB theme. Call:

~~~~
OB.UI.confirm([text],[callback],[okay_text],[cancel_text],[okay_class],[cancel_class])
~~~~

Arguments:

- text (string | array): Question posed to user for confirmation acceptance or denial

- callback (function): function to be called after accepting confirmation

- okay_text (string | array): button text for accepting confirmation

- cancel_text (string | array): button text for denying confirmation

- okay_class (string): button theme class for accepting confirmation.

- cancel_class (cancel): button theme class for denying confirmation.

See Translation Guide for an explanation on using arrays for text arguments.

Available standard button classes are "add", "edit", or "delete" - which show up as green, grey, or red, respectively.

You can also pass arguments with an object with any or all properties like so:

~~~~
OB.UI.confirm( {text: "Do you want to delete?", okay_class: "delete"} );
~~~~






