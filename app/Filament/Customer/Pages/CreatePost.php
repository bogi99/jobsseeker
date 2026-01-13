<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;

class CreatePost extends Page
{
    protected static string $view = 'filament.customer.create-post';

    protected static ?string $navigationIcon = 'heroicon-o-plus';

    protected static ?string $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'Create new posting';

    protected static ?int $navigationSort = 3;

    public function mount()
    {
        redirect()->route('filament.customer.resources.posts.create');
    }
}
