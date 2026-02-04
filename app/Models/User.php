<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; // ১. এই লাইনটা যোগ করো

// ২. implements JWTSubject যোগ করো
class User extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }
    // চেক করার জন্য হেল্পার ফাংশন
    public function isVendor()
    {
        // ভেন্ডর টেবিল এ এন্ট্রি আছে কিনা এবং স্ট্যাটাস অ্যাপ্রুভড কিনা
        return $this->vendor && $this->vendor->status === 'approved';
    }

    // ৩. এই দুটি ফাংশন নিচে যোগ করো (JWT এর জন্য বাধ্যতামূলক)

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // টোকেনের ভেতরেই রোল থাকবে, বারবার ডাটাবেস চেক করা লাগবে না
        ];
    }
}
