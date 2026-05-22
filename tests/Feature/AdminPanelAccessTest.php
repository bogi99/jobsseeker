<?php

namespace Tests\Feature;

use App\Filament\Admin\PostResource\Pages\ListPosts;
use App\Http\Middleware\EnsureAdminPanelAccess;
use App\Models\Post;
use App\Models\User;
use App\Models\UserType;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_access_admin_panel()
    {
        $adminType = UserType::where('name', 'admin')->first();

        /** @var User $user */
        $user = User::factory()->createOne(['usertype_id' => $adminType->id]);

        // Sanity check: ensure factory created an admin user correctly
        $this->assertTrue($user->fresh()->isAdmin());

        // Ensure the Filament auth guard recognizes the user (tests don't have a POST login route).
        $this->actingAs($user, 'web');
        Filament::auth()->login($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(200);
    }

    public function test_debug_ensure_admin_middleware_sees_user()
    {
        Route::middleware([EnsureAdminPanelAccess::class])->get('/_test-ensure', function () {
            $user = Auth::user();

            return response()->json([
                'auth_user' => $user?->id,
                'auth_usertype' => $user?->usertype?->name,
            ]);
        });

        $adminType = UserType::where('name', 'admin')->first();

        /** @var User $user */
        $user = User::factory()->createOne(['usertype_id' => $adminType->id]);

        $this->actingAs($user, 'web');

        $response = $this->get('/_test-ensure');

        $response->assertStatus(200)
            ->assertJsonFragment(['auth_usertype' => 'admin']);
    }

    public function test_superadmin_user_can_access_admin_panel()
    {
        $superType = UserType::where('name', 'superadmin')->first();

        /** @var User $user */
        $user = User::factory()->createOne(['usertype_id' => $superType->id]);

        // Ensure the Filament auth guard recognizes the user (tests don't have a POST login route).
        $this->actingAs($user, 'web');
        Filament::auth()->login($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(200);
    }

    public function test_customer_user_cannot_access_admin_panel()
    {
        $customerType = UserType::where('name', 'customer')->first();

        /** @var User $user */
        $user = User::factory()->createOne(['usertype_id' => $customerType->id]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(403);
    }

    public function test_guest_can_access_admin_login_page()
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertStatus(200);
    }

    public function test_admin_posts_index_displays_company_logo_from_public_disk()
    {
        Storage::fake('public');
        Storage::disk('public')->put('company-logos/test.png', 'contents');

        $adminType = UserType::where('name', 'admin')->first();

        /** @var User $user */
        $user = User::factory()->createOne(['usertype_id' => $adminType->id]);

        Post::factory()->create([
            'user_id' => $user->id,
            'company_logo' => 'company-logos/test.png',
        ]);

        $this->actingAs($user, 'web');
        Filament::auth()->login($user);

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords([$user->posts()->latest('id')->first()])
            ->assertTableColumnStateSet('company_logo', '/storage/company-logos/test.png', $user->posts()->latest('id')->first());
    }
}
