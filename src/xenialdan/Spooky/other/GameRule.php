<?php

namespace xenialdan\Spooky\other;

class GameRule {

	const TYPE_BOOL = 1;
	const TYPE_INT = 2;
	const TYPE_FLOAT = 3;

	/** @var string */
	public $name;
	/** @var int */
	public $type;
	/** @var mixed */
	public $value;

	/**
	 * Gamerule constructor.
	 * @param string $name
	 * @param int $type
	 * @param mixed $value
	 */
	public function __construct(string $name, int $type, mixed $value){
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
	}

	/**
	 * @return array
	 */
	public function get(){
		return [$this->name => [$this->type, $this->value]];
	}
}