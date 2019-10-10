<?php

namespace xenialdan\Slenderman;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\TextFormat;
use xenialdan\skinapi\API;
use xenialdan\Slenderman\entities\Slenderman;
use xenialdan\Slenderman\other\SpawnTask;

class Loader extends PluginBase
{
    /** @var Loader */
    private static $instance = null;
    private static $skins = [];
    private static $worlds = [];

    /**
     * @throws PluginException
     */
    public function onLoad()
    {
        if (!extension_loaded('gd')) {
            throw new PluginException("gd is not enabled!");
        }
        self::$instance = $this;
        // Skins
        if ($this->randomSkins()) $skinPaths = glob($this->getDataFolder() . "*.png");
        else $skinPaths = [];
        if ($this->saveDefaultConfig() || $skinPaths === false || empty($skinPaths)) {
            //save default skin upon first load, and also if there are no skins or an error occurred when looking for skins
            $this->saveResource('slenderman.png');
        }
        $skinPaths[] = $this->getDataFolder() . 'slenderman.png';
        print_r($skinPaths);
        if (is_array($skinPaths)) {
            foreach ($skinPaths as $id => $skinPath) {
                set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($skinPath) {
                    $this->getLogger()->warning("Skin " . basename($skinPath) . " could not be loaded. Error: #$errno - $errstr");
                });
                $img = imagecreatefrompng($skinPath);
                restore_error_handler();
                if ($img === false) {
                    continue;//just continue, log via error handler above
                }
                self::$skins[] = new Skin('Slenderman', API::fromImage($img), "", "geometry.humanoid.custom", "");
                @imagedestroy($img);
            }
        }
        if (empty(self::$skins)) {
            throw new PluginException("Could not get skin files, disabling!");
        }
    }

    /**
     * @throws \pocketmine\plugin\PluginException
     */
    public function onEnable()
    {
        // Levels
        self::$worlds = $this->getConfig()->get("levels", []);
        if (empty(self::$worlds)) self::$worlds[] = $this->getServer()->getDefaultLevel()->getFolderName();
        foreach (self::$worlds as $world) {
            print $world . " => " . ($this->isSlenderWorld($this->getServer()->getLevelByName($world)) ? "Yes" : "No");
        }
        //events
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        //entities
        Entity::registerEntity(Slenderman::class, true, ["slenderman:slenderman"]);
        //auto spawning task
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new SpawnTask(), 1000);
    }

    /**
     * Returns an instance of the plugin
     * @return Loader
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function getRandomSkin(): Skin
    {
        return self::$skins[array_rand(self::$skins)];
    }

    public function isSlenderWorld(Level $level): bool
    {
        var_dump(self::$worlds);
        print (in_array($level->getFolderName(), self::$worlds) ? "Is world" : "Not world");
        return in_array($level->getFolderName(), self::$worlds);
    }

    public function useLockTime(): bool
    {
        return $this->getConfig()->get("lock-time", true);
    }

    public function getLockedTime(): bool
    {
        return $this->getConfig()->get("locked-time", 18000);
    }

    public function useTimeRange(): bool
    {
        return $this->getConfig()->get("use-time-range", false);
    }

    public function inTimeRange(int $time): bool
    {
        if (!$this->useTimeRange()) return true;
        $min = $this->getConfig()->get("minimum-spawn-time", 13000);
        $max = $this->getConfig()->get("maximum-spawn-time", 22550);
        return min($min, $max) <= $time && $time <= max($min, $max);
    }

    public function getRandomSpawnRadius(): int
    {
        return mt_rand($this->getConfig()->get("minimum-spawn-radius", 10), $this->getConfig()->get("maximum-spawn-radius", 25));
    }

    public function getPlayerTeleportRange(): int
    {
        return $this->getConfig()->get("teleport-player-range", 3);
    }

    public function canTeleportNearbyPlayers(): bool
    {
        return $this->getConfig()->get("teleport-nearby-players", false);
    }

    public function sendRandomSpookMessage(Player $player): void
    {
        $messages = $this->getConfig()->get("spook-messages", []);
        if (empty($messages)) return;
        $player->sendMessage(TextFormat::colorize($messages[array_rand($messages)], "§"));
    }

    public function executeCommands(Player $player): void
    {
        $name = $player->getName();
        foreach ($this->getConfig()->get("player-commands", []) as $cmd) {
            $this->getServer()->getCommandMap()->dispatch($player, str_ireplace("{player}", $name, $cmd));
        }
        $consoleCommandSender = new ConsoleCommandSender();
        foreach ($this->getConfig()->get("console-commands", []) as $cmd) {
            $this->getServer()->getCommandMap()->dispatch($consoleCommandSender, str_ireplace("{player}", $name, $cmd));
        }
    }

    public function randomSkins(): bool
    {
        return $this->getConfig()->get("random-skins", false);
    }

    public function getMaxCount(): int
    {
        return max(1, $this->getConfig()->get("max-count", 1));
    }

    public function getSlendersInWorld(Level $level): array
    {
        $entities = $level->getEntities();
        return array_filter($entities, function (Entity $entity) {
            return $entity instanceof Slenderman;
        });
    }

    public function canSpawnSlender(Level $level): bool
    {
        return !$level->isClosed() && $this->isSlenderWorld($level) && count($this->getSlendersInWorld($level)) < $this->getMaxCount() && $this->inTimeRange($level->getTime() % Level::TIME_FULL);
    }

    public function spawnSlender(Position $position): void
    {
        if (!$this->canSpawnSlender($position->getLevel())) return;
        try {
            $slenderman = new Slenderman($position->getLevel(), Entity::createBaseNBT($position->getLevel()->getSafeSpawn($position)->asVector3()));
            if ($slenderman instanceof Slenderman) {
                $position->getLevel()->addEntity($slenderman);
                $slenderman->triggerTeleport();
                $slenderman->spawnToAll();
            }
        } catch (\Exception $exception) {
        }
    }
}