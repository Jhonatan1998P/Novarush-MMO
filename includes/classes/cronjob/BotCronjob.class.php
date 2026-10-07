<?php

/*
 * NovaRush - Motor de Inteligencia Artificial para Bots
 * Cronjob de Ejecución Periódica de Bots
 */

require_once 'includes/classes/cronjob/CronjobTask.interface.php';
require_once 'includes/classes/bot/BotEngine.class.php';

class BotCronjob implements CronjobTask
{
    function run()
    {
        BotEngine::runAllBots();
    }
}
