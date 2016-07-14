<?php

namespace x000000\StorageManager;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class Transform 
{
	const MAP = [
		Transforms\Resize::class => 'sz',
		Transforms\Crop::class   => 'cr',
	];

	/**
	 * Create list of Transforms\AbstractTransform from given $options
	 * @param array $options List of options for Transforms\AbstractTransform::create()
	 * @return array List of Transforms\AbstractTransform
	 */
	public static function create($options) 
	{
		$map = array_flip(self::MAP);
		foreach ($options as $key => &$config) {
			$method = $map[$key] . '::create';
			$config = $method($config);
		}
		return $options;
	}

	/**
	 * Serialize transform config to string
	 * @param array $options List of Transforms\AbstractTransform objects 
	 * or options for Transforms\AbstractTransform::create()
	 * (see Transforms\AbstractTransform::create() for $options details)
	 * @return string Serialized $config
	 */
	public static function serialize($options) 
	{
		if (!is_array($options)) {
			return '';
		}

		$tr  = array();
		$map = array_flip(self::MAP);
		foreach ($options as $key => $value) {
			if (!is_object($value)) {
				$method = $map[$key] . '::create';
				$value  = $method($value);
			}
			
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
	public static function scaleSize(&$width, &$height, BoxInterface $box) 
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