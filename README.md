# Woption

Woption (_WordPress Option_) is free & lightweight framework to manage options in WordPress.


## Get started

If you like to start using the framework, you need to:

- Clone repository
- Enable autoloader via Composer

> `composer` command used below assumes you have install Compose globally.

```
git clone https://github.com/bologer/Woption woption 
cd woption
composer dump-autoload
```

Then in your plugin you need to require Woption autoload file and you are ready to code! 

```php
<?php
/**
 * Plugin Name: Plugin name
 * Plugin URI: https://github.com/bologer/Woption
 * Description: Some plugin description.
 * Version: 0.1
 * Author: Woption
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/woption/woption.php';

class My_Plugin extends \Woption\OptionManager {
	// Happy coding
}
```

Happy coding!

## Example Projects 

Here is [demo plugin](https://github.com/bologer/Woption-Examples) built with Woption.

## Documentation

All of the documentation can be seen in the wiki page, here is the list of what is available by now:


- [How to create pages](https://github.com/bologer/Woption/wiki/Creating-Pages)
- [Available fields](https://github.com/bologer/Woption/wiki/Fields)

Here is more to come :)

## Contribution 

TBD
