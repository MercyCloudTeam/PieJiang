<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $table = 'proxies';

    protected $fillable = [
       'name','type','config','port','server_id','domain'
    ];

    protected $casts = [
        //json config
        'config'=>'array',
    ];

    protected $appends = [
        'display_name'
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function displayName() : Attribute
    {
        return Attribute::make(
            get: fn() => trim($this->name."-Direct(".$this->type.")"),
        );
    }

    public function domain() : Attribute
    {
        return Attribute::make(
            get: fn() => $this->domain ?? $this->server->domain,
        );
    }
}
