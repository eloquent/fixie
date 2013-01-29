# Fixie

*YAML-based data fixtures.*

[![Build status](https://secure.travis-ci.org/eloquent/fixie.png)](http://travis-ci.org/eloquent/fixie)
[![Test coverage](http://eloquent.github.com/fixie/coverage-report/coverage.png)](http://eloquent.github.com/fixie/coverage-report/index.html)

## What is Fixie?

Fixie is a format for storing tabular data. It blends the strengths of
[YAML](http://yaml.org/) and [CSV](http://en.wikipedia.org/wiki/Comma-separated_values)
to produce a syntax that is well suited for both human and machine readers.

The fixie syntax is actually a subset of [YAML 1.2](http://www.yaml.org/spec/1.2/spec.html),
meaning that any given example of Fixie syntax is also perfectly valid YAML.
Unlike free-form YAML however, all Fixie variants can be read row-by-row, which
allows for minimal memory usage when reading large amounts of data.

## Examples

### Compact style

```yaml
columns:
 [name,    mass,       radius,     humanPopulation]
data: [
 [Mercury, 3.302e+23,  2.4397e+6,  0              ],
 [Venus,   4.869e+24,  6.0518e+6,  0              ],
 [Earth,   5.9742e+24, 6.37814e+6, 6.9737e+9      ],
 [Mars,    6.4191e+23, 3.3972e+6,  0              ],
 [Jupiter, 1.8987e+27, 7.1492e+7,  0              ],
 [Saturn,  5.6851e+26, 6.0268e+7,  0              ],
 [Uranus,  8.6849e+25, 2.5559e+7,  0              ],
 [Neptune, 1.0244e+26, 2.4764e+7,  0              ],
 [Pluto,   1.31e+22,   1.180e+6,   0              ],
]
```

### Expanded style

## Comparison to CSV and YAML

### CSV

- Excellent for machine reading. Row-by-row reading means low memory use.
- No support for types. Every value is a string.
- Human readability is poor, especially with row values of differing lengths.

### YAML

- Type support. Strings, integers, floating-point values, booleans and nulls are
  all supported.
- Very human-readable when formatted correctly.
- Entire file must be read and parsed in one go. Memory usage scales with data
  size.

### Fixie

- Excellent for machine reading. Row-by-row reading means low memory use.
- Type support. Strings, integers, floating-point values, booleans and nulls are
  all supported.
- Very human-readable when formatted correctly.
