<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class Visitor extends Model
{
    use HasFactory, Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }
    protected $fillable = [
        'device_id',
        'device_type',
        'os',
        'browser',
        'device_name',
        'ip_address',
        'city',
        'region',
        'country',
        'isp'
    ];
}
