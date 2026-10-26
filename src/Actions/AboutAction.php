<?php

namespace Kuzonet\OverkillAbout\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Kuzonet\OverkillAbout\OverkillAboutPlugin;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class AboutAction extends Action
{
    protected ?OverkillAboutPlugin $plugin = null;

    protected ?string $panelId = null;

    public static function getDefaultName(): ?string
    {
        return 'overkill-about';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (): string => $this->evaluate($this->getPlugin()->getLabel())
                ?? $this->text($this->screen('menu.label'))
                ?? __('overkill-about::overkill-about.menu.label'))
            ->icon(fn () => $this->evaluate($this->getPlugin()->getIcon())
                ?? $this->screenString('menu.icon')
                ?? 'heroicon-o-information-circle')
            ->sort(function (): ?int {
                $sort = $this->evaluate($this->getPlugin()->getSort()) ?? $this->screen('menu.sort');

                return is_numeric($sort) ? (int) $sort : null;
            })
            ->visible(fn (): bool => $this->passesEnabledFlag($this->screen('enabled'))
                && (bool) $this->evaluate($this->getPlugin()->getVisible()))
            ->authorize(fn (): bool => (bool) $this->evaluate($this->getPlugin()->getAuthorize()))
            ->modalHeading(fn (): string => $this->text($this->screen('modal.heading'))
                ?? (filled($name = $this->getAppName())
                    ? __('overkill-about::overkill-about.modal.heading', ['name' => $name])
                    : __('overkill-about::overkill-about.menu.label')))
            ->modalDescription(fn (): ?string => $this->text($this->screen('modal.description')))
            ->modalWidth(fn (): Width | string => $this->evaluate($this->getPlugin()->getModalWidth())
                ?? $this->screenString('modal.width')
                ?? Width::Large)
            ->slideOver(fn (): bool => (bool) $this->screen('modal.slide_over'))
            ->modalSubmitAction(false)
            // Lazy: panels are built during service-provider registration, before
            // translations load; calling __() here would cache an empty group.
            ->modalCancelActionLabel(fn (): string => __('overkill-about::overkill-about.modal.close'))
            ->modalContent(fn (): View => view($this->getLayoutView(), ['sections' => $this->getAboutSections()]));
    }

    /**
     * `modal.layout`: 'component' renders each section as a Filament section (with description and
     * collapsing); anything else uses the standard callout layout.
     */
    public function getLayoutView(): string
    {
        return $this->screenString('modal.layout') === 'component'
            ? 'overkill-about::about-component'
            : 'overkill-about::about';
    }

    public function plugin(OverkillAboutPlugin $plugin): static
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * The panel this action was registered on. Kept on the action, not the plugin, so one plugin
     * instance shared by several panels still reads each panel's own entry.
     */
    public function panelId(?string $panelId): static
    {
        $this->panelId = $panelId;

        return $this;
    }

    public function getPlugin(): OverkillAboutPlugin
    {
        return $this->plugin ?? throw new LogicException('The about action has no plugin. Register it via OverkillAboutPlugin.');
    }

    /**
     * One section per entry in the panel's `sections`, in config order, ready for the view.
     *
     * @return list<array{title: string, icon: ?string, description: ?string, collapsible: bool, collapsed: bool, items: list<array{label: string, value: ?string, icon: ?string, url: ?string, new_tab: bool}>}>
     */
    public function getAboutSections(): array
    {
        $sections = $this->screen('sections');
        $result = [];

        foreach (is_array($sections) ? $sections : [] as $key => $section) {
            if (
                ! is_array($section)
                || ! is_array($section['items'] ?? null)
                || ! $this->passesEnabledFlag($section['enabled'] ?? null)
            ) {
                continue;
            }

            $items = [];

            foreach ($section['items'] as $itemKey => $item) {
                $items[] = $this->getItem((string) $itemKey, $item);
            }

            $items = array_values(array_filter($items));

            // A section whose items are all disabled or undisplayable is omitted.
            if ($items === []) {
                continue;
            }

            $result[] = [
                'title' => $this->text($section['title'] ?? null) ?? $this->properCase((string) $key),
                'icon' => $this->string($section['icon'] ?? null),
                'description' => $this->text($section['description'] ?? null),
                'collapsible' => (bool) ($section['collapsible'] ?? false),
                'collapsed' => (bool) ($section['collapsed'] ?? false),
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * An item is either `label => value` or `key => [title, value, icon, url, new_tab]`.
     *
     * @return array{label: string, value: ?string, icon: ?string, url: ?string, new_tab: bool} | null
     */
    protected function getItem(string $key, mixed $item): ?array
    {
        if (! is_array($item)) {
            $item = ['value' => $item];
        }

        if (! $this->passesEnabledFlag($item['enabled'] ?? null)) {
            return null;
        }

        $rawValue = $item['value'] ?? null;

        // A null or blank value still shows the label; values that can't be displayed are skipped.
        if ($rawValue !== null && ! is_scalar($rawValue)) {
            return null;
        }

        $value = $this->text($rawValue);
        $url = $value === null ? null : $this->safeUrl($item['url'] ?? null);

        return [
            'label' => $this->text($item['title'] ?? null) ?? $this->properCase($key),
            'value' => $value,
            'icon' => $this->string($item['icon'] ?? null),
            'url' => $url,
            'new_tab' => $url !== null
                && (bool) ($item['new_tab'] ?? in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)),
        ];
    }

    /**
     * Resolve an `enabled` flag: missing → shown, bool as-is, a Gate ability (or list of
     * abilities, all required) checked for the current user at render time.
     */
    protected function passesEnabledFlag(mixed $flag): bool
    {
        if ($flag === null || is_bool($flag)) {
            return $flag ?? true;
        }

        $abilities = is_string($flag) ? [$flag] : $flag;

        if (! is_array($abilities) || $abilities === []) {
            return false;
        }

        foreach ($abilities as $ability) {
            if (! is_string($ability) || blank($ability)) {
                return false;
            }
        }

        return Gate::check($abilities);
    }

    /**
     * `support_info` → "Support Info". Keeps acronyms such as `PHP` intact (unlike Str::headline()).
     */
    protected function properCase(string $key): string
    {
        return ucwords(trim((string) preg_replace('/[\s_-]+/', ' ', $key)));
    }

    protected function getAppName(): ?string
    {
        return $this->string(config('app.name'));
    }

    protected function screen(string $key): mixed
    {
        return data_get($this->getPlugin()->getScreen($this->panelId), $key);
    }

    protected function screenString(string $key): ?string
    {
        return $this->string($this->screen($key));
    }

    /**
     * Display text: `string()` plus `{user.*}` / `{tenant.*}` placeholders filled in for the
     * signed-in user and current tenant. Blank after filling in → null.
     */
    protected function text(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null || ! str_contains($value, '{')) {
            return $value;
        }

        $value = (string) preg_replace_callback(
            '/\{\s*(user|tenant)\.([A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*)\s*\}/',
            fn (array $matches): string => $this->placeholderValue(
                $matches[1] === 'user' ? Filament::auth()->user() : Filament::getTenant(),
                $matches[2],
            ) ?? '',
            $value,
        );

        return $this->string($value);
    }

    /**
     * An attribute of the signed-in panel user or the current tenant, e.g. `name` or `team.name`.
     * Anything that can't be read safely (hidden attributes, methods, lazy-loading violations)
     * resolves to null instead of throwing.
     */
    protected function placeholderValue(mixed $target, string $path): ?string
    {
        try {
            foreach (explode('.', $path) as $segment) {
                $target = $target instanceof Model
                    ? $this->modelValue($target, $segment)
                    : data_get($target, $segment);

                if ($target === null) {
                    return null;
                }
            }

            return $this->string($target);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Read one segment from a model: an attribute or accessor, or a relation. Never data_get(),
     * which would call any method of the model that isn't defined on Eloquent's base class.
     * Attributes the model keeps out of serialization ($hidden, or not in a non-empty $visible)
     * are never read.
     */
    protected function modelValue(Model $model, string $segment): mixed
    {
        if (in_array($segment, $model->getHidden(), true)) {
            return null;
        }

        if ($model->getVisible() !== [] && ! in_array($segment, $model->getVisible(), true)) {
            return null;
        }

        if ($model->hasAttribute($segment)) {
            return $model->getAttribute($segment);
        }

        if ($model->relationLoaded($segment)) {
            return $model->getRelation($segment);
        }

        return $this->isRelationMethod($model, $segment) ? $model->getAttribute($segment) : null;
    }

    /**
     * A public method that declares a Relation return type.
     */
    protected function isRelationMethod(Model $model, string $name): bool
    {
        if (! method_exists($model, $name) || method_exists(Model::class, $name)) {
            return false;
        }

        $method = new ReflectionMethod($model, $name);
        $type = $method->getReturnType();

        return $method->isPublic()
            && $type instanceof ReflectionNamedType
            && is_a($type->getName(), Relation::class, true);
    }

    protected function string(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? __('overkill-about::overkill-about.values.yes') : __('overkill-about::overkill-about.values.no');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Only allow http(s), mailto and tel links, or app-relative paths.
     */
    protected function safeUrl(mixed $url): ?string
    {
        // Placeholders are filled in first, so user data can't smuggle in another scheme.
        $url = $this->text($url);

        // Backslashes and control characters: browsers read `/\host` as `//host` and strip tabs
        // and newlines, so none of them can appear in a link.
        if ($url === null || preg_match('/[\x00-\x1F\x7F\\\\]/', $url)) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $url : null;
    }
}
