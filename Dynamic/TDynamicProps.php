<?php
namespace pandora3\core\Dynamic;

trait TDynamicProps {

	/**
	 * @param string $param
	 * @return mixed
	 */
	public function __get(string $param) {
		$getter = 'get'.ucfirst($param);
		if (method_exists($this, $getter)) {
			return $this->$getter();
		}
		return parent::__get($param);
	}

	/**
	 * @param string $param
	 * @return bool
	 */
	public function __isset(string $param): bool {
		$getter = 'get'.ucfirst($param);
		if (method_exists($this, $getter)) {
			return true;
		}
		return parent::__isset($param);
	}

}