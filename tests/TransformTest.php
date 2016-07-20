<?php

namespace x000000\StorageManager\tests;

use x000000\StorageManager\Helper;
use x000000\StorageManager\Transform;
use x000000\StorageManager\Transforms\AbstractTransform;
use x000000\StorageManager\Transforms\Crop;
use x000000\StorageManager\Transforms\Resize;

class TransformTest extends \PHPUnit_Framework_TestCase
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

	public function testAbstractTransform()
	{
		/** @var AbstractTransform|\PHPUnit_Framework_MockObject_MockObject $stub */
		$stub   = $this->getMockForAbstractClass(AbstractTransform::class);
		$list   = ['one,cfg', 'one-more.cfg', 'some-cfg'];
		$method = $stub
			->expects($this->exactly(count($list) * 3))
			->method('serializeConfig');
		$stub
			->expects($this->any())
			->method('getAlias')
			->will($this->returnValue('mock'));

		foreach ($list as $cfg) {
			$method->will($this->returnValue($cfg));
			$this->assertEquals($cfg, $stub->serializeConfig());
			$this->assertContains($cfg, $sz = $stub->serialize());
			$this->assertEquals($sz, (string) $stub);
		}
	}

	/**
	 * @depends testAbstractTransform
	 */
	public function testCrop()
	{
		if ($this->copyDataFiles()) {
			$this->assertFileTransforms($this->getCrop());
		}
	}

	/**
	 * @depends testAbstractTransform
	 */
	public function testResize()
	{
		if ($this->copyDataFiles()) {
			$this->assertFileTransforms($this->getResize());
		}
	}

	public function testTransform()
	{
		if (!$this->copyDataFiles()) {
			return;
		}

		$files = [];
		foreach (glob("$this->_runtime/*") as $file) {
			$files[] = $this->_storage->processFile($file);
		}

		$transforms = [$this->getResize(), $this->getCrop()];
		$sequencer  = new GroupSequencer(... array_filter($transforms));
		foreach ($files as $file) {
			foreach ($sequencer->getIterator() as $sequence) {
				$transform  = $this->_storage->thumb($file);
				$transforms = [];

				foreach ($sequence as list($key, $value)) {
					$transforms[] = $value;
					$transform->add($value);
				}

				$this->assertTransform('/storage/thumb', $file, $transform);
			}

			$this->assertTransform('/storage/source', $file, $this->_storage->thumb($file));
		}
	}

	private function getCrop()
	{
		return [
			new Crop(16, 16, 0, 0),
			new Crop(16, '50%', 0, 0),
			new Crop('50%', 16, 10, 20),
			new Crop('50%', '20%', 0, 0),
			new Crop(null, '20%', 20, 10),
			new Crop('20%', null, 0, 0),
			new Crop('41%', null, '71%', '40%'),
			new Crop(null, '41%', '71%', '40%'),
			new Crop('100%', '100%', '50%', '50%', Crop::COVER),
			new Crop('100%', '100%', '50%', '50%', Crop::CONTAIN),
			new Crop('20%', '10%', '30%', '40%', 16/10),
		];
	}

	private function getResize()
	{
		return [
			new Resize(32, 64),
			new Resize(32, '64%'),
			new Resize('32%', 64),
			new Resize('50%', '64%'),
			new Resize(null, 64),
			new Resize(null, '64%'),
			new Resize(32, null),
			new Resize('32%', null),
		];
	}

	private function assertTransform($thumbPath, $file, Transform $transform)
	{
		$tfmString = Helper::serializeTransforms($transform->getTransforms());
		$thumbFile = pathinfo($file, PATHINFO_FILENAME) . "$tfmString." . pathinfo($file, PATHINFO_EXTENSION);
		$relDir    = $this->_storage->getFileDir($file);
		$this->assertEquals("$thumbPath/$relDir/$thumbFile", $transform->rawUrl());
		$this->assertEquals($transform->rawUrl(), urldecode($transform->url()));

		$thrown = false;
		try {
			$transform->add(new Resize('50%', '50%'));
		}
		catch (\BadMethodCallException $e) {
			// we have expected this, but we do not want to stop the test
			$thrown = true;
		}
		finally {
			if (!$thrown) {
				$this->fail('Transform have to throw an exception on adding a new item to the queue after self render');
			}
		}
	}

	/**
	 * @param AbstractTransform[] $transforms
	 */
	private function assertFileTransforms($transforms)
	{
		if ($this->_isTravis) {
			$this->markTestSkipped("Test skipped due Travis-CI's version of GD");
			return;
		}

		$imagine = $this->_storage->getImagine();
		foreach (glob("$this->_runtime/*") as $file) {
			$image = $imagine->open($file);

			foreach ($transforms as $transform) {
				/** @var \Imagine\Image\ImageInterface $copy */
				$copy  = $image->copy();
				$thumb = pathinfo($file, PATHINFO_FILENAME) . Helper::serializeTransforms([$transform]) . '.' . pathinfo($file, PATHINFO_EXTENSION);
				$transform->apply($copy, $imagine);
				$copy->save("$this->_runtime/$thumb", ['quality' => 90]);

				$this->assertFileEquals("$this->_data/thumb/$thumb", "$this->_runtime/$thumb");
			}
		}
	}

}
