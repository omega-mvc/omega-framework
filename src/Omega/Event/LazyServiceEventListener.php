<?php

/**
 * Part of Omega - Event Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Event;

use Omega\Container\ContainerInterface;
use Omega\Event\Exception\ServiceMethodNotFoundException;
use Omega\Event\Exception\ServiceNotRegisteredException;
use Omega\Event\Exception\InvalidServiceListenerException;
use Omega\Event\Exceptions\InvalidServiceMethodException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function call_user_func;
use function get_class;
use function is_callable;
use function method_exists;
use function sprintf;

/**
 * Lazily resolves and executes a service-based event listener from a PSR-compatible container.
 *
 * This listener acts as a proxy: it retrieves a service from the container only when the event
 * is dispatched, then invokes it either as a callable or via a specified method.
 *
 * It supports both invokable services (`__invoke`) and traditional service method calls.
 * This design allows decoupling event dispatching from service instantiation, enabling lazy loading.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
final class LazyServiceEventListener
{
    /**
    /**
     * Creates a lazy event listener bound to a service in the container.
     *
     * The service is resolved at runtime when the event is dispatched.
     * If the service is not directly callable, a method name must be provided.
     *
     * @param ContainerInterface $container The dependency injection container used to resolve the service.
     * @param string $serviceId Identifier of the service registered in the container.
     * @param string $method Optional method name to invoke on the resolved service.
     * @throws InvalidServiceListenerException If the service identifier is empty.
     */
    public function __construct(
        private ContainerInterface $container,
        private string $serviceId,
        private string $method = ''
    )
    {
        if (empty($serviceId)) {
            throw new InvalidServiceListenerException(
                sprintf(
                    'The $serviceId parameter cannot be empty in %s',
                    self::class
                )
            );
        }
    }

    /**
     * Invokes the resolved service as an event listener.
     *
     * The service is retrieved lazily from the container when the event is dispatched.
     * If the service is callable, it is invoked directly. Otherwise, a method call is performed.
     *
     * Execution rules:
     * - If the service implements __invoke(), it is executed directly.
     * - Otherwise, a method name must be provided and must exist on the service.
     *
     * @param EventInterface $event The event instance being dispatched.
     * @return void
     * @throws ContainerExceptionInterface If the container fails while retrieving the service.
     * @throws NotFoundExceptionInterface If the service is not found in the container.
     * @throws ServiceNotRegisteredException If the service ID is not registered.
     * @throws InvalidServiceMethodException If no method is provided for non-callable services.
     * @throws ServiceMethodNotFoundException If the provided method does not exist on the service.
     */
    public function __invoke(EventInterface $event): void
    {
        if (!$this->container->has($this->serviceId)) {
            throw new ServiceNotRegisteredException(
                sprintf(
                    'The "%s" service has not been registered to the service container',
                    $this->serviceId
                )
            );
        }

        $service = $this->container->get($this->serviceId);

        // If the service is callable on its own, just execute it
        if (is_callable($service)) {
            call_user_func($service, $event);

            return;
        }

        if (empty($this->method)) {
            throw new InvalidServiceMethodException(
                sprintf(
                    'The $method argument is required when creating a "%s" to call a method from the "%s" service.',
                    self::class,
                    $this->serviceId
                )
            );
        }

        if (!method_exists($service, $this->method)) {
            throw new ServiceMethodNotFoundException(
                sprintf(
                    'The "%s" method does not exist on "%s" (from service "%s")',
                    $this->method,
                    get_class($service),
                    $this->serviceId
                )
            );
        }

        call_user_func([$service, $this->method], $event);
    }
}
