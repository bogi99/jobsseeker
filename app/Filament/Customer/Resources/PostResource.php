<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Customer';

    protected static ?string $navigationLabel = 'My posts';

    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('free_notice')
                    ->content('You are creating a <strong>free</strong> posting. The "Paid job" option is disabled and this post will be marked as free.')
                    ->visible(fn (): bool => (bool) request()->query('free'))
                    ->columnSpanFull(),
                // Track whether this is a free flow via session (set by middleware on the free route)
                Forms\Components\Hidden::make('is_free')
                    ->default(fn (): bool => (bool) session('customer_free_flow', false))
                    ->dehydrated(true),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn (): ?int => Auth::id())
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('full_content')
                    ->label('Full description')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Section::make('Company information')
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
                Forms\Components\Section::make('Visibility')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Paid job')
                            ->hidden(fn (): bool => (bool) request()->query('free')),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured job'),
                    ])
                    ->columns(3),
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation(),
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
        $items[] = \Filament\Navigation\NavigationItem::make('Create free posting')
            ->group(static::getNavigationGroup())
            ->url(route('customer.posts.create.free'))
            ->visible(fn (): bool => (bool) (\Filament\Facades\Filament::auth()->user()?->is_free ?? false));

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
