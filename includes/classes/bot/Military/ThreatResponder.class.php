<?php

require_once 'includes/classes/bot/Military/FleetsaveCalculator.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';

/**
 * NovaRush Bot AI v2 - Threat Responder
 *
 * Assesses incoming hostile threats, simulates battle with Combat Oracle,
 * and executes calibrated tactical responses (Fleetsave, Hold, or Shipyard Defenses).
 */
class BotThreatResponder
{
    private $ctx;
    private $gateway;
    private $fleetsaver;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
        $this->fleetsaver = new BotFleetsaveCalculator($ctx, $gateway);
    }

    /**
     * Respond to all active threats on the ThreatBoard
     *
     * @param BotWorldModel $world
     * @return int Number of tactical evasions / actions executed
     */
    public function respond(BotWorldModel $world)
    {
        global $resource, $reslist;

        $threats = $world->threats->getActiveThreats();
        if (empty($threats)) {
            return 0;
        }

        $actionsExecuted = 0;

        foreach ($threats as $threat) {
            $targetPlanetId = $threat['target_planet'];
            $targetBody = $world->empire->getPlanet($targetPlanetId);
            if (empty($targetBody)) continue;

            $pData = $targetBody['data'];

            // Gather local defense and fleet units
            $defenderUnits = array();
            $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();
            $defList   = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();
            foreach (array_merge($fleetList, $defList) as $unitId) {
                $qty = isset($pData[$resource[$unitId]]) ? (int)$pData[$resource[$unitId]] : 0;
                if ($qty > 0) {
                    $defenderUnits[$unitId] = $qty;
                }
            }

            // Track hostile interaction in Opponent Model
            $world->opponents->recordOpponentAttack($threat['attacker_id']);

            // Estimate battle outcome
            $shouldFleetsave = false;
            $riskScore = 0.0;

            if ($threat['visibility'] === 'none') {
                // Low spy tech (< 4): bot cannot see fleet size, assumes prudent stance based on personality
                if ($this->ctx->personality->lossAversion >= 0.8) {
                    $shouldFleetsave = true;
                    $riskScore = 1.0;
                }
            } elseif ($threat['visibility'] === 'types_only') {
                // Medium spy tech (4..7): total ship count known, composition counts hidden
                $totalDefenderUnits = array_sum($defenderUnits);
                $incomingTotal      = (int)$threat['visible_total'];

                if ($incomingTotal > ($totalDefenderUnits * 0.7) && $this->ctx->personality->lossAversion >= 0.9) {
                    $shouldFleetsave = true;
                    $riskScore = 0.8;
                } else {
                    // Estimate using average unit strength
                    $threatFleet = !empty($threat['raw_fleet']) ? $threat['raw_fleet'] : array();
                    $simResult = BotCombatOracle::evaluate(
                        $threatFleet,
                        $defenderUnits,
                        array('id' => $threat['attacker_id'], 'factor' => array()),
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                        $this->ctx->difficulty->simIterations
                    );
                    $riskScore = isset($simResult['win_probability']) ? (float)$simResult['win_probability'] : 0.0;
                    if ($riskScore >= $this->ctx->difficulty->fleetsaveRiskThreshold) {
                        $shouldFleetsave = true;
                    }
                }
            } else {
                // Full visibility: evaluate using Combat Oracle
                $threatFleet = !empty($threat['raw_fleet']) ? $threat['raw_fleet'] : array();
                
                // Threat is attacker; defender is bot. Attacker win probability is bot's defeat probability!
                $simResult = BotCombatOracle::evaluate(
                    $threatFleet,
                    $defenderUnits,
                    array('id' => $threat['attacker_id'], 'factor' => array()),
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    $this->ctx->difficulty->simIterations
                );

                $riskScore = isset($simResult['win_probability']) ? (float)$simResult['win_probability'] : 0.0;
                if ($riskScore >= $this->ctx->difficulty->fleetsaveRiskThreshold) {
                    $shouldFleetsave = true;
                }

                $this->ctx->decisionLog->record(
                    'threat_evaluated',
                    "Evaluated threat from player #{$threat['attacker_id']} to body #{$targetPlanetId}. Bot defeat prob: {$riskScore}",
                    array('threat' => $threat, 'evaluation' => $simResult),
                    $shouldFleetsave ? 'trigger_fleetsave' : 'hold_position',
                    $riskScore
                );
            }

            if ($shouldFleetsave) {
                $saved = $this->fleetsaver->executeFleetsave(
                    $world->empire,
                    $targetPlanetId,
                    $threat['arrival_time']
                );

                if ($saved) {
                    $actionsExecuted++;
                }
            }
        }

        return $actionsExecuted;
    }
}
