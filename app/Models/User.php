<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'streak_last_date' => 'date',
        ];
    }

    /**
     * Bumps the daily streak the first time (across any course) the learner finishes
     * something today. A gap of a day or more resets it to 1; same-day calls no-op.
     */
    public function recordActivityToday(): void
    {
        $today = today();

        if ($this->streak_last_date?->isSameDay($today)) {
            return;
        }

        $this->streak_count = $this->streak_last_date?->isSameDay($today->copy()->subDay()) ? $this->streak_count + 1 : 1;
        $this->streak_last_date = $today;
        $this->save();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function masteryRecords(): HasMany
    {
        return $this->hasMany(MasteryRecord::class);
    }
}
