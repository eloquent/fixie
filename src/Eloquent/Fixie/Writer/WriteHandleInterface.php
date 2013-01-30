<?php // @codeCoverageIgnoreStart

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\HandleInterface;

interface WriteHandleInterface extends HandleInterface
{
    /**
     * @param array $row
     */
    public function write(array $row);

    /**
     * @param array<array> $rows
     */
    public function writeAll(array $rows);
}
