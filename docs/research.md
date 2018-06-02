# PHP Serialization Research

## Types

### Boolean

Booleans are stored as `b:<0,1>;`, where `b:0;` is `false` and `b:1;` is `true`.

```php
serialize(true);
// b:1;

serialize(false);
// b:0;
```

### String

Strings are stored as `s:<n>:"<s>";`, where `<n>` is the length of the string in bytes, and `<s>` is the actual string.

```php
serialize('foo');
// s:3:"foo";

serialize('foo"bar');
// s:7:"foo"bar";

serialize('测');
// s:3:"测";
```

### Integer

Integers are stored as `i:<n>;` where `<n>` is the integer.

```php
serialize(50);
// i:50;
```

### Float

Floats are stored as `d:<n>;` where `<n>` is the float.
The precision of a float is determined by the [`serialize_precision` ini setting](https://secure.php.net/manual/en/ini.core.php#ini.serialize-precision).

```php
serialize(1.5);
// d:1.5;
```

### Null

Null is stored as `N;`.

```php
serialize(null);
// N;
```

### Resource

Resources can't be serialized properly, and attempting to serialize a resource will give you `i:0;`.

### Closure

Closures can't be serialized, and attempting to serialize a closure will throw an `Exception` with the message `Serialization of 'Closure' is not allowed`.

```php
try {
    serialize(function (): void {});
} catch (Exception $e) {
    echo $e->getMessage();
}
// Serialization of 'Closure' is not allowed
```

### Array

Arrays are stored as `a:<n>:{<p>}` where `<n>` is the number of elements in the array, and `<p>` is a list of other types with keys then values following each other.

```php
serialize([
    5 => 1,
    'foo' => 2,
]);
// a:2:{i:5;i:1;s:3:"foo";i:2;}

serialize([1, 2]);
// a:2:{i:0;i:1;i:1;i:2;}

serialize([]);
// a:0:{}
```

### Object

TODO

## Constants

- `NAN`: `d:NAN;`
- `INF`: `d:INF;`
- `-INF`: `d:-INF;`
