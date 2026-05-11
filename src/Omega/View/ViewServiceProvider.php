<?php

declare(strict_types=1);

namespace Omega\View;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\Container\AbstractServiceProvider;
use Omega\View\Vite;
use Omega\View\Templator\DirectiveTemplator;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_merge;
use function file_exists;
use function Omega\Application\get_path;

class ViewServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $this->registerViteResolver();
        $this->registerViewResolver();
        $this->registerViteDirectives();
    }

    /**
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function registerViteResolver(): void
    {
        $this->app->set('vite.gets', fn (): Vite => new Vite(get_path('path.public'), '/build/'));
        $this->app->set('vite.location', fn (): string => get_path('path.public') . '/build/manifest.json');
        $this->app->set('vite.hasManifest', fn (): bool => file_exists($this->app->get('vite.location')));
    }

    /**
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function registerViteDirectives(): void
    {
        if ($this->app->has('vite.gets')) {
            DirectiveTemplator::register('vite', function (array $attributes): string {
                $vite = $this->app->get('vite.gets');

                return $vite(...$attributes);
            });
        }
    }

    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function registerViewResolver(): void
    {
        $globalTemplateVar = [
            'vite_has_manifest' => $this->app->get('vite.hasManifest'),
            'vite_hmr_script'   => $this->app->get('vite.gets')
                ->isRunningHRM()
                ? $this->app->get('vite.gets')->getHmrScript()
                : '',
        ];

        $extensions = $this->app->get('config')['VIEW_EXTENSIONS'] ?? [];

        $this->app->set(TemplatorFinder::class, fn () => new TemplatorFinder(get_path('paths.view'), $extensions));
        $this->app->set(
            'view.instance',
            fn () => new Templator($this->app->get(TemplatorFinder::class), get_path('path.compiled_view_path'))
        );
        $this->app->set(
            'view.response',
            fn () => fn (string $view, array $data = []): Response => new Response(
                $this->app->get('view.instance')->render($view, array_merge($data, $globalTemplateVar))
            )
        );
    }
}
