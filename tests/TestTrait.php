<?php

namespace x000000\StorageManager\tests;

use x000000\StorageManager\Storage;

trait TestTrait
{
	/**
	 * @var Storage
	 */
	private $_storage;
	private $_runtime = __DIR__ . '/runtime';
	private $_data    = __DIR__ . '/data';
	private $_isTravis;

	private function setUpRuntime()
	{
		$this->_isTravis = getenv('CI') || getenv('TRAVIS');

		\yii\helpers\FileHelper::removeDirectory($this->_runtime);
		mkdir($this->_runtime, 0775);

		$this->_storage = new Storage([
			'deepLevel' => 6,
			'baseDir'   => "$this->_runtime/storage",
			'webDir'    => '/storage',
		]);
	}

	private function tearDownRuntime()
	{
		\yii\helpers\FileHelper::removeDirectory($this->_runtime);
	}

	private function copyDataFiles()
	{
		foreach (glob("$this->_data/*") as $file) {
			if (is_dir($file)) {
				continue;
			}
			if (!copy($file, "$this->_runtime/" . pathinfo($file, PATHINFO_BASENAME))) {
				$this->markTestSkipped('Can not copy fixture data');
				return false;
			}
		}
		return true;
	}

}
