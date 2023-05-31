# OBServer Tools

## CLI Checktool

~~~~
PHP tools/cli/ob.php check
~~~~
## mediacheck.php

Output list of missing media files

~~~~
//IP_OF_SERVER/tools/mediacheck.php
~~~~ 

## Updates 

Displays a list of availalble updates, errors or misconfigured services

~~~~
//IP_OF_SERVER/updates
~~~~

## Rename Script 

~~~~
python media_filename_update.py 
~~~~

Displays a dry run list of files that will be renamed(from, to). RUN THIS FIRST to verify correct operation.

~~~~
python media_filename_update.py go 
~~~~

Completes the rename.

Covers all data change that is simply the result of a rename or moving.

If file data changes as well as a rename, the rename will happen first but rsync will then update the file anyway.

