<?php

namespace App\Filament\Customer\Resources\PostResource\Pages;

use App\Filament\Customer\Resources\PostResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
