<?php
namespace pandora\core3\App;

use \Exception;
use pandora\core3\Dynamic\DIDynamic\DIContainerDynamic;

abstract class BaseApp extends DIContainerDynamic {

	/**
	 * Application instance
	 * @var BaseApp $appInstance
	 */
	private static $appInstance = null;

	public function __construct() {
		parent::__construct();
		if (self::$appInstance === null) {
			self::$appInstance = $this;
		}
	}

	/**
	 * Returns the application instance
	 * @return BaseApp
	 */
	public static function getInstance() {
		return self::$appInstance;
	}

	/**
	 * Configuration settrings
	 * @var array $config
	 */
	public $config;

	/**
	 * Path to application directory
	 * @var array $config
	 */
	public $path;

	/**
	 * Gets the path of application class
	 * @return string
	 */
	public function getPath() {
		$appClass = new \ReflectionClass(get_called_class());
		return unixPath(dirname($appClass->getFileName()));
	}

	/**
	 * Gets the application configuration
	 * @throws Exception
	 * @return array
	 */
	public function getConfig() {
		try {
			return require($this->path.'/config.php');
		} catch (Exception $e) {
			// Debug::logException($e);
			// todo: refactor in accordance with debug api
			throw new Exception('Unable to load application config', E_ERROR);
		}
	}

	/**
	 * Initialises application parameters
	 */
	public function initParams() {
		require(__DIR__.'/../functions.php');

		$this->entryPath = unixPath(getcwd());
		$this->path = $this->getPath();
		$this->config = $this->getConfig();
	}

	public abstract function init();

	public abstract function run();

}