<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable('title', 'slug', 'description', 'likes', 'author_id', 'community_id')]
class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Community, $this>
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'community_id');
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    /**
     * @return HasMany<PostLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }
}
