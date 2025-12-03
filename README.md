# Filament Actionable Columns

A powerful Filament v4 package that adds interactive action buttons to table columns. Display text or badges with seamlessly connected action buttons, all using Filament's native Action system.

## Features

- 🎨 **Smart Layout System**: Automatically adapts based on `->badge()` usage
  - **With Badge**: Seamlessly connected badge and action button
  - **Without Badge**: Text with action button side-by-side
- ⚡ **Full Filament Action Support**: Use all Filament Action methods (`modal()`, `url()`, `action()`, `requiresConfirmation()`, etc.)
- 🎯 **Customizable**: Icon colors, sizes, labels, and column colors
- 🔘 **Clickable Options**: Make entire column clickable or just the action button
- 📱 **Empty States**: Beautiful "+ Add" buttons for empty values
- 🎨 **Color Integration**: Action button hover colors match column colors

## Installation

```bash
composer require shreejan/filament-actionable-columns
```

The package will auto-register. No additional configuration needed.

## Quick Start

```php
use Shreejan\FilamentActionableColumns\Tables\Columns\ActionableColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

ActionableColumn::make('description')
    ->tapAction(
        Action::make('edit')
            ->form([
                TextInput::make('description')->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update($data);
            })
    )
```

## Basic Usage

### Simple Text with Action

```php
ActionableColumn::make('description')
    ->limit(50)
    ->tapAction(
        Action::make('edit')
            ->form([
                TextInput::make('description')->required(),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

### Badge with Action

```php
ActionableColumn::make('status')
    ->badge()
    ->color('success')
    ->tapAction(
        Action::make('changeStatus')
            ->form([
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->required(),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

## Configuration Methods

### Action Configuration

| Method | Description | Example |
|--------|-------------|---------|
| `tapAction(Action\|Closure $action)` | Set the Filament Action | `->tapAction(Action::make('edit')...)` |
| `actionLabel(string\|Closure $label)` | Custom label for "+ Add" button | `->actionLabel('Add Note')` |
| `clickableColumn(bool $clickable)` | Make entire column clickable | `->clickableColumn()` |

### Icon Configuration

| Method | Description | Example |
|--------|-------------|---------|
| `actionIcon(string\|Heroicon\|Closure $icon)` | Set action icon | `->actionIcon(Heroicon::Bookmark)` |
| `actionIconSize(IconSize\|string $size)` | Set icon size | `->actionIconSize(IconSize::Medium)` |
| `actionIconColor(string\|Closure $color)` | Set icon color | `->actionIconColor('success')` |
| `showActionIcon(bool $show)` | Show/hide icon | `->showActionIcon(false)` |

### Styling

| Method | Description | Example |
|--------|-------------|---------|
| `badge()` | Display as badge | `->badge()` |
| `color(string\|Closure $color)` | Set text/badge color | `->color('danger')` |
| `limit(int $length)` | Limit text length | `->limit(50)` |

All standard `TextColumn` methods are also available (`searchable()`, `sortable()`, `formatStateUsing()`, etc.).

## Examples

### Status Column with Badge

```php
ActionableColumn::make('status')
    ->badge()
    ->color('success')
    ->actionIcon(Heroicon::PencilSquare)
    ->actionIconColor('success')
    ->tapAction(
        Action::make('changeStatus')
            ->modal()
            ->form([
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

### Editable Description

```php
ActionableColumn::make('description')
    ->searchable()
    ->limit(30)
    ->color('primary')
    ->actionIcon(Heroicon::Pencil)
    ->clickableColumn() // Entire column is clickable
    ->tapAction(
        Action::make('edit')
            ->form([
                Textarea::make('description')
                    ->required()
                    ->rows(4),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

### Delete Action with Confirmation

```php
ActionableColumn::make('actions')
    ->actionIcon(Heroicon::Trash)
    ->actionIconColor('danger')
    ->tapAction(
        Action::make('delete')
            ->requiresConfirmation()
            ->modalHeading('Delete Record')
            ->modalDescription('Are you sure? This action cannot be undone.')
            ->action(fn ($record) => $record->delete())
            ->color('danger')
    )
```

### URL Redirect

```php
ActionableColumn::make('view')
    ->actionLabel('View Details')
    ->tapAction(
        Action::make('view')
            ->url(fn ($record) => route('items.show', $record))
            ->openUrlInNewTab()
    )
```

### With Tooltip

```php
ActionableColumn::make('notes')
    ->actionIcon(Heroicon::InformationCircle)
    ->tapAction(
        Action::make('viewNotes')
            ->modal()
            ->form([...])
            ->tooltip('Click to view notes')
    )
```

## Layout Behavior

### With `->badge()`

When `->badge()` is applied:
- Text displays as a badge with rounded corners
- Action button seamlessly connects to the badge (no gap)
- Perfect for status indicators, tags, or categorized data

```php
ActionableColumn::make('status')
    ->badge()
    ->color('success')
    ->tapAction(...)
```

### Without `->badge()`

When `->badge()` is NOT applied:
- Text displays normally
- Action button appears with spacing
- Perfect for descriptions, notes, or general text

```php
ActionableColumn::make('description')
    ->tapAction(...)
```

## Empty State

When a column has no value, a "+ Add" button is automatically displayed:

- Button text: "+ Add" (or custom via `->actionLabel()`)
- Hover color: Matches column color if `->color()` is set, otherwise black
- Styled to match Filament's design system

```php
ActionableColumn::make('notes')
    ->actionLabel('Add Note') // Custom label: "+ Add Note"
    ->color('success') // Hover will be green
    ->tapAction(...)
```

## Color System

### Column Colors

Use `->color()` to set text/badge colors:

```php
ActionableColumn::make('status')
    ->color('success')  // Green
    ->color('danger')   // Red
    ->color('warning')  // Amber
    ->color('info')     // Blue
    ->color('primary')  // Primary theme color
```

### Icon Colors

Use `->actionIconColor()` to set icon colors independently:

```php
ActionableColumn::make('status')
    ->badge()
    ->color('success')           // Badge is green
    ->actionIconColor('danger')   // Icon is red
    ->tapAction(...)
```

### Empty State Hover Colors

The "+ Add" button hover color automatically matches the column color:

- If `->color('success')` is set → hover is green
- If `->color('danger')` is set → hover is red
- If no color is set → hover is black

## Clickable Options

### Action Button Only (Default)

Only the action button triggers the action:

```php
ActionableColumn::make('description')
    ->tapAction(...) // Only button is clickable
```

### Entire Column Clickable

Make the entire column (text + button) clickable:

```php
ActionableColumn::make('description')
    ->clickableColumn() // Entire column is clickable
    ->tapAction(...)
```

**Note**: Call `->clickableColumn()` before `->tapAction()` for best results.

## Using Filament Actions

Since `tapAction()` accepts Filament's `Action` class, you can use all Action methods:

```php
Action::make('edit')
    ->modal()                    // Open modal
    ->form([...])                // Form fields
    ->action(fn ($r, $d) => ...) // Execute action
    ->requiresConfirmation()      // Require confirmation
    ->tooltip('Edit record')      // Tooltip
    ->icon('heroicon-o-pencil')   // Icon
    ->color('primary')            // Color
    ->visible(fn () => true)     // Visibility
    ->disabled(fn () => false)    // Disabled state
```

For complete Action documentation, see [Filament Actions](https://filamentphp.com/docs/actions).

## Real-World Examples

### Task Status Management

```php
ActionableColumn::make('status')
    ->badge()
    ->color(fn ($record) => match($record->status) {
        'completed' => 'success',
        'in_progress' => 'warning',
        'pending' => 'info',
        default => 'gray',
    })
    ->actionIcon(Heroicon::ArrowPath)
    ->tapAction(
        Action::make('updateStatus')
            ->form([
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ])
                    ->required(),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

### Quick Edit Description

```php
ActionableColumn::make('description')
    ->searchable()
    ->sortable()
    ->limit(50)
    ->color('primary')
    ->actionIcon(Heroicon::Pencil)
    ->clickableColumn()
    ->tapAction(
        Action::make('quickEdit')
            ->form([
                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->fillForm(fn ($record) => [
                'description' => $record->description,
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

### File Attachment

```php
ActionableColumn::make('attachment')
    ->actionLabel('Upload')
    ->actionIcon(Heroicon::PaperClip)
    ->tapAction(
        Action::make('upload')
            ->form([
                FileUpload::make('file')
                    ->required()
                    ->acceptedFileTypes(['image/*', 'application/pdf']),
            ])
            ->action(fn ($record, $data) => $record->update($data))
    )
```

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/shreejanpandit/filament-actionable-columns).

## Security

If you discover a security vulnerability, please email shreezanpandit@gmail.com. All security vulnerabilities will be promptly addressed.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

**Made with ❤️ by [Shreejan](https://github.com/shreejanpandit)**
