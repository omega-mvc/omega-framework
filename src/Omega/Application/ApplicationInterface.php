<?php

declare(strict_types=1);

namespace Omega\Application;

interface ApplicationInterface extends AbstractApplicationInterface
{
    /**
     * Determinate application maintenance mode.
     *
     * @return bool True if the application is currently in maintenance mode.
     */
    public function isDownMaintenanceMode(): bool;

    /**
     * Get down maintenance file config.
     *
     * @return array<string, string|int|null> Maintenance mode configuration data.
     */
    public function getDownData(): array;
}