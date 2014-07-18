<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Installer;

use Composer\Util\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;

/**
 * Manager of ignore patterns for bower.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class BowerIgnoreManager
{
    /**
     * @var Finder
     */
    private $files;

    /**
     * @var Finder
     */
    private $dirs;

    /**
     * Adds an ignore pattern.
     *
     * @param string $pattern The pattern
     */
    public function addPattern($pattern)
    {
        $type = '/' === substr($pattern, -1) ? 'dirs' : 'files';

        if (null === $this->$type) {
            $this->$type = Finder::create()->ignoreDotFiles(false)->ignoreVCS(false);

            if ('dirs' === $type) {
                $this->$type = $this->$type->directories();
            }
        }

        $this->addPatternToFinder($this->$type, $pattern);
    }

    /**
     * Deletes all files and directories that matches patterns in specified directory.
     *
     * @param string          $dir        The path to the directory
     * @param Filesystem|null $filesystem
     */
    public function deleteInDir($dir, Filesystem $filesystem = null)
    {
        $filesystem = $filesystem ?: new Filesystem();

        $files = null === $this->files ? array() : iterator_to_array($this->files->in($dir));
        $dirs = null === $this->dirs ? array() : iterator_to_array($this->dirs->in($dir));
        $all = array_merge($files, $dirs);

        /* @var \SplFileInfo $path */
        foreach ($all as $path) {
            $filesystem->remove($path->getRealpath());
        }
    }

    /**
     * Registers a pattern to finder.
     *
     * @param Finder $finder
     * @param string $pattern The pattern
     */
    private function addPatternToFinder(Finder $finder, $pattern)
    {
        $path = true;
        if (0 === strpos($pattern, '!')) {
            $pattern = substr($pattern, 1);
            $path = false;
        }

        $start = false;
        if (0 === strpos($pattern, '/')) {
            $pattern = substr($pattern, 1);
            $start = true;
        }

        $pattern = substr(Glob::toRegex($pattern, false), 2, -2);
        $pattern = strtr($pattern, array('[^/]*[^/]*/' => '(|.*/)(?<=^|/)', '[^/]*[^/]*' => '.*'));
        $pattern = '#'.($start ? '^' : '').$pattern.'#';

        if ($path) {
            $finder->path($pattern);
        } else {
            $finder->notPath($pattern);
        }
    }
}
