Cowegis Contao Geocoder
=======================

[![Build Status](http://img.shields.io/travis/cowegis/contao-geocoder/master.svg?style=flat-square)](https://travis-ci.org/cowegis/contao-geocoder)
[![Version](http://img.shields.io/packagist/v/cowegis/contao-geocoder.svg?style=flat-square)](http://packagist.org/packages/cowegis/contao-geocoder)
[![License](http://img.shields.io/packagist/l/cowegis/contao-geocoder.svg?style=flat-square)](http://packagist.org/packages/cowegis/contao-geocoder)
[![Downloads](http://img.shields.io/packagist/dt/cowegis/contao-geocoder.svg?style=flat-square)](http://packagist.org/packages/cowegis/contao-geocoder)

This extension integrates the [Geocoder PHP library](http://geocoder-php.org) into Contao CMS.
It's designed for other extensions to use a common geocoder implementation.

Features
--------

 - Geocoder service for other extensions
 - Out of the box support for `nominatim` and `google maps`
 - Extandable for other providers
 - Database driven configuration of providers in Contao backend 
 - Application driven configuration of providers
 - API endpoint for geocode queries
 
Requirements
------------

 - Contao `^4.4`
 - PHP `>= 7.1`

Installation
------------

### Contao Manager

Search the `cowegis/contao-geocoder` package and install it.

### Composer

```
 composer require cowegis/contao-geocoder ^0.2.0
```

Usage
-----

### Configuration

Optional application configuration

```yaml
# app/config/config.yml

cowegis_contao_geocoder:
    providers:
      foo:
        title: "Foo Geocoder"
        type: "google_maps"
        google_api_key: "ABC"
      bar:
        title: "Bar Geocoder"
        type: "nominatim"
    default_provider: "bar"
```

### Code example

```php
<?php
 
use Cowegis\ContaoGeocoder\Provider\Geocoder;

final class MyService
{
    private $geocoder;
    
    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }
    
    public function geocode(string $address) : \Geocoder\Location
    {
        return $this->geocoder
            // Optional use a specific geocoder. Otherwise the default provider is used 
            ->using('foo')
            ->geocodeQuery(\Geocoder\Query\GeocodeQuery::create($address))
            ->first();
    }
}

```

License
-------

This extension is licensed under [LGPL-3.0-or-later](LICENSE)
