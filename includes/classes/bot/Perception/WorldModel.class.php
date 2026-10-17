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
    public $ctx;
    public $empire;
    public $intel;
    public $threats;
    public $opponents;
    public $publicData;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
        $spyTechLvl = isset($ctx->user['spy_tech']) ? (int)$ctx->user['spy_tech'] : 0;

        $this->empire     = new BotEmpireState($ctx->botId, $ctx->user);
        $this->intel      = new BotIntelStore($ctx->botId);
        $this->threats    = new BotThreatBoard($ctx->botId, $spyTechLvl);
        $this->opponents  = new BotOpponentModel($ctx->botId);
        $this->publicData = new BotPublicDataReader();
    }

    public function refresh()
    {
        try {
            $db = Database::get();
            $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $this->ctx->botId));
            if (!empty($uRow)) {
                $uRow['factor'] = getFactors($uRow, 'basic');
                $this->ctx->user = $uRow;
            }
        } catch (Exception $e) {
            // DB not available or fallback
        }

        $spyTechLvl = isset($this->ctx->user['spy_tech']) ? (int)$this->ctx->user['spy_tech'] : 0;
        $this->empire     = new BotEmpireState($this->ctx->botId, $this->ctx->user);
        $this->intel      = new BotIntelStore($this->ctx->botId);
        $this->threats    = new BotThreatBoard($this->ctx->botId, $spyTechLvl);
        $this->opponents  = new BotOpponentModel($this->ctx->botId);
        $this->publicData = new BotPublicDataReader();
    }
}
