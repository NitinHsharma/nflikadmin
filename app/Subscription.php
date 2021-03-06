<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'status',
    ];
    function clients()
    {
        $this->belongsToMany(Client::class)->whereNull('parent_id');
    }
}
