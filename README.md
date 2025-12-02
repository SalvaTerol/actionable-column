# Filament Actionable Columns

The **Filament Actionable Columns** plugin allows you to create interactive table columns with clickable actions for Filament v4. Display text with side-by-side action buttons, inline editable columns, or icon-only actions - all using Filament's built-in Action system.

![Filament Actionable Columns](https://via.placeholder.com/800x400?text=Filament+Actionable+Columns)

## Features

- 🎨 **Three Layout Modes:**
  - **Separated** (default): Text and action side-by-side, only action is clickable
  - **Inline**: Text and icon together, both clickable
  - **Icon Only**: Just the action button/icon

- ⚡ **Full Filament Action Support:**
  - Use all Filament Action methods (`modal()`, `url()`, `action()`, `requiresConfirmation()`, `tooltip()`, etc.)
  - Native Filament modals, forms, and confirmations
  - Custom icons and styling options

- 🎯 **Simple API:**
  - Clean, intuitive methods
  - Leverages Filament's existing Action class
  - No extra dependencies or custom classes needed

## Installation

You can install the package via composer:

```bash
composer require shreejan/filament-actionable-columns
```

## Usage

### Basic Example

```php
use Shreejan\FilamentActionableColumns\Tables\Columns\ActionableColumn;
use Filament\Actions\Action;

ActionableColumn::make('status')
    ->label('Status')
    ->actionLabel('Change')
    ->badge()
    ->getStateUsing(fn ($record) => $record->status?->label)
    ->tapAction(
        fn ($record) => Action::make('changeStatus')
            ->modal()
            ->form([
                // Your form fields
            ])
            ->action(function ($record, array $data) {
                // Your action logic
            })
    )
```

### With Filament Modal

```php
ActionableColumn::make('notes')
    ->label('Notes')
    ->actionLabel('Add Note')
    ->tapAction(
        fn ($record) => Action::make('addNote')
            ->modal()
            ->modalHeading('Add Note')
            ->form([
                Textarea::make('note')
                    ->required()
                    ->rows(4),
            ])
            ->action(function ($record, array $data) {
                $record->notes()->create($data);
            })
    )
```

### With URL Redirect

```php
ActionableColumn::make('view')
    ->label('View')
    ->actionLabel('View Details')
    ->tapAction(
        fn ($record) => Action::make('view')
            ->url(fn ($record) => route('records.show', $record->id))
            ->openUrlInNewTab()
    )
```

### With Confirmation

```php
ActionableColumn::make('delete')
    ->label('Actions')
    ->actionLabel('Delete')
    ->iconOnly()
    ->actionIcon('heroicon-o-trash')
    ->tapAction(
        fn ($record) => Action::make('delete')
            ->requiresConfirmation()
            ->modalHeading('Delete Record')
            ->modalDescription('Are you sure you want to delete this record? This action cannot be undone.')
            ->action(fn ($record) => $record->delete())
            ->color('danger')
    )
```

### With Tooltip

```php
ActionableColumn::make('edit')
    ->label('Edit')
    ->actionLabel('Edit')
    ->tapAction(
        fn ($record) => Action::make('edit')
            ->modal()
            ->form([...])
            ->tooltip('Click to edit this record')
    )
```

### Custom Icon

```php
ActionableColumn::make('attachment')
    ->label('Attachment')
    ->actionLabel('Upload')
    ->actionIcon('heroicon-o-paper-clip')
    ->actionIconSize(\Filament\Support\Enums\IconSize::Medium)
    ->tapAction(
        fn ($record) => Action::make('upload')
            ->modal()
            ->form([
                FileUpload::make('file')
                    ->required(),
            ])
    )
```

## Layout Modes

### Separated Mode (Default)

Text and action side-by-side, only action is clickable:

```php
ActionableColumn::make('status')
    ->separated()  // Optional, this is the default
    ->actionLabel('Change')
    ->tapAction(...)
```

### Inline Mode

Text and icon together, both clickable:

```php
ActionableColumn::make('notes')
    ->displayInline()
    ->actionLabel('Edit')
    ->tapAction(...)
```

### Icon Only Mode

Just the action button:

```php
ActionableColumn::make('actions')
    ->iconOnly()
    ->actionIcon('heroicon-o-ellipsis-horizontal')
    ->tapAction(...)
```

## Configuration Options

### ActionableColumn Methods

| Method | Description | Default | Example |
|--------|-------------|---------|---------|
| `displayMode(string $mode)` | Set layout mode | `'separated'` | `->displayMode('inline')` |
| `separated()` | Set to separated mode | - | `->separated()` |
| `displayInline()` | Set to inline mode | - | `->displayInline()` |
| `iconOnly()` | Set to icon-only mode | - | `->iconOnly()` |
| `tapAction(Action\|Closure $action)` | Set the Filament Action | `null` | `->tapAction(Action::make(...))` |
| `actionLabel(string\|Closure $label)` | Set action label | `null` | `->actionLabel('Add')` |
| `actionIcon(string\|Closure $icon)` | Set action icon | `'heroicon-o-pencil'` | `->actionIcon('heroicon-o-star')` |
| `actionIconSize(IconSize\|string\|Closure $size)` | Set icon size | `IconSize::Small` | `->actionIconSize(IconSize::Medium)` |
| `showActionIcon(bool\|Closure $show)` | Show/hide icon | `true` | `->showActionIcon(false)` |
| `showLoadingState(bool\|Closure $show, int $duration)` | Show loading state | `true, 3000` | `->showLoadingState(true, 5000)` |
| `fallbackUrl(Closure\|string $url)` | Set fallback URL | `null` | `->fallbackUrl('/path')` |

### Filament Action Methods

Since `tapAction()` accepts Filament's `Action`, you can use all Action methods:

| Method | Description | Example |
|--------|-------------|---------|
| `modal()` | Open Filament modal | `->modal()` |
| `url(string\|Closure $url)` | Redirect to URL | `->url(fn($r) => route('view', $r->id))` |
| `action(Closure $action)` | Execute action | `->action(fn($r) => $r->delete())` |
| `requiresConfirmation()` | Require confirmation | `->requiresConfirmation()` |
| `tooltip(string\|Closure $tooltip)` | Set tooltip | `->tooltip('Click me')` |
| `icon(string\|Closure $icon)` | Set icon | `->icon('heroicon-o-star')` |
| `color(string\|Closure $color)` | Set color | `->color('danger')` |
| `visible(bool\|Closure $visible)` | Show/hide | `->visible(fn() => true)` |
| `disabled(bool\|Closure $disabled)` | Enable/disable | `->disabled(fn() => false)` |

For complete Action documentation, see [Filament Actions Documentation](https://filamentphp.com/docs/actions).

## Real-World Examples

### Status Column with Badge

```php
ActionableColumn::make('status')
    ->label('Status')
    ->actionLabel('Change')
    ->badge()
    ->getStateUsing(fn ($record) => $record->status?->label)
    ->visible(fn () => auth()->user()->can('update-status'))
    ->tapAction(
        fn ($record) => Action::make('changeStatus')
            ->modal()
            ->form([
                Select::make('status')
                    ->options(Status::class)
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update($data);
            })
    )
```

### Date Column with Action

```php
ActionableColumn::make('due_date')
    ->label('Due Date')
    ->actionLabel('Set Date')
    ->getStateUsing(fn ($record) => $record->due_date?->format('m/d/Y'))
    ->tapAction(
        fn ($record) => Action::make('setDueDate')
            ->modal()
            ->form([
                DatePicker::make('due_date')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update($data);
            })
            ->tooltip('Set due date')
    )
```

### Delete Action with Confirmation

```php
ActionableColumn::make('actions')
    ->label('Actions')
    ->actionLabel('Delete')
    ->iconOnly()
    ->actionIcon('heroicon-o-trash')
    ->tapAction(
        fn ($record) => Action::make('delete')
            ->requiresConfirmation()
            ->modalHeading('Delete Record')
            ->modalDescription('This action cannot be undone.')
            ->action(fn ($record) => $record->delete())
            ->color('danger')
    )
```

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+

## Credits

- [Shreejan][link-author]

### Security

If you discover a security vulnerability within this package, please send an e-mail to shreezanpandit@gmail.com. All security vulnerabilities will be promptly addressed.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[link-author]: https://github.com/shreejanpandit
