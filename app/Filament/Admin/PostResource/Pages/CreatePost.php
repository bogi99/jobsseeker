<?php

namespace App\Filament\Admin\PostResource\Pages;

use App\Filament\Admin\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
