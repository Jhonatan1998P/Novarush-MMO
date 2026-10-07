<?php

/**
 * NovaRush Bot AI v2 - Economy Valuator
 *
 * Expresses all economic values in MSE (Metal Standard Equivalent: M + 2C + 4D)
 * and HP (Hours of Production, server standard).
 * Eliminates arbitrary threshold bugs like "250,000 metal".
 */
class BotEconomyValuator
{
    const RATE_METAL     = 1.0;
    const RATE_CRYSTAL   = 2.0;
    const RATE_DEUTERIUM = 4.0;

    /**
     * Convert resource quantities to Metal Standard Equivalent (MSE)
     *
     * @param float $metal
     * @param float $crystal
     * @param float $deuterium
     * @return float
     */
    public static function toMSE($metal, $crystal, $deuterium)
    {
        return ($metal * self::RATE_METAL) +
               ($crystal * self::RATE_CRYSTAL) +
               ($deuterium * self::RATE_DEUTERIUM);
    }

    /**
     * Convert an array of resources [901 => M, 902 => C, 903 => D] or ['metal', 'crystal', 'deuterium'] to MSE
     *
     * @param array $resArray
     * @return float
     */
    public static function arrayToMSE(array $resArray)
    {
        $m = isset($resArray[901]) ? (float)$resArray[901] : (isset($resArray['metal']) ? (float)$resArray['metal'] : 0.0);
        $c = isset($resArray[902]) ? (float)$resArray[902] : (isset($resArray['crystal']) ? (float)$resArray['crystal'] : 0.0);
        $d = isset($resArray[903]) ? (float)$resArray[903] : (isset($resArray['deuterium']) ? (float)$resArray['deuterium'] : 0.0);

        return self::toMSE($m, $c, $d);
    }

    /**
     * Convert an MSE amount into Hours of Production (HP)
     *
     * @param float $mseValue
     * @param float $hourlyProductionMSE
     * @return float
     */
    public static function toHP($mseValue, $hourlyProductionMSE)
    {
        if ($hourlyProductionMSE <= 10.0) {
            // Safety fallback for fresh colony
            return (float)$mseValue / 1000.0;
        }

        return (float)$mseValue / (float)$hourlyProductionMSE;
    }

    /**
     * Get price of a game element (ship, defense, building, research) in MSE
     *
     * @param int $elementId
     * @param int $levelOrCount
     * @param array|null $USER
     * @param array|null $PLANET
     * @return float
     */
    public static function getElementPriceMSE($elementId, $levelOrCount = 1, $USER = null, $PLANET = null)
    {
        global $pricelist;

        if (!isset($pricelist[$elementId])) {
            return 0.0;
        }

        $price = $pricelist[$elementId];
        $costM = isset($price['cost'][901]) ? $price['cost'][901] : 0;
        $costC = isset($price['cost'][902]) ? $price['cost'][902] : 0;
        $costD = isset($price['cost'][903]) ? $price['cost'][903] : 0;

        // If it's a building or research with exponential factor
        if (isset($price['factor']) && $price['factor'] > 1.0 && $USER !== null) {
            $cost = BuildFunctions::getElementPrice($USER, $PLANET ? $PLANET : array(), $elementId, false, $levelOrCount);
            return self::arrayToMSE($cost);
        }

        return self::toMSE($costM * $levelOrCount, $costC * $levelOrCount, $costD * $levelOrCount);
    }
}
