# Spatie Media Library

## Prepare Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class YourModel extends Model implements HasMedia
{
    use InteractsWithMedia;
}
```

## Associate File

```php
//move the file
$yourModel = YourModel::find(1);
$yourModel
   ->addMedia($pathToFile)
   ->toMediaCollection();

//copy the file and keep original in place
$yourModel
   ->addMedia($pathToFile)
   ->preservingOriginal()
   ->toMediaCollection();


//add media from a url
$url = 'http://medialibrary.spatie.be/assets/images/mountain.jpg';
$yourModel
   ->addMediaFromUrl($url)
   ->toMediaCollection();

//add media from s3 disk
$yourModel
   ->addMediaFromDisk('/path/to/file', 's3')
   ->toMediaCollection();


//use another disk
$yourModel
->addMedia($pathToFile)
->toMediaCollection('images','s3');

$yourModel
->addMedia($pathToFile)
->usingName('new name')
->usingFileName('otherFileName.txt')
->sanitizingFileName(function($fileName) {
    return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
})
->toMediaCollection('images');
```

Notes:

- The media library does not restrict what kinds of files may be uploaded or associated with models.
  If you are accepting file uploads from users, you should take steps to validate those uploads,
  to ensure you don't introduce security vulnerabilities into your project.

- addMedia() doesnt execute the action, it just prepare media builder

- on call toMediaCollection(), this will upload the file and create a media row inside media table

- Security note. By default, Media Library rejects uploads whose file name contains a potentially executable extension such as .php or .phtml
  The blocked extensions can be configured (and an opt-in allowlist enabled) in config/media-library.php: Passing your own callable to sanitizingFileName fully replaces the default sanitizer (including this protection),
  so make sure your callable does not let dangerous file names through.

  ```text
  // Reject these extensions anywhere in the file name.
  'disallowed_extensions' => ['php', 'phtml', 'phar', 'htaccess', /* ... */],

  // When set, only accept uploads whose final extension is in this list.
  'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
  ```

## Retrieving Media

```php

//retrieve all media rows inside default collection as a MediaCollection object
$mediaItems = $yourModel->getMedia();

//retrieve all media rows from all collections
$mediaItems = $yourModel->getMedia("*");

//retrieve all media from images collection
$mediaItems = $YourModel->getMedia('images');


$publicUrl = $mediaItems[0]->getUrl(); //https://example.com/storage/123/manual.pdf
$fullPathOnDisk = $mediaItems[0]->getPath(); ///var/www/html/storage/app/public/123/manual.pdf
$temporaryS3Url = $mediaItems[0]->getTemporaryUrl(Carbon::now()->addMinutes(5)); // Temporary S3 url (Keep first parameter null to use default expiration time)

```

```php
$media = $yourModel->getFirstMedia(); // from default collection
$media = $yourModel->getFirstMedia('images'); // from images collection
$media = $yourModel->getFirstMedia('images','thumb'); // get converstion thumb from images collection

$url = $yourModel->getFirstMediaUrl(); // from default collection
$url = $yourModel->getFirstMediaUrl('images'); // from images collection
$url = $yourModel->getFirstMediaUrl('images','thumb'); // get converstion thumb from images collection

$media = $yourModel->getLastMedia();
$url = $yourModel->getLastMediaUrl();

```

## Delete

```php

$mediaItems[0]->delete();

$yourModel->delete(); // all associated files will be deleted as well

$yourModel->deletePreservingMedia(); // all associated files will be preserved

$yourModel->clearMediaCollection(); // all media in the "default" collection will be deleted

$yourModel->clearMediaCollection('images'); // all media in the images collection will be deleted

```

Note:

- if model is soft delete, then delete model won't delete its media

## Register Collection

```php

// in your model

public function registerMediaCollections(): void
{
    $this->addMediaCollection('my-collection')
        //add options
        ...

    // you can define as many collections as needed
    $this->addMediaCollection('my-other-collection')
        //add options
        ...
}

```

Note:

- you don't have to register the collection here in order to use toMediaCollection('my-collection')..
  this register is useful to add custom options for this collection

---

```php

->addMediaCollection('avatar')
    ->registerMediaConversions(function (Media $media) {
        $this
            ->addMediaConversion('thumb')
            ->width(100)
            ->height(100);
    });
```

Note: when you upload file into avatar collection, it will create one conversion image called thumb with 100x100
this conversion will be done using image tools and by default conversions done on queue unless in the config queue is set as sync or empty which means sync as well

---

```php

// in your model

public function registerMediaCollections(): void
{
    $this
        ->addMediaCollection('my-collection')
        ->withResponsiveImages();
}

```

---

```php
//global fallback for collection
$this
    ->addMediaCollection('avatars')
    ->useFallbackUrl('/images/anonymous-user.jpg')
    ->useFallbackPath(public_path('/images/anonymous-user.jpg'));


// custom fallbacks for conversions
$this
    ->addMediaCollection('avatar')
    ->useFallbackUrl('/default_avatar.jpg')
    ->useFallbackUrl('/default_avatar_thumb.jpg', 'thumb')
    ->useFallbackPath(public_path('/default_avatar.jpg'))
    ->useFallbackPath(public_path('/default_avatar_thumb.jpg'), 'thumb');

```

---

```php

// only allow certain types
$this->acceptsMimeTypes(['image/jpeg']); //or

$this->acceptsFile(function (File $file) {
    return $file->mimeType === 'image/jpeg';
});


//allow only one single file
$this
->addMediaCollection('avatar')
->singleFile();

//keep only 3 files
$this
->addMediaCollection('avatar')
->onlyKeepLatest(3);
```

Note:

- for single file and keep latest files, when you add another media it will delete the existing media and add this instead

## Regenerating images

- When you change a conversion on your model, all images that were previously generated will not be updated automatically. You can regenerate your images via an artisan command. Note that conversions are often queued,
  so it might take a while to see the effects of the regeneration in your application.
  `php artisan media-library:regenerate`

- If you only want to regenerate the images for a single model
  `php artisan media-library:regenerate "App\Models\Post"`

- When using a morph map, you should use the name of the morph.
  `php artisan media-library:regenerate "post"`

## Downloading images

### Single File

- Media implements the Responsable interface. This means that you can just return a media object to download the associated file in your browser.

  ```php

  use Spatie\MediaLibrary\MediaCollections\Models\Media;

  class DownloadMediaController
  {
    public function show(Media $mediaItem)
    {
        return $mediaItem;
    }
  }
  ```

- you can also do this for more control

  ```php

  use Spatie\MediaLibrary\MediaCollections\Models\Media;

  class DownloadMediaController
  {
    public function show(Media $mediaItem)
    {
        return response()->download($mediaItem->getPath(), $mediaItem->file_name);
    }
  }

  ```

Note: lambda allows response maximum to be 6 mb i think, so exeeding the maximum will cause lambda to fail and give 502 bad gateway error

### Multiple Files

```php

use Spatie\MediaLibrary\Support\MediaStream;

class DownloadMediaController
{
   public function download(YourModel $yourModel)
   {
        // Let's get some media.
        $downloads = $yourModel->getMedia('downloads');

        // Download the files associated with the media in a streamed way.
        return MediaStream::create('my-files.zip')->addMedia($downloads);
   }
}
```

for more control

```php

class DownloadMediaController
{
   public function download(YourModel $yourModel)
   {
        // Let's get some media.
        $downloads = $yourModel->getMedia('downloads');

        // Download the files associated with the media in a streamed way.
        // No prob if your files are very large.
        return MediaStream::create('my-files.zip')
            ->useZipOptions(function (&$zipOptions) {
                // ZipStream ^3.0 uses array
                $zipOptions['defaultEnableZeroHeader'] = true;
            })
            ->addMedia($downloads);
   }
}

```

Note: available zip options are [here](https://maennchen.dev/ZipStream-PHP/guide/Options.html)
