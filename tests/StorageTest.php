<?php

namespace x000000\StorageManager\tests;

use x000000\StorageManager\Helper;
use x000000\StorageManager\Transforms\Crop;
use x000000\StorageManager\Transforms\Resize;

class StorageTest extends \PHPUnit_Framework_TestCase
{
	use TestTrait;

	protected function setUp()
	{
		parent::setUp();
		$this->setUpRuntime();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->tearDownRuntime();
	}

	public function testRelativeDir()
	{
		$files = [];

		foreach ([
			'aBCdEfghi.png' => 'a/aB/aBC/aBCd/aBCdE',
			'aCBdEfghi.jpg' => 'a/aC/aCB/aCBd/aCBdE',
			'BdCEfghi.zip'  => 'B/Bd/BdC/BdCE/BdCEf',
		] as $file => $path) {
			$this->assertEquals($path, $this->_storage->getFileDir($file));
			$files[$file] = "$path/$file";
		}

		return $files;
	}

	/**
	 * @param string[] $files
	 * @depends testRelativeDir
	 */
	public function testSourcePath($files)
	{
		foreach ($files as $file => $path) {
			$this->assertEquals("/storage/source/$path", $this->_storage->getSource($file));
		}
	}

	/**
	 * @depends testRelativeDir
	 */
	public function testFileProcessing()
	{
		if (!$this->copyDataFiles()) {
            return null;
		}

		$files = [];
		$glob  = glob("$this->_runtime/*");

		foreach ([false, true] as $remove) {
			foreach ($glob as $file) {
				$hash   = md5_file($file);
				$result = $this->_storage->processFile($file, $remove);

				$this->assertEquals($hash . '.' . pathinfo($file, PATHINFO_EXTENSION), $result);
				$resultPath = $this->_runtime . $this->_storage->getSource($result);
				$this->assertFileExists($resultPath);

				if ($remove) {
					$this->assertFileNotExists($file);
					$files[] = $result;
				} else {
					$this->assertFileExists($file);
					$this->assertFileEquals($file, $resultPath);
				}
			}
		}

		foreach ($glob as $file) {
			$throwed = false;
			try {
				$this->_storage->processFile($file);
			}
			catch (\Exception $e) {
				$throwed = preg_match('#no such file or directory#i', $e->getMessage());
			}
			finally {
				if (!$throwed) {
					$this->fail('Storage manager have to throw an exception on processing an invalid file');
				}
			}
		}

		return $files;
	}

	/**
	 * @depends testFileProcessing
	 */
	public function testFileUpload()
	{
		if (!$this->copyDataFiles()) {
			return;
		}

		$glob = glob("$this->_runtime/*");
		foreach ($glob as $file) {
			$hash   = md5_file($file);
			$result = $this->_storage->processUploadedFile([
				'tmp_name' => $file,
				'name'     => pathinfo($file, PATHINFO_BASENAME),
				'error'    => UPLOAD_ERR_OK,
			]);

			$this->assertEquals($hash . '.' . pathinfo($file, PATHINFO_EXTENSION), $result);
			$resultPath = $this->_runtime . $this->_storage->getSource($result);
			$this->assertFileExists($resultPath);
			$this->assertFileNotExists($file);
			$this->assertEquals($hash, md5_file($resultPath));
		}

		foreach ([
			UPLOAD_ERR_INI_SIZE,
			UPLOAD_ERR_FORM_SIZE,
			UPLOAD_ERR_PARTIAL,
			UPLOAD_ERR_NO_FILE,
			UPLOAD_ERR_NO_TMP_DIR,
			UPLOAD_ERR_CANT_WRITE,
			UPLOAD_ERR_EXTENSION,
		] as $error) {
			/** @noinspection PhpUndefinedVariableInspection */
			$result = $this->_storage->processUploadedFile([
				'tmp_name' => $file,
				'name'     => pathinfo($file, PATHINFO_BASENAME),
				'error'    => $error,
			]);
			$this->assertFalse($result);
		}

		foreach ($glob as $file) {
			$throwed = false;
			try {
				$this->_storage->processUploadedFile([
					'tmp_name' => $file,
					'name'     => pathinfo($file, PATHINFO_BASENAME),
					'error'    => UPLOAD_ERR_OK,
				]);
			}
			catch (\Exception $e) {
				$throwed = preg_match('#no such file or directory#i', $e->getMessage());
			}
			finally {
				if (!$throwed) {
					$this->fail('Storage manager have to throw an exception on processing an invalid file');
				}
			}
		}
	}

	public function testTransformSerialize()
	{
		/**
		 * There are too many options to test (`maybe` my grandchildren will see the result or won't)
		 * So we test only most commonly used (even that many is A LOT)
		 */
		$resize = [
			'&sz(32,64)'    => new Resize(32, 64),
			'&sz(32,64%)'   => new Resize(32, '64%'),
			'&sz(32%,64)'   => new Resize('32%', 64),
			'&sz(50%,64%)'  => new Resize('50%', '64%'),
			'&sz(null,64)'  => new Resize(null, 64),
			'&sz(null,64%)' => new Resize(null, '64%'),
			'&sz(32,null)'  => new Resize(32, null),
			'&sz(32%,null)' => new Resize('32%', null),
		];
		$crop = [
			'&cr(0,16,16,0,0)'          => new Crop(16, 16, 0, 0),
			'&cr(0,16,50%,0,0)'         => new Crop(16, '50%', 0, 0),
			'&cr(0,50%,16,10,20)'       => new Crop('50%', 16, 10, 20),
			'&cr(0,50%,20%,0,0)'        => new Crop('50%', '20%', 0, 0),
			'&cr(0,null,20%,20,10)'     => new Crop(null, '20%', 20, 10),
			'&cr(0,20%,null,0,0)'       => new Crop('20%', null, 0, 0),
			'&cr(0,41%,null,71%,40%)'   => new Crop('41%', null, '71%', '40%'),
			'&cr(0,null,41%,71%,40%)'   => new Crop(null, '41%', '71%', '40%'),
			'&cr(cv,100%,100%,50%,50%)' => new Crop('100%', '100%', '50%', '50%', Crop::COVER),
			'&cr(cn,100%,100%,50%,50%)' => new Crop('100%', '100%', '50%', '50%', Crop::CONTAIN),
			'&cr(1.6,20%,10%,30%,40%)'  => new Crop('20%', '10%', '30%', '40%', 16/10),
		];

		$transforms = ['' => []];
		$sequencer  = new GroupSequencer(... array_filter([$resize, $crop]));

		foreach ($sequencer->getIterator() as $sequence) {
			$list = [];
			foreach ($sequence as list($key, $transform)) {
				$list[ $key ] = $transform;
			}
			$transforms[ implode('', array_keys($list)) ] = array_values($list);
		}

		foreach ($transforms as $key => $list) {
			$this->assertEquals($key, Helper::serializeTransforms($list));
		}

		return $transforms;
	}

	/**
	 * @param array $transforms
	 * @depends testTransformSerialize
	 * @depends testFileProcessing
	 * @depends testRelativeDir
	 */
	public function testCreateThumb($transforms)
	{
		if (!$this->copyDataFiles()) {
			return;
		}

		$glob = glob("$this->_runtime/*");
		foreach ($glob as $file) {
			$source = $this->_storage->processFile($file);

			foreach ($transforms as $key => $tr) {
				$thumb     = $this->_storage->createThumb($source, $tr);
				$thumbFile = pathinfo($source, PATHINFO_FILENAME) . "$key." . pathinfo($source, PATHINFO_EXTENSION);
				$dir       = $this->_storage->getFileDir($source);

				$this->assertEquals("/storage/thumb/$dir/$thumbFile", $thumb);
				$this->assertFileExists("$this->_runtime$thumb");

				if (!$this->_isTravis) {
					// I'm too lazy to make tons of images to test against so we test only simple ones...
					$thumbFile = pathinfo($file, PATHINFO_FILENAME) . "$key." . pathinfo($file, PATHINFO_EXTENSION);
					if (file_exists("$this->_data/thumb/$thumbFile")) {
						$this->assertFileEquals("$this->_data/thumb/$thumbFile", "$this->_runtime$thumb");
					}
				}
			}
		}
	}

	/**
	 * @param array $transforms
	 * @depends testTransformSerialize
	 * @depends testFileProcessing
	 * @depends testCreateThumb
	 */
	public function testThumbPath($transforms)
	{
		if (!$this->copyDataFiles()) {
			return;
		}

		$glob = glob("$this->_runtime/*");
		foreach ($glob as $file) {
			$file = $this->_storage->processFile($file);

			foreach ($transforms as $tr) {
				if (count($tr) > 1) {
					continue;
				}

				$this->assertStringStartsWith('/storage/', $this->_storage->getThumb($file, $tr));
				$this->assertFalse($this->_storage->getThumb("$file.zip", $tr));
			}
		}
	}

}
