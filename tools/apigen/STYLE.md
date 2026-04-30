# OpenBroadcaster Documentation Style Guide

## Packages

Due to namespacing issues, all PHP files in the core code need to be have the appropriate `@package` tag. For files in `/support`, add an `@package Support` tag at the top of the class; for files in `/controllers`, add an `@package Controller`; finally, for files in `/models`, add an `@package Model`.

## Implicit Data

Controllers do not usually get data passed to them as arguments. This makes proper documentation using `@param` challenging. As a result, use `@var [data-name] [description]` to describe all the expected values passed to a controller method in `$this->data`.

## Return Values

Most methods return values in the form of `[status, msg, data]`, where data is usually but not always another array containing multiple key-value pairings. For the sake of documentation, we don't care about status (always a bool) or msg (always a string), but about data. Not all methods return data, but for the ones that do, document the *successful* returns as `@return [keys,in,data,array]`. When returning a single array containing a variable amount of values, some improvisation may be necessary. As an example, the permissions controller documents the returned array of permissions as `[permission,...]`. Avoid spacing between the array values, as this causes issues with ApiGen.
