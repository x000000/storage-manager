<?php

namespace x000000\StorageManager\Transforms;

abstract class AbstractTransform
{
	public abstract function getAlias();
	public abstract function serializeConfig();
	public abstract function apply(\Imagine\Image\ImageInterface &$image, \Imagine\Image\ImagineInterface $imagine);

	public function serialize()
	{
		return $this->getAlias() . '(' . $this->serializeConfig() . ')';
	}

	public function __toString()
	{
		return $this->serialize();
	}

}
