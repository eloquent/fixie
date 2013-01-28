<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\AbstractHandle;
use Eloquent\Fixie\Handle\Exception\WriteException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

class CompactFixtureWriteHandle extends AbstractHandle implements WriteHandleInterface
{
    /**
     * @param stream{writable: true}|null $stream
     * @param string|null                 $path
     * @param Inline|null                 $generator
     * @param Isolator|null               $isolator
     */
    public function __construct(
        $stream = null,
        $path = null,
        Inline $generator = null,
        Isolator $isolator = null
    ) {
        parent::__construct(
            $stream,
            $path,
            $isolator
        );

        if (null === $generator) {
            $generator = new Inline;
        }

        $this->generator = $generator;
        $this->headerWritten = false;
    }

    /**
     * @return Inline
     */
    public function generator()
    {
        return $this->generator;
    }

    /**
     * @param array $row
     */
    public function write(array $row)
    {
        if (!$this->headerWritten) {
            $this->writeHeader(array_keys($row));
            $this->headerWritten = true;
        }

        $this->writeRow($row);
    }

    public function close()
    {
        if (!$this->isClosed() && $this->headerWritten) {
            $this->writeFooter();
        }

        parent::close();
    }

    /**
     * @return stream
     */
    protected function openStream()
    {
        try {
            $stream = $this->isolator()->fopen(
                $this->path(),
                'wb'
            );
        } catch (ErrorException $e) {
            throw new WriteException($this->path(), $e);
        }

        return $stream;
    }

    /**
     * @param array $columnNames
     */
    protected function writeHeader(array $columnNames)
    {
        if (range(0, count($columnNames) - 1) !== $columnNames) {
            $this->writeData(sprintf(
                "columns: %s\n",
                $this->generator()->dump($columnNames)
            ));
        }

        $this->writeData("data: [\n");
    }

    /**
     * @param array $row
     */
    protected function writeRow(array $row)
    {
        $this->writeData(sprintf(
            "%s,\n",
            $this->generator->dump(array_values($row))
        ));
    }

    protected function writeFooter()
    {
        $this->writeData("]\n");
    }

    /**
     * @param string $data
     */
    protected function writeData($data)
    {
        try {
            $this->isolator()->fwrite(
                $this->stream(),
                $data
            );
        } catch (ErrorException $e) {
            throw new WriteException($this->path(), $e);
        }
    }

    private $generator;
    private $headerWritten;
}
