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

		$this->removeDirectory($this->_runtime);
		mkdir($this->_runtime, 0775);

		$this->_storage = new Storage("$this->_runtime/storage", '/storage', 6);
	}

	private function tearDownRuntime()
	{
		$this->removeDirectory($this->_runtime);
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

	private function removeDirectory($dir)
	{
		if (!is_dir($dir)) {
			return;
		}
		if (is_link($dir)) {
			unlink($dir);
		} else {
			if (!($handle = opendir($dir))) {
				return;
			}

			while (($file = readdir($handle)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}

				$path = $dir . DIRECTORY_SEPARATOR . $file;
				if (is_dir($path)) {
					$this->removeDirectory($path);
				} else {
					unlink($path);
				}
			}
			closedir($handle);

			rmdir($dir);
		}
	}

}
