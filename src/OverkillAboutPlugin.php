<?php

namespace Kuzonet\OverkillAbout;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Kuzonet\OverkillAbout\Actions\AboutAction;
use Kuzonet\OverkillAbout\Support\AboutConfig;

class OverkillAboutPlugin implements Plugin
{
    protected ?string $panelId = null;

    protected ?string $configKey = null;

    protected string | Closure | null $label = null;

    protected string | BackedEnum | Closure | null $icon = null;

    protected int | Closure | null $sort = null;

    protected Width | string | Closure | null $modalWidth = null;

    protected bool | Closure $isVisible = true;

    protected bool | Closure $isAuthorized = true;

    /**
     * `overkill-about`, or `overkill-about-<key>` with a config key, so several instances can share a panel.
     * Also used as the user menu action's name.
     */
    public function getId(): string
    {
        return $this->configKey === null ? 'overkill-about' : 'overkill-about-' . $this->configKey;
    }

    public function register(Panel $panel): void
    {
        $this->panelId = $panel->getId();

        $panel->userMenuItems([
            $this->getId() => AboutAction::make($this->getId())->plugin($this)->panelId($panel->getId()),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * @param  ?string  $key  config entry to show (`overkill-about.<key>`); defaults to the panel id
     */
    public static function make(?string $key = null): static
    {
        return app(static::class)->configKey($key);
    }

    public static function get(?string $key = null): static
    {
        /** @var static $plugin */
        $plugin = filament(static::make($key)->getId());

        return $plugin;
    }

    /**
     * Read `overkill-about.<key>` instead of `overkill-about.<panel-id>`. Also sets the plugin id and
     * action name, so set it before the plugin is registered on a panel.
     */
    public function configKey(?string $key): static
    {
        $this->configKey = $key;

        return $this;
    }

    public function getConfigKey(?string $panelId = null): ?string
    {
        return $this->configKey ?? $panelId ?? $this->panelId;
    }

    /**
     * The resolved about screen for this panel.
     *
     * @return array<string, mixed>
     */
    public function getScreen(?string $panelId = null): array
    {
        return AboutConfig::forPanel($this->getConfigKey($panelId));
    }

    public function label(string | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string | Closure | null
    {
        return $this->label;
    }

    public function icon(string | BackedEnum | Closure | null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string | BackedEnum | Closure | null
    {
        return $this->icon;
    }

    public function sort(int | Closure | null $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): int | Closure | null
    {
        return $this->sort;
    }

    public function modalWidth(Width | string | Closure | null $width): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function getModalWidth(): Width | string | Closure | null
    {
        return $this->modalWidth;
    }

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function getVisible(): bool | Closure
    {
        return $this->isVisible;
    }

    public function authorize(bool | Closure $condition = true): static
    {
        $this->isAuthorized = $condition;

        return $this;
    }

    public function getAuthorize(): bool | Closure
    {
        return $this->isAuthorized;
    }
}
