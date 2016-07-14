<?php

namespace x000000\StorageManager;

class Helper 
{
	/**
	 * Serialize $transforms to a single string
	 * @param Transforms\AbstractTransform[] $transforms List of transforms
	 * @return string Serialized string
	 */
	public static function serializeTransforms($transforms) 
	{
		if (!is_array($transforms)) {
			return '';
		}

		$tr = array();
		foreach ($transforms as $key => $value) {
			$tr[] = (string) $value;
		}

		return empty($tr) ? '' : '&' . implode('&', $tr);
	}

	/**
	 * Returns serialized $value with null check
	 * @param mixed $value Value to be serialized
	 * @return string Serialized $value
	 */
	public static function nullSerialize($value) 
	{
		return $value === null ? 'null' : (string) $value;
	}

	/**
	 * Convert percent value to absolute value
	 * @param string $percent Percent value with trailed '%'
	 * @param number $maxValue 100% value for a reference
	 * @return number Return a percent value based on $percent of $maxValue. 
	 * If $percent isn't trailed by '%', then $percent will be returned without changes.
	 */
	public static function percentValue($percent, $maxValue) 
	{
		return substr($percent, -1) == '%' 
			? rtrim($percent, ' %') * .01 * $maxValue 
			: $percent;
	}
	
	/**
	 * Modifies $width or $height (if one of them is empty) based on $box ratio.
	 * @param number|null $width Desired width
	 * @param number|null $height Desired height
	 * @param BoxInterface $box Original box for a reference
	 */
	public static function scaleSize(&$width, &$height, \Imagine\Image\BoxInterface $box) 
	{
		if (!$width || !$height) {
			$r = $box->getWidth() / $box->getHeight();
			if ($width) {
				$height = floor($width / $r);
			} else {
				$width  = floor($height * $r);
			}
		}
	}
	
}
