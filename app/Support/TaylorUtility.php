<?php

namespace App\Support;

class TaylorUtility 
{
  public static function makeToken(int $chars = 32): string 
  {
    $bytes = ceil($chars / 2);
    return bin2hex(random_bytes($bytes));
  }
}