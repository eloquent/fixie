<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Handle;

use ErrorException;
use Icecave\Isolator\Isolator;

abstract class AbstractHandle implements HandleInterface
{
    /**
     * @param stream|null   $stream
     * @param string|null   $path
     * @param Isolator|null $isolator
     */
    public function __construct(
        $stream = null,
        $path = null,
        Isolator $isolator = null
    ) {
        if (null === $stream && null === $path) {
            throw new Exception\EmptyHandleException;
        }

        $this->stream = $stream;
        $this->path = $path;
        $this->isolator = Isolator::get($isolator);
        $this->isClosed = false;
    }

    /**
     * @return stream
     */
    public function stream()
    {
        if ($this->isClosed()) {
            throw new Exception\ClosedHandleException($this->path());
        }

        if (null === $this->stream) {
            try {
                $this->stream = $this->isolator()->fopen(
                    $this->path(),
                    $this->streamMode()
                );
            } catch (ErrorException $e) {
                throw new Exception\ReadException($this->path(), $e);
            }
        }

        return $this->stream;
    }

    /**
     * @return string|null
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * @return boolean
     */
    public function isClosed()
    {
        return $this->isClosed;
    }

    public function close()
    {
        if ($this->isClosed()) {
            throw new Exception\ClosedHandleException($this->path());
        }

        if (null === $this->stream) {
            $this->isClosed = true;
        } else {
            try {
                $this->isolator()->fclose($this->stream());
            } catch (ErrorException $e) {
                throw new Exception\ReadException($this->path(), $e);
            }

            $this->isClosed = true;
        }
    }

    /**
     * @return Isolator
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    /**
     * @return string
     */
    abstract protected function streamMode();

    private $stream;
    private $path;
    private $isolator;
    private $isClosed;
}
