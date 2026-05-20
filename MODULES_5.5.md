# Breaking Changes to Modules

A refactor in 5.5 changed most of the structure of the core code. In practically all cases this ends up breaking most modules that don't account for it. The following is an incomplete list of changes to account for when a module breaks that will need to be ported to function properly with OpenBroadcaster 5.5:

- Module update files no longer extend `OBUpdate`, but instead extend `OpenBroadcaster\Base\Update`.
- Cron files now extend `OpenBroadcaster\Base\Cron`
- Module controllers changed from `OBFController` -> `OpenBroadcaster\Base\Controller`
- Module models changed from `OBFModel` -> `OpenBroadcaster\Base\Model`
- Module top level file changed from `OBFModule` -> `OpenBroadcaster\Base\Module`
- All support classes that begin with `OBF` have been namespaced and renamed. They changed from `OBFSupportClass` to `OpenBroadcaster\Support\SupportClass`. This applies to `OBFCallbackReturn`, `OBFCallbacks`, `OBFDB`, `OBFHelpers`, `OBFIO`, `OBFLoad`, `OBFLock`, `OBFModels`, `OBFUsers`.

## Loading models

- Loading a model now requires the name of the module to be passed on to `OBFLoad`. Practically, this means a change from `$model = $this->load->model('ModelName');` to `$model = $this->load->model('ModelName', 'ModuleName');`. This is a new requirement since core and module models are now in different namespaces.
- `OBFModels` *can no longer be used directly to call module model methods*. Its `__call` method uses `$this->load->model` under the hood with its name as the first argument, so a module can't be passed to it since they're no longer all in the same namespace. The code `$models = OBFModels::get_instance(); $models->YourModuleController('argument1', 'argument2');` can no longer tell what module something is from.

## Module Namespacing

- Module directory names now need to match namespace of the actual module, e.g. `now_playing` to `NowPlaying`
- The top level module definition file went from `module.php` to the actual module name. It also needs to be be properly namespaced. For example, `NowPlaying.php` now defines the class `NowPlaying` (without the old `Module` suffix) and has the namespace `OpenBroadcaster\Modules\NowPlaying`. The full class name is `OpenBroadcaster\Modules\NowPlaying\NowPlaying`
- All controller, model, and update files in module follow the same namespacing convention. Rather than being in the global namespace, a model in a module (still in the models directory) now uses the namespace `OpenBroadcaster\Modules\YourModuleName\Models`

## Controller Routes

- All module controllers now requires v2 routes using DocBlocks. See the example module controllers for how to use these.
- The final generated route gets prefixed with `/api/v2/module/ModuleName/`, so for example a DocBlock route called `@route GET /example` in a module named `YourModule` can be accessed with the GET HTTP method at `/api/v2/module/YourModule/example`.
- TODO: v2 API integration for JS with modules