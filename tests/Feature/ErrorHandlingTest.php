<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    /* ── Error pages render friendly, self-contained, no raw detail ── */

    public function test_403_page_renders_friendly_message(): void
    {
        $html = View::make('errors.403')->render();

        $this->assertStringContainsString('Access denied', $html);
        $this->assertStringContainsString('do not have permission', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $this->assertStringNotContainsString('Stack trace', $html);
    }

    public function test_404_page_renders_friendly_message(): void
    {
        $html = View::make('errors.404')->render();

        $this->assertStringContainsString('Page not found', $html);
        $this->assertStringContainsString('could not be found', $html);
    }

    public function test_419_page_renders_friendly_message(): void
    {
        $html = View::make('errors.419')->render();

        $this->assertStringContainsString('Session expired', $html);
        $this->assertStringContainsString('refresh', $html);
    }

    public function test_500_page_does_not_expose_technical_detail(): void
    {
        $html = View::make('errors.500')->render();

        $this->assertStringContainsString('Something went wrong', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $this->assertStringNotContainsString('QueryException', $html);
        $this->assertStringNotContainsString('vendor/', $html);
    }

    public function test_error_pages_render_without_authenticated_layout_data(): void
    {
        // The error layout must never depend on sidebar/permission data.
        foreach (['errors.403', 'errors.404', 'errors.419', 'errors.500', 'errors.503'] as $view) {
            $html = View::make($view)->render();
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString('bootstrap.min.css', $html);
        }
    }

    /* ── Real HTTP: missing route → friendly 404 ── */

    public function test_missing_route_returns_friendly_404_page(): void
    {
        $response = $this->get('/this-route-does-not-exist-'.uniqid());

        $response->assertStatus(404);
        $response->assertSee('Page not found');
        $response->assertDontSee('NotFoundHttpException');
    }

    /* ── Real HTTP: abort(403) → friendly 403 page ── */

    public function test_unauthorized_returns_friendly_403_page(): void
    {
        Route::get('/__test/forbidden', fn () => abort(403));

        $response = $this->get('/__test/forbidden');

        $response->assertStatus(403);
        $response->assertSee('Access denied');
        $response->assertDontSee('SQLSTATE');
    }

    /* ── Disabled module: friendly web page + clean JSON ── */

    public function test_disabled_module_shows_friendly_page(): void
    {
        $this->makeDisabledModule('labx', 'Imaging Lab');
        Route::get('/__test/modguard', fn () => 'reached')->middleware('module:labx');

        $response = $this->get('/__test/modguard');

        $response->assertStatus(403);
        $response->assertSee('Module disabled');
        $response->assertSee('Imaging Lab');
        $response->assertDontSee('reached');
        $response->assertDontSee('Exception');
    }

    public function test_disabled_module_json_returns_clean_message(): void
    {
        $this->makeDisabledModule('labx', 'Imaging Lab');
        Route::get('/__test/modguard-json', fn () => 'reached')->middleware('module:labx');

        $response = $this->getJson('/__test/modguard-json');

        $response->assertStatus(403);
        $response->assertExactJson(['message' => 'The Imaging Lab module is currently disabled.']);
    }

    /* ── QueryException shield: logged, friendly, no SQL leak (production) ── */

    public function test_query_exception_is_shielded_and_logged_in_production(): void
    {
        config(['app.debug' => false]);
        Log::spy();

        Route::get('/__test/db-error', function () {
            return DB::table('a_table_that_does_not_exist_'.uniqid())->get();
        });

        $response = $this->get('/__test/db-error');

        $response->assertStatus(500);
        $response->assertSee('Something went wrong');
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('a_table_that_does_not_exist');

        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'Database error'))->atLeast()->once();
    }

    /* ── Validation still works (not broken by the shield) ── */

    public function test_validation_errors_still_return_field_messages(): void
    {
        Route::post('/__test/validate', function (\Illuminate\Http\Request $request) {
            $request->validate(['name' => 'required']);

            return 'ok';
        })->middleware('web');

        $response = $this->post('/__test/validate', []);

        $response->assertSessionHasErrors('name');
    }

    private function makeDisabledModule(string $slug, string $name): void
    {
        Module::create([
            'name' => $name,
            'slug' => $slug,
            'is_core' => false,
            'is_enabled' => false,
            'sort_order' => 99,
        ]);
        app(ModuleService::class)->flush();
    }
}
