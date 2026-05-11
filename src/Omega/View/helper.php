<?php

declare(strict_types=1);

namespace Omega\View;

use Exception;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\View\Vite as GetVite;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function Omega\Application\app;

if (!function_exists('vite')) {
    /**
     * Get resource using entry point(s) from the Vite build system.
     *
     * @param string ...$entry_points One or more entry point names to retrieve resources for.
     * @return array<string, string>|string Returns an associative array of entry point names to resource URLs if
     *                                      multiple are given, or a single resource URL string if only one entry point
     *                                      is provided.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when resource cannot be retrieved.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function vite(string ...$entry_points): array|string
    {
        /** @var GetVite $vite */
        $vite = app()->get('vite.gets');

        $resource = $vite->gets($entry_points);
        $first    = array_key_first($resource);

        return 1 === count($resource) ? $resource[$first] : $resource;
    }
}

if (!function_exists('view')) {
    /**
     * Render with custom template engine, wrap in `Route\Controller`.
     *
     * @param string $view_path Path to the template file to render.
     * @param array<string, mixed> $data Associative array of data to pass to the template.
     * @param array<string, mixed> $option Optional settings such as 'status' (HTTP status code) and 'header'
     *           (HTTP headers).
     * @return Response Returns a Response object containing the rendered template along with the specified
     *           status and headers.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    function view(string $view_path, array $data = [], array $option = []): Response
    {
        $view = app()->get('view.response');
        $status_code = $option['status'] ?? 200;
        $headers = $option['header'] ?? [];

        return $view($view_path, $data)
            ->setResponseCode($status_code)
            ->setHeaders($headers);
    }
}