<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Fixie\Handle\HandleFactoryInterface;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Parser;

class FixtureReader implements HandleFactoryInterface
{
    /**
     * @param Parser|null   $parser
     * @param Isolator|null $isolator
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
     * @return Parser
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * @param string $path
     *
     * @return ReadHandleInterface
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
     * @param stream{readable: true} $stream
     * @param string|null            $path
     *
     * @return ReadHandleInterface
     */
    public function openStream($stream, $path = null)
    {
        return new ReadHandle(
            $stream,
            $path,
            $this->parser(),
            $this->isolator
        );
    }

    private $parser;
    private $isolator;
}
