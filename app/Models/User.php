<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'google_id', 'username', 'profile_color'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia, JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = static::generateUniqueUsername($user->name ?? $user->email ?? 'user');
            } else {
                $user->username = static::generateUniqueUsername($user->username);
            }

            if (empty($user->profile_color)) {
                $user->profile_color = static::generateRandomProfileColor();
            } else {
                $user->profile_color = static::normalizeHexColor($user->profile_color);
            }
        });
    }

    public static function generateUniqueUsername(string $name, ?int $ignoreUserId = null): string
    {
        $base = preg_replace('/[^a-z0-9_.-]/', '', Str::lower(Str::ascii($name)));

        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (static::where('username', $username)->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Curated list of allowed profile theme colors.
     *
     * @var array<int, array{key: string, name: string, hex: string}>
     */
    public const PROFILE_COLORS = [
        ['key' => 'indigo',  'name' => 'Indigo',  'hex' => '#6366F1'],
        ['key' => 'blue',    'name' => 'Blue',    'hex' => '#3B82F6'],
        ['key' => 'emerald', 'name' => 'Emerald', 'hex' => '#10B981'],
        ['key' => 'amber',   'name' => 'Amber',   'hex' => '#F59E0B'],
        ['key' => 'red',     'name' => 'Red',     'hex' => '#EF4444'],
        ['key' => 'purple',  'name' => 'Purple',  'hex' => '#8B5CF6'],
        ['key' => 'pink',    'name' => 'Pink',    'hex' => '#EC4899'],
        ['key' => 'cyan',    'name' => 'Cyan',    'hex' => '#06B6D4'],
        ['key' => 'teal',    'name' => 'Teal',    'hex' => '#14B8A6'],
        ['key' => 'orange',  'name' => 'Orange',  'hex' => '#F97316'],
        ['key' => 'lime',    'name' => 'Lime',    'hex' => '#84CC16'],
        ['key' => 'slate',   'name' => 'Slate',   'hex' => '#64748B'],
    ];

    /**
     * Get list of allowed HEX color strings.
     *
     * @return array<int, string>
     */
    public static function getAllowedProfileColorHexes(): array
    {
        return array_column(self::PROFILE_COLORS, 'hex');
    }

    public static function generateRandomProfileColor(): string
    {
        $hexes = static::getAllowedProfileColorHexes();

        return $hexes[array_rand($hexes)];
    }

    public static function normalizeHexColor(string $color): string
    {
        $color = trim($color);
        if (! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        return strtoupper($color);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @var list<string> */
    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar');
    }

    /**
     * @return BelongsToMany<Community, $this>
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members');
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}

