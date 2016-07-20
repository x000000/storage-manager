# Storage Manager
This project is a PHP library written to make uploading and storing files as easy as possible. And it can make thumbnails you want.

## Requirements & Dependency
The library has the following requirements:
* PHP 5.6+
* The library has a single dependency - [Imagine](http://imagine.readthedocs.io/).
This only needed if you what to make thumbnails with storage-manager.

## Installation
The preferred way to install this library is through composer.

Either run
```
php composer.phar require --prefer-dist x000000/storage-manager
```
or add
```
"x000000/storage-manager": "^0.3"
```

to the require section of your composer.json.

## Usage

### Storing
First of all you need to instantiate Storage object:
```php
<?php

$baseDir = '/path/to/the/storage/directory';
$baseUrl = '/static';
$storage = new \x000000\StorageManager\Storage(
    // this is a full path to the directory where files will be stored in
    $basedir,
    // this is a url to the directory above (how directory will be available by HTTP)
    $baseUrl
);
```

Now we need to store some files there.

We can make it either with `$storage->processFile()` and passing full path to the file we want to store:
```php
<?php

$image = $storage->processFile('/full/path/to/image.png');
```

or if you want to process newly uploaded file you can use `$storage->processUploadedFile()` and passing an item from $_FILES array:
```php
<?php

$image = $storage->processUploadedFile($_FILES['your_file_key']);
```

Now if everything is fine we have `$image` stored in `$baseDir` and renamed with it md5 content accordingly.
For example it could be something like `aed0d71a5adaff3a43b75fb4ccc7ff48.png`.
Yes, we've lost it original name, but we do not store duplicates either.

After successful storing file on your physical disk, you may want to save it to the database too.
And you do not have to save a full path to the file, you may save only a file name - `$image`.

### Displaying
Now you may want to display an `$image` or just share a link to it:
```html
<img src="<?= $storage->getSource($image); ?>" />
```
That's it. By calling `$storage->getSource()` we can get a full url (domain not included) to the source file (not only images).

### Thumbnails
There are use-cases when you do not want to display an original `$image`.
Or you want to display a nice square thumbnail (hello Instagram). Or just resized/watermarked `$image`.

To make it possible there is a helper method `$storage->thumb()`:

```html
This $image resized to fit 300 width
<img src="<?= $storage->thumb($image)->resize(300, null); ?>" />

This $image resized to fit 600 height
<img src="<?= $storage->thumb($image)->resize(null, 600); ?>" />

This $image resized to fit 300x600 pixels size
<img src="<?= $storage->thumb($image)->resize(300, 600); ?>" />

This $image resized by the half with saving original aspect ratio
<img src="<?= $storage->thumb($image)->resize('50%', null); ?>" />


This $image cropped with a rectangle sized 50% x 60% from original image with center in 20x30 on original image
<img src="<?= $storage->thumb($image)->crop(20, 30, '50%', '60%'); ?>" />

This $image cropped with a square with a side size of a lowest $image side.
This is Instagram-ish square crop.
<img src="<?= $storage->thumb($image)->crop('50%', '50%', '100%', '100%', Crop::COVER); ?>" />

This $image cropped with a square with a side size of a greatest $image side.
This is Instagram-ish square crop either, but it adds padding instead of cropping the original.
<img src="<?= $storage->thumb($image)->crop('50%', '50%', '100%', '100%', Crop::CONTAIN); ?>" />

This $image cropped with a rectangle with aspect ratio 16:9.
<img src="<?= $storage->thumb($image)->crop('50%', '50%', '100%', null, 16/9); ?>" />


And of course you can chain resize, crop etc...
<img src="<?= $storage->thumb($image)
    ->crop('50%', '50%', '100%', '100%', Crop::COVER)
    ->resize(300, null); ?>" />


If you do not chain any transform url to the source file will be returned:
<img src="<?= $storage->thumb($image); ?>" />
equals to
<img src="<?= $storage->getSource($image); ?>" />
```

For now, there are only resize and crop supported,
but you can use your own image transforms. For more info see `Transform::add()`.