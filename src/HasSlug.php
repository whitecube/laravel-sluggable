<?php

namespace Whitecube\Sluggable;

use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Facades\Route as Router;

trait HasSlug
{
    /**
     * Register the saving event callback
     */
    public static function bootHasSlug()
    {
        static::saving(function($model) {
            $model->attributes[$model->getSlugStorageAttribute()] = $model->generateSlug();
        });
    }

    /**
     * Get the attribute name used to generate the slug from
     *
     * @return string
     */
    public function getSluggable()
    {
        return $this->sluggable;
    }

    /**
     * Get the attribute name used to store the slug into
     *
     * @return string
     */
    public function getSlugStorageAttribute()
    {
        return $this->slugStorageAttribute ?? 'slug';
    }

    /**
     * Generate the slug.
     *
     * @return false|string
     */
    protected function generateSlug()
    {
        return $this->slugify($this->getSluggable());
    }

    /**
     * Handle the slug generation for translatable and
     * non-translatable attributes.
     *
     * @param string $attribute
     * @return false|string
     */
    protected function slugify($attribute)
    {
        if($this->attributeIsTranslatable($attribute)) {
            return json_encode($this->translatedSlugs($attribute));
        }

        return str_slug($this->$attribute);
    }

    /**
     * Handle the generation of translated slugs
     *
     * @param string $attribute
     * @return array
     */
    protected function translatedSlugs($attribute)
    {
        $slugs = [];

        foreach($this->getTranslatedLocales($attribute) as $locale) {
            $slugs[$locale] = str_slug($this->getTranslation($attribute, $locale));
        }

        return $slugs;
    }

    /**
     * Check if the model has the HasTranslations trait.
     *
     * @return bool
     */
    protected function hasTranslatableTrait()
    {
        return method_exists($this, 'getTranslations');
    }

    /**
     * Check if the given attribute is translatable.
     *
     * @param string $attribute
     * @return bool
     */
    protected function attributeIsTranslatable($attribute)
    {
        return $this->hasTranslatableTrait() && $this->isTranslatableAttribute($attribute);
    }

    /**
     * Override the route key name to allow proper
     * Route-Model binding.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getSlugStorageAttribute();
    }

    /**
     * Generate an URI containing the model's slug from
     * given route.
     *
     * @param Illuminate\Routing\Route $route
     * @param null|string $locale
     * @param null|Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    public function getSluggedUrlForRoute(Route $route, $locale = null, $model = null)
    {
        $parameters = $this->getTranslatedSlugRouteParameters($route, $locale, $model);

        return app(UrlGenerator::class)->toRoute($route, $parameters, false);
    }

    /**
     * Get a bound route's parameters with the 
     * model's slug set to the desired locale.
     *
     * @param Illuminate\Routing\Route $route
     * @param null|string $locale
     * @param null|Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    public function getTranslatedSlugRouteParameters(Route $route, $locale = null, $model = null)
    {
        $model = is_null($model) ? $this : $model;

        $parameters = $route->signatureParameters(UrlRoutable::class);

        $parameter = array_reduce($parameters, function($carry, $parameter) use ($model) {
            if($carry || $parameter->getClass()->name !== get_class($model)) return $carry;
            return $parameter;
        });

        if(!$parameter) {
            return $route->parameters();
        }

        $key = $model->getRouteKeyName();

        $value = ($model->attributeIsTranslatable($key) && $locale)
            ? $model->getTranslation($key, $locale)
            : $model->$key;

        $route->setParameter($parameter->name, $value);

        return $route->parameters();
    }

    /**
     * Resolve the route binding with the translatable slug in mind
     *
     * @param $value
     * @return mixed
     */
    public function resolveRouteBinding($value)
    {
        $key = $this->getRouteKeyName();

        if(!$this->attributeIsTranslatable($key)) {
            return parent::resolveRouteBinding($value);
        }

        $locale = app()->getLocale();

        // Return exact match if we find it
        if($result = $this->where($key . '->' . $locale, $value)->first()) {
            return $result;
        }

        // If cross-lang redirects are disabled, stop here
        if(!($this->slugTranslationRedirect ?? true)) {
            return;
        }

        // Get the models where this slug exists in other langs as well
        $results = $this->whereRaw('JSON_SEARCH(`'.$key.'`, "one", "'.$value.'")')->get();

        // If we have zero or multiple results, don't guess
        if($results->count() !== 1) {
            return;
        }

        // Redirect to the current route using the translated model key
        return abort(301, '', ['Location' => $this->getSluggedUrlForRoute(
            Router::current(),
            $locale,
            $results->first()
        )]);
    }
}
