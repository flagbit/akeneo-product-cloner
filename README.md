# Flagbit ProductClonerBundle for Akeneo PIM

This bundle is aimed to offer product clone functionality within Akeneo PIM.

## Functionalities ##
* Clone a product model


## Installation ##
You can simply install the package with the following command.

``` bash
composer require flagbit/product-cloner-bundle
```

### Enable the bundle ####

Enable the bundle in the kernel:

``` php
<?php

// app/AppKernel.php

protected function registerProjectBundles()
{
    return [
        // ...
        new Flagbit\Bundle\ProductClonerBundle\FlagbitProductClonerBundle(),
        // ...
    ];
}
```

#### Import the routing ####
Now that you have activated and configured the bundle, all that is left to do is import the FlagbitProductClonerBundle
routing files.

``` yaml
# app/config/routing.yml
flagbit_product_cloner:
    resource: "@FlagbitProductClonerBundle/Resources/config/routing.yml"

```

Clear the cache:

``` bash
php bin/console -e=prod cache:clear
```

Build and install the new front-end dependencies (new translations, etc.)

``` bash
php bin/console pim:installer:assets --symlink --clean --env=prod
yarn run webpack
```

## License ##

The TableAttributeBundle is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
