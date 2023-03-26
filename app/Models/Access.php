<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;

    protected $table = 'access';

    protected $fillable = [
        'name',
        'proxy_id',
        'server_id',
        'port',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];


    public function proxy()
    {
        return $this->belongsTo(Proxy::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

}
