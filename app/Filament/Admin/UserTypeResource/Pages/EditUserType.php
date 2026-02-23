<?php

namespace App\Filament\Admin\UserTypeResource\Pages;

use App\Filament\Admin\UserTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserType extends EditRecord
{
    protected static string $resource = UserTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
