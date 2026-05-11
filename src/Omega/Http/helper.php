<?php

declare(strict_types=1);

namespace Omega\Http;

use Exception;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;

if (!function_exists('redirect')) {
    /**
     * Redirect to a specific URL.
     *
     * @param string $url The destination URL for the redirection.
     * @return RedirectResponse Returns a RedirectResponse object representing the redirection.
     * @throws Exception Thrown if the redirection cannot be created.
     */
    function redirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url);
    }
}

if (!function_exists('redirect_route')) {
    /**
     * Redirect to another route using the route's name and optional parameters.
     *
     * @param string $route_name The name of the route to redirect to.
     * @param array<string|int, string|int|bool> $parameter Optional dynamic parameters to populate the
     *                                                      route's URL pattern.
     * @return RedirectResponse Returns a RedirectResponse object representing the redirection.
     * @throws Exception Thrown if the route cannot be resolved or URL cannot be built.
     */
    function redirect_route(string $route_name, array $parameter = []): RedirectResponse
    {
        $route  = Router::redirect($route_name);
        $builder = new RouteUrlBuilder(Router::$patterns);

        return new RedirectResponse($builder->buildUrl($route, $parameter));
    }
}