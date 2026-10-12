<?php

/**
 * NovaRush - Generador y Gestor de Nombres Diversos
 *
 * Provee nombres alusivos a jugadores reales para bots (mínimo 100 únicos),
 * y nombres para planetas y lunas de entre 8 y 12 caracteres respetando los límites
 * de caracteres y longitud del juego y base de datos (VARCHAR 20).
 */

class BotNameHelper
{
    private static $cachedPlayerNames = null;
    private static $cachedPlanetNames = null;
    private static $cachedMoonNames   = null;

    /**
     * Obtiene el listado completo de nombres de jugadores reales
     */
    public static function getPlayerNames()
    {
        if (self::$cachedPlayerNames !== null) {
            return self::$cachedPlayerNames;
        }

        $path = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'botnames.txt';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $names = array_values(array_unique(array_filter(array_map('trim', $lines))));
            if (count($names) >= 50) {
                self::$cachedPlayerNames = $names;
                return self::$cachedPlayerNames;
            }
        }

        // Respaldo interno en caso de ausencia de archivo
        self::$cachedPlayerNames = array(
            'AegisKnight', 'Aftershock', 'AlphaWolf', 'Annihilator', 'Apocalypse', 'AstroViper',
            'Backstab', 'BattleHawk', 'Berserker', 'BlackLotus', 'BlackMamba', 'BladeRunner',
            'Blitzkrieg', 'BloodRaven', 'Bullseye', 'Cataclysm', 'Centurion', 'ChaosReign',
            'Chronos', 'ColdFusion', 'Corsair', 'Crossfire', 'Crusader', 'CyberDemon',
            'CyberKnight', 'DarkAvenger', 'DarkHorizon', 'DarkMatter', 'DarkNebula', 'Deadeye',
            'DeathWing', 'Disruptor', 'Dominator', 'DoomBringer', 'Dreadnought', 'Drifter',
            'EchoStrike', 'Eclipse', 'Equinox', 'EventHorizon', 'Excalibur', 'FalconEye',
            'FireStorm', 'FrostBite', 'GhostRider', 'GhostSniper', 'Gladiator', 'GraveDigger',
            'GrimReaper', 'Gunslinger', 'Havoc', 'HellFire', 'HyperDrive', 'Hyperion',
            'Inferno', 'Infiltrator', 'Inquisitor', 'IronClad', 'IronFist', 'IronRaven',
            'IronWill', 'Kamikaze', 'Leonidas', 'LordVader', 'Marauder', 'Marksman',
            'MasterChief', 'Megatron', 'MindFlayer', 'Monsoon', 'Nemesis', 'NeonRider',
            'NightHawk', 'NightStalker', 'NovaBlade', 'Oblivion', 'Odinsson', 'OmegaLeader',
            'OrbitMaster', 'Outlaw', 'Overkill', 'Overlord', 'Paladin', 'PhantomX',
            'PhoenixDown', 'PulseFire', 'QuantumLeap', 'Ragnarok', 'Ravage', 'Reckless',
            'RedBaron', 'Requiem', 'RoguePilot', 'Scorpion', 'Sentinel', 'ShadowFang',
            'ShadowHunter', 'ShadowReaper', 'Sharpshooter', 'SilentReaper', 'SilverHawk', 'Singularity',
            'SkyHunter', 'SkyReaper', 'SolarEclipse', 'SolarFlare', 'SolarKnight', 'Spartacus',
            'SpecterX', 'StarBreaker', 'StarLord', 'SteelStorm', 'StormBringer', 'Subzero',
            'Supernova', 'Terminator', 'Thanatos', 'ThunderBolt', 'TitanSlayer', 'Torpedo',
            'Typhoon', 'Vagabond', 'Valkyrie', 'Vanguard', 'VectorPrime', 'Venom',
            'VoidStriker', 'VoidWalker', 'VortexRider', 'WarHammer', 'WarMachine', 'Warlock',
            'Wormhole', 'ZenithAce', 'ZeroCool', 'ZeroGravity'
        );

        return self::$cachedPlayerNames;
    }

    /**
     * Obtiene el listado completo de nombres de planetas (8 a 12 caracteres estrictos)
     */
    public static function getPlanetNames()
    {
        if (self::$cachedPlanetNames !== null) {
            return self::$cachedPlanetNames;
        }

        $path = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'planetnames.txt';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $names = array();
            foreach ($lines as $line) {
                $trimmed = trim($line);
                $len = strlen($trimmed);
                if ($len >= 8 && $len <= 12) {
                    $names[] = $trimmed;
                }
            }
            if (count($names) >= 30) {
                self::$cachedPlanetNames = array_values(array_unique($names));
                return self::$cachedPlanetNames;
            }
        }

        self::$cachedPlanetNames = array(
            'Aldebaran', 'Alderaan-I', 'Andromeda', 'Antares-01', 'Apus-Prime', 'Aquarius-I',
            'Ara-Sanctum', 'Aries-Prime', 'Asgard-One', 'Astra-Nova', 'Aethelgard', 'Babylon-05',
            'Bespin-Sec', 'Betelgeuse', 'Bootes-Void', 'Caelum-Deep', 'Canis-Major', 'Canis-Minor',
            'Canopus-01', 'Capella-One', 'Capricorn-I', 'Cassiopeia', 'Castor-Node', 'Centaurus-I',
            'Chamaeleon', 'Circinus-01', 'Columba-One', 'Corona-Aust', 'Corona-Bore', 'Coruscant',
            'Crux-Austral', 'Cygnus-Core', 'Dagobah-Out', 'Dathomir-I', 'Deep-Abyss', 'Deneb-Prime',
            'Dorado-Core', 'Draco-Major', 'Elysium-01', 'Endor-Colo', 'Epsilon-B', 'Fornax-Deep',
            'Frost-Haven', 'Geonosis-X', 'Gliese-581', 'Gliese-667', 'Gliese-832', 'Gorgon-Base',
            'Grus-Colony', 'Haumea-Base', 'Helios-Prim', 'Horologium', 'Hoth-Secund', 'Hydra-Seven',
            'Hydrus-Deep', 'Hyperion-V', 'Indus-Prime', 'Iron-Forge', 'Kamino-Deep', 'Kapteyn-B',
            'Kashyyyk-I', 'Kepler-186', 'Kepler-22B', 'Kepler-452', 'Kepler-62F', 'Kronos-VII',
            'Lacerta-One', 'Leo-Major-I', 'Lepus-Minor', 'Libra-Prime', 'Lupus-Prime', 'Luyten-01',
            'Makemake-I', 'Mensa-Base', 'Monoceros-I', 'Musca-Alpha', 'Mustafar-IX', 'Naboo-Prime',
            'Neo-Avalon', 'New-Genesis', 'Nirvana-One', 'Norma-Core', 'Nova-Terra', 'Olympus-01',
            'Ophiuchus-I', 'Orion-Alpha', 'Pandora-Sec', 'Pavo-Major', 'Pegasus-One', 'Phoenix-One',
            'Pictor-Beta', 'Piscis-Aust', 'Polaris-Out', 'Pollux-Node', 'Procyon-One', 'Proxima-B',
            'Proxima-C', 'Regulus-Sec', 'Reticulum-I', 'Rigel-Prime', 'Ross-128B', 'Sagittarius',
            'Scorpius-I', 'Scutum-Core', 'Sedna-Outp', 'Serpens-Cap', 'Shadow-Keep', 'Sirius-Beta',
            'Solaris-IX', 'Solis-Major', 'Spica-Prime', 'Storm-Peak', 'Tatooine-I', 'Tau-Ceti-E',
            'Teegarden', 'Telescopium', 'Terra-Nova', 'Titan-Prime', 'Trappist-1', 'Triangulum',
            'Tucana-Core', 'Ursa-Major', 'Ursa-Minor', 'Valhalla-I', 'Vanguard-X', 'Vega-Secund',
            'Virgo-Alpha', 'Void-Reach', 'Volans-Nova', 'Vulpecula-I', 'Wolf-1061', 'Zephyrus-3'
        );

        return self::$cachedPlanetNames;
    }

    /**
     * Obtiene el listado completo de nombres de lunas (8 a 12 caracteres estrictos)
     */
    public static function getMoonNames()
    {
        if (self::$cachedMoonNames !== null) {
            return self::$cachedMoonNames;
        }

        $path = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'moonnames.txt';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $names = array();
            foreach ($lines as $line) {
                $trimmed = trim($line);
                $len = strlen($trimmed);
                if ($len >= 8 && $len <= 12) {
                    $names[] = $trimmed;
                }
            }
            if (count($names) >= 30) {
                self::$cachedMoonNames = array_values(array_unique($names));
                return self::$cachedMoonNames;
            }
        }

        self::$cachedMoonNames = array(
            'Actaea-Base', 'Aegaeon-Orb', 'Aegis-Orbit', 'Anthe-Orbit', 'Apex-Orbit', 'Ariel-Orbit',
            'Atlas-Orbit', 'Bastion-Orb', 'Belinda-Orb', 'Bianca-Base', 'Borasisi-O', 'Caliban-Orb',
            'Callisto-I', 'Calypso-Orb', 'Ceto-Phorc', 'Charon-Base', 'Cordelia-O', 'Corona-Node',
            'Cressida-O', 'Daphnis-Orb', 'Dark-Orbit', 'Deimos-Orb', 'Desdemona-O', 'Despina-Orb',
            'Dione-Orbit', 'Dysnomia-O', 'Eclipse-Orb', 'Enceladus-I', 'Epimetheus', 'Equinox-Orb',
            'Europa-Orb', 'Ferdinand-O', 'Francisco-O', 'Frost-Orbit', 'Galatea-Orb', 'Ganymede-I',
            'Gonggong-O', 'Helene-Base', 'Hiiaka-Base', 'Hydra-Orbit', 'Hyperion-O', 'Iapetus-Orb',
            'Ilmare-Base', 'Io-Station', 'Ixion-Orbit', 'Janus-Orbit', 'Juliet-Base', 'Kerberos-O',
            'Larissa-Orb', 'Luna-Austra', 'Luna-Boreal', 'Luna-Centra', 'Luna-Mayor', 'Luna-Menor',
            'Luna-Occid', 'Luna-Orient', 'Luna-Secund', 'Manwe-Orbit', 'Margaret-O', 'Methone-Orb',
            'Mimas-Orbit', 'Miranda-Orb', 'Nadir-Orbit', 'Naiad-Orbit', 'Namaka-Base', 'Nereid-Base',
            'Nix-Orbiter', 'Node-Alpha', 'Node-Bravo', 'Node-Delta', 'Oberon-Base', 'Ophelia-Orb',
            'Orbital-One', 'Orbital-Six', 'Orbital-Two', 'Orcus-Orbit', 'Pallene-Orb', 'Pan-Station',
            'Pandora-Orb', 'Perdita-Orb', 'Phoebe-Base', 'Phobos-Orb', 'Pinnacle-O', 'Polydeuces',
            'Portia-Base', 'Prometh-Orb', 'Prospero-O', 'Proteus-Orb', 'Puck-Orbit', 'Quaoar-Base',
            'Rhea-Orbiter', 'Rosalind-O', 'Salacia-Orb', 'Sedna-Orbit', 'Sentinel-O', 'Setebos-Orb',
            'Sila-Nunam', 'Silver-Orb', 'Solstice-O', 'Spectral-O', 'Stellar-Orb', 'Stephano-O',
            'Styx-Outpost', 'Sycorax-Orb', 'Teharon-Orb', 'Telesto-Orb', 'Tethys-Base', 'Thalassa-O',
            'Titan-Orbit', 'Titania-Orb', 'Trinculo-O', 'Triton-Base', 'Umbriel-Orb', 'Vanguard-O',
            'Vanth-Orbit', 'Varda-Orbit', 'Varuna-Base', 'Weywot-Base', 'Xiangliu-O', 'Zenith-Orb',
            'Zoe-Station'
        );

        return self::$cachedMoonNames;
    }

    /**
     * Selecciona un nombre de jugador alusivo a jugador real, no repetido en la base de datos
     */
    public static function getRandomPlayerName()
    {
        $pool = self::getPlayerNames();
        shuffle($pool);

        try {
            $db = Database::get();
            foreach ($pool as $candidate) {
                $exists = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :name;", array(':name' => $candidate));
                if (empty($exists)) {
                    return $candidate;
                }
            }
        } catch (Throwable $e) {
            // Si no hay conexión o error, devolver el primer candidato
            return $pool[0];
        }

        // Si todos estuvieran tomados, generar una variante elegante respetando estilo gamer
        return 'Commander_' . mt_rand(10, 99);
    }

    /**
     * Selecciona un nombre aleatorio de planeta respetando el rango estricto de 8 a 12 caracteres
     * @param array $exclude Lista de nombres a evitar (para no repetir en el mismo imperio)
     */
    public static function getRandomPlanetName(array $exclude = array())
    {
        $pool = self::getPlanetNames();
        if (!empty($exclude)) {
            $pool = array_diff($pool, $exclude);
            if (empty($pool)) {
                $pool = self::getPlanetNames();
            }
        }

        $chosen = $pool[array_rand($pool)];
        return $chosen;
    }

    /**
     * Selecciona un nombre aleatorio de luna respetando el rango estricto de 8 a 12 caracteres
     * @param array $exclude Lista de nombres a evitar
     */
    public static function getRandomMoonName(array $exclude = array())
    {
        $pool = self::getMoonNames();
        if (!empty($exclude)) {
            $pool = array_diff($pool, $exclude);
            if (empty($pool)) {
                $pool = self::getMoonNames();
            }
        }

        $chosen = $pool[array_rand($pool)];
        return $chosen;
    }

    /**
     * Valida si un nombre cumple con la regla de longitud de 8 a 12 caracteres y límites de BD
     */
    public static function isLengthValid($name)
    {
        $len = strlen(trim($name));
        return ($len >= 8 && $len <= 12);
    }
}
