<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable('title', 'slug', 'description', 'author_id')]
class Community extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'community_members');
    }
}
