<?php

namespace xenialdan\Spooky;


use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use xenialdan\Spooky\entities\Slenderman;


class Loader extends PluginBase{
	/** @var Loader */
	private static $instance = null;

	public function onLoad(){
		self::$instance = $this;
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		//entities
		Entity::registerEntity(Slenderman::class, true, ["spooky:slenderman"]);
		//try to lock time
		$this->getServer()->getDefaultLevel()->setTime(17000);
		$this->getServer()->getDefaultLevel()->stopTime();
	}

	/**
	 * Returns an instance of the plugin
	 * @return Loader
	 */
	public static function getInstance(){
		return self::$instance;
	}
}