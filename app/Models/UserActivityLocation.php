<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserActivityLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'user_activity_locations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'event_type',
        'actor_type',
        'actor_id',
        'order_id',
        'ip_address',
        'user_agent',
        'country',
        'state',
        'city',
        'postal_code',
        'address',
        'latitude',
        'longitude',
        'event_at',
        'device_name'
    ];
}