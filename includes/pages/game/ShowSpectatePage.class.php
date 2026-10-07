<?php

/**
 * NovaRush - ShowSpectatePage
 *
 * Permite a Administradores (authlevel > 0) y a la cuenta -GODWAR- (User ID 4)
 * espectear en primera persona cuentas de bots, dummies o jugadores desde el Ranking,
 * con cambio de sesión instantáneo y retorno seguro a su cuenta original.
 */
class ShowSpectatePage extends AbstractGamePage
{
    public static $requireModule = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function show()
    {
        global $USER;

        $target = HTTP::_GP('target', '');
        $session = Session::load();
        $db = Database::get();

        // Determinar identidad original si ya se está en modo espectador
        $origUserId = !empty($_SESSION['spectator_admin_id']) ? (int)$_SESSION['spectator_admin_id'] : (int)$USER['id'];
        $origUser = $db->selectSingle("SELECT id, username, authlevel, id_planet FROM %%USERS%% WHERE id = :id LIMIT 1;", array(
            ':id' => $origUserId
        ));

        // Verificación de autorización estricta: Solo Admin o -GODWAR-
        $isAuthorized = (
            (!empty($origUser) && ((int)$origUser['authlevel'] > 0 || strtolower($origUser['username']) === '-godwar-' || (int)$origUser['id'] === 4))
            || (int)$USER['authlevel'] > 0
            || strtolower($USER['username']) === '-godwar-'
            || (int)$USER['id'] === 4
            || !empty($session->adminAccess)
        );

        if (!$isAuthorized) {
            ShowErrorPage::printError("Acceso no autorizado al Modo Espectador de NovaRush.");
        }

        $target = strtolower(trim((string)$target));
        $targetUserId = 0;

        if ($target === 'godwar' || $target === 'return' || $target === 'exit' || $target === (string)$origUserId) {
            $targetUserId = $origUserId;
        } elseif ($target === 'laparca') {
            $targetUserId = 1007;
        } elseif ($target === 'dummy' || $target === 'sector_dummy') {
            $targetUserId = 998;
        } elseif ($target === 'omega' || $target === 'sector_omega') {
            $targetUserId = 999;
        } elseif (is_numeric($target) && (int)$target > 0) {
            $targetUserId = (int)$target;
        }

        if ($targetUserId <= 0) {
            $targetUserId = $origUserId;
        }

        $targetUser = $db->selectSingle("SELECT id, username, id_planet, universe FROM %%USERS%% WHERE id = :id LIMIT 1;", array(
            ':id' => $targetUserId
        ));

        if (empty($targetUser)) {
            ShowErrorPage::printError("La cuenta objetivo solicitada (ID {$targetUserId}) no existe en la base de datos.");
        }

        if ($targetUserId === $origUserId) {
            // Retorno a la cuenta original (-GODWAR- o Admin)
            unset($_SESSION['spectator_admin_id']);
            unset($_SESSION['spectator_target_id']);
            $session->userId = $origUserId;
            $session->planetId = (int)$origUser['id_planet'];
            $session->save();
            HTTP::redirectTo('game.php?page=overview');
        } else {
            // Cambio a la cuenta objetivo para espectear en primera persona
            $_SESSION['spectator_admin_id'] = $origUserId;
            $_SESSION['spectator_target_id'] = $targetUserId;
            $session->userId = $targetUserId;
            $session->planetId = (int)$targetUser['id_planet'];
            $session->save();
            HTTP::redirectTo('game.php?page=overview');
        }
    }
}
