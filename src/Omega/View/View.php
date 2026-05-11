<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View;

use Omega\Http\Response;
use Omega\View\Exceptions\ViewFileNotFoundException;

use function file_exists;
use function ob_get_clean;
use function ob_start;
use function Omega\Application\slash;

/**
 * Class View
 *
 * Responsible for rendering view templates with provided data.
 * Supports injecting structured data via the Portal objects and returns a Response instance.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class View
{
    /**
     * Render a view template and return a Response object.
     *
     * Extracts data from the provided portal array and makes it accessible inside the view.
     * Throws an exception if the view file cannot be found.
     *
     * @param string               $viewPath Full path to the view template file.
     * @param array<string, mixed> $portal   Associative array of data to inject into the view.
     *                                      Common keys: 'auth', 'meta', 'contents'.
     * @return Response The Response object containing the rendered HTML content.
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    public static function render(string $viewPath, array $portal = []): Response
    {
        $viewPath = slash($viewPath);

        if (!file_exists($viewPath)) {
            throw new ViewFileNotFoundException($viewPath);
        }

        $auth    = new Portal($portal['auth'] ?? []);
        $meta    = new Portal($portal['meta'] ?? []);
        $content = new Portal($portal['contents'] ?? []);

        // Capture the rendered template content
        ob_start();
        require_once $viewPath;
        $html = ob_get_clean();

        // Return as HTTP response
        return new Response()
            ->setContent($html)
            ->setResponseCode(Response::HTTP_OK)
            ->removeHeaders([
                'Expires',
                'Pragma',
                'X-Powered-By',
                'Connection',
                'Server',
            ]);
    }
}
