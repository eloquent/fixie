<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Fixie\Handle\HandleFactoryInterface;
use Eloquent\Fixie\Handle\HandleInterface;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Parser;

/**
 * Reads data fixtures.
 */
class FixtureReader implements HandleFactoryInterface
{
    /**
     * Construct a new fixture reader.
     *
     * @param Parser|null   $parser   The YAML parser to use.
     * @param Isolator|null $isolator THe isolator to use.
     */
    public function __construct(
        Parser $parser = null,
        Isolator $isolator = null
    ) {
        if (null === $parser) {
            $parser = new Parser;
        }

        $this->parser = $parser;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the parser.
     *
     * @return Parser The parser.
     */
    public function parser()
    {
        return $this->parser;
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
        return new ReadHandle(
            null,
            $path,
            $this->parser(),
            $this->isolator
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
        return new ReadHandle(
            $stream,
            $path,
            $this->parser(),
            $this->isolator()
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

    private $parser;
    private $isolator;
}
