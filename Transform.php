<?php

namespace x000000\StorageManager;

/**
 * @method Transform resize(int? $width, int? $height) resize
 * @method Transform crop(int? $width, int? $height, int|string $x, int|string $y, int|string $ratio = null) crop
 */
class Transform
{
	private $_storage;
	private $_source;
	private $_transforms = [];
	private $_rawUrl;
	private $_url;

	private $_map = [
		'resize' => Transforms\Resize::class,
		'crop'   => Transforms\Crop::class,
	];

	public function __construct(Storage $storage, $source)
	{
		$this->_storage = $storage;
		$this->_source  = $source;
	}

	public function __toString()
	{
		return $this->url();
	}

	public function url()
	{
		if ($this->_url === null) {
			if (empty($this->_source)) {
				return $this->_url = $this->_rawUrl = false;
			}
			if (empty($this->_transforms)) {
				// no transform given so we can return url to the source file
				return $this->_url = $this->_rawUrl = $this->_storage->getSource($this->_source);
			}

			if (!$path = $this->_storage->getThumb($this->_source, $this->_transforms)) {
				return $this->_url = $this->_rawUrl = false;
			}

			$this->_rawUrl = $path;

			// we should encode file name so it won't break anything
			$path   = explode('/', $path);
			$path[] = urlencode( array_pop($path) );

			return $this->_url = implode('/', $path);
		}
		return $this->_url;
	}

	public function rawUrl()
	{
		if ($this->_url === null) {
			$this->url();
		}
		return $this->_rawUrl;
	}

	public function getTransforms()
	{
		return $this->_transforms;
	}

	public function add($transform)
	{
		if ($this->_url !== null) {
			throw new \yii\base\InvalidCallException('Transforms already applied');
		}
		$this->_transforms[] = $transform;
	}

	public function __call($name, $arguments)
	{
		if (isset($this->_map[$name])) {
			$class = $this->_map[$name];
			$this->add(new $class(... $arguments));
			return $this;
		} else {
			throw new \BadFunctionCallException('Method ' . self::class . "::$name() is not exists");
		}
	}

}