<?php

namespace x000000\StorageManager;

class Storage extends \yii\base\Component
{
	public $deepLevel = 4;

	public  $webDir;
	private $_webSrc;
	private $_webThumb;

	public  $baseDir;
	private $_baseSrc;
	private $_baseThumb;

	private $_finfo;
	private $_allowedFiles = [
		'images' => [
//			'bmp'  => ['image/bmp'],
			'gif'  => ['image/gif'],
			'jpeg' => ['image/jpeg'],
			'jpg'  => ['image/jpeg'],
			'png'  => ['image/png'],
//			'tif'  => ['image/tiff'],
//			'tiff' => ['image/tiff'],
		],
		'docs' => [
			'pdf' => ['application/pdf', 'application/x-pdf'],
			'txt' => ['text/plain'],
			'rtf' => ['application/rtf', 'text/rtf'],

			'odt' => ['application/vnd.oasis.opendocument.text'],
			'ott' => ['application/vnd.oasis.opendocument.text-template'],
			'odp' => ['application/vnd.oasis.opendocument.presentation'],
			'otp' => ['application/vnd.oasis.opendocument.presentation-template'],
			'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
			'ots' => ['application/vnd.oasis.opendocument.spreadsheet-template'],
			'odc' => ['application/vnd.oasis.opendocument.chart'],
			'odf' => ['application/vnd.oasis.opendocument.formula'],

			'doc'  => ['application/msword', 'application/x-msword'],
			'xls'  => ['application/excel', 'application/vnd.ms-excel'],
			'xlsm' => ['application/vnd.ms-excel.sheet.macroenabled.12'],
			'ppt'  => ['application/vnd.ms-powerpoint'],

			'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			'dotx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.template'],
			'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
		],
		'video' => [
			'avi' => ['video/x-msvideo'],
			'flv' => ['video/x-flv'],
			'mov' => ['video/quicktime'],
			'mp4' => ['video/vnd.objectvideo'],
			'mpg' => ['video/mpeg'],
			'wmv' => ['video/x-ms-wmv'],
		],
		'archives' => [
			'7z'  => ['application/x-7z-compressed', 'application/7z'],
			'rar' => ['application/x-rar-compressed', 'application/rar'],
			'zip' => ['application/x-zip-compressed', 'application/zip'],
			'gz'  => ['application/x-gzip','application/gzip'],
			'tar' => ['application/x-tar', 'application/tar'],
			'tgz' => ['application/gzip', 'application/tar', 'application/tar+gzip'],
		],
		'audio' => [
			'mp3' => ['audio/mpeg'],
			'ogg' => ['application/ogg'],
			'wma' => ['audio/x-ms-wma'],
		],
	];

	public function init()
	{
		if (!($this->deepLevel > 0 && $this->deepLevel < 16)) {
			throw new \yii\base\InvalidConfigException("Invalid deep level!");
		}
		if (empty($this->baseDir)) {
			throw new \yii\base\InvalidConfigException("Base directory have not set!");
		}
		if (empty($this->webDir)) {
			throw new \yii\base\InvalidConfigException("Web directory have not set!");
		}

		$this->baseDir    = \Yii::getAlias($this->baseDir);
		$this->webDir     = \Yii::getAlias($this->webDir);
		$this->_baseSrc   = "{$this->baseDir}/source";
		$this->_baseThumb = "{$this->baseDir}/thumb";
		$this->_webSrc    = "{$this->webDir}/source";
		$this->_webThumb  = "{$this->webDir}/thumb";

		if (class_exists('\finfo', false)) {
			$this->_finfo = new \finfo();
		}
	}

	/**
	 * Check file extension against allowed file types
	 * @param string $ext file extension
	 * @return string|bool File extension's mime-types of false if extension is not allowed
	 */
	public function isAllowed($ext)
	{
		foreach ($this->_allowedFiles as $collection) {
			if (isset($collection[$ext])) {
				return $collection[$ext];
			}
		}
		return false;
	}

	/**
	 * Get $file's source url<br/>
	 * Important: method do not check file existence for performance purpose!
	 * @param string $file File name (see $this->processFile()'s return value)
	 * @return string|bool File's source url or false on empty $file
	 */
	public function getSource($file)
	{
		return empty($file)
			? false
			: $this->_webSrc . '/' . $this->getFileDir($file) . '/' . $file;
	}

	/**
	 * Get $file's thumb url. method works for image types only.
	 * @param string $file File name (see $this->processFile()'s return value)
	 * @param Transforms\AbstractTransform[] $transforms Transforms ro be applied
	 * @return string|bool File's thumb url or false on fail
	 */
	public function getThumb($file, $transforms = [])
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if (!isset($this->_allowedFiles['images'][$ext])) {
			return false;
		}

		$relDir = $this->getFileDir($file);
		$dir    = $this->_baseThumb . "/$relDir";

		if (is_dir($dir)) {
			$name = pathinfo($file, PATHINFO_FILENAME) . Helper::serializeTransforms($transforms) . ".$ext";
			if (file_exists("$dir/$name")) {
				return $this->_webThumb . "/$relDir/$name";
			}
		}

		return $this->createThumb($file, $transforms);
	}

	/**
	 * Get $file's directory relative to $this->baseDir
	 * @param string $file File name (see $this->processFile()'s return value)
	 * @return string $file's directory under $this->baseDir
	 */
	public function getFileDir($file)
	{
		$dir = '';
		for ($i = 1; $i < $this->deepLevel; $i++) {
			$dir .= '/' . substr($file, 0, $i);
		}
		return substr($dir, 1);
	}

	/**
	 * Process uploaded file<br/>See $this->processFile()
	 * @param array $file File entry from $_FILES
	 * @return string|boolean process result (see $this->processFile()'s return value)
	 */
	public function processUploadedFile($file)
	{
		if (UPLOAD_ERR_OK == $file['error']) {
			$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
			$tempfile = $file['tmp_name'];
			if ($file = $this->processFileInternal($tempfile, $ext, false)) {
				unlink($tempfile);
				return $file;
			}
		}
		return false;
	}

	/**
	 * Process file and store it under new name in the storage folder
	 * @param string $file Full path to the file
	 * @param bool $removeOriginal Should be $file be deleted after success process
	 * @return string|false Processed file or false on fail
	 **/
	public function processFile($file, $removeOriginal = true)
	{
		return $this->processFileInternal($file, pathinfo($file, PATHINFO_EXTENSION), $removeOriginal);
	}

	private function processFileInternal($file, $type, $removeOriginal = true)
	{
		foreach ($this->_allowedFiles as $list) {
			if (isset($list[$type])) {
				if ($this->_finfo && $mime = $this->_finfo->file($file, FILEINFO_MIME_TYPE)) {
					if (!in_array($mime, $list[$type])) {
						return false;
					}
				}

				$name = md5_file($file);
				$dest = $this->_baseSrc . '/' . $this->getFileDir($name);
				if (!is_dir($dest)) {
					mkdir($dest, 0775, true);
				}
				$dest .= "/$name.$type";

				if ($removeOriginal) {
					return rename($file, $dest) ? "$name.$type" : false;
				} else {
					return copy($file, $dest) ? "$name.$type" : false;
				}
			}
		}
		return false;
	}

	/**
	 * Create a thumbnail of the source $file with given $transforms
	 * @param string $file Source File (see $this->processFile()'s return value)
	 * @param Transforms\AbstractTransform[] $transforms Transforms ro be applied
	 * @return string|bool Thumb url or false on fail
	 */
	public function createThumb($file, $transforms = [])
	{
		$finfo   = pathinfo($file);
		$relDir  = $this->getFileDir($file);
		$thbPath = "/$relDir/" . $finfo['filename'] . Helper::serializeTransforms($transforms) . '.' . $finfo['extension'];

		if (is_dir($this->_baseThumb . "/$relDir")) {
			if (file_exists($this->_baseThumb . $thbPath)) {
				return $this->_webThumb . $thbPath;
			}
		}
		elseif (!mkdir($this->_baseThumb . "/$relDir", 0775, true)) {
			return false;
		}

		$image = \yii\imagine\Image::getImagine()->open($this->_baseSrc . "/$relDir/$file");
		foreach ($transforms as $transform) {
			$transform->apply($image);
		}
		$image->save($this->_baseThumb . $thbPath, ['quality' => 90]);

		return $this->_webThumb . $thbPath;
	}

	/**
	 * Get Transform object of the $source image<br/>
	 * This is just a helper method for convenience
	 * @param string $source File name (see $this->processFile()'s return value)
	 * @return Transform Transform object for chained modify
	 */
	public function thumb($source)
	{
		return new Transform($this, $source);
	}

}