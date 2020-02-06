# Laravel Sluggable

A trait to use on your models to generate slugs based on another column. Supports translated attributes (using [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable)).

## Installation

You can install the package via composer:

```bash
composer require whitecube/laravel-sluggable
```

## Usage

``` php
<?php

namespace App;

use Whitecube\Sluggable\Sluggable;

class Post extends Model
{
    use Sluggable;
    
    public function sluggable(): string
    {
        return 'title';
    }
}
```

### Changing the destination column

By default, the slug is configured to be stored in a column named `slug` in the database. You can overwrite this setting with the `public $sluggableAttribute` property on your model.

```php
<?php

namespace App;

use Whitecube\Sluggable\Sluggable;

class Post extends Model
{
    use Sluggable;
  
    public $sluggableAttribute = 'custom-slug-column';
    
    public function sluggable(): string
    {
        return 'title';
    }
}
```



### Route Model Binding

Be advised that this package overrides the `getRouteKeyName` method, which means [Laravel's Route Model Binding](https://laravel.com/docs/5.0/routing#route-model-binding) will use the slug column by default (or the `$sluggableAttribute` you have defined). In most cases, this is great, saves you a step and cleans up your models, but if you must, you can change it to whatever you like.



### Translated slugs

You can generate slugs based on translated attributes (using [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable)). Remember to add the `slug` column to the `public $translatable` array to easily access them.

```php
<?php

namespace App;

use Whitecube\Sluggable\Sluggable;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use Sluggable;
    use HasTranslations;
  
    public $translatable = ['title', 'slug'];
    
    public function sluggable(): string
    {
        return 'title';
    }
}
```

```php
Post::create([
    'title' => [
        'en' => 'The title',
        'fr' => 'Le titre'
    ]
]);

$post->getAttributes()['slug']; // ['en' => 'the-title', 'fr' => 'le-titre']
$post->slug; // the-title (given that the crrent app locale is 'en')
$post->translate('slug', 'fr'); // 'le-titre'
```



## Contributing

Feel free to suggest changes, ask for new features or fix bugs yourself. We're sure there are still a lot of improvements that could be made, and we would be very happy to merge useful pull requests.

Thanks!

## Made with ❤️ for open source

At [Whitecube](https://www.whitecube.be) we use a lot of open source software as part of our daily work.
So when we have an opportunity to give something back, we're super excited!

We hope you will enjoy this small contribution from us and would love to [hear from you](mailto:hello@whitecube.be) if you find it useful in your projects. Follow us on [Twitter](https://twitter.com/whitecube_be) for more updates!
