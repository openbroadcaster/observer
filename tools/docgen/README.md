# OpenBroadcaster Docgen

Documentation generator for OpenBroadcaster core code. Follows DocBlock conventions where possible in a minimalist way, and implements a small number of tags. Specifically made to work with OpenBroadcaster conventions (e.g. `@param` works in controller methods that take no arguments, but rely on `$this->data` instead).

## Usage

Invocation: `./docgen target_dir source_dir [source_dirs]`

Target directory comes first. One or more source directories can be specified. Note that DocGen will *not* go through source files recursively&mdash;if multiple subdirectories are required, they need to be specified separately.

DocGen will not remove previously generated documentation or write to non-empty directories. When running DocGen in an automated environment, it's important to clear out previous documentation first.

## Supported Tags

Tag | Description
--- | ---
`@package` | The package a class is in. Useful for separating out different types of classes, such as controllers, models, core classes.
`@param` | A method parameter and its description. This can be an *argument* to a method but because of the way OpenBroadcaster controllers are structured, this is not required.
`@return` | The return value of a method. Because most controllers and models in OpenBroadcaster return data in a `[status, msg, data]` format, this for the most part specifies what we can expect `data` to contain.

# API Routes JSON File

DocGen is also responsible for generating a JSON file showing all the API routes. These routes can be created in the OpenBroadcaster code with the `@route` tag. For example, creating a route to the delete method in the players controller could be written as `@route DELETE /players/(:id:)`. This creates a route to the players controller taking a DELETE HTTP request, with the player id as the argument in the URL. Note that the URL provided in the route tag does not have to match the controller name (players vs player, for example), although it is recommended that they do. This example tag will generate the following JSON:

```json
{
    "DELETE": [
        [
            "/api/players/(:id:)",
            "players",
            "delete"
        ]
    ]
}
```

## Usage

Invocation: `./docgen_routes target.json source_dir [source_dirs]`

Target JSON file comes first. One or more source directories can be specified. As with DocGen, this script will *not* go through source files recursively.
