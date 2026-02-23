<?php

namespace App\Filament\Admin\UserResource\Pages;

use App\Filament\Admin\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
