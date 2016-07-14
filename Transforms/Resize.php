<?php

namespace x000000\StorageManager\Transforms;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use x000000\StorageManager\Transform;

class Resize extends AbstractTransform
{
	private $_width;
	private $_height;
	
	public function __construct($width, $height) 
	{
		$this->_width  = $width;
		$this->_height = $height;
	}

	public static function create($config) 
	{
		return new self($config['w'], $config['h']);
	}
	
	public function serializeConfig() 
	{
		return Transform::nullSerialize($this->_width) . ',' . Transform::nullSerialize($this->_height);
	}
	
	public function apply(ImageInterface &$image) 
	{
		$box    = $image->getSize();
		$width  = Transform::percentValue($this->_width,  $box->getWidth());
		$height = Transform::percentValue($this->_height, $box->getHeight());
		
		// no upscale
		if ($box->getWidth() <= $width && $box->getHeight() <= $height) {
			return;
		}

		Transform::scaleSize($width, $height, $box);

		$image->resize(new Box($width, $height));
	}
	
}