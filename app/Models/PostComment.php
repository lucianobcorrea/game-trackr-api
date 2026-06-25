<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('content', 'likes', 'parent_id', 'post_id', 'author_id')]
class PostComment extends Model
{
    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
