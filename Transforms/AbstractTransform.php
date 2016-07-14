<?php

namespace x000000\StorageManager\Transforms;

abstract class AbstractTransform 
{
	public abstract function serializeConfig();
	public abstract function apply(\Imagine\Image\ImageInterface &$image);

	public function serialize() 
	{
		$map = \x000000\StorageManager\Transform::ALIAS_MAP;
		return $map[get_called_class()] . '(' . $this->serializeConfig() . ')';
	}

	public function __toString() 
	{
		return $this->serialize();
	}
	
}
