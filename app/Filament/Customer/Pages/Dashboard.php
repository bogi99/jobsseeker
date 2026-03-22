<?php

namespace App\Filament\Customer\Pages;

use App\Models\Post;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected string $view = 'filament.customer.dashboard';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static \UnitEnum|string|null $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'Customer Panel';

    protected function getViewData(): array
    {
        $userId = Auth::id();

        return [
            'totalPosts' => $userId ? Post::where('user_id', $userId)->count() : 0,
            'activePosts' => $userId ? Post::where('user_id', $userId)->where('is_active', true)->count() : 0,
            'userName' => Auth::user()?->name,
        ];
    }
}
