<?php

/**
 * Part of Omega - Exceptions Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Exceptions;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Exceptions\HttpResponseException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Throwable;

use function array_map;
use function array_merge;
use function Omega\Application\get_path;
use function Omega\View\view;

/**
 * Handles exceptions for the application, including rendering, reporting, and HTTP responses.
 *
 * @category  Omega
 * @package   Exceptions
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ExceptionHandler
{
    /** @var Container The main application container. */
    protected Container $app;

    /** @var array<int, class-string<Throwable>> List of exceptions not to report. */
    protected array $dontReport = [];

    /** @var array<int, class-string<Throwable>> Internal exceptions not to report (framework). */
    protected array $dontReportInternal = [
        HttpResponseException::class,
        HttpException::class,
    ];

    /**
     * Initialize the exception handler with the application container.
     *
     * @param Container $application The main application container.
     */
    public function __construct(Container $application)
    {
        $this->app = $application;
    }

    /**
     * Render an exception to an HTTP response.
     *
     * @param Request   $request The current HTTP request.
     * @param Throwable $th      The exception to render.
     * @return Response The HTTP response generated from the exception.
     * @throws Throwable If the exception should bubble up in debug mode.
     */
    public function render(Request $request, Throwable $th): Response
    {
        if ($request->isJson()) {
            return $this->handleJsonResponse($th);
        }

        if ($th instanceof HttpResponseException) {
            return $th->getResponse();
        }

        if ($th instanceof HttpException) {
            return $this->handleHttpException($th);
        }

        if (false === $this->isDebug()) {
            return $this->handleResponse($th);
        }

        throw $th;
    }

    /**
     * Report an exception (useful for logging).
     *
     * @param Throwable $th The exception to report.
     * @return void
     */
    public function report(Throwable $th): void
    {
        if ($this->dontReport($th)) {
            /** @noinspection PhpUnnecessaryStopStatementInspection */
            return;
        }
    }

    /**
     * Determine if an exception should not be reported.
     *
     * @param Throwable $th The exception to check.
     * @return bool True if the exception should not be reported, false otherwise.
     */
    protected function dontReport(Throwable $th): bool
    {
        return array_any(
            array_merge(
                $this->dontReport,
                $this->dontReportInternal
            ),
            fn($report) => $th instanceof $report
        );
    }

    /**
     * Handle exception for JSON response.
     *
     * @param Throwable $th The exception to render as JSON.
     * @return Response The JSON HTTP response.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function handleJsonResponse(Throwable $th): Response
    {
        $response = new Response([
            'code'     => 500,
            'messages' => [
                'message'   => 'Internal Server Error',
            ]], 500);

        if ($th instanceof HttpException) {
            $response->setResponseCode($th->getStatusCode());
            $response->headers->add($th->getHeaders());
        }

        if ($this->isDebug()) {
            return $response->json([
                'code'     => $response->getStatusCode(),
                'messages' => [
                    'message'   => $th->getMessage(),
                    'exception' => $th::class,
                    'file'      => $th->getFile(),
                    'line'      => $th->getLine(),
                ],
            ]);
        }

        return $response->json();
    }

    /**
     * Handle exception for standard response (HTML or generic).
     *
     * @param Throwable $th The exception to handle.
     * @return Response The generated HTTP response.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function handleResponse(Throwable $th): Response
    {
        return $this->isProduction()
            ? $this->handleHttpException(new HttpException(500, 'Internal Server Error'))
            : new Response($th->getMessage(), 500);
    }

    /**
     * Handle HttpException specifically.
     *
     * @param HttpException $e The HTTP exception.
     * @return Response The HTTP response for the exception.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function handleHttpException(HttpException $e): Response
    {
        $templator = $this->registerViewPath();
        $code      = $templator->viewExist((string) $e->getStatusCode())
            ? $e->getStatusCode()
            : 500;

        $this->app->set('view.instance', fn () => $templator);

        $response = view((string) $code);
        $response->setResponseCode($code);
        $response->headers->add($e->getHeaders());

        return $response;
    }

    /**
     * Register view paths for rendering exception templates.
     *
     * @return Templator Configured templator instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function registerViewPath(): Templator
    {
        $view_paths   = array_map(fn ($path): string => $path . 'pages/', get_path('paths.view'));
        $view_paths[] = get_path('path.view');
        /** @var TemplatorFinder $finder */
        $finder = $this->app->make(TemplatorFinder::class);
        $finder->setPaths($view_paths);

        /** @var Templator $view */
        $view = $this->app->make('view.instance');
        $view->setFinder($finder);

        return $view;
    }

    /**
     * Check if the application is in debug mode.
     *
     * @return bool True if debug mode is enabled.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function isDebug(): bool
    {
        return $this->app->get('app.debug');
    }

    /**
     * Check if the application environment is production.
     *
     * @return bool True if the environment is 'prod'.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function isProduction(): bool
    {
        return $this->app->get('environment') === 'prod';
    }
}
