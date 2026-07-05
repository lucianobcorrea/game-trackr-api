<?php

namespace App\Support;

use Illuminate\Support\Str;

class SlugHelper
{
    public static function make(string $text, string $modelClass): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $count = 1;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}