<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Template\VarExport;
use ReflectionClass;
use ReflectionException;

#[CoversClass(VarExport::class)]
class HeaderGenerationTest extends TestCase
{
    /**
     * @test
     * @throws ReflectionException
     */
    public function itGeneratesHeader()
    {
        $exporter   = new VarExport();
        $reflection = new ReflectionClass($exporter);
        $method     = $reflection->getMethod('compileToString');
        $method->setAccessible(true);
        $output = $method->invoke($exporter, []);

        $this->assertStringStartsWith('<?php', $output);
        $this->assertStringContainsString('declare(strict_types=1);', $output);
        $this->assertStringContainsString('// auto-generated file, do not edit!', $output);
        $this->assertStringContainsString('return ', $output);
    }
}
