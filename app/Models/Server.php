<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;
    protected $table = 'servers';

    protected $casts = [
        'config'=> 'array',
    ];

    protected $fillable = [
        'name',
        'ip',
        'location',
        'domain',
        'country',
        'config',
        'status',
        'token',
    ];

    public function proxies()
    {
        return $this->hasMany(Proxy::class,'server_id','id');
    }

    public function access()
    {
        return $this->hasMany(Access::class,'server_id','id');
    }
}
