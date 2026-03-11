# Breaking Changes to Modules

A refactor in 5.5 changed most of the structure of the core code. In practically all cases this ends up breaking most modules that don't account for it. The following is an incomplete list of changes to account for when a module breaks that will need to be ported to function properly with OpenBroadcaster 5.5:

- Module update files no longer extend `OBUpdate`, but instead extend `OpenBroadcaster\Base\Update`.
- Cron files now extend `OpenBroadcaster\Base\Cron`
- Module controllers go from `OBFController` -> `OpenBroadcaster\Base\Controller`
- Module models go from `OBFModel` -> `OpenBroadcaster\Base\Model`
- Module top level file goes from `OBFModule` -> `OpenBroadcaster\Base\Module`
