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

use Exception;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator\BooleanTemplator;
use Omega\View\Templator\BreakTemplator;
use Omega\View\Templator\CommentTemplator;
use Omega\View\Templator\ComponentTemplator;
use Omega\View\Templator\ContinueTemplator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\Templator\EachTemplator;
use Omega\View\Templator\IfTemplator;
use Omega\View\Templator\IncludeTemplator;
use Omega\View\Templator\JsonTemplator;
use Omega\View\Templator\NameTemplator;
use Omega\View\Templator\PHPTemplator;
use Omega\View\Templator\SectionTemplator;
use Omega\View\Templator\SetTemplator;
use Omega\View\Templator\UseTemplator;
use Throwable;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_string;
use function ltrim;
use function md5;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function Omega\Application\slash;

/**
 * Class Templator
 *
 * Main template engine class responsible for parsing, compiling, caching, and rendering templates.
 *
 * It supports dependencies, component namespaces, caching, and a suite of templator classes for parsing
 * directives, sections, includes, and other template features.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Templator
{
    /** @var TemplatorFinder Template finder instance for locating template files. */
    public TemplatorFinder $finder;

    /** @var string Directory path for cached compiled templates. */
    private string $cacheDir;

    /** @var string Suffix appended to template names when rendering or compiling. */
    public string $suffix = '';

    /** @var int Maximum recursion depth for template inclusion and components. */
    public int $maxDepth = 5;

    /** @var string Namespace for component classes. */
    private string $componentNamespace = '';

    /** @var array<string, array<string, int>> Stores template dependencies by parent and child templates. */
    private array $dependency = [];


    /**
     * Templator constructor.
     *
     * @param string|TemplatorFinder $finder  Template directory or a TemplatorFinder instance.
     * @param string                 $cacheDir Directory for cached compiled templates.
     */
    public function __construct(TemplatorFinder|string $finder, string $cacheDir)
    {
        // Backwards compatibility with templator finder.
        $this->finder   = is_string($finder) ? new TemplatorFinder([$finder]) : $finder;
        $this->cacheDir = slash($cacheDir);
    }

    /**
     * Set a new TemplatorFinder instance.
     *
     * @param TemplatorFinder $finder Template finder instance.
     * @return self
     */
    public function setFinder(TemplatorFinder $finder): self
    {
        $this->finder = $finder;

        return $this;
    }

    /**
     * Set the namespace used for component classes.
     *
     * @param string $namespace Component namespace.
     * @return self
     */
    public function setComponentNamespace(string $namespace): self
    {
        $this->componentNamespace = $namespace;

        return $this;
    }

    /**
     * Add a dependency between a parent and a child template.
     *
     * @param string $parent      Parent template file path.
     * @param string $child       Child template file path.
     * @param int    $dependDeep  Dependency depth (default 1).ls
     * @return self
     */
    public function addDependency(string $parent, string $child, int $dependDeep = 1): self
    {
        $this->dependency[$parent][$child] = $dependDeep;

        return $this;
    }

    /**
     * Prepend multiple dependencies for a parent template.
     *
     * @param string             $parent   Parent template file path.
     * @param array<string, int> $children Array of child template paths with depth values.
     * @return self
     */
    public function prependDependency(string $parent, array $children): self
    {
        array_walk($children, function (int $depth, string $child) use ($parent) {
            $existingDepth = $this->dependency[$parent][$child] ?? null;

            if ($existingDepth === null || $depth > $existingDepth) {
                $this->addDependency($parent, $child, $depth);
            }
        });

        return $this;
    }

    /**
     * Get the dependencies of a parent template.
     *
     * @param string $parent Parent template file path.
     * @return array<string, int> Array of child template paths with their depth.
     */
    public function getDependency(string $parent): array
    {
        return $this->dependency[$parent] ?? [];
    }

    /**
     * Render a template with provided data, optionally using cached compiled templates.
     *
     * @param string               $templateName Template file name without suffix.
     * @param array<string, mixed> $data         Associative array of template variables.
     * @param bool                 $cache        Whether to use cached compiled template if available.
     * @return string Rendered template output.
     * @throws Throwable If an error occurs during template execution.
     */
    public function render(string $templateName, array $data, bool $cache = true): string
    {
        $templateName .= $this->suffix;
        $templatePath  = $this->finder->find($templateName);
        $cachePath     = $this->cacheDir . '/' . md5($templateName) . '.php';

        if ($cache && file_exists($cachePath) && filemtime($cachePath) >= filemtime($templatePath)) {
            return $this->getView($cachePath, $data);
        }

        $template = file_get_contents($templatePath);
        $template = $this->templates($template, $templatePath);

        file_put_contents($cachePath, $template);

        return $this->getView($cachePath, $data);
    }

    /**
     * Compile a template file to a PHP file without rendering.
     *
     * @param string $templateName Template file name without suffix.
     * @return string Compiled PHP template content.
     * @throws Exception If the template file cannot be read or processed.
     */
    public function compile(string $templateName): string
    {
        $templateName .= $this->suffix;
        $templateDir   = $this->finder->find($templateName);

        $cachePath = $this->cacheDir . '/' . md5($templateName) . '.php';

        $template = file_get_contents($templateDir);
        $template = $this->templates($template, $templateDir);

        file_put_contents($cachePath, $template);

        return $template;
    }

    /**
     * Check whether a template file exists.
     *
     * @param string $templateName Template file name without suffix.
     * @return bool True if template exists, false otherwise.
     */
    public function viewExist(string $templateName): bool
    {
        $templateName .= $this->suffix;

        try {
            $this->finder->find($templateName);
            return true;
        } catch (ViewFileNotFoundException) {
            return false;
        }
    }

    /**
     * Get the rendered view from a compiled PHP template file.
     *
     * @param string               $templatePath Path to compiled PHP template file.
     * @param array<string, mixed> $data         Associative array of variables to extract.
     * @return string Rendered template output.
     * @throws Throwable If an error occurs during execution of the template.
     */
    private function getView(string $templatePath, array $data): string
    {
        $level = ob_get_level();

        ob_start();

        try {
            (static function ($__, $__file_name__) {
                extract($__);
                include $__file_name__;
            })($data, $templatePath);
        } catch (Throwable $th) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $th;
        }

        $out = ob_get_clean();

        return $out === false ? '' : ltrim($out);
    }

    /**
     * Parse a template using all available templators.
     *
     * Applies transformations from each templator class in sequence and records dependencies.
     *
     * @param string $template     Template content to parse.
     * @param string $viewLocation Original template file path for dependency tracking.
     * @return string Parsed template content.
     * @throws Exception If a templator fails to process the template.
     */
    public function templates(string $template, string $viewLocation = ''): string
    {
        return array_reduce([
            SetTemplator::class,
            SectionTemplator::class,
            IncludeTemplator::class,
            PHPTemplator::class,
            DirectiveTemplator::class,
            ComponentTemplator::class,
            NameTemplator::class,
            IfTemplator::class,
            EachTemplator::class,
            CommentTemplator::class,
            ContinueTemplator::class,
            BreakTemplator::class,
            UseTemplator::class,
            JsonTemplator::class,
            BooleanTemplator::class,
        ], function (string $template, string $templator) use ($viewLocation): string {
            $templator = new $templator($this->finder, $this->cacheDir);
            if ($templator instanceof IncludeTemplator) {
                $templator->maksDept($this->maxDepth);
            }

            if ($templator instanceof ComponentTemplator) {
                $templator->setNamespace($this->componentNamespace);
            }

            $parse = $templator->parse($template);

            // Get dependency view file (parent) after parse template.
            if ($templator instanceof DependencyTemplatorInterface) {
                $this->prependDependency($viewLocation, $templator->dependOn());
            }

            return $parse;
        }, $template);
    }
}
