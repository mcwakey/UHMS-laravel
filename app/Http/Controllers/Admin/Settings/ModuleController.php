<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ModuleController extends Controller
{
    public function __construct(protected ModuleService $modules) {}

    public function index()
    {
        $modules = Module::orderBy('is_core', 'desc')
            ->orderBy('sort_order')
            ->get();

        // Build dependents map: slug => [child slugs that depend on it]
        $dependents = [];
        foreach ($modules as $m) {
            if ($m->depends_on) {
                $dependents[$m->depends_on][] = $m->slug;
            }
        }

        return view('admin.modules.index', compact('modules', 'dependents'));
    }

    public function toggle(Request $request, Module $module)
    {
        if ($module->is_core) {
            return back()->with('error', __('messages.modules.cannot_disable_core', ['name' => $module->name]));
        }

        if ($module->is_enabled) {
            abort_unless($request->user()?->can('modules.disable'), 403);

            // About to disable — check for enabled dependents
            $dependents = Module::where('depends_on', $module->slug)
                ->where('is_enabled', true)
                ->pluck('name')
                ->all();

            if (!empty($dependents)) {
                return back()->with(
                    'error',
                    __('messages.modules.disable_dependents_first', ['modules' => implode(', ', $dependents)])
                );
            }

            $this->modules->disable($module->slug);
            $msg = __('messages.modules.disabled', ['name' => $module->name]);
        } else {
            abort_unless($request->user()?->can('modules.enable'), 403);

            // About to enable — check parent dependency is enabled
            if ($module->depends_on) {
                $parent = Module::where('slug', $module->depends_on)->first();
                if ($parent && !$parent->is_enabled) {
                    return back()->with(
                        'error',
                        __('messages.modules.enable_parent_first', ['name' => $parent->name])
                    );
                }
            }
            $this->modules->enable($module->slug);
            $msg = __('messages.modules.enabled', ['name' => $module->name]);
        }

        return back()->with('success', $msg);
    }

    public function flushCache()
    {
        $this->modules->flush();
        Cache::forget('spatie.permission.cache');
        return back()->with('success', __('messages.modules.cache_flushed'));
    }
}
