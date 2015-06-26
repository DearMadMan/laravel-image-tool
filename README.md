# Captcha
Simple ImageTool for laravel 5



## Installation

The ImageTool Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the
`dearmadman/laravel-image-tool` package and setting the `minimum-stability` to `dev` (required for Laravel 5) in your
project's `composer.json`.

```json
{
    "require": {
        "laravel/framework": "5.0.*",
        "dearmadman/laravel-image-tool": "dev-master"
    },
    "minimum-stability": "dev"
}
```

## Usage

To use the Captcha Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.

Find the `providers` key in `app/config/app.php` and register the Captcha Service Provider.

```php
    'providers' => array(
        // ...
        'Dearmadman\ImageTool\ImageToolServiceProvider',
    )
```

## Example Usage

```php

    // [your site path]/app/routes.php

   Route::Post('image-tool',function(){
     $target=false;
     if (config('image.compress_config_enable')) {
         /* compress image */
        $image_tool = ImageTool::GetInstance();
        $arr=[
            'jpeg_quality'=>config('image.compress_rate'),
            'cover_img'=>config('image.compress_cover'),
        ];
        $image_tool->SetConfig($arr);
        $target = $image_tool->GetImageFromString($target,'img');
      }

     return $target ? $target : "false" ;

	});



```

^_^