<?php

/**
 * NovaRush Bot AI v2 - Metrics
 *
 * Compiles performance metrics for administration and game balancing.
 */
class BotMetrics
{
    /**
     * Get aggregate statistics across all registered bots
     */
    public static function getGlobalMetrics()
    {
        $db = Database::get();

        $activeBots = $db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bots WHERE is_active = 1;", array(), 'cnt');
        $totalDecisions = $db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_decisions;", array(), 'cnt');
        $totalTasks = $db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_tasks;", array(), 'cnt');
        $totalIntel = $db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_intel;", array(), 'cnt');

        return array(
            'active_bots'     => (int)$activeBots,
            'total_decisions' => (int)$totalDecisions,
            'pending_tasks'   => (int)$totalTasks,
            'intel_records'   => (int)$totalIntel,
        );
    }
}
