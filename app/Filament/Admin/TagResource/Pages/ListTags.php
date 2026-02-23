<?php

namespace App\Filament\Admin\TagResource\Pages;

use App\Filament\Admin\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected string|int|null $defaultTableRecordsPerPageSelectOption = 8;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
