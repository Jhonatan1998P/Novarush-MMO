<?php

/*
 * NovaRush - War Event Boss Engine
 * Cronjob de Activación, Monitoreo y Expiración del Evento de Guerra
 */

require_once 'includes/classes/cronjob/CronjobTask.interface.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';

class WarEventCronjob implements CronjobTask
{
    function run()
    {
        // 1. Activar eventos programados cuya cuenta regresiva haya llegado a cero
        WarEventBossEngine::checkScheduledActivation();

        // 2. Expirar eventos activos cuyo tiempo límite haya finalizado
        $active = WarEventBossEngine::getActiveEvent();
        if (!empty($active)) {
            if ($active['end_time'] > 0 && TIMESTAMP >= $active['end_time']) {
                WarEventBossEngine::expireEvent($active['id']);
            }
        }
    }
}
