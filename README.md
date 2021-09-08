# Flagbit ProductClonerBundle for Akeneo PIM

[![CI](https://github.com/flagbit/akeneo-product-cloner/actions/workflows/ci.yml/badge.svg)](https://github.com/flagbit/akeneo-product-cloner/actions/workflows/ci.yml)
[![Total Downloads](https://poser.pugx.org/flagbit/product-cloner-bundle/downloads?format=flat-square)](https://packagist.org/packages/flagbit/product-cloner-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/flagbit/akeneo-product-cloner.svg?style=flat-square)](https://scrutinizer-ci.com/g/Flagbit/akeneo-product-cloner)
[![Packagist Version](https://img.shields.io/packagist/v/flagbit/product-cloner-bundle.svg?style=flat-square)](https://packagist.org/packages/flagbit/product-cloner-bundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This bundle is aimed to offer product clone functionality within Akeneo PIM.

## Functionalities ##
* Clone a product model
* Clone a product or a variant product

> _Note:_ As of Akeneo 5, the Enterprise Edition ships with an included clone feature, therefore this bundle
is not compatible with Akeneo 5 Enterprise Edition! For the Akeneo 5 Community Edition, the bundle will work as before.

## Installation ##

You can install the package with the following command.

``` bash
composer require flagbit/product-cloner-bundle
```

### Enable the bundle ####

Add the bundle to the `config/bundles.php` file:

``` php
<?php

return [
    \Flagbit\Bundle\ProductClonerBundle\FlagbitProductClonerBundle::class => ['all' => true],
    // ...
];
```

#### Import the routing ####
Now that you have activated and configured the bundle, all that is left to do is import the FlagbitProductClonerBundle
routing files.

``` yaml
# config/routes/product_cloner.yml
flagbit_product_cloner:
    resource: "@FlagbitProductClonerBundle/Resources/config/routing.yml"
```

Build and install the new front-end dependencies (new translations, etc.)

``` bash
make cache assets css javascript-prod
```

## How to use it ##
Open a product and there open the **options dialog** at the **right corner**.
You can see it here on the screen:
![Product Model Clone Screen](https://raw.githubusercontent.com/Flagbit/akeneo-product-cloner/master/screens/product_model_clone.png "Product Model Clone Screen")

After clicking on **clone** you will see this **dialog**:
![Product Model Clone Dialog Screen](https://raw.githubusercontent.com/Flagbit/akeneo-product-cloner/master/screens/product_model_clone_dialog.png "Product Model Clone Dialog Screen")

Put in a new **product code** and click on **save**. After that check if all the data is correct for the new product.

### Configuration

You don't need to configure this bundle by default.
The default behaviour is to clone all product or product model attributes except the unique attributes.
In addition, you can specify a blacklist of attributes that shouldn't be cloned:

``` yaml
flagbit_product_cloner.attribute_blacklist:
        - your_attribute_code1
        - your_attribute_code2
        - your_attribute_code3
        ...
```

## Akeneo Compatibility ##

This extension supports the latest Akeneo PIM CE/EE stable versions:

* 2.3 (LTS)
* 3.0 (LTS)
* 3.2 (LTS)
* 4.0
* 5.0

## License ##

The ProductClonerBundle is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
