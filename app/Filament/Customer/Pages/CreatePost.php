<?php

namespace App\Filament\Customer\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;

class CreatePost extends Page
{
    protected string $view = 'filament.customer.create-post';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-plus';

    protected static \UnitEnum|string|null $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'Create new posting';

    protected static ?int $navigationSort = 3;

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        return array_map(
            fn ($item) => $item->visible(fn (): bool => ! (Filament::auth()->user()?->is_free ?? false)),
            $items
        );
    }

    public static function canAccess(): bool
    {
        // Prevent free-posting users from accessing the paid create flow
        return ! (Filament::auth()->user()?->is_free ?? false);
    }

    public function mount()
    {
        redirect()->route('filament.customer.resources.posts.create');
    }
}
