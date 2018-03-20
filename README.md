# Flagbit ProductClonerBundle for Akeneo PIM CE & EE

This bundle is aimed to offer product clone functionality within Akeneo PIM.

### Functionalities ###
* Clone a product model


### Installation ###
Installation is a quick 4 step process:

1. Add the repository
2. Download FlagbitProductClonerBundle using composer
3. Enable the bundle
4. Import the routing

#### Step 1: Add the repository ####
First manually add the repository to your `composer.json` executing the following command:

``` bash
$ composer config repositories.productcloner vcs git@bitbucket.org:flagbit/akeneo-productclonebundle.git
```
#### Step 2: Download FlagbitProductClonerBundle using composer ####
Once the repository was added to your ``composer.json`, install the bundle using the following command:

``` bash
$ composer require flagbit/product-cloner-bundle
```

#### Step 3: Enable the bundle ####

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

#### Step 4: Import the routing ####
Now that you have activated and configured the bundle, all that is left to do is import the FlagbitProductClonerBundle
routing files.

``` yaml
# app/config/routing.yml
flagbit_product_cloner:
    resource: "@FlagbitProductClonerBundle/Resources/config/routing.yml"

```
