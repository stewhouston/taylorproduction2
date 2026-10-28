<?php

namespace App\Objects;

class PrintReport
{
	public static function buildDocumentDescription($name, $description, $type, $size, $finish): string {
		$description = $name.' - '.$description.' '.$type.' - '.$size.' - '.$finish;
    return $description;
	}

	/*
     * Creates concatenation of the component specification for display on Estimate
     *  one line for a single component document and two lines for a 2 component document
     */
    static function buildComponentDescription(int $document_component_count,
      string $component_type,
      int $pages,
      string $print_side1,
      string $print_side2,
      bool $bleed,
      string $stock,
      bool $is_sequential = false,
      int $count_color_inserts = 0,
      int $count_imprints = 0
    ) {
      if ($print_side1 === 'Black' && ($print_side2 === 'Black' || $print_side2 === 'none')) {
        $colour = 'black';
      }
      else {
        $colour = 'colour';
      }
      if ($print_side1 === 'Black') {
        $side1_colours = 1;
      } elseif ($print_side1 === 'Colour') {
        $side1_colours = 4;
      } else {
        $side1_colours = 0;
      }

      if ($print_side2 === 'Black') {
        $side2_colours = 1;
      } elseif ($print_side2 === 'Colour') {
        $side2_colours = 4;
      } else {
        $side2_colours = 0;
      }

      if ($bleed) {
        $bleed_description = "bleed";
      } else {
        $bleed_description = "no bleed";
      }
			// Updated January 2021 to include colour inserts, imprints and numbering
      if ($document_component_count === 2) {
        if ($component_type == 'Cover') {
          $component_report_description = "$component_type : $pages pages $side1_colours/$side2_colours-$colour on $stock - $bleed_description";

          if ($count_imprints > 0) {
            $component_report_description .= " - $count_imprints imprints ";
          }
          if ($is_sequential == true) {
            $component_report_description .= " - seq. number (red) ";
          }
        } else {
          $component_report_description = "$component_type : $pages pages $side1_colours/$side2_colours-$colour on $stock - $bleed_description";
          if ($count_color_inserts > 0) {
            $component_report_description .= " - $count_color_inserts colour pages/inserts ";
          }
        }
      } else {
        $component_report_description = "$pages pages $side1_colours/$side2_colours-$colour on $stock - $bleed_description";

        if ($count_color_inserts > 0) {
          $component_report_description .= " - $count_color_inserts colour pages/inserts ";
        }
        if ($count_imprints > 0 && $document_component_count === 1) {
          $component_report_description .= " - $count_imprints imprints ";
        }
        if ($is_sequential == true && $document_component_count === 1) {
          $component_report_description .= " - seq. number (red) ";
        }
      }
      return $component_report_description;
    }

		// Temporarily in PrintReport, may need its own general object (new object creation)
		static function applyPagesModuloBooks($document_finish, $pages) {
			if ($document_finish == 'Saddlestitch') {
				$mod = $pages % 4;
				if ($mod === 1) {
					$pages += 3;
				} elseif ($mod === 2) {
					$pages += 2;
				} elseif ($mod > 0) {
					$pages += 1;
				}
      } elseif ($document_finish != 'Saddlestitch') {
				$mod = $pages % 2;
        if ($mod > 0) {
					$pages += 1;
				}
			}
      return $pages;
	}
}
