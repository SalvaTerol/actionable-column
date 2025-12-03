<?php

namespace Shreejan\FilamentActionableColumns\Tables\Columns;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;

class ActionableColumn extends TextColumn implements HasEmbeddedView
{
    protected Action | Closure | null $tapAction = null;
    protected bool $clickableColumn = false;
    protected string | Closure | null $actionLabel = null;
    protected string | Heroicon | Closure | null $actionIcon = 'heroicon-o-pencil-square';
    protected IconSize | string | Closure | null $actionIconSize = IconSize::Small;
    protected string | Closure | null $actionIconColor = null;
    protected bool | Closure $showActionIcon = true;

    private const ICON_COLOR_MAP = [
        'success' => ['light' => 'rgb(22 163 74)', 'dark' => 'rgb(74 222 128)'],
        'danger' => ['light' => 'rgb(220 38 38)', 'dark' => 'rgb(248 113 113)'],
        'warning' => ['light' => 'rgb(217 119 6)', 'dark' => 'rgb(251 191 36)'],
        'info' => ['light' => 'rgb(37 99 235)', 'dark' => 'rgb(96 165 250)'],
        'primary' => ['light' => 'rgb(99 102 241)', 'dark' => 'rgb(165 180 252)'],
    ];

    private const ICON_SIZE_MAP = [
        'xs' => 'ExtraSmall',
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
        'xl' => 'ExtraLarge',
        '2xl' => 'TwoExtraLarge',
    ];

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

    public function tapAction(Action | Closure $action): self
    {
        $this->tapAction = $action;
        $this->action($action);
        
        if (! $this->clickableColumn) {
            $this->disabledClick();
        }

        return $this;
    }
    
    public function clickableColumn(bool $clickable = true): self
    {
        $this->clickableColumn = $clickable;
        
        if ($this->tapAction !== null) {
            if ($clickable) {
                try {
                    $reflection = new \ReflectionClass($this);
                    $property = $reflection->getProperty('isClickDisabled');
                    $property->setValue($this, false);
                } catch (\ReflectionException $e) {
                    // Reflection failed, getUrl() will handle it
                }
            } else {
                $this->disabledClick();
            }
        }
        
        return $this;
    }

    public function actionLabel(string | Closure | null $label): self
    {
        $this->actionLabel = $label;
        return $this;
    }

    public function actionIcon(string | Heroicon | Closure | null $icon): self
    {
        $this->actionIcon = $icon;
        return $this;
    }

    public function actionIconSize(IconSize | string | Closure | null $size): self
    {
        $this->actionIconSize = $size;
        return $this;
    }
    
    public function actionIconColor(string | Closure | null $color): self
    {
        $this->actionIconColor = $color;
        return $this;
    }

    public function showActionIcon(bool | Closure $show = true): self
    {
        $this->showActionIcon = $show;
        return $this;
    }

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
    
    protected function prepareAndCacheAction(Action $action): void
    {
        $table = $this->getTable();
        $livewire = $this->getLivewire();
        
        $actionName = $action->getName() ?: 'actionable-' . $this->getName();
        if ($action->getName() !== $actionName) {
            $action->name($actionName);
        }
        
        $action->table($table);
        if ($livewire instanceof Component) {
            $action->livewire($livewire);
        }
        
        try {
            $reflection = new \ReflectionClass($table);
            try {
                $property = $reflection->getProperty('cachedActions');
                $cachedActions = $property->getValue($table) ?? [];
                $cachedActions[$actionName] = $action;
                $property->setValue($table, $cachedActions);
            } catch (\ReflectionException $e) {
                try {
                    $method = $reflection->getMethod('cacheAction');
                    $method->invoke($table, $action);
                } catch (\ReflectionException $e2) {
                    // Both methods failed
                }
            }
        } catch (\ReflectionException $e) {
            // Reflection failed
        }
    }

    public function getActionLabel(): string
    {
        return $this->evaluate($this->actionLabel) ?? '';
    }

    public function getActionIcon(): ?string
    {
        $icon = $this->evaluate($this->actionIcon);
        
        if ($icon instanceof Heroicon) {
            return $this->normalizeHeroicon($icon->value);
        }
        
        return $icon;
    }

    protected function normalizeHeroicon(string $value): string
    {
        return str_starts_with($value, 'heroicon-') ? $value : 'heroicon-o-' . $value;
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
        if (! $this->getTapAction()) {
            return null;
        }
        
        return $this->clickableColumn ? 'javascript:void(0);' : null;
    }

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();
        $hasState = ! empty($state);
        $tapAction = $this->getTapAction();
        $isActionVisible = $tapAction?->isVisible() ?? false;
        $isBadge = $this->isBadge();

        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-ta-text',
                'fi-inline' => $this->isInline(),
            ])
            ->class([
                ($alignment = $this->getAlignment()) instanceof \Filament\Support\Enums\Alignment
                    ? "fi-align-{$alignment->value}"
                    : (is_string($alignment) ? $alignment : ''),
            ]);

        ob_start();
        ?>
        <div <?= $attributes->toHtml() ?> wire:key="actionable-<?= $this->getRecord()?->getKey() ?? 'no-record' ?>-<?= $this->getName() ?>">
            <?= $isBadge 
                ? $this->renderSeparated($state, $hasState, $isActionVisible, $tapAction)
                : $this->renderSimple($state, $hasState, $isActionVisible, $tapAction)
            ?>
        </div>
        <?php
        return ob_get_clean() ?: '';
    }

    protected function renderSeparated(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction): string
    {
        ob_start();
        
        $badgeColor = $this->getColor($state);
        $hasAction = $isActionVisible && $tapAction;
        $shouldShowActionButton = $hasAction && $this->shouldShowActionIcon();
        
        ?>
        <?php if (! $hasState && $hasAction) { ?>
            <?= $this->renderEmptyStateButton($tapAction) ?>
        <?php } else { ?>
            <?php [$clickHandler, $clickStyle] = $this->getClickHandlers($hasAction, $tapAction); ?>
            <span class="fi-ta-text-item">
                <div class="fi-actionable-separated inline-flex items-center" style="gap: 0 !important;">
                    <?php if ($hasState) { ?>
                        <span 
                            class="<?= e($this->buildBadgeClasses($badgeColor, $shouldShowActionButton)) ?>" 
                            style="display: inline-flex; align-items: center; height: 1.5rem; min-height: 1.5rem; max-height: 1.5rem; <?= $shouldShowActionButton ? 'border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;' : '' ?> <?= $clickStyle ?>"
                            <?= $clickHandler ?>
                        >
                            <?= e($this->formatState($state)) ?>
                        </span>
                    <?php } else { ?>
                        <span class="text-zinc-400 dark:text-zinc-500 text-sm" style="<?= $clickStyle ?>" <?= $clickHandler ?>>
                            <?= e($this->getEmptyPlaceholder()) ?>
                        </span>
                    <?php } ?>

                    <?php if ($shouldShowActionButton) { ?>
                        <?= $this->renderActionButton($tapAction, $badgeColor, true) ?>
                    <?php } ?>
                </div>
            </span>
        <?php } ?>
        <?php
        return ob_get_clean();
    }

    protected function renderSimple(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction): string
    {
        ob_start();
        
        $textColorClasses = $this->buildTextColorClasses($this->getColor($state));
        $hasAction = $isActionVisible && $tapAction;
        $shouldShowActionButton = $hasAction && $this->shouldShowActionIcon();
        
        ?>
        <?php if ($hasAction) { ?>
            <?php if (! $hasState) { ?>
                <?= $this->renderEmptyStateButton($tapAction) ?>
            <?php } else { ?>
                <?php [$clickHandler, $clickStyle] = $this->getClickHandlers($hasAction, $tapAction); ?>
                <div class="inline-flex items-center" style="display: inline-flex !important; align-items: center !important; gap: 0.625rem !important;">
                    <span class="fi-ta-text-item <?= e($textColorClasses) ?>" style="display: inline !important; <?= $clickStyle ?>" <?= $clickHandler ?>>
                        <span class="fi-size-sm"><?= e($this->formatState($state)) ?></span>
                    </span>
                    <?php if ($shouldShowActionButton) { ?>
                        <?= $this->renderActionButton($tapAction, null, false) ?>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <?php if ($hasState) { ?>
                <div class="fi-ta-text-item fi-ta-text <?= e($textColorClasses) ?>">
                    <span class="fi-size-sm"><?= e($this->formatState($state)) ?></span>
                </div>
            <?php } else { ?>
                <span class="text-zinc-400 dark:text-zinc-500 text-sm"><?= e($this->getEmptyPlaceholder()) ?></span>
            <?php } ?>
        <?php } ?>
        <?php
        return ob_get_clean();
    }

    protected function buildBadgeClasses(?string $color, bool $hasAction): string
    {
        $classes = 'fi-badge fi-size-sm';
        
        if ($color) {
            $classes .= ' fi-color fi-color-'.e($color).' fi-text-color-700 dark:fi-text-color-200';
        } else {
            $classes .= ' fi-color-gray fi-text-color-700 dark:fi-text-color-200';
        }
        
        if ($hasAction) {
            $classes .= ' fi-badge-connected';
        }
        
        return $classes;
    }

    protected function buildTextColorClasses(?string $color): string
    {
        if (! $color) {
            return '';
        }
        
        $classes = \Filament\Support\Facades\FilamentColor::getComponentClasses(
            \Filament\Tables\View\Components\Columns\TextColumnComponent\ItemComponent::class,
            $color
        );
        
        return is_array($classes) ? implode(' ', $classes) : (string) $classes;
    }

    protected function getClickHandlers(bool $isActionVisible, ?Action $tapAction): array
    {
        if (! ($this->clickableColumn && $isActionVisible && $tapAction)) {
            return ['', ''];
        }
        
        $actionName = $tapAction->getName();
        $recordKey = $this->getRecordKey();
        
        return [
            'wire:click.prevent.stop="mountTableAction(\'' . e($actionName) . '\', \'' . e($recordKey) . '\')"',
            'cursor: pointer;'
        ];
    }

    protected function renderEmptyStateButton(Action $tapAction): string
    {
        $actionName = $tapAction->getName();
        $recordKey = $this->getRecordKey();
        $color = $this->getColor(null);
        
        ob_start();
        ?>
        <div class="fi-ta-text-item">
            <button
                type="button"
                wire:click.prevent.stop="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                wire:loading.attr="disabled"
                wire:target="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
                class="fi-ta-placeholder"
                <?= $color ? 'data-color="'.e($color).'"' : '' ?>
            >
                + <?= e($this->getActionLabel() ?: 'Add') ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function renderActionButton(Action $tapAction, ?string $badgeColor, bool $isSeparated): string
    {
        $tooltip = $tapAction->getTooltip();
        $tooltipAttr = filled($tooltip)
            ? 'x-tooltip="{ content: \''.addslashes(e($tooltip instanceof \Illuminate\Contracts\Support\Htmlable ? $tooltip->toHtml() : (string) $tooltip)).'\' }"'
            : '';
        
        $actionName = $tapAction->getName();
        $recordKey = $this->getRecordKey();
        $iconColor = $this->getActionIconColor();
        $badgeColorAttr = 'data-badge-color="'.e($badgeColor ?: 'gray').'"';
        
        if ($isSeparated) {
            $buttonHeight = '1.5rem';
            $borderRadius = 'border-radius: 0 !important; border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; border-top-right-radius: 0.375rem !important; border-bottom-right-radius: 0.375rem !important;';
            $buttonColor = $iconColor ? '' : 'color: rgb(107 114 128) !important;';
            $style = "display: inline-flex !important; align-items: center !important; justify-content: center !important; width: {$buttonHeight} !important; height: {$buttonHeight} !important; min-width: {$buttonHeight} !important; min-height: {$buttonHeight} !important; {$borderRadius} {$buttonColor} transition: all 0.15s ease-in-out !important; flex-shrink: 0 !important; cursor: pointer !important; padding: 0 !important; margin: 0 !important;";
            $class = 'fi-action-btn';
        } else {
            $style = 'display: inline-flex !important; flex-shrink: 0 !important;';
            $class = 'fi-action-btn inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors cursor-pointer';
        }
        
        ob_start();
        ?>
        <button
            type="button"
            wire:click.prevent.stop="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
            wire:loading.attr="disabled"
            wire:target="mountTableAction('<?= e($actionName) ?>', '<?= e($recordKey) ?>')"
            style="<?= $style ?>"
            class="<?= $class ?>"
            <?= $badgeColorAttr ?>
            <?= $tooltipAttr ?>
        >
            <?php if ($this->shouldShowActionIcon() && $this->getActionIcon()) { ?>
                <?= $this->renderIcon() ?>
            <?php } ?>
        </button>
        <?php
        return ob_get_clean();
    }

    protected function renderIcon(): string
    {
        $icon = $this->getActionIcon();
        if (! $icon) {
            return '';
        }

        $size = $this->getActionIconSize();
        $enumCase = $size instanceof IconSize
            ? $size->name
            : (self::ICON_SIZE_MAP[$size ?? 'sm'] ?? 'Small');

        $color = $this->getActionIconColor();
        
        if ($color) {
            $colors = self::ICON_COLOR_MAP[$color] ?? ['light' => 'rgb(22 163 74)', 'dark' => 'rgb(74 222 128)'];
            
            return Blade::render(
                '<x-filament::icon icon="'.e($icon).'" class="inline text-'.e($color).'-600 dark:text-'.e($color).'-400 dark:brightness-125" style="color: '.$colors['light'].' !important;" :size="\Filament\Support\Enums\IconSize::'.$enumCase.'" />'
            );
        }
        
        return Blade::render(
            '<x-filament::icon icon="'.e($icon).'" class="inline dark:brightness-150 dark:opacity-100" :size="\Filament\Support\Enums\IconSize::'.$enumCase.'" />'
        );
    }
}
