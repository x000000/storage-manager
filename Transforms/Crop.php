<?php

namespace x000000\StorageManager\Transforms;

use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use x000000\StorageManager\Helper;

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

	public function getAlias()
	{
		return 'cr';
	}

	public function serializeConfig()
	{
		return
			($this->_ratio ? Helper::nullSerialize($this->_ratio) : '0') . ',' .
			Helper::nullSerialize($this->_width) . ',' . Helper::nullSerialize($this->_height) . ',' .
			Helper::nullSerialize($this->_x)     . ',' . Helper::nullSerialize($this->_y);
	}

	public function apply(ImageInterface &$image, ImagineInterface $imagine)
	{
		$box = $image->getSize();

		// x and y is the center of the crop
		$x = Helper::percentValue($this->_x,      $boxw = $box->getWidth());
		$y = Helper::percentValue($this->_y,      $boxh = $box->getHeight());
		$w = Helper::percentValue($this->_width,  $boxw);
		$h = Helper::percentValue($this->_height, $boxh);

		if ($this->_ratio) {
			switch ($this->_ratio) {
				case self::COVER:
					Helper::scaleSize($w, $h, $box);
					$w = $h = min($w, $h);
					break;

				case self::CONTAIN:
					Helper::scaleSize($w, $h, $box);
					$max = max($w, $h);
					$img = $imagine->create(new Box($max, $max), new Color(0, 100));
					$img->paste($image, new Point(($max - $boxw) * .5, ($max - $boxh) * .5));
					$image = $img;
					return;

				default: // custom ratio
					$this->fitByRatio($w, $h, $w && $h ? $w / $h : 0);
					if (!$w || !$h) {
						throw new \RuntimeException('Invalid ratio supplied');
					}
					break;
			}
		} else {
			Helper::scaleSize($w, $h, $box);
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

	private function fitByRatio(&$w, &$h, $ratio)
	{
		if ($ratio) {
			if ($this->_ratio - $ratio < 0) {
				// fit by height
				$k = 1 * $this->_ratio;
				$w = $h * $k;
			} else {
				// fit by width
				$k = 1 / $this->_ratio;
				$h = $w * $k;
			}
		}
	}

}