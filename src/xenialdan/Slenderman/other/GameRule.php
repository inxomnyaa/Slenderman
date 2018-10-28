<?php

namespace xenialdan\Slenderman\other;

class GameRule
{

    const TYPE_BOOL = 1;
    const TYPE_INT = 2;
    const TYPE_FLOAT = 3;

    //TODO "cheats enabled"
    const COMMANDBLOCKOUTPUT = "commandblockoutput";
    const DODAYLIGHTCYCLE = "dodaylightcycle";
    const DOENTITYDROPS = "doentitydrops";
    const DOFIRETICK = "dofiretick";
    const DOMOBLOOT = "domobloot";
    const DOMOBSPAWNING = "domobspawning";
    const DOTILEDROPS = "dotiledrops";
    const DOWEATHERCYCLE = "doweathercycle";
    const DROWNINGDAMAGE = "drowningdamage";
    const FALLDAMAGE = "falldamage";
    const FIREDAMAGE = "firedamage";
    const KEEPINVENTORY = "keepinventory";
    const MOBGRIEFING = "mobgriefing";
    const NATURALREGENERATION = "naturalregeneration";
    const PVP = "pvp";
    const SENDCOMMANDFEEDBACK = "sendcommandfeedback";
    const SHOWCOORDINATES = "showcoordinates";
    const TNTEXPLODES = "tntexplodes";

    /**
     * GameRule constructor.
     * @param string $name
     * @param int $type
     * @param mixed $value
     */
    public function __construct(string $name, int $type = self::TYPE_BOOL, $value)
    {
        $this->$name = [$type, $value];
    }
}