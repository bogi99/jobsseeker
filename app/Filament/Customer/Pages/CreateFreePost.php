<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;

class CreateFreePost extends Page
{
    protected static string $view = 'filament.customer.create-post-free';

    protected static ?string $navigationIcon = 'heroicon-o-plus';

    protected static ?string $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'Create free posting';

    protected static ?int $navigationSort = 4;

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        return array_map(
            fn ($item) => $item->visible(fn (): bool => (bool) (\Filament\Facades\Filament::auth()->user()?->is_free ?? false)),
            $items
        );
    }

    public static function canAccess(): bool
    {
        return (bool) (\Filament\Facades\Filament::auth()->user()?->is_free ?? false);
    }

    public function mount()
    {
        // Redirect to the free create route which enforces eligibility via middleware
        redirect()->route('customer.posts.create.free');
    }
}
