<?php

namespace x000000\StorageManager\Transforms;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use x000000\StorageManager\Helper;

class Resize extends AbstractTransform
{
	private $_width;
	private $_height;

	public function __construct($width, $height)
	{
		$this->_width  = $width;
		$this->_height = $height;
	}

	public function getAlias()
	{
		return 'sz';
	}

	public function serializeConfig()
	{
		return Helper::nullSerialize($this->_width) . ',' . Helper::nullSerialize($this->_height);
	}

	public function apply(ImageInterface &$image)
	{
		$box    = $image->getSize();
		$width  = Helper::percentValue($this->_width,  $box->getWidth());
		$height = Helper::percentValue($this->_height, $box->getHeight());

		// no upscale
		if ($box->getWidth() <= $width && $box->getHeight() <= $height) {
			return;
		}

		Helper::scaleSize($width, $height, $box);

		$image->resize(new Box($width, $height));
	}

}