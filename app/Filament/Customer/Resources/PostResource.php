<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\PostResource\Pages;
use App\Models\Enums\SalaryCurrency;
use App\Models\Enums\SalaryPeriod;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-briefcase';

    protected static \UnitEnum|string|null $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'My posts';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                // Forms\Components\Placeholder::make('free_notice')
                //     ->content('You are creating a <strong>free</strong> posting. The "Paid job" option is disabled and this post will be marked as free.')
                //     ->visible(fn (): bool => (bool) request()->query('free'))
                //     ->columnSpanFull(),
                // Track whether this is a free flow via session (set by middleware on the free route)
                Forms\Components\Hidden::make('is_free')
                    ->default(fn (): bool => (bool) session('customer_free_flow', false))
                    ->dehydrated(true),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn (): ?int => Auth::id())
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Section::make('Company information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('company_logo')
                            ->disk('public')
                            ->image()
                            ->directory('company-logos')
                            ->maxSize(2048)
                            ->label('Company logo'),
                        Forms\Components\TextInput::make('application_link')
                            ->url()
                            ->maxLength(255)
                            ->label('Application URL'),
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

                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->label('Short Description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('full_content')
                    ->label('Full description')
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('formatted_salary_range')
                    ->label('Compensation')
                    ->placeholder('Not specified')
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state ? 'Live' : 'Draft')
                    ->colors([
                        'danger' => fn ($state): bool => (bool) $state === false,
                        'success' => fn ($state): bool => (bool) $state === true,
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Published')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Live')
                    ->falseLabel('Draft'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        // Add a conditional "Create free posting" shortcut under the Customer group
        $items[] = NavigationItem::make('Create free posting')
            ->group(static::getNavigationGroup())
            ->url(route('customer.posts.create.free'))
            ->visible(fn (): bool => (bool) (Filament::auth()->user()?->is_free ?? false));

        return $items;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Auth::user()?->posts()->count() ?? 0;

        return $count > 0 ? (string) $count : null;
    }
}
