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
            $attribute = $model->getSlugStorageAttribute();

            if($model->attributeIsTranslatable($attribute)) {
                $model->translateSlugs($attribute);
            } else {
                if(!is_null($model->$attribute)) return;

                $sluggable = $model->getSluggable();
                $model->attributes[$attribute] = $model->getUniqueSlug($model->$sluggable);
            }
        });
    }

    /**
     * Generate translated slugs. Keeps any existing translated slugs.
     *
     * @param string $attribute
     */
    public function translateSlugs($attribute)
    {
        $sluggable = $this->getSluggable();
        $value = isset($this->attributes[$attribute])
            ? json_decode($this->attributes[$attribute], true)
            : [];

        foreach($this->getTranslatedLocales($this->getSluggable()) as $locale) {
            if(!isset($value[$locale]) || is_null($value[$locale])) {
                $value[$locale] = $this->getUniqueSlug($sluggable, $locale);
            }
        }

        $this->attributes[$attribute] = json_encode($value);
    }

    /**
     * Get a unique slug
     *
     * @param string $value
     * @param string|null $locale
     * @return string
     */
    public function getUniqueSlug($sluggable, $locale = null)
    {
        if(!is_null($locale)) {
            $sluggable = $this->getTranslation($sluggable, $locale);
        }

        $slug = str_slug($sluggable);

        $i = 0;
        while($this->slugExists($slug, $locale, $i)) {
            $i++;
        }

        if ($i) {
            $slug = $slug . '-' . $i;
        }

        return $slug;
    }

    /**
     * Check if the slug exists (for the given locale if any)
     *
     * @param string $slug
     * @param string|null $locale
     * @param int $i
     * @return bool
     */
    public function slugExists($slug, $locale = null, $i = 0)
    {
        $whereKey = is_null($locale) ? $this->getSlugStorageAttribute() : $this->getSlugStorageAttribute().'->'.$locale;

        if ($i) {
            $slug = $slug . '-' . $i;
        }

        $query = $this->getSlugExistsQuery($whereKey, $slug);

        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    /**
     * Get the query that checks if the slug already exists
     *
     * @param string $whereKey
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getSlugExistsQuery($whereKey, $slug)
    {
        return $query = static::where($whereKey, $slug)
            ->withoutGlobalScopes();
    }

    /**
     * Check if model uses soft deletes
     *
     * @return bool
     */
    protected function usesSoftDeletes(): bool
    {
        return (bool) in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
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
     * @param bool $fullUrl
     * @return string
     */
    public function getSluggedUrlForRoute(Route $route, $locale = null, $fullUrl = true)
    {
        $parameters = $this->getTranslatedSlugRouteParameters($route, $locale);

        return app(UrlGenerator::class)->toRoute($route, $parameters, $fullUrl);
    }

    /**
     * Get a bound route's parameters with the
     * model's slug set to the desired locale.
     *
     * @param Illuminate\Routing\Route $route
     * @param null|string $locale
     * @return array
     */
    public function getTranslatedSlugRouteParameters(Route $route, $locale = null)
    {
        $parameters = $route->signatureParameters(UrlRoutable::class);

        $parameter = array_reduce($parameters, function($carry, $parameter) {
            if($carry || $parameter->getClass()->name !== get_class()) return $carry;
            return $parameter;
        });

        if(!$parameter) {
            return $route->parameters();
        }

        $key = $this->getRouteKeyName();

        $value = ($this->attributeIsTranslatable($key) && $locale)
            ? $this->getTranslation($key, $locale)
            : $this->$key;

        $route->setParameter($parameter->name, $value);

        return $route->parameters();
    }

    /**
     * Resolve the route binding with the translatable slug in mind
     *
     * @param $value
     * @return mixed
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $key = $this->getRouteKeyName();

        if(!$this->attributeIsTranslatable($key)) {
            return parent::resolveRouteBinding($value);
        }

        $locale = app()->getLocale();

        // Return exact match if we find it
        $result = $this->getRouteBindingQueryBuilder()
            ->where($key . '->' . $locale, $value)
            ->first();

        if($result) {
            return $result;
        }

        // If cross-lang redirects are disabled, stop here
        if(!($this->slugTranslationRedirect ?? true)) {
            return;
        }

        // Get the models where this slug exists in other langs as well
        $results = $this->getRouteBindingQueryBuilder()
            ->whereRaw('JSON_SEARCH(`'.$key.'`, "one", "'.$value.'")')
            ->get();

        // If we have zero or multiple results, don't guess
        if($results->count() !== 1) {
            return;
        }

        // Redirect to the current route using the translated model key
        return abort(301, '', ['Location' => $results->first()->getSluggedUrlForRoute(
            Router::current(), $locale, false
        )]);
    }

    /**
     * Get a new query builder for the Route Binding
     *
     * @param $value
     * @return mixed
     */
    protected function getRouteBindingQueryBuilder()
    {
        $query = $this->newQuery();

        if(method_exists($this, 'getRouteBindingQuery')) {
            return $this->getRouteBindingQuery($query);
        }

        return $query;
    }
}
