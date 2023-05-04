<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = [
        'display_name'
    ];

    public function displayName() : Attribute
    {
        return Attribute::make(
            get: fn() => trim($this->name."-Transit(".$this->type.")"),
        );
    }


    public function proxy()
    {
        return $this->belongsTo(Proxy::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function domain() : Attribute
    {
        return Attribute::make(
            get: fn() => $this->domain ?? $this->server->domain,
        );
    }

}
