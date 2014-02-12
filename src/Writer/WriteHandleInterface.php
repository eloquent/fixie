<?php // @codeCoverageIgnoreStart

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\Exception\WriteException;
use Eloquent\Fixie\Handle\HandleInterface;

/**
 * The interface implemented by writable data handles.
 */
interface WriteHandleInterface extends HandleInterface
{
    /**
     * Write a single data row.
     *
     * @param array<string,mixed> $row The data row.
     *
     * @throws WriteException If data is unable to be written.
     */
    public function write(array $row);

    /**
     * Write a sequence of data rows.
     *
     * @param array<array<string,mixed>> $rows The data rows.
     *
     * @throws WriteException If data is unable to be written.
     */
    public function writeAll(array $rows);
}
