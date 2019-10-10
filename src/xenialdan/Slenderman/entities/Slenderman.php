<?php

namespace xenialdan\Slenderman\entities;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\level\sound\GenericSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use xenialdan\Slenderman\Loader;

class Slenderman extends Human
{

    /**
     * Slenderman constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @throws \InvalidStateException
     */
    public function __construct(Level $level, CompoundTag $nbt)
    {
        $this->setCanSaveWithChunk(false);
        $this->setSkin(Loader::getInstance()->getRandomSkin());
        parent::__construct($level, $nbt);
        $this->getDataPropertyManager()->setFloat(self::DATA_SCALE, 1.35);
        $this->setImmobile();
        $this->setMaxHealth(200);
        $this->setHealth(200);
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
    }

    public function spawnTo(Player $player): void
    {

        parent::spawnTo($player);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        // Remove if "daytime"
        if (!Loader::getInstance()->inTimeRange($this->getLevel()->getTime() % Level::TIME_FULL)) {
            $this->close();
            return false;
        }
        // Randomly teleport
        if ($this->ticksLived % 20 === mt_rand(0, 20) && mt_rand(0, 100) === 0) {
            $this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
            $this->triggerTeleport();
            $this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
            return true;
        }
        // Find closest player
        $player = $this->getLevel()->getNearestEntity($this, max(15, Loader::getInstance()->getPlayerTeleportRange() * 2), Player::class);
        // No need to update, return
        if (!$player instanceof Player) return false;
        // Look at player, add particles
        $this->lookAt($player);
        if ($this->ticksLived % (20 * 10) === 0) {
            $this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
        }
        // Teleport player if too close
        if (Loader::getInstance()->canTeleportNearbyPlayers() && $this->distanceSquared($player) <= Loader::getInstance()->getPlayerTeleportRange()) {
            $rad = deg2rad(mt_rand(0, 360));
            $player->teleport($player->getLevel()->getSafeSpawn($player->add(Loader::getInstance()->getPlayerTeleportRange() * cos($rad), 1, Loader::getInstance()->getPlayerTeleportRange() * sin($rad))));
        }
        return Entity::entityBaseTick($tickDiff);
    }

    /**
     * Teleport the Slender
     */
    public function triggerTeleport()
    {
        $rad = deg2rad(mt_rand(0, 360));
        $this->teleport(($newpos = $this->getLevel()->getSafeSpawn($this->add(Loader::getInstance()->getRandomSpawnRadius() * cos($rad), 1, Loader::getInstance()->getRandomSpawnRadius() * sin($rad)))));
        Loader::getInstance()->getLogger()->debug("Slenderman teleported to X:" . $newpos->x . " Y:" . $newpos->y . " Z:" . $newpos->z);
    }

    /**
     * Plays the effects on the player
     * @param Player $player
     * @throws \InvalidArgumentException
     */
    public function scarePlayer(Player $player)
    {
        Loader::getInstance()->executeCommands($player);
        Loader::getInstance()->sendRandomSpookMessage($player);
        $level = $player->getLevel();
        $pk = new LevelEventPacket();
        $pk->evid = LevelEventPacket::EVENT_GUARDIAN_CURSE;
        $pk->data = 0;
        $pk->position = $player->asVector3();
        $player->sendDataPacket($pk);
        $level->addSound(new GenericSound($player->asVector3(), LevelEventPacket::EVENT_SOUND_TOTEM, 0.1), [$player]);
        $level->addSound(new GenericSound($player->asVector3(), LevelEventPacket::EVENT_SOUND_GHAST, 3.5), [$player]);
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 10 * 20));
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 4 * 20));
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::NAUSEA), 5 * 20));
        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_VIBRATING, true);
        // Flashing screen, stop vibration after 5 seconds
        $task = new class($player) extends Task
        {
            private $counter = 0;
            /** @var Player */
            private $player;

            public function __construct(Player $player)
            {
                $this->player = $player;
            }

            public function onRun(int $currentTick)
            {
                if ($this->counter > 100 || !$this->player->isOnline()) {
                    $this->getHandler()->cancel();
                } else {
                    if ($this->counter <= 20) {
                        if (($this->counter % 2) === 0) {
                            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 5));
                        } else {
                            $this->player->removeEffect(Effect::NIGHT_VISION);
                        }
                    }
                    $this->counter++;
                }
            }

            public function onCancel()
            {
                if ($this->player->isOnline())
                    $this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_VIBRATING, false);
            }
        };

        Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, 5);

        $this->triggerTeleport();
    }

    //Remove gravity
    protected function applyGravity(): void
    {
    }

    //Stop damaging
    public function attack(EntityDamageEvent $source): void
    {
    }
}
