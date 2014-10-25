<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\HandleFactoryInterface;
use Eloquent\Fixie\Handle\HandleInterface;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

/**
 * Writes data fixtures.
 */
class FixtureWriter implements HandleFactoryInterface
{
    /**
     * Construct a new fixture writer.
     *
     * @param string|null   $handleClassName The class name of the writable handle type to use.
     * @param Inline|null   $renderer        The YAML renderer to use.
     * @param Isolator|null $isolator        The isolator to use.
     */
    public function __construct(
        $handleClassName = null,
        Inline $renderer = null,
        Isolator $isolator = null
    ) {
        if (null === $handleClassName) {
            $handleClassName =
                'Eloquent\Fixie\Writer\SwitchingCompactFixtureWriteHandle';
        }
        if (null === $renderer) {
            $renderer = new Inline();
        }

        $this->handleClassName = $handleClassName;
        $this->renderer = $renderer;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the handle class name.
     *
     * @return string The handle class name.
     */
    public function handleClassName()
    {
        return $this->handleClassName;
    }

    /**
     * Get the YAML renderer.
     *
     * @return Inline The YAML renderer.
     */
    public function renderer()
    {
        return $this->renderer;
    }

    /**
     * Create a data handle for the file at the specified path.
     *
     * @param string $path The path to the file.
     *
     * @return HandleInterface The newly created handle.
     */
    public function openFile($path)
    {
        $className = $this->handleClassName();

        return new $className(
            null,
            $path,
            $this->renderer(),
            $this->isolator()
        );
    }

    /**
     * Create a data handle for the supplied native stream handle.
     *
     * @param stream      $stream The native stream handle.
     * @param string|null $path   The filesystem path, or null if the path is unknown.
     *
     * @return HandleInterface The newly created handle.
     */
    public function openStream($stream, $path = null)
    {
        $className = $this->handleClassName();

        return new $className(
            $stream,
            $path,
            $this->renderer(),
            $this->isolator
        );
    }

    /**
     * Get the isolator.
     *
     * @return Isolator The isolator.
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    private $handleClassName;
    private $renderer;
    private $isolator;
}
