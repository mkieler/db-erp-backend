<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\LowInventoryStockAlert;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $notificationTypes = NotificationType::all();
            foreach ($notificationTypes as $notificationType) {
                $user->notificationTypes()->attach($notificationType->id, ['is_active' => $notificationType->send_by_default]);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The relations to load on every query.
     *
     * @var list<string>
     */
    protected $with = ['accesses'];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'firstName',
        'lastName',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function accesses()
    {
        return $this->belongsToMany(UserAccess::class);
    }

    public function notificationTypes()
    {
        return $this->belongsToMany(NotificationType::class)->withPivot('is_active');
    }

    public function getFirstNameAttribute(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', $this->name);
        return count($parts) > 1 ? $parts[count($parts) - 1] : '';
    }

    /**
     * @param Notification $notification
     * @return void
     */
    public function scopeRecipientsForNotificationType($query, $type)
    {
        return $query->whereHas('notificationTypes', function ($q) use ($type) {
            $q->where('type', $type)
              ->where('is_active', true);
        });
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
