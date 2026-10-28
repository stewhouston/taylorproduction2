<?php

namespace App\Objects;


class PrintHours {
	static function runSpeed(string $device, float $run_sheet_sqin, string $run_type): float {
		/*
		 * Speeds as per Val / TRIVOR October 2020
		 */
		if (in_array($device, ['495', 'TRIVOR'])){
		   $run_speed = round(25000 / (2 * ($run_sheet_sqin / 93.5)), 0);
		}
		elseif ($device == '144') {
			if ($run_type == 'Simplex'){
				$run_speed = round(7300 / ($run_sheet_sqin / 93.5), 0);
			} else{
				$run_speed = round(7300 / (2 * ($run_sheet_sqin / 93.5)), 0);
			}
		} else {
			if ($run_type == 'Simplex'){
				$run_speed = round(4500 / ($run_sheet_sqin / 93.5), 0);
			} else{
				$run_speed = round(4500 / (2 * ($run_sheet_sqin / 93.5)), 0);
			}
		}
		return $run_speed;
	}

	static function printRunHrs(
    bool $offline_bind_required, string $document_finish, float $print_run_speed,
    float $run_sheets, string $device
  ): float {
		if ($offline_bind_required == false && in_array($document_finish, ['Saddlestitch', 'Corner Stitch'])) {
			// setup time for inline bind functions:
			$print_run_hours = .25;
		}
		else {
			$print_run_hours = 0;
		}
		// setup time continued:
		if (in_array($device, ['495', 'TRIVOR'])) {
		  $print_run_hours = $print_run_hours + 1;
		} elseif ($device == '800') {
		  $print_run_hours = $print_run_hours + .5;
		} else {
		  $print_run_hours = $print_run_hours + .25;
		}
		$print_run_hours = $print_run_hours + round($run_sheets/$print_run_speed, 2);
		/* add time for roll change - 1 hour for every 4 hours of running */
	  if (in_array($device, ['495', 'TRIVOR'])) {
			$print_run_hours = $print_run_hours + ceil(($run_sheets/$print_run_speed)/4) - 1;
	  }
		return $print_run_hours;
	}
}
