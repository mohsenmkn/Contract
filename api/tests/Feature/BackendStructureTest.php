<?php

namespace Tests\Feature;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BackendStructureTest extends TestCase
{
    public function test_application_boots_without_library(): void
    {
        $this->assertInstanceOf(Kernel::class, $this->app->make(ConsoleKernel::class));
        $this->assertDirectoryDoesNotExist(base_path('Modules/Library'));
        $modules = array_keys(json_decode(file_get_contents(base_path('modules_statuses.json')), true));
        sort($modules);
        $this->assertSame(['Acl', 'Attendance', 'Auth', 'HR', 'Payroll', 'User'], $modules);
    }

    public function test_controller_actions_exist_and_internal_apis_require_authentication(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (str_contains($action, '@') && str_starts_with($action, 'Modules\\')) {
                [$class, $method] = explode('@', $action, 2);
                $this->assertTrue(class_exists($class), $action);
                $this->assertTrue(method_exists($class, $method), $action);
                $this->assertStringStartsWith('api/v1/', $route->uri());
            }
            if (str_starts_with($route->uri(), 'api/v1/') && !str_starts_with($route->uri(), 'api/v1/auth/')) {
                $this->assertContains('auth:sanctum', $route->gatherMiddleware(), $route->uri());
                $this->assertContains('user.can_login', $route->gatherMiddleware(), $route->uri());
            }
            $this->assertStringNotContainsString('Library', $action);
        }
    }

    public function test_retained_jobs_are_scheduled_without_running_them(): void
    {
        $events = $this->app->make(Schedule::class)->events();
        $expected = ['gtarabar:import-all' => '*/15 * * * *', 'gtarabar:status' => '0 8 * * *',
            'training:sync-all' => '0 2 * * *', 'sanctum:prune-expired' => '0 0 * * *'];
        $this->assertCount(4, $events);
        $commands = Artisan::all();
        foreach ($expected as $command => $expression) {
            $matches = array_values(array_filter($events, fn ($event) => str_contains($event->command, $command)));
            $this->assertCount(1, $matches, $command);
            $this->assertSame($expression, $matches[0]->expression);
            $this->assertArrayHasKey($command, $commands);
        }
        foreach (array_keys($commands) as $command) {
            $this->assertFalse(str_starts_with($command, 'library:'), $command);
        }
    }

    public function test_removed_modules_do_not_generate_permissions(): void
    {
        foreach (['library', 'projects', 'assessment', 'virtual_secretariat', 'warehouse_gtrabar'] as $module) {
            $this->assertArrayNotHasKey($module, config('rbac.modules'));
        }
        $this->assertArrayHasKey('Payroll', config('rbac.modules'));
        $this->assertArrayHasKey('hr', config('rbac.modules'));
    }
}
