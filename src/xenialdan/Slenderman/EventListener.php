<?php

namespace xenialdan\Slenderman;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use xenialdan\Slenderman\entities\Slenderman;
use xenialdan\Slenderman\other\GameRule;

class EventListener implements Listener
{

    /**
     * Checks
     * @param DataPacketReceiveEvent $event
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        /** @var InteractPacket $packet */
        if (!($packet = $event->getPacket()) instanceof InteractPacket) return;
        /** @var Player $player */
        if (!($player = $event->getPlayer()) instanceof Player) return;
        /** @var Level $level */
        if (!Loader::getInstance()->isSlenderWorld($level = $player->getLevel()) || !Loader::getInstance()->inTimeRange($level->getTime() % Level::TIME_FULL)) {
            return;
        }
        /** @var Slenderman $entity */
        if (($entity = Loader::getInstance()->getServer()->findEntity($packet->target)) instanceof Slenderman) {
            $entity->scarePlayer($player);
            $event->setCancelled();
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     * @throws \InvalidArgumentException
     */
    public function levelChange(EntityLevelChangeEvent $event)
    {
        // Try to spawn slender. The function handles all cases already
        Loader::getInstance()->spawnSlender($event->getEntity()->getLevel()->getSafeSpawn($event->getEntity()));
        // Nothing else to do if we do not lock the time
        if (!Loader::getInstance()->useLockTime()) return;
        /** @var Player $player */
        if (!($player = $event->getEntity()) instanceof Player) return;

        $pk = new GameRulesChangedPacket();
        $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, Loader::getInstance()->isSlenderWorld($player->getLevel()));
        $pk->gameRules = (array)$gamerule;
        $player->sendDataPacket($pk);
    }

    public function levelLoad(LevelLoadEvent $event)
    {
        // Nothing to do if we do not lock the time or not slender world
        if (!Loader::getInstance()->useLockTime() && !Loader::getInstance()->isSlenderWorld($event->getLevel())) return;
        $event->getLevel()->setTime(Loader::getInstance()->getLockedTime());
        $event->getLevel()->stopTime();
    }

    /**
     * @param PlayerJoinEvent $event
     * @throws \InvalidArgumentException
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        // Nothing to do if not slender world
        if (!Loader::getInstance()->isSlenderWorld($event->getPlayer()->getLevel())) return;
        // Stop time
        if (Loader::getInstance()->useLockTime()) {
            $event->getPlayer()->getLevel()->setTime(Loader::getInstance()->getLockedTime());
            $event->getPlayer()->getLevel()->stopTime();
            $pk = new GameRulesChangedPacket();
            $gamerule = new GameRule(GameRule::DODAYLIGHTCYCLE, GameRule::TYPE_BOOL, true);
            $pk->gameRules = (array)$gamerule;
            $event->getPlayer()->sendDataPacket($pk);
        }
        // Try to spawn slender. The function handles all cases already
        Loader::getInstance()->spawnSlender($event->getPlayer()->getLevel()->getSafeSpawn($event->getPlayer()));
    }
}