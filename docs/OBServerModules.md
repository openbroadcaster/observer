# OBServer Modules Development
{:.no_toc}

* TOC
{:toc}

## Module Support 

OpenBroadcaster v5.X supports modules with a plugin architecture.

The module system extends OpenBroadcaster functionality (by adding new functionality), but can also integrate with core functionality using callbacks/hooks.

## Module install instructions 

Server lives at /var/www/openbroadcaster, all files under there need to be owned by www-data:www-data.

## Directory Structure 

Modules are contained within the /modules directory. /modules/MODULE_NAME is the main directory for the module. Within this directory, we find the following structure:

MODULE_NAME/js : These javascript files are automatically loaded with OpenBroadcaster. They can be used for front-end functionality.

MODULE_NAME/html : These HTML files are automatically loaded with OpenBroadcaster. They are used to define the layout of front-end functionality.

MODULE_NAME/css : These CSS files are automatically loaded with OpenBroadcaster.

MODULE_NAME/images : Add any supporting image files here. These are pre-loaded on client-side application load and can be accessed directly.

MODULE_NAME/controllers : Add controllers to be accessed by the OpenBroadcaster API.

MODULE_NAME/models : Add models to be accessed by controllers (and models).

MODULE_NAME/module.php : Main module file which provides install/uninstall procedures,

## Javascript Files 

Module javascript files are automatically loaded after core javascript. This javascript can be used to append or modify the client application. Be sure to encapsulate your javascript code in a single object (class) to avoid namespace issues with the core or other modules.

For example, the logger module places all code in the ModuleLogger object (except for some init code in $(document).ready() to get things started).

The core OpenBroadcaster code is not as nicely encapsulated in this way, but should be as a part of code cleanup in the future.

## HTML Files

HTML files are loaded (cached) into a javascript object. You can get the contents of a module HTML file using the html.get javascript method. html.get accepts a single argument defining the HTML file you want to retrieve. For example, to get the contents of /modules/logger/html/main/logger.html, you would call html.get('modules/logger/main/logger.html'). Note that the HTML directory is removed from the parameter as it is redundant, but modules/logger remains in order to avoid naming conflicts with the core or other modules.

## CSS Files 

CSS files are automatically loaded on client application startup. They are loaded after the core CSS (including core CSS overrides by themes), but before non-overriding theme CSS. While themes do not have the ability to override complete module CSS files, they can override individual module CSS definitions as they are loaded after the module CSS files.

## Image Files 

Add any support images required. There are presently no module image overrides available with themes (but this should be done at some point).

## Controllers 

TODO: Create guide for controller files. Namespace/integration/coding guidelines.

NOTE: If you use the same controller name as one of the core controllers, it will completely override the core controller.

Controllers can be accessed by the user (or front-end application) directly through the api (api.php), or by hooking into existing controllers. For information on linking an existing controller with your module's controller, see the callbacks section of module.php below.

## Models 

TODO: Create guide for model files. Namespace/integration/coding guidelines (and OB DB abstraction).

NOTE: If you use the same model name as one of the core models, it will completely override the core model.

## Module.php

Each module must have a module.php in the main module directly. This contains a class which extends OBFModule. Let's start with an example:

~~~~
class LoggerModule extends OBFModule
{

	public $name = 'Logger v1.0';
	public $description = 'Track account logins.';

	public function callbacks()
	{
		
		$this->callback_handler->register_callback('LoggerModel.log','Account.login','return',0);
	}

	public function install()
	{
		$this->db->query('CREATE TABLE IF NOT EXISTS `module_logger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');

		return true;

	}

	public function uninstall()
	{
		$this->db->query('DROP TABLE  `module_logger`');
		return true;
	}
}
~~~~

### Properties 

First, there are two properties. $name provides the name of the module, and $description provides the description.
callback() Method

Next, there is a callbacks() method to register any callbacks. This uses the callback handler with a few properties:

$this->callback_handler->register_callback($callback,$hook,$position,$weight):

$callback: String. For a controller this will look like "Controllername.Action". For a model this will look like "ModelnameModel.method".

$hook: String. This follows the same format as $callback. What do you want to hook into?

$position: This will be either 'init' or 'return' depending on when you want the callback to be run.

$weight: This defines the order the callbacks are run in. '0' is fine if it's not important. The number can be positive or negative.
install() and uninstall() Methods

These methods are called when the module is installed or uninstalled. If they return true, the (un)install will be considered successful. If they return false, the (un)install not be considered successful and an error will be returned to the user. A return value is required.

## Callbacks 

OpenBroadcaster uses a callback system to link modules and core functionality, as well as modules with other modules. There are for times when callbacks are called:

Controllers - Init: Callbacks assigned to a controller action (method) with the 'init' position are called before the requested controller is run.

Controllers - Return: Callbacks assigned to a controller action (method) with the 'return' position are called after the requested controller has returned.

Models - Init: Callbacks assigned to a model method with the 'init' position are called before the requested method is run.

Models - Return: Callbacks assigned to a model method with the 'return' position are called after the requested model has returned.

### Callback Process Chain 

Multiple callbacks can be assigned to a single hook (controller/action or model/method). Callbacks for the same hook and position are called in order of weight (which is specified when the callback is registered). The chain of callbacks for a given action or method is referred to as the callback process chain. This process chain includes the init callbacks, the requested action or method, and the return callbacks. Return values for any function in the chain can be accessed using the callback handler class (see next section).

### Callback Return Value 

Any method acting as a callback is expected to return a OBFCallbackReturn object. This object can provide some data to the next callback in the process chain, or force an early return requested method.

To return a OBFCallbackReturn object, consider the following:


~~~~
public function someControllerAction()
{
  ...
  return OBFCallbackReturn(); (option #1)
  return OBFCallbackReturn($data); (option #2)
  return OBFCallbackReturn($data,true); (option #3)
}
~~~~

Option #1: A normal return. Do not provide any information to the next callback, and do not force an early return.

Option #2: Return with data. Provide some information to the next callback, but do not force an early return.

Option #3: Force early return with data. $data will be returned immediately as the requested model/method or controller/action. $data will replace the requested model/method or controller/action return value.

### Accessing Other Return Values 

Sometimes it is necessary to access other return values in the process chain. You can do this using the callback handler class ($this->callback_handler) available in any model or controller. $this->callback_handler->get_retvals($hook) will give you a list of return values for the specified hook. Hook must be specified since there may be multiple process chains running simultaneously (a single controller/action or multiple model/methods).
