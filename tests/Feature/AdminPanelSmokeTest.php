<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Meal;
use App\Models\Photo;
use App\Models\Scan;
use App\Models\ScanParseJob;
use App\Models\User;
use App\Models\UserTarget;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Boots the Filament admin panel end-to-end: seeds the demo data, signs in,
 * and renders every list / view / edit page so any schema error surfaces.
 */
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $user = User::first();

        // The seeder covers users/targets/scans/meals/activities but not
        // photos or parse jobs — create one of each so their pages render too.
        Photo::create([
            'user_id' => $user->id,
            'kind' => 'dish',
            'disk' => 'public',
            'path' => 'uploads/demo.jpg',
            'mime' => 'image/jpeg',
            'size' => 123456,
        ]);
        ScanParseJob::create([
            'user_id' => $user->id,
            'status' => 'done',
            'confidence' => 0.92,
            'insight' => 'Composition looks balanced.',
            'draft' => ['weight' => 80.4, 'fatPct' => 18.2],
        ]);

        $this->actingAs($user);
    }

    public function test_login_page_renders_for_guests(): void
    {
        auth()->logout();
        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_dashboard_with_widgets_renders(): void
    {
        $this->get('/admin')->assertSuccessful();
    }

    public function test_resource_index_pages_render(): void
    {
        foreach ([
            '/admin/users',
            '/admin/user-targets',
            '/admin/scans',
            '/admin/meals',
            '/admin/activities',
            '/admin/photos',
            '/admin/scan-parse-jobs',
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_view_and_edit_pages_render_for_each_model(): void
    {
        $records = [
            'users' => User::first(),
            'user-targets' => UserTarget::first(),
            'scans' => Scan::first(),
            'meals' => Meal::first(),
            'activities' => Activity::first(),
            'photos' => Photo::first(),
            'scan-parse-jobs' => ScanParseJob::first(),
        ];

        foreach ($records as $slug => $record) {
            $this->assertNotNull($record, "No seeded record for {$slug}");
            $this->get("/admin/{$slug}/{$record->getKey()}")->assertSuccessful();
            $this->get("/admin/{$slug}/{$record->getKey()}/edit")->assertSuccessful();
        }
    }

    public function test_create_pages_render(): void
    {
        foreach ([
            '/admin/users/create',
            '/admin/user-targets/create',
            '/admin/scans/create',
            '/admin/meals/create',
            '/admin/activities/create',
            '/admin/photos/create',
            '/admin/scan-parse-jobs/create',
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }
}
