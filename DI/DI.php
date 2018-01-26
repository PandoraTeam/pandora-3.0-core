<?php
namespace pandora\core3\DI;

use pandora\core3\DI\Exceptions\{DIException, DIKeyNotFoundException};
use \Closure;

class DI {

	/**
	 * DI constructor.
	 * @param array $dependencies
	 */
	public function __construct(array $dependencies = []) {
		if ($dependencies) {
			$this->setDependencies($dependencies);
		}
	}

	/**
	 * DI container instance.
	 * @var object $containerInstance
	 */
	private $containerInstance = null;

	/**
	 * Binds the DI container instance.
	 * @param object $containerInstance
	 */
	public function bind($containerInstance) {
		$this->containerInstance = $containerInstance;
	}

	/**
	 * Array of dependencies.
	 * @var array $dependencies
	 */
	private $dependencies = [];

	/**
	 * Array of instances.
	 * @var array $instances
	 */
	private $instances = [];

	/**
	 * @param string $key
	 * @param array|Closure $constructionParams
	 */
	public function set(string $key, $constructionParams) {
		try {
			$this->_setDependency($key, $constructionParams);
		} catch (DIException $e) { }
	}
	
	/**
	 * @param string $key
	 * @param array|Closure $constructionParams
	 * @throws DIException
	 */
	private function _setDependency(string $key, $constructionParams) {
		if (array_key_exists($key, $this->dependencies)) {
			// todo: refactor in accordance with debug api
			// 'dependency already set'
			throw new DIException(['DI_DEPENDENCY_KEY_ALREADY_SET', $key]);
		}
		$this->dependencies[$key] = $constructionParams;
	}

	/**
	 * @param array $dependencies
	 */
	public function setDependencies(array $dependencies) {
		foreach ($dependencies as $key => $constructionParams) {
			try {
				$this->_setDependency($key, $constructionParams);
			} catch (DIException $e) { }
		}
	}

	/**
	 * @param array|Closure $constructionParams
	 * @param array|null $overrideParams
	 * @throws DIException
	 * @return null|object
	 */
	private function createInstance($constructionParams, $overrideParams = null) {
		if ($constructionParams instanceof Closure) {
			$args = $overrideParams ?? [];
			array_unshift($args, $this->containerInstance);
			return call_user_func_array($constructionParams, $args);
		} else {
			$className = array_shift($constructionParams);
			$constructionParams = $overrideParams ?? $constructionParams;
			try {
				if ($constructionParams) {
					return call_user_func_array([$className, '__construct'], $constructionParams);
				} else {
					return new $className();
				}
			} catch (\Throwable $e) {
				// todo: refactor in accordance with debug api
				// 'Creating class "'.$className.'" failed'
				throw new DIException(['DI_DEPENDENCY_CREATION_FAILED', $className, $constructionParams], E_WARNING, $e);
			}
		}
	}

	/**
	 * @param string $key
	 * @param bool $isInstance
	 * @param array|null $overrideParams
	 * @throws DIKeyNotFoundException
	 * @throws DIException
	 * @return null|object
	 */
	private function _getDependency(string $key, bool $isInstance, $overrideParams = null) {
		if (!array_key_exists($key, $this->dependencies)) {
			// todo: refactor in accordance with debug api
			// 'dependency key not found'
			throw new DIKeyNotFoundException($key);
		}
		$constructionParams = $this->dependencies[$key];
		if ($isInstance) {
			if (!array_key_exists($key, $this->instances)) {
				$this->instances[$key] = $this->createInstance($constructionParams, $overrideParams);
			}
			return $this->instances[$key];
		} else {
			return $this->createInstance($constructionParams, $overrideParams);
		}
		
	//	} catch (\Throwable $e) {
	//		// todo: refactor in accordance with debug api
		//	ob_start();
		//	var_dump($constructionParams);
		//	$params = '<pre>'.ob_get_clean().'</pre>';
		//	trigger_error('DI dependency not created "'.$key.'" '.$e->getMessage(), E_USER_WARNING);
		//	trigger_error($params, E_USER_WARNING);
		
	//		throw new DIException([], E_WARNING, $e);
		
			// trigger_error($e->getMessage(), E_USER_WARNING);
			
	//	}
	}

	/**
	 * @param string $key
	 * @param array|null $overrideParams
	 * @throws DIKeyNotFoundException
	 * @return null|object
	 */
	public function create(string $key, $overrideParams = null) {
		try {
			return $this->_getDependency($key, false, $overrideParams);
		} catch (DIKeyNotFoundException $e) {
			throw $e;
		} catch (DIException $e) {
			return null;
		}
	}

	/**
	 * @param string $key
	 * @param array|null $overrideParams
	 * @throws DIKeyNotFoundException
	 * @return null|object
	 */
	public function get(string $key, $overrideParams = null) {
		try {
			return $this->_getDependency($key, true, $overrideParams);
		} catch (DIKeyNotFoundException $e) {
			throw $e;
		} catch (DIException $e) {
			return null;
		}
	}

}