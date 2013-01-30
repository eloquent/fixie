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

abstract class AbstractWriteHandle extends AbstractHandle implements WriteHandleInterface
{
    /**
     * @param stream{writable: true}|null $stream
     * @param string|null                 $path
     * @param Inline|null                 $renderer
     * @param Isolator|null               $isolator
     */
    public function __construct(
        $stream = null,
        $path = null,
        Inline $renderer = null,
        Isolator $isolator = null
    ) {
        parent::__construct(
            $stream,
            $path,
            $isolator
        );

        if (null === $renderer) {
            $renderer = new Inline;
        }

        $this->renderer = $renderer;
    }

    /**
     * @return Inline
     */
    public function renderer()
    {
        return $this->renderer;
    }

    /**
     * @param array<array> $rows
     */
    public function writeAll(array $rows)
    {
        foreach ($rows as $row) {
            $this->write($row);
        }
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

    /**
     * @param array<integer,string> $columnNames
     * @param array<string,mixed>   $row
     *
     * @return array<integer,mixed>
     */
    protected function projectRow(array $columnNames, array $row)
    {
        $projected = array();
        foreach ($columnNames as $columnName) {
            if (!array_key_exists($columnName, $row)) {
                throw new WriteException($this->path());
            }

            $projected[] = $row[$columnName];
        }

        return $projected;
    }

    private $renderer;
}
