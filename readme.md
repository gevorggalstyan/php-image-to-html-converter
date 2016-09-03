# Image to HTML Converter

## Description and Simple Usage

This is a library helping to convert JPG and PNG images to HTML.

The library was written to fix image issues in emails. Sometimes when 
emails contain images they may be blocked in a very unpredicted manner.
With the help of this library you can convert image to a HTML table or 
a style attribute value.

The usage is very simple.

At the top of the PHP file where you want to use the file you specify 
that you want to use the lib.

```php

<?php

use \GevorgGalstyan\Image2HTML\Converter;

```

After that you can start using the converter.

```php

$image_converter = new Converter('./images/logo.png');
$image_converter->set_width(256);

$table_html = $image_converter->get_as_table();

echo $table_html;

```

This will generate a table with 1px X 1px cells representing each pixel
of the image.

Usually this method is guaranteed to be successfully delivered to any 
email client and browser. But as the html of the image becomes very long
the email may be truncated in some email services and additional 
**Show full message** button click may be required.

You can also get the image as a style attribute value.

```php

$image_converter = new Converter('./images/logo.png');
$image_converter->set_width(256);

$style_html = $image_converter->get_as_style();

echo '<div style="' . $style_html . '"></div>';

```

## Documentation

### Constructor

`__construct` function has several arguments:

```
public function __construct($file_name,
                            $width = 100,
                            $color_type = Converter::BEST,
                            $pixel_size = 1,
                            $blur = 0,
                            $true_color = TRUE) 
                            {
                            ...
                            }
```

As you may notice only `$file_name` is required. The others have 
default values.

Acceptable values for `$color_type` are

```php
    const HEXA = 0;
    const RGBA = 1;
    const BEST = 2;
```

### Public functions

You can also set the variable values after creating the converter with
these functions. Here are their blueprints from the library.

```php

public function set_path($path) {...}
public function set_color_type($type) {...}
public function set_width($width) {...}
public function set_pixel_size($size) {...}
public function set_blur($blur) {...}
public function set_true_color($true_color) {...}

```

All these values have getters too.

Usually you need to set the width of the image and keep the default
values for the rest. That is why the width is the second attribute in
the constructor. So the shorter way of one of above examples will be

```php

$image_converter = new Converter('./images/logo.png', 256);

$table_html = $image_converter->get_as_table();

echo $table_html;

```