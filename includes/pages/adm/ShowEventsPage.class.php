<?php

/*
 * NovaRush - Admin Panel: Gestión de Eventos de Guerra
 */

require_once 'includes/classes/events/WarEventBossEngine.class.php';

class ShowEventsPage extends AbstractAdminPage
{
    public static $requireModule = 0;

    function __construct() 
    {
        if (!allowedTo(str_replace(array(dirname(__FILE__), '\\', '/', '.php'), '', __FILE__))) {
            throw new Exception("Permission error!");
        }
        parent::__construct();
    }

    function show()
    {
        global $LNG;

        $action = HTTP::_GP('action', '');

        if ($action == 'start') {
            try {
                $budgetRatio = HTTP::_GP('budget_ratio', 0.60);
                $durationHours = HTTP::_GP('duration_hours', 24);
                $preCountdown = HTTP::_GP('pre_countdown_minutes', 15);
                $containers = HTTP::_GP('containers_per_winner', 50);
                $antimatter = HTTP::_GP('antimatter_per_winner', 100);
                $minSystem = HTTP::_GP('min_system', 1);
                $maxSystem = HTTP::_GP('max_system', 200);

                $serverBudget = WarEventBossEngine::calculateServerBudget();
                $defaultLootTotal = round($serverBudget * 0.40);
                $defaultLootMetal = round($defaultLootTotal * (4 / 7));
                $defaultLootCrystal = round($defaultLootTotal * (2 / 7));
                $defaultLootDeuterium = round($defaultLootTotal * (1 / 7));

                $lootMetal = HTTP::_GP('loot_metal', (float)$defaultLootMetal);
                $lootCrystal = HTTP::_GP('loot_crystal', (float)$defaultLootCrystal);
                $lootDeuterium = HTTP::_GP('loot_deuterium', (float)$defaultLootDeuterium);

                $result = WarEventBossEngine::startEvent(array(
                    'budget_ratio'          => $budgetRatio,
                    'duration_hours'        => $durationHours,
                    'pre_countdown_minutes' => $preCountdown,
                    'containers_per_winner' => $containers,
                    'antimatter_per_winner' => $antimatter,
                    'min_system'            => $minSystem,
                    'max_system'            => $maxSystem,
                    'loot_metal'            => $lootMetal,
                    'loot_crystal'          => $lootCrystal,
                    'loot_deuterium'        => $lootDeuterium
                ));

                if (!empty($result['scheduled'])) {
                    $this->printMessage("¡Evento de Guerra programado con éxito! Iniciará en {$result['pre_countdown_minutes']} minutos. El aviso y temporizador ya son visibles para todos los comandantes en la pantalla principal (game.php).", true, array('?page=events', 4));
                } else {
                    $this->printMessage("¡Evento de Guerra iniciado con éxito! La Fortaleza Ancestral ha aparecido en [{$result['coords']}]. Duración: {$result['duration']} horas.", true, array('?page=events', 3));
                }
            } catch (Exception $e) {
                $this->printMessage("Error al iniciar el evento: " . $e->getMessage(), true, array('?page=events', 4));
            }
            return;
        }

        if ($action == 'cancel_scheduled') {
            try {
                WarEventBossEngine::cancelScheduledEvent();
                $this->printMessage("El evento programado ha sido cancelado exitosamente.", true, array('?page=events', 3));
            } catch (Exception $e) {
                $this->printMessage("Error al cancelar el evento: " . $e->getMessage(), true, array('?page=events', 4));
            }
            return;
        }

        if ($action == 'stop') {
            try {
                WarEventBossEngine::expireEvent();
                $this->printMessage("El evento de guerra activo ha sido cancelado y la fortaleza ha sido retirada.", true, array('?page=events', 3));
            } catch (Exception $e) {
                $this->printMessage("Error al detener el evento: " . $e->getMessage(), true, array('?page=events', 4));
            }
            return;
        }

        // Gather real-time universe metrics
        $serverBudget = WarEventBossEngine::calculateServerBudget();
        $status = WarEventBossEngine::getEventStatus();

        // Calculate preview for selected or default ratio (0.60)
        $selectedRatio = HTTP::_GP('preview_ratio', 0.60);
        $previewBudget = round($serverBudget * $selectedRatio);
        $previewUnits = WarEventBossEngine::computeUnitsForBudget($previewBudget);
        $unitRatios = WarEventBossEngine::getUnitRatios();

        $previewList = array();
        foreach ($unitRatios as $id => $data) {
            $previewList[$id] = array(
                'name'     => $data['name'],
                'cost'     => $data['cost'],
                'ratio'    => ($data['ratio'] * 100) . '%',
                'amount'   => isset($previewUnits[$id]) ? $previewUnits[$id] : 0
            );
        }

        $defaultLootTotal = round($serverBudget * 0.40);
        $defaultLootMetal = round($defaultLootTotal * (4 / 7));
        $defaultLootCrystal = round($defaultLootTotal * (2 / 7));
        $defaultLootDeuterium = round($defaultLootTotal * (1 / 7));

        $this->assign(array(
            'server_budget'              => $serverBudget,
            'server_budget_fmt'          => pretty_number($serverBudget),
            'status'                     => $status,
            'preview_ratio'              => $selectedRatio,
            'preview_budget_fmt'         => pretty_number($previewBudget),
            'preview_list'               => $previewList,
            'default_loot_total'         => $defaultLootTotal,
            'default_loot_total_fmt'     => pretty_number($defaultLootTotal),
            'default_loot_metal'         => $defaultLootMetal,
            'default_loot_crystal'       => $defaultLootCrystal,
            'default_loot_deuterium'     => $defaultLootDeuterium,
            'default_loot_metal_fmt'     => pretty_number($defaultLootMetal),
            'default_loot_crystal_fmt'   => pretty_number($defaultLootCrystal),
            'default_loot_deuterium_fmt' => pretty_number($defaultLootDeuterium),
        ));

        $this->display('page.events.default.tpl');
    }
}
