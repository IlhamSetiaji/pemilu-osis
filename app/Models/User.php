<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function($model) {
            $model->name .= 'NAMA-' . str_pad($model->id, 5, 0, STR_PAD_LEFT);
            $model->username .= 'USER-' . str_pad($model->id, 5, 0, STR_PAD_LEFT);
            $model->save();
        });
    }

    public function pemilu()
    {
        return $this->belongsTo(Pemilu::class,'pemilu_id');
    }

    public function pemilih_osis()
    {
        return $this->belongsToMany(User::class,'pemilih_osis','user_id','osis_id')->withTimestamps();
    }

}
