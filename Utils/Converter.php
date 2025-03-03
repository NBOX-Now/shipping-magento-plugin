<?php 
namespace NBOX\Shipping\Utils;

class Converter
{
   private static $weightToKg = [
      'kg'  => 1,         // Kilograms
      'kgs'  => 1,        // Kilograms
      'g'   => 0.001,     // Grams
      'mg'  => 0.000001,  // Milligrams
      'lbs' => 0.453592,  // Pounds
      'oz'  => 0.0283495, // Ounces
      'ton' => 1000,      // Metric tons
  ];

  /**
   * Convert weight to kilograms
   * @param float $value - weight value
   * @param string $unit - weight unit (kg, g, mg, lbs, oz, ton)
   * @return float - converted value in kg
   */
  public static function convertToKg($value, $unit)
  {
      $unit = strtolower($unit);
      if (isset(self::$weightToKg[$unit])) {
          return $value * self::$weightToKg[$unit];
      }
      throw new \Exception("Unsupported weight unit: $unit");
  }

  /**
   * Convert weight to grams
   * @param float $value - weight value
   * @param string $unit - weight unit (kg, g, mg, lbs, oz, ton)
   * @return float - converted value in grams
   */
  public static function convertToGrams($value, $unit)
  {
      return self::convertToKg($value, $unit) * 1000;
  }
}
