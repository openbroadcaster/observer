# OBServer Coding Standards #

## OBServer (Clientside) ##

__HTML/CSS__

- Tag names should be lowercase

- IDs and Classes should be lowercase_with_underscores.

- For indentation, use 2 space characters instead of Tabs

- Keep things clean and themeable, avoid internal stylesheets and inline styles.

__JavaScript__

- For indentation, use 2 space characters instead of Tabs


__JavaScript Naming Conventions__

- Class names should use CamelCase / CapsWords. i.e., ClassName. Core classes go in OB object. Modules should create their own object to use (i.e., OBModuleName).

- Variables/properties should use lowercase_with_underscore.

- Methods/functions should use lowerCamelCase.

- CONSTANTS should use UPPERCASE_WITH_UNDERSCORES.


## OBServer (Serverside) ##

The following is the style guide for the server­side (PHP) code in the OpenBroadcaster Server web application.

__General Notes__

- For indentation, use 2 space characters instead of Tabs

- Model methods should be invoked via the model object, rather than the method directly. For example, to call the "get_shows" in the "schedule" model, you should use:
 $schedule_model('get_shows',$param1,$param2);
This is required to allow modules to hook into model code.

- Unless it makes things terribly ugly, controllers should not call other controllers. Put reusable code into models.


__Naming Conventions__

- Class names should use CapsWords. i.e., ClassName

- Variables, functions, and methods should use lowercase_with_underscore.

- CONSTANTS should use UPPERCASE_WITH_UNDERSCORES.
