<?php

namespace App\Filament\Admin;

use App\Filament\Admin\PostResource\Pages;
use App\Models\Enums\SalaryCurrency;
use App\Models\Enums\SalaryPeriod;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Post Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Author'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->label('Short Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('full_content')
                            ->label('Full Job Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('company_logo')
                            ->disk('public')
                            ->image()
                            ->maxSize(2048)
                            ->directory('company-logos')
                            ->label('Company Logo'),
                        Forms\Components\TextInput::make('application_link')
                            ->url()
                            ->maxLength(255)
                            ->label('Application URL')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Compensation')
                    ->schema([
                        Forms\Components\TextInput::make('salary_min_amount')
                            ->label('Minimum salary')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->rule('required_with:salary_max_amount,salary_currency,salary_period')
                            ->rule(function ($get): \Closure {
                                return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                    $maximum = $get('salary_max_amount');

                                    if (blank($value) || blank($maximum)) {
                                        return;
                                    }

                                    if ((float) $value > (float) $maximum) {
                                        $fail('The minimum salary must be less than or equal to the maximum salary.');
                                    }
                                };
                            })
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state === null) {
                                    return;
                                }

                                $component->state(number_format($state / 100, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(fn ($state): ?int => filled($state) ? (int) round(((float) $state) * 100) : null),
                        Forms\Components\TextInput::make('salary_max_amount')
                            ->label('Maximum salary')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->rule('required_with:salary_min_amount,salary_currency,salary_period')
                            ->rule(function ($get): \Closure {
                                return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                    $minimum = $get('salary_min_amount');

                                    if (blank($value) || blank($minimum)) {
                                        return;
                                    }

                                    if ((float) $value < (float) $minimum) {
                                        $fail('The maximum salary must be greater than or equal to the minimum salary.');
                                    }
                                };
                            })
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state === null) {
                                    return;
                                }

                                $component->state(number_format($state / 100, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(fn ($state): ?int => filled($state) ? (int) round(((float) $state) * 100) : null),
                        Forms\Components\Select::make('salary_currency')
                            ->label('Currency')
                            ->options(SalaryCurrency::options())
                            ->native(false)
                            ->rule('required_with:salary_min_amount,salary_max_amount,salary_period'),
                        Forms\Components\Select::make('salary_period')
                            ->label('Period')
                            ->options(SalaryPeriod::options())
                            ->native(false)
                            ->rule('required_with:salary_min_amount,salary_max_amount,salary_currency'),
                    ])
                    ->columns(2),

                Section::make('Tags')
                    ->schema([
                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Tags'),
                    ]),

                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                        Forms\Components\Toggle::make('is_paid')
                            ->default(false)
                            ->label('Paid Job'),
                        Forms\Components\Toggle::make('is_featured')
                            ->default(false)
                            ->label('Featured'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('formatted_salary_range')
                    ->label('Compensation')
                    ->placeholder('Not specified')
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('company_logo')
                    ->getStateUsing(fn (Post $record): ?string => $record->company_logo_url)
                    ->circular(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_paid')
                    ->label('Paid')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_featured')
                    ->label('Featured')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Author')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All posts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Paid')
                    ->placeholder('All posts')
                    ->trueLabel('Paid only')
                    ->falseLabel('Free only'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All posts')
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->hiddenLabel(),
                EditAction::make()
                    ->iconButton()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->iconButton()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
