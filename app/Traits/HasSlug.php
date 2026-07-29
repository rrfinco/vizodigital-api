<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model): void {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::uniqueSlugFrom($model, $model->name);
            } elseif (empty($model->slug) && ! empty($model->title)) {
                $model->slug = static::uniqueSlugFrom($model, $model->title);
            }
        });
    }

    protected static function uniqueSlugFrom(object $model, string $value): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 1;

        while (
            $model->newQuery()
                ->when(
                    $model->getKey(),
                    fn ($q) => $q->where($model->getKeyName(), '!=', $model->getKey())
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
