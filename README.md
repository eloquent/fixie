# Fixie

*YAML-based data fixtures.*

[![Build status](https://api.travis-ci.org/eloquent/fixie.png)](http://travis-ci.org/eloquent/fixie)
[![Test coverage](http://eloquent.github.com/fixie/coverage-report/coverage.png)](http://eloquent.github.com/fixie/coverage-report/index.html)

## What is Fixie?

Fixie is a format for storing tabular data. It blends the strengths of
[YAML](http://yaml.org/) and [CSV](http://en.wikipedia.org/wiki/Comma-separated_values)
to produce a syntax that is well suited for both human and machine readers.

The fixie syntax is actually a subset of [YAML 1.2](http://www.yaml.org/spec/1.2/spec.html),
meaning that any given example of Fixie syntax is also perfectly valid YAML.
Unlike free-form YAML however, all Fixie variants can be read row-by-row, which
allows for minimal memory usage when reading large amounts of data.

## Output styles

### Compact style

The default style, recommended for data sets of any size. If the data set is
very large, a more compact [variant](#output-style-variants) may be better
suited.

```yaml
columns:
 [name,      symbol, number, weight,     metallic, group                ]
data: [
 [Hydrogen,  H,      1,      1.00794,    false,    ~                    ],
 [Helium,    He,     2,      4.002602,   false,    Noble gas            ],
 [Lithium,   Li,     3,      6.941,      true,     Alkali metal         ],
 [Beryllium, Be,     4,      9.012182,   true,     Alkaline earth metal ],
 [Boron,     B,      5,      10.811,     true,     ~                    ],
 [Carbon,    C,      6,      12.0107,    false,    ~                    ],
 [Nitrogen,  N,      7,      14.0067,    false,    Pnictogen            ],
 [Oxygen,    O,      8,      15.9994,    false,    Chalcogen            ],
 [Fluorine,  F,      9,      18.9984032, false,    Halogen              ],
 [Neon,      Ne,     10,     20.1797,    false,    Noble gas            ],
]
```

### Expanded style

This style is useful for small data sets where a more vertical layout improves
human readability.

```yaml
- name:     Hydrogen
  symbol:   H
  number:   1
  weight:   1.00794
  metallic: false
  group:    ~

- name:     Helium
  symbol:   He
  number:   2
  weight:   4.002602
  metallic: false
  group:    Noble gas

- name:     Lithium
  symbol:   Li
  number:   3
  weight:   6.941
  metallic: true
  group:    Alkali metal

- name:     Beryllium
  symbol:   Be
  number:   4
  weight:   9.012182
  metallic: true
  group:    Alkaline earth metal

- name:     Boron
  symbol:   B
  number:   5
  weight:   10.811
  metallic: true
  group:    ~

- name:     Carbon
  symbol:   C
  number:   6
  weight:   12.0107
  metallic: false
  group:    ~

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
  group:    Noble gas
```

### Output style variants

Fixie implements multiple output styles. In addition to the 'aligned'
[output styles](#output-styles) above, there are non-aligned variants of both
compact and expanded styles that reduce file size at the cost of reduced human
readablity.

Be aware that the 'aligned compact' output style must buffer every row that is
written, and only actually writes anything when the handle is closed. This makes
it poorly suited for very large data sets as every row is retained in memory.

The `FixtureWriter` class takes, as its first constructor parameter, a class
name to use when opening a file or stream for writing. The options available
are (in the `Eloquent\Fixie\Writer` namespace):

- `AlignedCompactFixtureWriteHandle` (default)
- `AlignedExpandedFixtureWriteHandle`
- `CompactFixtureWriteHandle`
- `ExpandedFixtureWriteHandle`

## Usage

### A little setup

```php
use Eloquent\Fixie\Reader\FixtureReader;
use Eloquent\Fixie\Writer\FixtureWriter;

$writer = new FixtureWriter;
$reader = new FixtureReader;

// some tabular data
$data = array(
    array('foo' => 'bar',  'baz' => 'qux'),
    array('foo' => 'doom', 'baz' => 'splat'),
);
```

### Writing row-by-row

```php
$handle = $writer->openFile('/path/to/file');
foreach ($data as $row) {
    $handle->write($row);
}
$handle->close();
```

### Writing an entire set of data

```php
$handle = $writer->openFile('/path/to/file');
$handle->writeAll($data);
$handle->close();
```

### Reading row-by-row

```php
$handle = $reader->openFile('/path/to/file');
$data = array();
foreach ($handle as $row) {
    $data[] = $row;
}
$handle->close();
```

### Reading an entire set of data

```php
$handle = $reader->openFile('/path/to/file');
$data = $handle->readAll();
$handle->close();
```

### Reading rows manually

```php
$handle = $reader->openFile('/path/to/file');
$row = $handle->read();
if (null !== $row){
    // some custom logic
}
$handle->close();
```

## Comparison to CSV and YAML

### CSV

- Excellent for machine reading. Row-by-row reading means low memory use.
- No support for types. Every value is a string.
- Human readability is poor, especially with row values of differing lengths.
- String encoding support is unpredictable across implementations. This makes
  it a poor choice for multilingual data.

### YAML

- Type support. Strings, integers, floating-point values, booleans and nulls are
  all supported.
- Very human-readable when formatted correctly.
- Good string encoding support.
- Entire file must be read and parsed in one go. Memory usage scales with data
  size.

### Fixie

- Excellent for machine reading. Row-by-row reading means low memory use.
- Type support. Strings, integers, floating-point values, booleans and nulls are
  all supported.
- Very human-readable when formatted correctly.
- Good string encoding support.
