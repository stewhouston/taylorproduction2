<?php

namespace App\Services;

use App\Models\StatHoliday;
use \Moment\Moment;

class ShippingService {
  public static function getStatHolidaysDb() {
      return StatHoliday::orderBy('id', 'desc')->get();
  }

  /**
   * Used to determine if an instance of Moment is being used in place of a null/false return value so as to work
   *  within the restrictions of PHP type hinting.
   */
  public static function isEmptyMoment($moment_instance): bool {
    if (empty($moment_instance)) {
      return true;
    }
    if (is_string($moment_instance)) {
      return preg_match("/^0000\-00\-00/", $moment_instance);
    }
    // $moment->setYear(0) idiom
    return intval($moment_instance->format('U')) < -62138;
  }
}