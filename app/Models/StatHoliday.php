<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('stat_holidays')]
class StatHoliday extends Model
{
    protected $fillable = [
        'stat_date', 'stat_year', 'date_string', 'name', 'observance_can', 'observance_usa'
    ];
}
