<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support;

use Omega\Application\Application;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use Omega\Http\MacroServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AddonServiceProvider support class.
 *
 * This test suite verifies that the AddonServiceProvider correctly
 * registers custom macros on the Request class and that those macros
 * behave as expected once registered.
 *
 * In particular, it ensures that:
 * - The `validate` and `upload` macros are properly registered.
 * - The `upload` macro correctly resolves uploaded files and returns
 *   an instance of the UploadFile object.
 *
 * The tests simulate request input and file uploads to confirm that
 * the macros integrate correctly with the Request component.
 *
 * @category  Tests
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(MacroServiceProvider::class)]
#[CoversClass(Request::class)]
class AddonServiceProviderTest extends TestCase
{
    /**
     * Test it register request macros.
     *
     * @return void
     */
    public function testItRegistersRequestMacros(): void
    {
        $provider = new MacroServiceProvider(new Application(''));

        $provider->register();

        $this->assertTrue(Request::hasMacro('validate'));
        $this->assertTrue(Request::hasMacro('upload'));
    }

    /**
     * Test upload macro executes correctly.
     *
     * @return void
     */
    public function testUploadMacroExecutesCorrectly(): void
    {
        $provider = new MacroServiceProvider(new Application(''));
        $provider->register();

        $mockFiles = [
            'avatar' => [
                'name'     => 'test.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => '/tmp/php_mock_file_123', // Un percorso inventato!
                'error'    => 0,
                'size'     => 1024,
            ]
        ];

        $request = new Request(
            url: '/',
            files: $mockFiles
        );

        $uploadFile = $request->upload('avatar');

        $this->assertInstanceOf(UploadFile::class, $uploadFile);
    }
}
