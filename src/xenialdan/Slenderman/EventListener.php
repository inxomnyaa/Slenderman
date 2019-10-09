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
use xenialdan\Slenderman\entities\Slenderman;
use xenialdan\Slenderman\other\GameRule;

class EventListener implements Listener
{
    /**
     * @param PlayerMoveEvent $ev
     */
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

    /**
     * @param DataPacketReceiveEvent $event
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        /** @var DataPacket $packet */
        if (!($packet = $event->getPacket()) instanceof InteractPacket) return;
        /** @var Player $player */
        if (!($player = $event->getPlayer()) instanceof Player) return;
        /** @var Level $level */
        if (($level = $player->getLevel())->getId() !== Loader::getInstance()->getServer()->getDefaultLevel()->getId()) {
            return;
        }
        /** @var InteractPacket $packet */
        if ($packet instanceof InteractPacket) {
            if (($entity = Loader::getInstance()->getServer()->findEntity($packet->target)) instanceof Slenderman) {
                /** @var Slenderman $entity */
                $entity->triggerTeleport($player);
                $event->setCancelled();
            }
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     * @throws \InvalidArgumentException
     */
    public function levelChange(EntityLevelChangeEvent $event)
    {
        /** @var Player $player */
        if (!($player = $event->getEntity()) instanceof Player) return;

        $pk = new GameRulesChangedPacket();
        $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, $player->getLevel()->getId() !== Loader::getInstance()->getServer()->getDefaultLevel()->getId());
        $pk->gameRules = (array)$gamerule;
        $player->sendDataPacket($pk);
    }

    /**
     * @param PlayerJoinEvent $event
     * @throws \InvalidArgumentException
     * @throws \pocketmine\level\LevelException
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        $pk = new GameRulesChangedPacket();
        $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, $event->getPlayer()->getLevel()->getId() !== Loader::getInstance()->getServer()->getDefaultLevel()->getId());
        $pk->gameRules = (array)$gamerule;
        $event->getPlayer()->sendDataPacket($pk);
        $entities = Loader::getInstance()->getServer()->getDefaultLevel()->getEntities();
        /** @var Slenderman[] $slenders */
        $slenders = array_filter($entities, function (Entity $entity) {
            return $entity instanceof Slenderman;
        });
        if (count($slenders) >= 1) {
            array_pop($slenders);//keeps one alive
            //remove leftover slenders
            foreach ($slenders as $slender)
                $slender->getLevel()->removeEntity($slender);
        } else {
            $slenderman = new Slenderman(Loader::getInstance()->getServer()->getDefaultLevel(), Entity::createBaseNBT(Loader::getInstance()->getServer()->getDefaultLevel()->getSafeSpawn()->asVector3()));
            if ($slenderman instanceof Entity) {
                Loader::getInstance()->getServer()->getDefaultLevel()->addEntity($slenderman);
                $slenderman->spawnToAll();
            }
        }
    }
}