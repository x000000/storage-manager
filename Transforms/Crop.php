<?php

namespace x000000\StorageManager\Transforms;

use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use x000000\StorageManager\Transform;

class Crop extends AbstractTransform
{
	const COVER   = 'cv';
	const CONTAIN = 'cn';
	
	private $_width;
	private $_height;
	private $_x;
	private $_y;
	private $_ratio;
	
	public function __construct($width, $height, $x, $y, $ratio = null) 
	{
		$this->_width  = $width;
		$this->_height = $height;
		$this->_x      = $x;
		$this->_y      = $y;
		$this->_ratio  = $ratio;
	}

	public static function create($config) 
	{
		return new self(
			$config['w'], $config['h'], 
			$config['x'], $config['y'], 
			empty($config['r']) ? null : $config['r']
		);
	}
	
	public function serializeConfig() 
	{
		return 
			($this->_ratio ? Transform::nullSerialize($this->_ratio) : '0') . ',' .
			Transform::nullSerialize($this->_width) . ',' . Transform::nullSerialize($this->_height) . ',' .
			Transform::nullSerialize($this->_x)     . ',' . Transform::nullSerialize($this->_y);
	}
	
	public function apply(ImageInterface &$image) 
	{
		$box = $image->getSize();

		// x and y is the center of the crop
		$x = Transform::percentValue($this->_x,      $boxw = $box->getWidth());
		$y = Transform::percentValue($this->_y,      $boxh = $box->getHeight());
		$w = Transform::percentValue($this->_width,  $boxw);
		$h = Transform::percentValue($this->_height, $boxh);

		Transform::scaleSize($w, $h, $box);
		
		if ($this->_ratio) {
			switch ($this->_ratio) {
				case self::COVER:
					$w = $h = min($w, $h);
					break;

				case self::CONTAIN:
					$max = max($w, $h);
					$img = \yii\imagine\Image::getImagine()->create(new Box($max, $max), new Color(0, 100));
					$img->paste($image, new Point(($max - $w) * .5, ($max - $h) * .5));
					$image = $img;
					return;

				default: // custom ratio
					if ($this->_ratio - $w / $h < 0) {
						// fit by height
						$k = 1 * $this->_ratio;
						$w = $h * $k;
					} else {
						// fit by width
						$k = 1 / $this->_ratio;
						$h = $w * $k;
					}
					break;
			}
		}

		$halfw = $w / 2;
		$halfh = $h / 2;
		if ($x + $halfw > $boxw) {
			$x = $boxw - $halfw;
		}
		if ($y + $halfh > $boxh) {
			$y = $boxh - $halfh;
		}
		if ($x < $halfw) {
			$x = $halfw;
		}
		if ($y < $halfh) {
			$y = $halfh;
		}

		$image->crop(
			new Point($x - $w / 2, $y - $h / 2), // top-left corner
			new Box($w, $h)
		);
	}
	
}