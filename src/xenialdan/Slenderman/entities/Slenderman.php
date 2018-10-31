<?php

namespace xenialdan\Slenderman\entities;

use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use xenialdan\skinapi\API;
use xenialdan\Slenderman\Loader;

class Slenderman extends Human
{

    public function __construct(Level $level, CompoundTag $nbt)
    {
        $path = Loader::getInstance()->getSkin();
        $img = imagecreatefrompng($path . '.png');
        $skin = new Skin('Slenderman', API::fromImage($img), "", "geometry.humanoid.custom", "");
        imagedestroy($img);
        $this->setSkin($skin);
        parent::__construct($level, $nbt);
        $this->getDataPropertyManager()->setFloat(self::DATA_SCALE, 1.3);
        $this->setImmobile();
        $this->setMaxHealth(200);
        $this->setHealth(200);
    }

    public function triggerTeleport(Player $player)
    {
        $level = $player->getLevel();
        $allowedground = [Block::PODZOL, Block::STAINED_CLAY, Block::DIRT, Block::CONCRETE_POWDER, Block::GRASS, Block::GRASS_PATH, Block::GRAVEL];
        $min = 10;
        $max = 20;
        //teleport it to a save spot min to max blocks away
        $tries = 0;
        do {
            $newpos = $level->getSafeSpawn($this->add((mt_rand(0, 1) === 0 ? mt_rand(-$max, -$min) : mt_rand($min, $max)), (mt_rand(0, 1) === 0 ? mt_rand(-$max, -$min) : mt_rand($min, $max)), (mt_rand(0, 1) === 0 ? mt_rand(-$max, -$min) : mt_rand($min, $max))));
            $tries++;
        } while ($tries <= 10 && !in_array($level->getBlock($newpos)->getSide(Vector3::SIDE_DOWN)->getId(), $allowedground));
        $this->teleport($newpos);
        Loader::getInstance()->getLogger()->debug("Slenderman teleported to X:" . $newpos->x . " Y:" . $newpos->y . " Z:" . $newpos->z);
        $this->lookAt($player);

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
        $plugin = Loader::getInstance();
        $task = new class($plugin, $player) extends Task
        {
            private $counter = 0;
            /** @var Player */
            private $player;
            private $plugin;

            public function __construct(Plugin $owner, Player $player)
            {
                $this->player = $player;
                $this->plugin = $owner;
            }

            public function onRun(int $currentTick)
            {
                if ($this->counter > 100 || !$this->player->isOnline()) {
                    $this->plugin->getScheduler()->cancelTask($this->getTaskId());
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
                $this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_VIBRATING, false);
            }
        };

        $plugin->getScheduler()->scheduleRepeatingTask($task, 5);
    }
}
