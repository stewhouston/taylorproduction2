<?php

namespace App\Services;

use App\Models\StatHoliday;

class ShippingService {
  public static function getStatHolidaysDb() {
      return StatHoliday::orderBy('id', 'desc')->get();
  }
}