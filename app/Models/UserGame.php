<?php

namespace App\Models;

use Database\Factories\UserGameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'igdb_id', 'name', 'slug', 'cover_url', 'rating', 'status', 'hours_played', 'is_favorite'])]
class UserGame extends Model
{
    /** @use HasFactory<UserGameFactory> */
    use HasFactory;

    public const STATUS_PLAYING = 'playing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_BACKLOG = 'backlog';

    public const STATUS_PLATINUM = 'platinum';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUSES = [
        self::STATUS_PLAYING,
        self::STATUS_COMPLETED,
        self::STATUS_BACKLOG,
        self::STATUS_PLATINUM,
        self::STATUS_ABANDONED,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'igdb_id' => 'integer',
            'rating' => 'float',
            'hours_played' => 'float',
            'is_favorite' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the counts for each status for a given user.
     *
     * @return array<string, int>
     */
    public static function getBreakdownForUser(int $userId): array
    {
        $counts = static::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $breakdown = [];

        foreach (self::STATUSES as $status) {
            $breakdown[$status] = (int) ($counts[$status] ?? 0);
        }

        return $breakdown;
    }
}
