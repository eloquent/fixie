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

use ErrorException;
use Icecave\Isolator\Isolator;

class Reader
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $path
     *
     * @return HandleInterface
     */
    public function openFile($path)
    {
        try {
            $stream = $this->isolator->fopen($path, 'rb');
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        return $this->openStream($stream, $path);
    }

    /**
     * @param stream {readable: true} $stream
     * @param string|null             $path
     *
     * @return HandleInterface
     */
    public function openStream($stream, $path = null)
    {
        try {
            $line = $this->isolator->fgets($stream);
            $this->isolator->rewind($stream);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        if (
            is_string($line) &&
            '- ' === substr($line, 0, 2)
        ) {
            return new ExpandedHandle(
                $stream,
                $path,
                null,
                $this->isolator
            );
        }

        return new CompactHandle(
            $stream,
            $path,
            null,
            $this->isolator
        );
    }

    private $isolator;
}
