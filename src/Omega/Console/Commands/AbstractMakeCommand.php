<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;

use Omega\Console\Attribute\Make;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Text\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use function Omega\Application\path;

abstract class AbstractMakeCommand extends AbstractCommand
{
    /**
     * @return int
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __invoke(): int
    {
        $name = $this->getArgument('name');

        $reflection = new ReflectionClass($this);
        $attribute = $reflection->getAttributes(Make::class)[0] ?? null;

        if (!$attribute) {
            $this->io->error('Missing #[Make] attribute.');
            return self::FAILURE;
        }

        $config = $attribute->newInstance();

        $savePath = $this->app->get($config->path);

        $success = $this->makeTemplate($name, [
            'template_location' => $config->template,
            'save_location'     => $savePath,
            'pattern'           => $config->pattern,
            'suffix'            => $config->suffix,
            'vars'              => $this->resolveVars($config->vars, $name),
        ]);

        if (!$success) {
            $this->warning($config, $name);
            return self::FAILURE;
        }

        $this->info($config, $name);

        return self::SUCCESS;
    }

    protected function resolveVars(array $vars, string $name): array
    {

        return array_map(function ($value) use ($name) {
            return match ($value) {
                'kebab' => Str::toKebabCase($name),
                'snake' => Str::toSnakeCase($name),
                default => $value,
            };
        }, $vars);
    }

    protected function makeTemplate(string $argument, array $makeOption, string $folder = ''): bool
    {
        $folder = $folder ? ucfirst($folder) . DIRECTORY_SEPARATOR : '';

        $basePath = rtrim($makeOption['save_location'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $targetDir = $basePath . $folder;

        $fileName = $targetDir . $argument . $makeOption['suffix'];

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            $this->io->error("Unable to create directory [$targetDir]");
            return false;
        }

        if (file_exists($fileName)) {
            return false;
        }

        $template = file_get_contents($makeOption['template_location']);

        if ($template === false) {
            $this->io->error('Unable to read stub file');
            return false;
        }

        $template = str_replace(
            $makeOption['pattern'],
            $makeOption['replace'] ?? $argument,
            $template
        );

        if (!empty($makeOption['vars'])) {
            foreach ($makeOption['vars'] as $search => $replace) {
                $template = str_replace($search, $replace, $template);
            }
        }

        $template = preg_replace('/^.+\n/', '', $template);

        if (file_put_contents($fileName, $template) === false) {
            $this->io->error("Failed to write file [$fileName]");
            return false;
        }

        return true;
    }

    protected function info(object $config, string $name): void
    {
        $location = path($config->target);
        $fileName = $name . $config->suffix;
        $fullPath = $location . $fileName;

        $message = str_replace(
            '__file__name__',
            $fullPath,
            $config->info
        );

        $this->io->info($message);
    }

    protected function warning(object $config, string $name): void
    {
        $location = path($config->target);
        $fileName = $name . $config->suffix;
        $fullPath = $location . $fileName;

        $message = str_replace(
            '__file__name__',
            $fullPath,
            $config->warning
        );

        $this->io->warning($message);
    }
}
