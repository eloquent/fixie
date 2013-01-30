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

use Eloquent\Fixie\Handle\HandleFactoryInterface;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

class FixtureWriter implements HandleFactoryInterface
{
    /**
     * @param string|null   $handleClassName
     * @param Inline|null   $renderer
     * @param Isolator|null $isolator
     */
    public function __construct(
        $handleClassName = null,
        Inline $renderer = null,
        Isolator $isolator = null
    ) {
        if (null === $handleClassName) {
            $handleClassName = __NAMESPACE__.'\AlignedCompactFixtureWriteHandle';
        }
        if (null === $renderer) {
            $renderer = new Inline;
        }

        $this->handleClassName = $handleClassName;
        $this->renderer = $renderer;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return string
     */
    public function handleClassName()
    {
        return $this->handleClassName;
    }

    /**
     * @return Inline
     */
    public function renderer()
    {
        return $this->renderer;
    }

    /**
     * @param string $path
     *
     * @return WriteHandleInterface
     */
    public function openFile($path)
    {
        $className = $this->handleClassName();

        return new $className(
            null,
            $path,
            $this->renderer(),
            $this->isolator
        );
    }

    /**
     * @param stream{writable: true} $stream
     * @param string|null            $path
     *
     * @return WriteHandleInterface
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

    private $handleClassName;
    private $renderer;
    private $isolator;
}
