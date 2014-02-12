# Fixie

*YAML-based data fixtures.*

[![There is no current stable version][version-image]][Semantic versioning]
[![Current build status image][build-image]][Current build status]
[![Current coverage status image][coverage-image]][Current coverage status]

## Installation and documentation

- Available as [Composer] package [eloquent/dumpling].
- [API documentation] available.

## What is Fixie?

Fixie is a format for storing tabular data. It blends the strengths of [YAML]
and [CSV] to produce a syntax that is well suited for both human and machine
readers.

The Fixie syntax is actually a subset of [YAML 1.2], meaning that any given
example of Fixie syntax is also perfectly valid YAML. Unlike free-form YAML
however, all Fixie variants can be read row-by-row, which allows for minimal
memory usage when reading large amounts of data.

## Output styles

### Compact style

The default style, recommended for data sets of any size.

```yaml
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
```

### Output style variants

Fixie implements multiple output styles. In addition to the 'aligned'
[output styles](#output-styles) above, there are non-aligned variants of both
compact and expanded styles that reduce file size at the cost of reduced human
readability.

The `FixtureWriter` class takes, as its first constructor parameter, a class
name to use when opening a file or stream for writing. The options available
are (in the `Eloquent\Fixie\Writer` namespace):

#### SwitchingCompactFixtureWriteHandle

This variant will buffer up to an approximate given data size (defaults to
10MiB). If the data written is within the size limit, the output will be that
produced by the [AlignedCompactFixtureWriteHandle](#alignedcompactfixturewritehandle).
If the size limit is exceeded, this variant will switch to unbuffered output
using the [CompactFixtureWriteHandle](#compactfixturewritehandle).

This is the default variant used by Fixie, as it offers the best compromise
between memory usage and human readability.

#### AlignedCompactFixtureWriteHandle

Writes rows in the 'compact' style, and keeps column headers and row values
aligned with each other. This style is excellent for human readability but poor
for large data sets as the data must be buffered in memory. Unless the maximum
data size is known in advance, it is recommended to use the
[SwitchingCompactFixtureWriteHandle](#switchingcompactfixturewritehandle)
instead.

#### CompactFixtureWriteHandle

Writes rows in the 'compact' style, using the minimal amount of whitespace. This
variant is excellent for any data size, but is not as good for human readability
as other options. If human readability is not an issue, use this variant.

#### AlignedExpandedFixtureWriteHandle

Writes rows in the 'expanded' style, and aligns row values. A versatile variant
that produces a much more vertically elongated output. Good for both human
readability and memory usage.

#### ExpandedFixtureWriteHandle

Writes rows in the 'expanded' style, but does not align row values. Only useful
if the data should be output in a similar way to typical YAML renderers.

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

### Opening streams

```php
$stream = fopen('php://temp', 'wb');
$handle = $writer->openStream($stream);
```

```php
$stream = fopen('data://text/plain;base64,LSBmb28NCi0gYmFy', 'rb');
$handle = $reader->openStream($stream);
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

<!-- References -->

[CSV]: http://en.wikipedia.org/wiki/Comma-separated_values
[YAML 1.2]: http://www.yaml.org/spec/1.2/spec.html
[YAML]: http://yaml.org/

[API documentation]: http://lqnt.co/dumpling/artifacts/documentation/api/
[Composer]: http://getcomposer.org/
[build-image]: http://img.shields.io/travis/eloquent/dumpling/develop.svg "Current build status for the develop branch"
[Current build status]: https://travis-ci.org/eloquent/dumpling
[coverage-image]: http://img.shields.io/coveralls/eloquent/dumpling/develop.svg "Current test coverage for the develop branch"
[Current coverage status]: https://coveralls.io/r/eloquent/dumpling
[eloquent/dumpling]: https://packagist.org/packages/eloquent/dumpling
[Semantic versioning]: http://semver.org/
[version-image]: http://img.shields.io/:semver-0.0.0-red.svg "This project uses semantic versioning"
