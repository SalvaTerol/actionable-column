<?php

namespace Shreejan\FilamentActionableColumns\Tables\Columns;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Concerns\CanCallAction;
use Illuminate\Support\Facades\Blade;

class ActionableColumn extends TextColumn implements HasEmbeddedView
{
    protected Action | Closure | null $tapAction = null;
    
    protected bool $clickableColumn = false;
    
    /**
     * Override getAction() to return our tapAction
     * When we have a record, evaluate Closure and return Action instance
     */
    public function getAction(): Closure | Action | null
    {
        $record = $this->getRecord();
        
        if ($record && $this->tapAction instanceof Closure) {
            $action = $this->evaluate($this->tapAction, ['record' => $record]);
            
            if ($action instanceof Action) {
                $this->prepareAndCacheAction($action);
                return $action;
            }
        }
        
        return $this->tapAction;
    }

    protected string | Closure | null $actionLabel = null;

    protected Closure | string | null $fallbackUrl = null;

    protected string | Heroicon | Closure | null $actionIcon = 'heroicon-o-pencil';

    protected IconSize | string | Closure | null $actionIconSize = IconSize::Small;
    
    protected string | Closure | null $actionIconColor = null;

    protected bool | Closure $showActionIcon = true;



    /**
     * Set the action - Filament Action directly!
     */
    public function tapAction(Action | Closure $action): self
    {
        $this->tapAction = $action;
        $this->action($action);
        
        // Only disable click if column is not set to be clickable
        if (! $this->clickableColumn) {
            $this->disabledClick();
        }

        return $this;
    }
    
    /**
     * Make the whole column (text + action) clickable
     * When enabled, clicking anywhere on the column will trigger the action
     * 
     * Note: Call this method BEFORE tapAction() for best results
     */
    public function clickableColumn(bool $clickable = true): self
    {
        $this->clickableColumn = $clickable;
        
        // If we already have an action, update the click behavior
        if ($this->tapAction !== null) {
            if ($clickable) {
                // Try to re-enable click using reflection
                try {
                    $reflection = new \ReflectionClass($this);
                    $property = $reflection->getProperty('isClickDisabled');
                    $property->setAccessible(true);
                    $property->setValue($this, false);
                } catch (\ReflectionException $e) {
                    // If reflection fails, the getUrl() method will handle it
                }
            } else {
                $this->disabledClick();
            }
        }
        
        return $this;
    }

    /**
     * Set action label
     */
    public function actionLabel(string | Closure | null $label): self
    {
        $this->actionLabel = $label;

        return $this;
    }

    /**
     * Set fallback URL
     */
    public function fallbackUrl(Closure | string | null $url): self
    {
        $this->fallbackUrl = $url;

        return $this;
    }

    /**
     * Set action icon
     */
    public function actionIcon(string | Heroicon | Closure | null $icon): self
    {
        $this->actionIcon = $icon;

        return $this;
    }

    /**
     * Set action icon size
     */
    public function actionIconSize(IconSize | string | Closure | null $size): self
    {
        $this->actionIconSize = $size;

        return $this;
    }
    
    /**
     * Set action icon color
     */
    public function actionIconColor(string | Closure | null $color): self
    {
        $this->actionIconColor = $color;

        return $this;
    }

    /**
     * Show/hide action icon
     */
    public function showActionIcon(bool | Closure $show = true): self
    {
        $this->showActionIcon = $show;

        return $this;
    }

    /**
     * Get the tap action - evaluates closure and caches the action
     */
    public function getTapAction(): ?Action
    {
        if ($this->tapAction === null) {
            return null;
        }
        
        $record = $this->getRecord();
        
        if ($this->tapAction instanceof Closure) {
            if (! $record) {
                return null;
            }
            
            $action = $this->evaluate($this->tapAction, ['record' => $record]);
            
            if (! ($action instanceof Action)) {
                return null;
            }
        } else {
            $action = $this->tapAction;
        }
        
        $this->prepareAndCacheAction($action);
        return $action;
    }
    
    /**
     * Prepare and cache the action for mountTableAction
     */
    protected function prepareAndCacheAction(Action $action): void
    {
        $table = $this->getTable();
        $livewire = $this->getLivewire();
        
        // Ensure action has a name
        $actionName = $action->getName();
        if (empty($actionName)) {
            $actionName = 'actionable-' . $this->getName();
            $action->name($actionName);
        }
        
        // Attach to table and livewire
        $action->table($table);
        if ($livewire instanceof \Livewire\Component) {
            $action->livewire($livewire);
        }
        
        // Cache the action on the table
        try {
            $reflection = new \ReflectionClass($table);
            try {
                $cachedActionsProperty = $reflection->getProperty('cachedActions');
                $cachedActionsProperty->setAccessible(true);
                $cachedActions = $cachedActionsProperty->getValue($table) ?? [];
                $cachedActions[$actionName] = $action;
                $cachedActionsProperty->setValue($table, $cachedActions);
            } catch (\ReflectionException $e) {
                try {
                    $cacheMethod = $reflection->getMethod('cacheAction');
                    $cacheMethod->setAccessible(true);
                    $cacheMethod->invoke($table, $action);
                } catch (\ReflectionException $e2) {
                    // Both methods failed, continue
                }
            }
        } catch (\ReflectionException $e) {
            // Reflection failed, continue
        }
    }

    public function getActionLabel(): string
    {
        return $this->evaluate($this->actionLabel) ?? '';
    }

    public function getFallbackUrl(): ?string
    {
        return $this->evaluate($this->fallbackUrl);
    }

    public function getActionIcon(): ?string
    {
        // Check if property is Heroicon enum before evaluation
        $rawIcon = $this->actionIcon;
        if ($rawIcon instanceof Heroicon) {
            $iconValue = $rawIcon->value;
            // Add heroicon-o- prefix if not already present
            if (! str_starts_with($iconValue, 'heroicon-')) {
                return 'heroicon-o-' . $iconValue;
            }
            return $iconValue;
        }
        
        // Evaluate if it's a Closure, otherwise use as-is
        $icon = $this->evaluate($rawIcon);
        
        // Also check after evaluation in case it was a Closure returning Heroicon
        if ($icon instanceof Heroicon) {
            $iconValue = $icon->value;
            if (! str_starts_with($iconValue, 'heroicon-')) {
                return 'heroicon-o-' . $iconValue;
            }
            return $iconValue;
        }
        
        return $icon;
    }

    public function getActionIconSize(): IconSize | string | null
    {
        return $this->evaluate($this->actionIconSize);
    }
    
    public function getActionIconColor(): ?string
    {
        return $this->evaluate($this->actionIconColor);
    }

    public function shouldShowActionIcon(): bool
    {
        return $this->evaluate($this->showActionIcon);
    }

    protected function getEmptyPlaceholder(): string
    {
        return config('filament-actionable-columns.placeholder', '—');
    }

    public function getUrl(mixed $state = null): ?string
    {
        // If column is clickable and has an action, return action URL
        if ($this->clickableColumn && $this->getTapAction() !== null) {
            return 'javascript:void(0);';
        }
        
        // If column is not clickable, return null to prevent default link behavior
        if ($this->getTapAction() !== null && ! $this->clickableColumn) {
            return null;
        }

        return $this->getFallbackUrl();
    }

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();
        $hasState = ! empty($state);
        $tapAction = $this->getTapAction();
        $isActionVisible = $tapAction !== null && $tapAction->isVisible();
        $placeholder = $this->getEmptyPlaceholder();
        $record = $this->getRecord();
        $isBadge = $this->isBadge();

        // Use the same structure as TextColumn for proper alignment
        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-ta-text',
                'fi-inline' => $this->isInline(),
            ]);

        $alignment = $this->getAlignment();
        $attributes = $attributes->class([
            ($alignment instanceof \Filament\Support\Enums\Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : ''),
        ]);

        ob_start();

        ?>
        <div <?= $attributes->toHtml() ?> wire:key="actionable-<?= $record?->getKey() ?? 'no-record' ?>-<?= $this->getName() ?>">
            <?php
            if ($isBadge) {
                // Badge is applied: use separated mode (badge + button connected)
                echo $this->renderSeparated($state, $hasState, $isActionVisible, $tapAction, $placeholder);
            } else {
                // No badge: use simple rendering (text + action icon)
                echo $this->renderSimple($state, $hasState, $isActionVisible, $tapAction, $placeholder);
            }
            ?>
        </div>
        <?php

        return ob_get_clean() ?: '';
    }

    protected function renderSeparated(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction, string $placeholder): string
    {
        // This method only works when badge is applied
        ob_start();

        ?>
        <?php
        // Get badge color from parent TextColumn using the proper method
        $badgeColor = $this->getColor($state);
        
        // Build badge classes - Filament uses fi-color fi-color-{color} for badge colors
        // Also need fi-text-color-* classes for proper text color and fi-size-sm for size
        $badgeClasses = 'fi-badge fi-size-sm';
        if ($badgeColor) {
            $badgeClasses .= ' fi-color fi-color-'.e($badgeColor).' fi-text-color-700 dark:fi-text-color-200';
        }
        if ($isActionVisible && $tapAction) {
            $badgeClasses .= ' fi-badge-connected';
        }
        
        $badgeStyle = $isActionVisible && $tapAction
            ? 'border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; border-right: none !important;'
            : '';
        $buttonBorderRadius = 'border-radius: 0 !important; border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; border-top-right-radius: 0.375rem !important; border-bottom-right-radius: 0.375rem !important; border-left: none !important;';
        $buttonBg = 'background-color: rgb(248 250 252) !important;';
        ?>
        <?php if (! $hasState && $isActionVisible && $tapAction) { ?>
            <?php
            // Empty state: Show "+ Add" button
            $actionName = $tapAction->getName();
            $recordKey = $this->getRecordKey();
            // Get color from column for hover state
            $columnColor = $this->getColor($state);
            $colorAttr = $columnColor ? 'data-color="'.e($columnColor).'"' : '';
            ?>
            <div class="fi-ta-col">
                <div class="fi-ta-text fi-ta-btn-placeholder">
                    <button
                        type="button"
                        wire:click.prevent.stop="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                        wire:loading.attr="disabled"
                        wire:target="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                        class="fi-ta-placeholder"
                        <?= $colorAttr ?>
                    >
                        <?php if ($this->getActionLabel()) { ?>
                            + <?= e($this->getActionLabel()) ?>
                        <?php } else { ?>
                            + Add
                        <?php } ?>
                    </button>
                </div>
            </div>
        <?php } else { ?>
            <?php
            // Prepare click handler for text if column is clickable
            $textClickHandler = '';
            $textClickStyle = '';
            if ($this->clickableColumn && $isActionVisible && $tapAction) {
                $actionName = $tapAction->getName();
                $recordKey = $this->getRecordKey();
                $textClickHandler = 'wire:click.prevent.stop="mountTableAction(\'' . e($actionName) . '\', \'' . e($recordKey) . '\')"';
                $textClickStyle = 'cursor: pointer;';
            }
            ?>
            <span class="fi-ta-text-item">
                <div class="fi-actionable-separated inline-flex items-center" style="gap: 0 !important;">
                    <?php if ($hasState) { ?>
                        <span 
                            class="<?= e($badgeClasses) ?>" 
                            style="display: inline-flex; align-items: center; height: 1.5rem; min-height: 1.5rem; max-height: 1.5rem; <?= $badgeStyle ?> <?= $textClickStyle ?>"
                            <?= $textClickHandler ?>
                        >
                            <?= e($this->formatState($state)) ?>
                        </span>
                <?php } else { ?>
                    <span 
                        class="text-zinc-400 dark:text-zinc-500 text-sm" 
                        style="<?= $textClickStyle ?>"
                        <?= $textClickHandler ?>
                    >
                        <?= e($placeholder) ?>
                    </span>
                <?php } ?>

                <?php if ($isActionVisible && $tapAction) { ?>
                    <?php
                    $tooltip = $tapAction->getTooltip();
                    $tooltipAttr = filled($tooltip) 
                        ? 'x-tooltip="'.htmlspecialchars($tooltip instanceof \Illuminate\Contracts\Support\Htmlable ? $tooltip->toHtml() : (string) $tooltip, ENT_QUOTES, 'UTF-8').'"'
                        : '';
                    $actionName = $tapAction->getName();
                    $recordKey = $this->getRecordKey();
                    $buttonHeight = '1.5rem';
                    // Remove button color if icon has its own color
                    $iconColor = $this->getActionIconColor();
                    $buttonColor = $iconColor ? '' : 'color: rgb(107 114 128) !important;';
                    ?>
                    <button
                        type="button"
                        wire:click.prevent.stop="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                        wire:loading.attr="disabled"
                        wire:target="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                        style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: <?= $buttonHeight ?> !important; height: <?= $buttonHeight ?> !important; min-width: <?= $buttonHeight ?> !important; min-height: <?= $buttonHeight ?> !important; <?= $buttonBorderRadius ?> border: 1px solid rgb(229 231 235) !important; <?= $buttonBg ?> <?= $buttonColor ?> transition: all 0.15s ease-in-out !important; flex-shrink: 0 !important; cursor: pointer !important; padding: 0 !important; margin: 0 !important;"
                        class="fi-action-btn hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-500"
                        <?= $tooltipAttr ?>
                    >
                        <?php if ($this->shouldShowActionIcon() && $this->getActionIcon()) { ?>
                            <?= $this->renderIcon() ?>
                        <?php } ?>
                    </button>
                    <?php } ?>
                </div>
            </span>
        <?php } ?>
        <?php

        return ob_get_clean();
    }

    protected function renderSimple(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction, string $placeholder): string
    {
        ob_start();

        ?>
        <?php
        // Get text color from parent TextColumn using the proper method
        $textColor = $this->getColor($state);
        
        // Build text color classes - use FilamentColor directly (same as ComponentAttributeBag macro does)
        $textColorClasses = '';
        if ($textColor) {
            $classes = \Filament\Support\Facades\FilamentColor::getComponentClasses(
                \Filament\Tables\View\Components\Columns\TextColumnComponent\ItemComponent::class,
                $textColor
            );
            $textColorClasses = is_array($classes) ? implode(' ', $classes) : (string) $classes;
        }
        $textClasses = 'fi-size-sm '.$textColorClasses;
        ?>
        <?php if ($isActionVisible && $tapAction) { ?>
            <?php if (! $hasState) { ?>
                <?php
                // Empty state: Show "+ Add" button
                $actionName = $tapAction->getName();
                $recordKey = $this->getRecordKey();
                // Get color from column for hover state
                $columnColor = $this->getColor($state);
                $colorAttr = $columnColor ? 'data-color="'.e($columnColor).'"' : '';
                ?>
                <div class="fi-ta-col">
                    <div class="fi-ta-text fi-ta-btn-placeholder">
                        <button
                            type="button"
                            wire:click.prevent.stop="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                            wire:loading.attr="disabled"
                            wire:target="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                            class="fi-ta-placeholder"
                            <?= $colorAttr ?>
                        >
                            <?php if ($this->getActionLabel()) { ?>
                                + <?= e($this->getActionLabel()) ?>
                            <?php } else { ?>
                                + Add
                            <?php } ?>
                        </button>
                    </div>
                </div>
            <?php } else { ?>
                <?php
                // Has state: Show text with action icon
                $tooltip = $tapAction->getTooltip();
                $tooltipAttr = filled($tooltip) 
                    ? 'x-tooltip="'.htmlspecialchars($tooltip instanceof \Illuminate\Contracts\Support\Htmlable ? $tooltip->toHtml() : (string) $tooltip, ENT_QUOTES, 'UTF-8').'"'
                    : '';
                // Prepare click handler for text if column is clickable
                $textClickHandler = '';
                $textClickStyle = '';
                if ($this->clickableColumn) {
                    $actionName = $tapAction->getName();
                    $recordKey = $this->getRecordKey();
                    $textClickHandler = 'wire:click.prevent.stop="mountTableAction(\'' . e($actionName) . '\', \'' . e($recordKey) . '\')"';
                    $textClickStyle = 'cursor: pointer;';
                }
                ?>
                <div class="inline-flex items-center" style="display: inline-flex !important; align-items: center !important; gap: 0.625rem !important;">
                    <span 
                        class="fi-ta-text-item <?= e($textColorClasses) ?>"
                        style="display: inline !important; <?= $textClickStyle ?>"
                        <?= $textClickHandler ?>
                    >
                        <span class="fi-size-sm">
                            <?= e($this->formatState($state)) ?>
                        </span>
                    </span>
                    <button
                        type="button"
                        wire:click.prevent.stop="mountTableAction('<?= e($tapAction->getName()) ?>', '<?= e($this->getRecordKey()) ?>')"
                        wire:loading.attr="disabled"
                        wire:target="mountTableAction('<?= e($tapAction->getName()) ?>', '<?= e($this->getRecordKey()) ?>')"
                        class="fi-action-btn inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors cursor-pointer"
                        style="display: inline-flex !important; flex-shrink: 0 !important;"
                        title="<?= e($tapAction->getLabel() ?: 'Edit') ?>"
                        <?= $tooltipAttr ?>
                    >
                        <?php if ($this->shouldShowActionIcon() && $this->getActionIcon()) { ?>
                            <?= $this->renderIcon() ?>
                        <?php } ?>
                    </button>
                </div>
            <?php } ?>
        <?php } else { ?>
            <?php if ($hasState) { ?>
                <div class="fi-ta-text-item fi-ta-text <?= e($textColorClasses) ?>">
                    <span class="fi-size-sm">
                        <?= e($this->formatState($state)) ?>
                    </span>
                </div>
            <?php } else { ?>
                <span class="text-zinc-400 dark:text-zinc-500 text-sm">
                    <?= e($placeholder) ?>
                </span>
            <?php } ?>
        <?php } ?>
        <?php

        return ob_get_clean();
    }

    protected function renderIcon(): string
    {
        $icon = $this->getActionIcon();
        $size = $this->getActionIconSize();

        if (! $icon) {
            return '';
        }

        // Map string values to IconSize enum cases
        $sizeMap = [
            'xs' => 'ExtraSmall',
            'sm' => 'Small',
            'md' => 'Medium',
            'lg' => 'Large',
            'xl' => 'ExtraLarge',
            '2xl' => 'TwoExtraLarge',
        ];

        if ($size instanceof IconSize) {
            $enumCase = $size->name;
        } else {
            $sizeValue = $size ?? 'sm';
            $enumCase = $sizeMap[$sizeValue] ?? 'Small';
        }

        $color = $this->getActionIconColor();
        
        // If color is set, apply it directly to the icon with !important via inline style
        if ($color) {
            // Map Filament color names to Tailwind colors
            $colorMap = [
                'success' => ['light' => 'rgb(22 163 74)', 'dark' => 'rgb(74 222 128)'], // green-600, green-400
                'danger' => ['light' => 'rgb(220 38 38)', 'dark' => 'rgb(248 113 113)'], // red-600, red-400
                'warning' => ['light' => 'rgb(217 119 6)', 'dark' => 'rgb(251 191 36)'], // amber-600, amber-400
                'info' => ['light' => 'rgb(37 99 235)', 'dark' => 'rgb(96 165 250)'], // blue-600, blue-400
                'primary' => ['light' => 'rgb(99 102 241)', 'dark' => 'rgb(165 180 252)'], // indigo-600, indigo-400
            ];
            
            $colors = $colorMap[$color] ?? ['light' => 'rgb(22 163 74)', 'dark' => 'rgb(74 222 128)'];
            $colorStyle = 'color: '.$colors['light'].' !important;';
            $colorClass = 'text-'.e($color).'-600 dark:text-'.e($color).'-400';
            
            $iconHtml = Blade::render(
                '<x-filament::icon icon="'.e($icon).'" class="inline '.$colorClass.'" style="'.$colorStyle.'" :size="\Filament\Support\Enums\IconSize::'.$enumCase.'" />'
            );
            return $iconHtml;
        }
        
        return Blade::render(
            '<x-filament::icon icon="'.e($icon).'" class="inline" :size="\Filament\Support\Enums\IconSize::'.$enumCase.'" />'
        );
    }
}
