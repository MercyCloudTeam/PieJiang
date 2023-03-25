<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxyGroup extends Model
{
    use HasFactory;

    protected $table = 'proxy_group';

    protected $fillable = [
        'name',
        'type'
    ];

    public function rules()
    {
        return $this->hasMany(Rule::class, 'proxy_group', 'name');
    }
}
