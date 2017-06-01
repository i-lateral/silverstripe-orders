Silverstripe Orders Module
==========================

Provides a simple order managemend interface, as well as an order object
that can be easily worked with from your own code.

In the future, it would be nice to add the ability to generate an XML
API to allow third party software to setup and manage orders (which
could be useful for integrating with inshop POI systems).

This module is designed to replace the current i-lateral Silverstripe
commerce module and will only provide the order admin and management
part of the module.


Also Provides a shopping cart interface to a Silverstripe install that
allows users to add items to a shopping cart, enter their personal
details and make payments.


## Dependancies

* [SilverStripe Framework 3.1.x](https://github.com/silverstripe/silverstripe-framework)
* [Grid Field Bulk Editing Tools](https://github.com/colymba/GridFieldBulkEditingTools)
* [Grid Field Extensions](https://github.com/ajshort/silverstripe-gridfieldextensions)
* [VersionedDataObjects](https://github.com/heyday/silverstripe-versioneddataobjects)
* [SiteConfig](https://github.com/silverstripe/siteconfig)

## Also Integrates With

* Silverstripe Reports
* Silverstripe Subsites
* Silverstripe CMS
* Silverstripe Subsites

**NOTE** This module will need **either** the [CMS module](https://github.com/silverstripe/silverstripe-cms),
or the 

## Order Admin

If you would like to generate custom orders, that can be tracked in the
admin interface, then you will need to install the silverstripe orders
module as well.
