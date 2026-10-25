<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('companies')]
class Company extends Model
{
    protected $fillable = [
        'name', 'is_active',
    ];

    public function users() {
    	return $this->hasMany(User::class);
    }

    public function estimates() {
        return $this->hasMany(Estimate::class);
    }
}
