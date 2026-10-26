<?php

namespace Kuzonet\OverkillAbout\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    protected $guarded = [];

    protected $hidden = ['password'];

    public static int $calls = 0;

    public function buddy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'buddy_id');
    }

    /**
     * Not an attribute and not a relation: a placeholder must never be able to call it.
     */
    public function dangerous(): string
    {
        self::$calls++;

        return 'called';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
