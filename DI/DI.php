<?php
namespace pandora3\core\DI;

use pandora3\core\DI\Exceptions\{DIException, DIKeyNotFoundException};
use Closure;
use Throwable;

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
	 * @param string|array|Closure $constructionParams
	 */
	public function set(string $key, $constructionParams): void {
		try {
			$this->_setDependency($key, $constructionParams);
		} catch (DIException $ex) { }
	}

	/**
	 * @param string $key
	 * @param string|array|Closure $constructionParams
	 * @throws DIException
	 */
	private function _setDependency(string $key, $constructionParams): void {
		if (array_key_exists($key, $this->dependencies)) {
			// 'dependency already set'
			throw new DIException(['DI_DEPENDENCY_KEY_ALREADY_SET', $key]);
		}
		if (is_string($constructionParams)) {
			$constructionParams = [$constructionParams];
		}
		$this->dependencies[$key] = $constructionParams;
	}

	/**
	 * @param array $dependencies
	 */
	public function setDependencies(array $dependencies): void {
		foreach ($dependencies as $key => $constructionParams) {
			try {
				$this->_setDependency($key, $constructionParams);
			} catch (DIException $ex) { }
		}
	}

	/**
	 * @param array $dependencies
	 * @param DI $parent
	 */
	public function extend(array $dependencies, DI $parent): void {
		foreach ($dependencies as $key => $constructionParams) {
			if (array_key_exists($key, $parent->dependencies)) {
				$constructionParams = $parent->dependencies[$key];
			}
			try {
				$this->_setDependency($key, $constructionParams);
			} catch (DIException $ex) { }
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
			return call_user_func_array($constructionParams, $args);
		} else {
			$className = array_shift($constructionParams);
			$constructionParams = $overrideParams ?? $constructionParams;
			try {
				if ($constructionParams) {
					return new $className(...$constructionParams);
				} else {
					return new $className();
				}
			} catch (Throwable $ex) {
				// 'Creating class "'.$className.'" failed'
				throw new DIException(['DI_DEPENDENCY_CREATION_FAILED', $className, $constructionParams], E_WARNING, $ex);
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
		} catch (DIKeyNotFoundException $ex) {
			throw $ex;
		} catch (DIException $ex) {
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
		} catch (DIKeyNotFoundException $ex) {
			throw $ex;
		} catch (DIException $ex) {
			return null;
		}
	}

}