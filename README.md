# network-objects

A set of objects that adds some convenience in handling IP and networks.

![Minimum PHP Version](https://img.shields.io/badge/php-≥%205.6-8892BF.svg)
![Build Status](https://img.shields.io/travis/Dormilich/network-objects/master.svg)
![License](https://img.shields.io/github/license/dormilich/network-objects.svg)

## Installation

Install via Composer:
```
composer require dormilich/network-objects
```

## Using IPs

### Supported Input Formats

- IPv4 & IPv6 addresses (`'192.168.2.1'`, `'2001:db8:85a3::'`)
- zero-padded IPv4 addresses (`'192.168.002.001'`, cf. [ARIN RegRWS](https://www.arin.net/resources/restfulpayloads.html#netblock))
- Decimal format (`3232236033`), note: a numeric string will always be interpreted as an IPv6.
```php
echo new IP(1);     // 0.0.0.1
echo new IP('1');   // ::1
```
- Binary format (`'11000000101010000000001000000001'`)
- Hexadecimal format (`'c0a80201'`)
- *in_addr* (byte stream) format, cf. [inet_pton()](https://secure.php.net/inet-pton)
- `IP` objects

### Supported Output formats

- unpadded IPv4 & IPv6 addresses
```php
(string) new IP('2001:0db8:85a3::')     // '2001:db8:85a3::'
```
- Decimal format, IPv4 as integers, IPv6 as strings.
```php
(new IP('192.168.2.1'))->toDec()        // 3232236033
(new IP('::c0a8:201'))->toDec()         // '3232236033'
```
- Binary format
```php
(new IP('192.168.2.1'))->toBin()        // '11000000101010000000001000000001'
```
- Hexadecimal format
```php
(new IP('192.168.2.1'))->toHex()        // 'c0a80201'
```
- Byte stream (use with the bitwise operations)
```php
(new IP('192.168.2.1'))->inAddr()
```

### IP Operations

Get the next/previous IP address
```php
echo (new IP('192.168.2.1'))->next()    // 192.168.2.2
echo (new IP('192.168.2.1'))->prev()    // 192.168.2.0
```

Compare IP addresses
```php
$a = new IP('192.168.2.1');
$b = new IP('192.168.5.9');

$a->is( $b );   // false
# greater than
$a->gt( $b );   // false
# less than
$a->lt( $b );   // true
```

## Using Networks

### Supported Input Formats

- CIDR (`'192.168.2.1/29'`)
- `Network` objects

### Network Operations

Network characteristics
```php
$net = new Network('192.168.2.1/29');

# network address
(string) $net->getNetwork()     // '192.168.2.0'
# broadcast address
(string) $net->getBroadcast()   // '192.168.2.7'
# netmask
(string) $net->getNetmask()     // '255.255.255.248'
# CIDR
$net->getCIDR()                 // '192.168.2.0/29'
# CIDR prefix length
$net->getPrefixLength()         // 29
# number of IPs
$net->count()                   // '8' (IPv6 can easily exceed the integer range!)
```

Mind that network & broadcast address neither make sense in IPv6 context nor for IPv4 peer-to-peer networks.

Get network host addresses
```php
$net = new Network('192.168.2.1/29');
$hosts = $net->getHosts();      // Range(192.168.2.1, 192.168.2.6)
```

## Using Ranges

### Supported Input Formats

- Start & end IP (object)
- CIDR (`'192.168.2.0/29'`)
- IP range string (`'192.168.2.1 - 192.168.2.6'`)
- `Network` or `Range` object

### Range Operations

Range characteristics
```php
$range = new Range('192.168.31.240 - 192.168.35.193');

# start IP
(string) $range->getFirstIP()   // '192.168.31.240'
# end IP
(string) $range->getLastIP()    // '192.168.35.193'
# number of IPs
$range->count()                 // '978'
```

Express range as a list of networks
```php
$range = new Range('192.168.31.240', '192.168.35.193');

foreach($range->getNetworks() as $net) {
    echo $net->getCIDR(), PHP_EOL;
}
```
```
192.168.31.240/28
192.168.32.0/23
192.168.34.0/24
192.168.35.0/25
192.168.35.128/26
192.168.35.192/31
```

Networks also work as Ranges.

## Thanks

This work was heavily inspired by [S1lentium/IPTools](https://github.com/S1lentium/IPTools).
