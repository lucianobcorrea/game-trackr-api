<?php

namespace App\Models;

use Database\Factories\UserSetupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['user_id', 'title', 'description'])]
class UserSetup extends Model implements HasMedia
{
    /** @use HasFactory<UserSetupFactory> */
    use HasFactory, InteractsWithMedia;

    public const MAX_IMAGES = 10;

    /**
     * @var list<string>
     */
    protected $appends = ['images'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted media images list.
     *
     * @return array<int, array{id: int, url: string, name: string, file_name: string, mime_type: string|null, size: int}>
     */
    public function getImagesAttribute(): array
    {
        return $this->getMedia('images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
            ];
        })->values()->all();
    }
}
