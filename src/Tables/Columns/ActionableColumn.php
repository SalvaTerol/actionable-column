<?php

namespace Shreejan\FilamentActionableColumns\Tables\Columns;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;

class ActionableColumn extends TextColumn implements HasEmbeddedView
{
    // Layout Modes
    public const LAYOUT_SEPARATED = 'separated';
    public const LAYOUT_INLINE = 'inline';
    public const LAYOUT_ICON_ONLY = 'icon-only';

    protected string $displayMode = self::LAYOUT_SEPARATED;

    protected Action | Closure | null $tapAction = null;

    protected string | Closure | null $actionLabel = null;

    protected Closure | string | null $fallbackUrl = null;

    protected string | Closure | null $actionIcon = 'heroicon-o-pencil';

    protected IconSize | string | Closure | null $actionIconSize = IconSize::Small;

    protected bool | Closure $showActionIcon = true;

    protected bool | Closure $showLoadingState = true;

    protected int $loadingStateDuration = 3000;

    /**
     * Set display mode
     */
    public function displayMode(string $mode): self
    {
        $this->displayMode = $mode;

        return $this;
    }

    /**
     * Set to separated mode (default)
     */
    public function separated(): self
    {
        return $this->displayMode(self::LAYOUT_SEPARATED);
    }

    /**
     * Set to inline mode
     */
    public function displayInline(): self
    {
        return $this->displayMode(self::LAYOUT_INLINE);
    }

    /**
     * Set to icon-only mode
     */
    public function iconOnly(): self
    {
        return $this->displayMode(self::LAYOUT_ICON_ONLY);
    }

    /**
     * Set the action - just use Filament Action directly!
     */
    public function tapAction(Action | Closure $action): self
    {
        $this->tapAction = $action;

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
    public function actionIcon(string | Closure | null $icon): self
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
     * Show/hide action icon
     */
    public function showActionIcon(bool | Closure $show = true): self
    {
        $this->showActionIcon = $show;

        return $this;
    }

    /**
     * Show/hide loading state
     */
    public function showLoadingState(bool | Closure $show = true, int $duration = 3000): self
    {
        $this->showLoadingState = $show;
        $this->loadingStateDuration = $duration;

        return $this;
    }

    // Getters
    public function getTapAction(): ?Action
    {
        return $this->evaluate($this->tapAction);
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
        return $this->evaluate($this->actionIcon);
    }

    public function getActionIconSize(): IconSize | string | null
    {
        return $this->evaluate($this->actionIconSize);
    }

    public function shouldShowActionIcon(): bool
    {
        return $this->evaluate($this->showActionIcon);
    }

    public function shouldShowLoadingState(): bool
    {
        return $this->evaluate($this->showLoadingState);
    }

    public function getLoadingStateDuration(): int
    {
        return $this->loadingStateDuration;
    }

    public function getUrl(mixed $state = null): ?string
    {
        if ($this->getTapAction() !== null) {
            return 'javascript:void(0);';
        }

        return $this->getFallbackUrl();
    }

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();
        $hasState = ! empty($state);
        $tapAction = $this->getTapAction();
        $isActionVisible = $tapAction !== null && $tapAction->isVisible();
        $displayMode = $this->displayMode;

        ob_start();

        ?>
        <div class="fi-actionable-column">
            <?php if ($displayMode === self::LAYOUT_SEPARATED) { ?>
                <?= $this->renderSeparated($state, $hasState, $isActionVisible, $tapAction) ?>
            <?php } elseif ($displayMode === self::LAYOUT_INLINE) { ?>
                <?= $this->renderInline($state, $hasState, $isActionVisible, $tapAction) ?>
            <?php } else { ?>
                <?= $this->renderIconOnly($hasState, $isActionVisible, $tapAction) ?>
            <?php } ?>
        </div>
        <?php

        return ob_get_clean() ?: '';
    }

    protected function renderSeparated(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction): string
    {
        ob_start();

        ?>
        <div class="flex items-center gap-2">
            <?php if ($hasState) { ?>
                <span class="fi-ta-text-item fi-ta-text <?= $this->isBadge() ? 'fi-badge' : '' ?>">
                    <?= e($state) ?>
                </span>
            <?php } else { ?>
                <span class="text-zinc-400 dark:text-zinc-500 text-sm">
                    <?= e(__('panel.placeholder.empty', [], 'en')) ?>
                </span>
            <?php } ?>

            <?php if ($isActionVisible && $tapAction) { ?>
                <?= $tapAction->toHtml() ?>
            <?php } ?>
        </div>
        <?php

        return ob_get_clean();
    }

    protected function renderInline(mixed $state, bool $hasState, bool $isActionVisible, ?Action $tapAction): string
    {
        ob_start();

        ?>
        <?php if ($isActionVisible && $tapAction) { ?>
            <?php if (! $hasState) { ?>
                <div class="fi-ta-col">
                    <div class="fi-ta-text fi-ta-btn-placeholder">
                        <p class="fi-ta-placeholder">+ <?= e($this->getActionLabel()) ?></p>
                    </div>
                    <?= $tapAction->toHtml() ?>
                </div>
            <?php } else { ?>
                <div class="fi-ta-text-item fi-ta-text-has-badges fi-ta-text cursor-pointer">
                    <span class="fi-color fi-color-primary fi-text-color-500 dark:fi-text-color-200 fi-size-sm <?= $this->isBadge() ? 'fi-badge' : '' ?>">
                        <?= e($state) ?>
                        <?php if ($this->shouldShowActionIcon()) { ?>
                            <?= $this->renderIcon() ?>
                        <?php } ?>
                    </span>
                    <?= $tapAction->toHtml() ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <?php if ($hasState) { ?>
                <div class="fi-ta-text-item fi-ta-text-has-badges fi-ta-text">
                    <span class="fi-color fi-color-primary fi-text-color-500 dark:fi-text-color-200 fi-size-sm <?= $this->isBadge() ? 'fi-badge' : '' ?>">
                        <?= e($state) ?>
                    </span>
                </div>
            <?php } else { ?>
                <span class="text-zinc-400 dark:text-zinc-500 px-3 py-1 text-sm">
                    <?= e(__('panel.placeholder.empty', [], 'en')) ?>
                </span>
            <?php } ?>
        <?php } ?>
        <?php

        return ob_get_clean();
    }

    protected function renderIconOnly(bool $hasState, bool $isActionVisible, ?Action $tapAction): string
    {
        ob_start();

        ?>
        <?php if ($isActionVisible && $tapAction) { ?>
            <?= $tapAction->toHtml() ?>
        <?php } else { ?>
            <?php if ($hasState) { ?>
                <span class="fi-ta-text-item fi-ta-text">
                    <span class="fi-color fi-color-primary fi-text-color-500 dark:fi-text-color-200 fi-size-sm">
                        <?= e($this->getState()) ?>
                    </span>
                </span>
            <?php } else { ?>
                <span class="text-zinc-400 dark:text-zinc-500 text-sm">
                    <?= e(__('panel.placeholder.empty', [], 'en')) ?>
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

        $sizeValue = $size instanceof IconSize ? $size->value : ($size ?? 'sm');

        return Blade::render(
            '<x-filament::icon icon="'.e($icon).'" class="inline" :size="\Filament\Support\Enums\IconSize::'.ucfirst($sizeValue).'" />'
        );
    }
}