<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Router;

use ArrayAccess;
use Omega\Router\Exceptions\RouteNotRegisteredException;
use ReturnTypeWillChange;

/**
 * Represents a single route definition including name, URI,
 * handler, middleware, and custom parameter patterns.
 *
 * This class serves as a convenient wrapper for route metadata
 * and allows fluent configuration through methods such as
 * name(), middleware(), and where(). It also implements ArrayAccess
 * so that route attributes can be accessed like an array.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @implements ArrayAccess<string, mixed>
 *
 * @method route()
 */
class Route implements ArrayAccess
{
    /**
     * Internal route definition array containing all metadata such as:
     * - expression
     * - method(s)
     * - function (handler)
     * - name
     * - middleware
     * - patterns
     *
     * @var array<string, mixed>
     */
    private array $route;

    /**
     * Route name prefix added from the current Router group context.
     *
     * Used to automatically prepend group-based naming prefixes
     * (e.g., "admin." -> "admin.dashboard").
     *
     * @var string
     */
    private string $prefixName;

    /**
     * Create a new Route instance.
     *
     * Injects the raw route array and automatically applies any active
     * group name prefix from Router::$group['as'].
     *
     * @param array<string, mixed> $route Initial route definition
     * @return void
     */
    public function __construct(array $route)
    {
        $this->prefixName = Router::$group['as'] ?? '';

        $route['name'] ??= '';
        $route['name'] = $this->prefixName . $route['name'];
        $this->route   = $route;
    }

    /**
     * Dynamically access certain route properties.
     *
     * Currently, supports:
     *   - route(): returns the entire route definition array.
     *
     * @param string   $name      The called method name
     * @param string[] $arguments Arguments passed to the magic call
     * @return array<string, mixed>
     * @throws RouteNotRegisteredException If an unsupported magic method is called
     */
    public function __call(string $name, array $arguments)
    {
        if ($name === 'route') {
            return $this->route;
        }

        throw new RouteNotRegisteredException($name);
    }

    /**
     * Set the route name.
     *
     * Automatically prepends any active group prefix. The final route name
     * must be unique within the routing system.
     *
     * @param string $name Route name without prefix
     * @return self
     */
    public function name(string $name): self
    {
        $this->route['name'] = $this->prefixName . $name;

        return $this;
    }

    /**
     * Attach one or more middleware class names to this route.
     *
     * Middleware are executed during dispatch, allowing preprocessing
     * such as authentication, rate limiting, etc.
     *
     * @param class-string[] $middlewares Array of middleware class names
     * @return self
     */
    public function middleware(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->route['middleware'][] = $middleware;
        }

        return $this;
    }

    /**
     * Define custom URL parameter patterns for this route.
     *
     * Patterns map placeholder formats (e.g. "(:num)") to regex
     * expressions used during matching and URL generation.
     *
     * @param array<string, string> $patterns Map of pattern => regex
     * @return self
     */
    public function where(array $patterns): self
    {
        $this->route['patterns'] = $patterns;

        return $this;
    }

    /**
     * Assign a value to the given route attribute.
     *
     * @param string $offset The attribute name
     * @param mixed  $value  The value to assign
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->route[$offset] = $value;
    }

    /**
     * Determine whether the given route attribute exists.
     *
     * Called automatically by isset() or empty().
     *
     * @param string $offset Attribute name
     * @return bool True if the attribute exists, false otherwise
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->route[$offset]);
    }

    /**
     * Determine whether the given route attribute exists.
     *
     * Called automatically by isset() or empty().
     *
     * @param string $offset Attribute name
     * @return void True if the attribute exists, false otherwise
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->route[$offset]);
    }

    /**
     * Retrieve a route attribute by name.
     *
     * Returns null if the attribute does not exist.
     *
     * @param string $offset Attribute name
     * @return mixed|null The stored value or null if absent
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->route[$offset] ?? null;
    }
}
