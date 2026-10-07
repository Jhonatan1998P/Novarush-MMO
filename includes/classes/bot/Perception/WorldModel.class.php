<?php

require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Perception/IntelStore.class.php';
require_once 'includes/classes/bot/Perception/ThreatBoard.class.php';
require_once 'includes/classes/bot/Perception/OpponentModel.class.php';
require_once 'includes/classes/bot/Perception/PublicDataReader.class.php';

/**
 * NovaRush Bot AI v2 - World Model
 *
 * Primary perception facade uniting empire status, espionage intel,
 * incoming threats, opponent behavioral models, and public galaxy observation.
 */
class BotWorldModel
{
    public $empire;
    public $intel;
    public $threats;
    public $opponents;
    public $publicData;

    public function __construct(BotContext $ctx)
    {
        $spyTechLvl = isset($ctx->user['spy_tech']) ? (int)$ctx->user['spy_tech'] : 0;

        $this->empire     = new BotEmpireState($ctx->botId, $ctx->user);
        $this->intel      = new BotIntelStore($ctx->botId);
        $this->threats    = new BotThreatBoard($ctx->botId, $spyTechLvl);
        $this->opponents  = new BotOpponentModel($ctx->botId);
        $this->publicData = new BotPublicDataReader();
    }
}
