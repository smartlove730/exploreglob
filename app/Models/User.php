<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordQueued;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'google_id',
        'email_verification_otp',
        'email_verification_otp_expires_at',
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
            'email_verification_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin || $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string ...$roles): bool
    {
        if (empty($roles)) {
            return true;
        }

        if (in_array(self::ROLE_ADMIN, $roles, true) && $this->isAdmin()) {
            return true;
        }

        return $this->role !== null && in_array($this->role, $roles, true);
    }

    public function facebookAccounts(): HasMany
    {
        return $this->hasMany(FacebookAccount::class);
    }

    public function googleAccounts(): HasMany
    {
        return $this->hasMany(GoogleAccount::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latestOfMany();
    }

    public function sendEmailVerificationNotification(): void
    {
        if (! $this->email_verification_otp_expires_at || now()->greaterThan($this->email_verification_otp_expires_at)) {
            $this->forceFill([
                'email_verification_otp' => (string) random_int(100000, 999999),
                'email_verification_otp_expires_at' => now()->addMinutes(10),
            ])->save();
        }

        Log::info('Dispatching email verification OTP notification.', ['user_id' => $this->id, 'email' => $this->email]);
        $this->notify(new VerifyEmailQueued());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordQueued($token));
    }
}
