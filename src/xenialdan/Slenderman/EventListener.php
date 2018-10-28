<?php

namespace xenialdan\Slenderman;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use xenialdan\Slenderman\entities\Slenderman;
use xenialdan\Slenderman\other\GameRule;

class EventListener implements Listener
{
    /** @var Loader */
    public $owner;

    public function __construct(Plugin $plugin)
    {
        $this->owner = $plugin;
    }

    public function onPlayerMove(PlayerMoveEvent $ev)
    {
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();
        if ($from->distanceSquared($to) < 0.1 ** 2) {
            return;
        }
        $maxDistance = 15;
        $entities = $player->getLevel()->getNearbyEntities($player->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $player);
        foreach ($entities as $e) {
            if (!$e instanceof Slenderman) {
                continue;
            }
            $e->lookAt($player);
        }
    }

    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        /** @var DataPacket $packet */
        if (!($packet = $event->getPacket()) instanceof InteractPacket) return;
        /** @var Player $player */
        if (!($player = $event->getPlayer()) instanceof Player) return;
        /** @var Level $level */
        if (($level = $player->getLevel())->getId() !== Server::getInstance()->getDefaultLevel()->getId()) {
            return;
        }
        /** @var InteractPacket $packet */
        if ($packet instanceof InteractPacket) {
            if (($entity = Server::getInstance()->findEntity($packet->target)) instanceof Slenderman) {
                /** @var Slenderman $entity */
                $entity->triggerTeleport($player);
                $event->setCancelled();
            }
        }
    }

    public function levelChange(EntityLevelChangeEvent $event)
    {
        /** @var Player $player */
        if (!($player = $event->getEntity()) instanceof Player) return;

        $pk = new GameRulesChangedPacket();
        $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, $player->getLevel()->getId() !== Server::getInstance()->getDefaultLevel()->getId());
        $pk->gameRules = (array)$gamerule;
        $player->sendDataPacket($pk);
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $pk = new GameRulesChangedPacket();
        $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, $event->getPlayer()->getLevel()->getId() !== Server::getInstance()->getDefaultLevel()->getId());
        $pk->gameRules = (array)$gamerule;
        $event->getPlayer()->sendDataPacket($pk);
        $entities = $this->owner->getServer()->getDefaultLevel()->getEntities();
        /** @var Slenderman[] $slenders */
        $slenders = array_filter($entities, function (Entity $entity) {
            return $entity->getDataPropertyManager()->getString(Entity::DATA_NAMETAG) === "Slenderman";
        });
        if (count($slenders) >= 1) {
            $slenderman = array_pop($slenders); //keeps one alive
        } else {
            $slenderman = new Slenderman($this->owner->getServer()->getDefaultLevel(), Entity::createBaseNBT($this->owner->getServer()->getDefaultLevel()->getSafeSpawn()->asVector3()));
            if ($slenderman instanceof Entity) {
                $slenderman->setNameTag("Slenderman");
                $this->owner->getServer()->getDefaultLevel()->addEntity($slenderman);
                $slenderman->spawnToAll();
            }
        }
        foreach ($slenders as $slender)
            $slender->getLevel()->removeEntity($slender);
        $slenderman->spawnTo($event->getPlayer());
    }
}