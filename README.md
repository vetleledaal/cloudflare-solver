# cloudflare-solver

## Install within another project with Composer
[Make sure composer is installed.](https://getcomposer.org/doc/00-intro.md)

Add this repository to your `composer.json` file:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/vetleledaal/cloudflare-solver"
        }
    ],
    "require": {
        "vetleledaal/cloudflare-solver": "dev-master"
    }
}
```
Install with `composer install`.

To use the examples replace
```php
include 'cloudflare.php';
```
with
```php
include 'vendor/autoload.php';
```
