<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

use Eloquent\Fixie\Reader\FixtureReader;
use Eloquent\Fixie\Writer\FixtureWriter;

class FunctionalTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->writer = new FixtureWriter(null, null, $this->isolator);
        $this->reader = new FixtureReader(null, $this->isolator);

        $this->handles = array();

        $this->output = '';
        $self = $this;
        Phake::when($this->isolator)->fwrite(Phake::anyParameters())->thenGetReturnByLambda(
            function ($h, $data) use ($self) {
                $self->output .= $data;
            }
        );

        $this->sampleData = array(
            array(
                'name' => 'Hydrogen',
                'symbol' => 'H',
                'number' => 1,
                'weight' => 1.00794,
                'metallic' => false,
                'group' => null,
            ),
            array(
                'name' => 'Helium',
                'symbol' => 'He',
                'number' => 2,
                'weight' => 4.002602,
                'metallic' => false,
                'group' => 'Noble gas',
            ),
            array(
                'name' => 'Lithium',
                'symbol' => 'Li',
                'number' => 3,
                'weight' => 6.941,
                'metallic' => true,
                'group' => 'Alkali metal',
            ),
            array(
                'name' => 'Beryllium',
                'symbol' => 'Be',
                'number' => 4,
                'weight' => 9.012182,
                'metallic' => true,
                'group' => 'Alkaline earth metal',
            ),
            array(
                'name' => 'Boron',
                'symbol' => 'B',
                'number' => 5,
                'weight' => 10.811,
                'metallic' => true,
                'group' => null,
            ),
            array(
                'name' => 'Carbon',
                'symbol' => 'C',
                'number' => 6,
                'weight' => 12.0107,
                'metallic' => false,
                'group' => null,
            ),
            array(
                'name' => 'Nitrogen',
                'symbol' => 'N',
                'number' => 7,
                'weight' => 14.0067,
                'metallic' => false,
                'group' => 'Pnictogen',
            ),
            array(
                'name' => 'Oxygen',
                'symbol' => 'O',
                'number' => 8,
                'weight' => 15.9994,
                'metallic' => false,
                'group' => 'Chalcogen',
            ),
            array(
                'name' => 'Fluorine',
                'symbol' => 'F',
                'number' => 9,
                'weight' => 18.9984032,
                'metallic' => false,
                'group' => 'Halogen',
            ),
            array(
                'name' => 'Neon',
                'symbol' => 'Ne',
                'number' => 10,
                'weight' => 20.1797,
                'metallic' => false,
                'group' => 'Noble gas',
            ),
        );
    }

    protected function tearDown()
    {
        foreach ($this->handles as $handle) {
            if (!$handle->isClosed()) {
                $handle->close();
            }
        }
    }

    protected function readFilename($data)
    {
        return sprintf('data://text/plain;base64,%s', base64_encode($data));
    }

    public function testOutputStyleExamplesCompact()
    {
        $yaml = <<<'EOD'
columns:
 [name,      symbol, number, weight,     metallic, group                 ]
data: [
 [Hydrogen,  H,      1,      1.00794,    false,    null                  ],
 [Helium,    He,     2,      4.002602,   false,    'Noble gas'           ],
 [Lithium,   Li,     3,      6.941,      true,     'Alkali metal'        ],
 [Beryllium, Be,     4,      9.012182,   true,     'Alkaline earth metal'],
 [Boron,     B,      5,      10.811,     true,     null                  ],
 [Carbon,    C,      6,      12.0107,    false,    null                  ],
 [Nitrogen,  N,      7,      14.0067,    false,    Pnictogen             ],
 [Oxygen,    O,      8,      15.9994,    false,    Chalcogen             ],
 [Fluorine,  F,      9,      18.9984032, false,    Halogen               ],
 [Neon,      Ne,     10,     20.1797,    false,    'Noble gas'           ],
]

EOD;
        $this->handles[] = $readHandle = $this->reader->openFile($this->readFilename($yaml));
        $this->handles[] = $writeHandle = $this->writer->openFile('php://temp');
        $writeHandle->writeAll($this->sampleData);
        $writeHandle->close();

        $this->assertSame($this->sampleData, $readHandle->readAll());
        $this->assertSame($yaml, $this->output);
    }

    public function testOutputStyleExamplesExpanded()
    {
        $this->writer = new FixtureWriter(
            'Eloquent\Fixie\Writer\AlignedExpandedFixtureWriteHandle',
            null,
            $this->isolator
        );
        $yaml = <<<'EOD'
- name:     Hydrogen
  symbol:   H
  number:   1
  weight:   1.00794
  metallic: false
  group:    null

- name:     Helium
  symbol:   He
  number:   2
  weight:   4.002602
  metallic: false
  group:    'Noble gas'

- name:     Lithium
  symbol:   Li
  number:   3
  weight:   6.941
  metallic: true
  group:    'Alkali metal'

- name:     Beryllium
  symbol:   Be
  number:   4
  weight:   9.012182
  metallic: true
  group:    'Alkaline earth metal'

- name:     Boron
  symbol:   B
  number:   5
  weight:   10.811
  metallic: true
  group:    null

- name:     Carbon
  symbol:   C
  number:   6
  weight:   12.0107
  metallic: false
  group:    null

- name:     Nitrogen
  symbol:   N
  number:   7
  weight:   14.0067
  metallic: false
  group:    Pnictogen

- name:     Oxygen
  symbol:   O
  number:   8
  weight:   15.9994
  metallic: false
  group:    Chalcogen

- name:     Fluorine
  symbol:   F
  number:   9
  weight:   18.9984032
  metallic: false
  group:    Halogen

- name:     Neon
  symbol:   Ne
  number:   10
  weight:   20.1797
  metallic: false
  group:    'Noble gas'

EOD;
        $this->handles[] = $readHandle = $this->reader->openFile($this->readFilename($yaml));
        $this->handles[] = $writeHandle = $this->writer->openFile('php://temp');
        $writeHandle->writeAll($this->sampleData);
        $writeHandle->close();

        $this->assertSame($this->sampleData, $readHandle->readAll());
        $this->assertSame($yaml, $this->output);
    }
}
