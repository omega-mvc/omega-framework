<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Template\VarExport;

/**
 * @testdox Skeleton Test for Buffer Management
 */
#[CoversClass(VarExport::class)]
class BufferManagementTest extends TestCase
{
    /**
     * @test
     *
     * @testdox Ensures buffer starts empty
     */
    public function bufferStartsEmpty(): void
    {
        $varExport = new VarExport();
        $result    = $varExport->export([]);
        $this->assertEquals('[]', $result);
    }

    /**
     * @test
     *
     * @testdox Ensures buffer resets after compile
     */
    public function bufferResetsAfterCompile(): void
    {
        $varExport = new VarExport();

        // First export operation
        $varExport->export(['foo' => 'bar']);

        // Second export operation, expecting buffer to be reset
        $result = $varExport->export([]);

        $this->assertEquals('[]', $result);
    }
}
