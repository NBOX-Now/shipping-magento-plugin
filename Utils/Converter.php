<?php

namespace Nbox\Shipping\Utils;

/**
 * Converter class to handle weight unit conversions.
 */
class Converter
{
    /**
     * @var array $weightToKg - Mapping of weight units to their conversion factor to kilograms
     */
    private $weightToKg;

    /**
     * Constructor to initialize the conversion factor mapping.
     */
    public function __construct()
    {
        $this->weightToKg = [
            'kg'  => 1,         // Kilograms
            'kgs' => 1,         // Kilograms
            'g'   => 0.001,     // Grams
            'mg'  => 0.000001,  // Milligrams
            'lbs' => 0.453592,  // Pounds
            'oz'  => 0.0283495, // Ounces
            'ton' => 1000,      // Metric tons
        ];
    }

    /**
     * Convert weight to kilograms.
     *
     * @param float $value - Weight value
     * @param string $unit - Weight unit (kg, g, mg, lbs, oz, ton)
     * @return float - Converted value in kilograms
     * @throws \InvalidArgumentException - If the weight unit is unsupported
     */
    public function convertToKg($value, $unit)
    {
        $unit = strtolower($unit);
        if (isset($this->weightToKg[$unit])) {
            return $value * $this->weightToKg[$unit];
        }
        
        // Using a more specific exception type (InvalidArgumentException)
        throw new \InvalidArgumentException("Unsupported weight unit: $unit");
    }

    /**
     * Convert weight to grams.
     *
     * @param float $value - Weight value
     * @param string $unit - Weight unit (kg, g, mg, lbs, oz, ton)
     * @return float - Converted value in grams
     */
    public function convertToGrams($value, $unit)
    {
        return $this->convertToKg($value, $unit) * 1000;
    }
}
