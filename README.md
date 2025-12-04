# Filament Actionable Columns

The **Filament Actionable Columns** plugin allows you to add interactive action buttons to Filament table columns. Display text or badges with seamlessly connected action buttons using Filament's native Action system.

## Features

- Badge mode with connected action button
- Simple text mode with side-by-side action button
- Customizable action icons and colors
- Entire column clickable option
- Empty state "+ Add" button
- Support for any Filament Action type (edit, delete, approve, etc.)
- Independent icon and badge/text colors
- Seamless integration with Filament tables

## Installation

You can install the package via composer:

```bash
composer require shreejan/filament-actionable-columns
```

## Usage

```php
use Shreejan\FilamentActionableColumns\Tables\Columns\ActionableColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

ActionableColumn::make('status')
    ->badge()                                    // Display as badge (or remove for simple text)
    ->color('success')                           // Badge/text color: success, danger, warning, info, primary
    ->actionIcon(Heroicon::PencilSquare)         // Action button icon (Heroicon enum or string)
    ->actionIconColor('warning')                 // Icon color (independent from badge color)
    ->clickableColumn()                          // Make entire column clickable (or remove for button-only)
    ->tapAction(
        Action::make('changeStatus')              // Any Filament Action: edit, delete, approve, etc.
            ->label('Change Status')
            ->tooltip('Click to change status')
            ->schema([
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ])
            ->fillForm(fn ($record) => [
                'status' => $record->status,
            ])
            ->action(function ($record, array $data) {
                $record->update($data);
            })
    )
```

## Configuration Options

| Method | Description | Default |
|--------|-------------|---------|
| `tapAction(Action\|Closure)` | Set any Filament Action (edit, delete, approve, etc.) | - |
| `badge()` | Display as badge (connected to action button) | Simple text mode |
| `color(string)` | Set badge/text color: `success`, `danger`, `warning`, `info`, `primary` | - |
| `actionIcon(Heroicon\|string)` | Set action button icon | `heroicon-o-pencil-square` |
| `actionIconColor(string)` | Set icon color (independent from badge color) | - |
| `actionIconSize(IconSize\|string)` | Set icon size: `xs`, `sm`, `md`, `lg`, `xl`, `2xl` | `sm` |
| `actionLabel(string)` | Custom "+ Add" button label for empty state | "Add" |
| `clickableColumn()` | Make entire column clickable (not just button) | Button only |
| `showActionIcon(bool)` | Show/hide action button (empty state button always shows) | `true` |

All standard `TextColumn` methods are available: `searchable()`, `sortable()`, `limit()`, `date()`, `formatStateUsing()`, etc.

## Credits

- [Shreejan][link-author]

### Security

If you discover a security vulnerability within this package, please send an e-mail to shreezanpandit@gmail.com. All security vulnerabilities will be promptly addressed.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

**Made with ❤️ by [Shreejan](https://github.com/shreejanpandit)**

[link-author]: https://github.com/shreejanpandit
