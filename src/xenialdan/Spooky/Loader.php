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
		Entity::registerEntity(Slenderman::class, true);
		//try to stop time
		$this->getServer()->getDefaultLevel()->setTime(17000);
		$this->getServer()->getDefaultLevel()->stopTime();
		$entities = $this->getServer()->getDefaultLevel()->getEntities();
		$slenders = array_filter($entities, function (Entity $entity){
			return $entity instanceof Slenderman;
		});
		if (count($slenders) < 1){
			$entity = new Slenderman($this->getServer()->getDefaultLevel(), Entity::createBaseNBT($this->getServer()->getDefaultLevel()->getSafeSpawn()->asVector3()));
			if ($entity instanceof Entity){
				$this->getServer()->getDefaultLevel()->addEntity($entity);
				$entity->spawnToAll();
			}
		}
	}

	/**
	 * Returns an instance of the plugin
	 * @return Loader
	 */
	public static function getInstance(){
		return self::$instance;
	}
}