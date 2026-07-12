<?php

declare(strict_types=1);

namespace Tests\Template\Parser\File;

use PHPUnit\Framework\TestCase;
use Omega\Template\Parser\File\NamespaceResolver;

final class NamespaceResolverTest extends TestCase
{
    /**
     * @test
     */
    public function testItCanParseUseStatements(): void
    {
        $sources = <<<'PHP'
    <?php

    declare(strict_types=1);

    use Omega\Http\Request;
    use Omega\Http\Response;
    use Omega\Router\Route;
    use Omega\Router\Router;
    use Omega\Template\VarExport;
    use Omega\Template\VarExport\Buffer;
    PHP;

        $parser = new NamespaceResolver();
        $uses   = $parser->resolve($sources);

        $expected = [
            'Omega\Http\Request',
            'Omega\Http\Response',
            'Omega\Router\Route',
            'Omega\Router\Router',
            'Omega\Template\VarExport',
            'Omega\Template\VarExport\Buffer',
        ];

        $this->assertEquals($expected, $uses);
    }

    /**
     * @test
     */
    public function testItCanParseGroupUseStatements(): void
    {
        $sources = <<<'PHP'
    <?php

    declare(strict_types=1);

    use Omega\Http\{Request, Response};
    use Omega\Router\{Route, Router};
    use Omega\Template\VarExport;
    use Omega\Template\VarExport\Buffer;
    PHP;

        $parser = new NamespaceResolver();
        $uses   = $parser->resolve($sources);

        $expected = [
            'Omega\Http\Request',
            'Omega\Http\Response',
            'Omega\Router\Route',
            'Omega\Router\Router',
            'Omega\Template\VarExport',
            'Omega\Template\VarExport\Buffer',
        ];

        $this->assertEquals($expected, $uses);
    }

    /**
     * @test
     */
    public function testItHandlesFileWithNoUseStatements(): void
    {
        $sources = '<?php class MyClass {}';
        $parser  = new NamespaceResolver();
        $uses    = $parser->resolve($sources);

        $this->assertEmpty($uses);
    }
}
