<?php

declare(strict_types=1);

namespace xenialdan\Slenderman\other;

use pocketmine\scheduler\Task;
use xenialdan\Slenderman\Loader;

class SpawnTask extends Task
{
    public function onRun(int $currentTick)
    {
        foreach (Loader::getInstance()->getServer()->getLevels() as $level) {
            if ($level->isClosed() || !Loader::getInstance()->isSlenderWorld($level) || empty($players = $level->getPlayers())) continue;
            for ($i = 0; $i < Loader::getInstance()->getMaxCount(); $i++) {
                $player = $players[array_rand($players)];
                Loader::getInstance()->spawnSlender($player->asPosition());
            }
        }
    }
}