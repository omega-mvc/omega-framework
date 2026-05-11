<?php

/**
 * Part of Omega - Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Testing;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Container\AbstractServiceProvider;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Throwable;

use function array_key_exists;

/**
 * TestCase
 *
 * This class extends the PHPUnit TestCase and provides convenient methods for testing
 * HTTP requests and JSON responses within the Omega framework. It initializes an
 * Application instance and allows interaction with the Http kernel to simulate requests
 * in a controlled test environment.
 *
 * @category  Omega
 * @package   Testing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class TestCase extends PhpUnitTestCase
{
    /** @var Application  The application instance used for testing. */
    protected Application $app;

    /** @var Http The Http kernel used to handle requests. */
    protected Http $kernel;

    /** @var string Fully qualified class name under test. */
    protected string $class;

    /**
     * Clean up after each test.
     *
     * This method flushes the application, resets facades and service providers,
     * and unsets the $app and $kernel properties.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->app->flush();
        AbstractFacade::flushInstance();
        AbstractServiceProvider::flushModule();
        unset($this->app);
        unset($this->kernel);
    }

    /**
     * Call a service method and return a TestJsonResponse.
     *
     * @param array|string|callable $call The service method to call, can be a string or an array for object callables.
     * @param array<string, string> $params Parameters to pass to the method.
     * @return TestJsonResponse The response wrapped in a TestJsonResponse instance.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception If an error occurs during the service call.
     */
    protected function json(array|string|callable $call, array $params = []): TestJsonResponse
    {
        $data     = $this->app->call($call, $params);
        $response = new Response($data);

        if (array_key_exists('code', $data)) {
            $response->setResponseCode((int) $data['code']);
        }

        if (array_key_exists('headers', $data)) {
            $response->setHeaders($data['headers']);
        }

        return new TestJsonResponse($response);
    }

    /**
     * Make an HTTP request and return a TestResponse.
     *
     * @param string $url The URL to request.
     * @param array<string, string> $query Query parameters.
     * @param array<string, string> $post POST parameters.
     * @param array<string, string> $attributes Attributes to pass to the request.
     * @param array<string, string> $cookies Cookies to include in the request.
     * @param array<string, string> $files Files to include in the request.
     * @param array<string, string> $headers HTTP headers to include.
     * @param string $method HTTP method to use (GET, POST, PUT, DELETE, etc.).
     * @param string $remoteAddress Remote IP address for the request.
     * @param string|null $rawBody Raw request body content.
     * @return TestResponse The response wrapped in a TestResponse instance.
     * @throws Throwable
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function call(
        string $url,
        array $query = [],
        array $post = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $headers = [],
        string $method = 'GET',
        string $remoteAddress = '::1',
        ?string $rawBody = null,
    ): TestResponse {
        /** @var Http $kernel */
        $kernel  = $this->app->make(Http::class);
        $request = new Request(
            $url,
            $query,
            $post,
            $attributes,
            $cookies,
            $files,
            $headers,
            $method,
            $remoteAddress,
            $rawBody
        );
        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return new TestResponse($response);
    }

    /**
     * Perform a GET request and return a TestResponse.
     *
     * @param string $url The URL to request.
     * @param array<string, string> $parameter Optional query parameters.
     * @return TestResponse The response wrapped in a TestResponse instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     * @noinspection PhpRedundantOptionalArgumentInspection
     */
    protected function get(string $url, array $parameter = []): TestResponse
    {
        return $this->call(url: $url, query: $parameter, method: 'GET');
    }

    /**
     * Perform a POST request and return a TestResponse.
     *
     * @param string $url The URL to request.
     * @param array<string, string> $post POST data.
     * @param array<string, string> $files Optional files to upload.
     * @return TestResponse The response wrapped in a TestResponse instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    protected function post(string $url, array $post, array $files = []): TestResponse
    {
        return $this->call(url: $url, post: $post, files: $files, method: 'POST');
    }

    /**
     * Perform a PUT request and return a TestResponse.
     *
     * @param string $url The URL to request.
     * @param array<string, string> $put PUT data (sent as attributes).
     * @param array<string, string> $files Optional files to upload.
     * @return TestResponse The response wrapped in a TestResponse instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    protected function put(string $url, array $put, array $files = []): TestResponse
    {
        return $this->call(url: $url, attributes: $put, files: $files, method: 'PUT');
    }

    /**
     * Perform a DELETE request and return a TestResponse.
     *
     * @param string $url The URL to request.
     * @param array<string, string> $delete DELETE data.
     * @return TestResponse The response wrapped in a TestResponse instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     * @noinspection PhpUnusedParameterInspection
     */
    protected function delete(string $url, array $delete): TestResponse
    {
        return $this->call(url: $url, post: $_POST, method: 'DELETE');
    }
}
