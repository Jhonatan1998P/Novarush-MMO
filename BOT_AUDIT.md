# Auditoría Técnica del Motor y Sistema de Bots de NovaRush (v2)

**Fecha:** 2026-10-05  
**Entorno:** NovaRush (2Moons v1.8.0 Basis / New-Star)  
**Propósito:** Validación exhaustiva previa a la implementación de la IA estratégica v2.

---

## 1. Resolución de Contradicciones del Contexto

### 1.1 Identificadores de Naves (IDs 202, 204, 206, 214)
- **ID 202:** `small_ship_cargo` (Pequeño Carguero). Clase 200, blindaje `type_defend = 'light'`, escudo `type_shield = 'light'`. Capacidad: 5.000.
- **ID 204:** `light_hunter` (Caza Ligero). Clase 200, blindaje `type_defend = 'light'`, escudo `type_shield = 'light'`. Arma: `notype`, ataque 50.
- **ID 206:** `crusher` (Crucero). Clase 200, blindaje `type_defend = 'medium'`, escudo `type_shield = 'medium'`. Arma: `ion,100`.
- **ID 214:** `dearth_star` (Estrella de la Muerte). Clase 200, blindaje `type_defend = 'medium'`, escudo `type_shield = 'heavy'`. Arma: `gravity,100`.
- **Conclusión:** El informe de bots tenía la razón (202 = Carguero Pequeño, 204 = Caza Ligero). La Estrella de la Muerte (214) posee escudo pesado (`heavy`) pero blindaje medio (`medium`).

### 1.2 Nombre de la Función de Requisitos Tecnológicos
- **Función canónica en el motor:** `BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, $ReqArray)` (escrito con `ie`: `isTechnologieAccessible`).
- Ubicación: `includes/classes/class.BuildFunctions.php` línea 170.

---

## 2. Mapa Canónico de Misiones de Flota

Definido en `FlyingFleetHandler::$missionObjPattern` (`includes/classes/class.FlyingFleetHandler.php`):

| ID | Nombre de Clase Handler | Misión en el Juego |
|---|---|---|
| 1 | `MissionCaseAttack` | Ataque estándar |
| 2 | `MissionCaseACS` | Ataque en confederación (ACS) |
| 3 | `MissionCaseTransport` | Transporte de recursos |
| 4 | `MissionCaseStay` | Despliegue a base o colonia propia |
| 5 | `MissionCaseStayAlly` | Mantener posición / Defender aliado en órbita |
| 6 | `MissionCaseSpy` | Espionaje |
| 7 | `MissionCaseColonisation` | Colonización de posición vacía |
| 8 | `MissionCaseRecycling` | Reciclaje de campo de escombros |
| 9 | `MissionCaseDestruction` | Destrucción de luna con Estrella de la Muerte |
| 10 | `MissionCaseMIP` | Ataque con Misiles Interplanetarios |
| 11 | `MissionCaseFoundDM` | Expedición de búsqueda de Materia Oscura |
| 15 | `MissionCaseExpedition` | Expedición científica estándar |
| 18 | `MissionCaseMilitaryExpedition` | Expedición militar (Sectores de la Nebulosa / PvE) |

---

## 3. Semántica de la Tabla `uni1_fleets`

- `fleet_id`: Clave primaria autoincremental de la flota.
- `fleet_owner`: ID del usuario propietario y despachador de la flota.
- `fleet_target_owner`: ID del usuario destino (puede ser 0 si es posición vacía o escombros).
- `fleet_mission`: ID de misión (ver sección 2).
- `fleet_amount`: Cantidad total de naves en la flota (suma de todas las unidades).
- `fleet_array`: Cadena serializada en formato `shipID,shipCount;shipID,shipCount;...`. Decodificada y codificada mediante `FleetFunctions::unserialize()` y `FleetFunctions::serialize()`.
- `fleet_mess`: Estado de la trayectoria de la flota (`includes/constants.php`):
  - `0 = FLEET_OUTWARD`: Vuelo de ida hacia las coordenadas objetivo.
  - `1 = FLEET_RETURN`: Vuelo de regreso a la base de origen.
  - `2 = FLEET_HOLD`: Permanencia en órbita (misiones 5, 15, 18).
- `fleet_start_time`: Timestamp Unix del momento de arribo al objetivo (finaliza el vuelo de ida).
- `fleet_end_stay`: Timestamp Unix en el que concluye la permanencia en el objetivo.
- `fleet_end_time`: Timestamp Unix del retorno final al planeta/luna de origen.

---

## 4. Motor de Combate (`calculateAttack`)

Ubicación: `includes/classes/missions/functions/calculateAttack.php`.

### 4.1 Pureza
- **Comprobación:** La función `calculateAttack(&$attackers, &$defenders, $FleetTF, $DefTF)` es **100% pura**. No realiza ninguna consulta ni modificación en la base de datos MySQL.
- Modifica por referencia los arreglos `$attackers` y `$defenders` en memoria (resta bajas y reconstruye porcentajes de defensa) y retorna un arreglo con los escombros generados, las rondas detalladas y el resultado (`won` = `'a'`, `'r'`, o `'w'`).

### 4.2 Matriz de Armas vs Clases de Blindaje
- Los tipos de armas se dividen en: `laser`, `ion`, `plasma`, `gravity`, `notype`, `none`.
- Los blindajes y escudos se clasifican en: `light`, `medium`, `heavy`.
- Reparto de disparos por arma (`attackShoting` / `defenseShoting`):
  - `laser`: 65-75% hacia blindaje ligero, 15-25% hacia medio, insignificante hacia pesado.
  - `ion`: 55-65% hacia ligero, 65-75% hacia medio, 0-10% hacia pesado.
  - `plasma`: 0-2% hacia ligero, 35-45% hacia medio, 50-60% hacia pesado.
  - `gravity`: 0-5% hacia ligero, 10-20% hacia medio, 65-75% hacia pesado.
  - `notype`: 45-55% ligero, 1-5% medio, 0% pesado.
- **Implicación doctrinal crítica:** Los cazas ligeros (`type_defend = 'light'`) **NO absorben disparos de plasma ni de gravitón**, ya que estos se dirigen en un 95-100% a blindajes medio y pesado. La doctrina clásica de OGame ("40-45% de carne de cañón ligera") deja indefensas a las naves pesadas frente a flotas con cañones de plasma y gravitón.

### 4.3 Regla de Rebote de Escudo
- Línea 425: Si `(escudo_por_unidad) >= daño_recibido`, el daño es absorbido completamente por el escudo y la nave no sufre bajas en esa ronda (`attacker_n = round($amount)`).

### 4.4 Fuego Rápido y Regla de 6 Rondas
- Fuego rápido (`$RF`): Lectura inversa de `uni1_vars_rapidfire`: `$RF[$blanco][$atacante] = $disparos`. Multiplica los disparos adicionales contra el blanco objetivo, mitigable por la tecnología `AntiFocusing`.
- Límite de rondas: `MAX_ATTACK_ROUNDS = 6`. Si al término de la ronda 6 ningún bando ha sido aniquilado, la batalla termina en empate (`won = 'w'`).

---

## 5. Puntos de Entrada de las Acciones del Juego

### 5.1 Enviar Flotas (`FleetFunctions::sendFleet`)
- Firma: `FleetFunctions::sendFleet($fleetArray, $fleetMission, $fleetStartOwner, $fleetStartPlanetID, $fleetStartPlanetGalaxy, $fleetStartPlanetSystem, $fleetStartPlanetPlanet, $fleetStartPlanetType, $fleetTargetOwner, $fleetTargetPlanetID, $fleetTargetPlanetGalaxy, $fleetTargetPlanetSystem, $fleetTargetPlanetPlanet, $fleetTargetPlanetType, $fleetResource, $fleetStartTime, $fleetStayTime, $fleetEndTime, $fleetGroup = 0, $missileTarget = 0, $consumption = 0, $sector = 0)`
- Dependencias globales: No depende de variables de sesión `$USER` ni `$PLANET`. Resta las naves del planeta de origen y descuenta el deuterio consumido mediante SQL directo atómico.

### 5.2 Retirada de Flotas (`FleetFunctions::SendFleetBack`)
- Firma: `FleetFunctions::SendFleetBack($USER, $FleetID)`
- Comprueba pertenencia `$fleetResult['fleet_owner'] == $USER['id']` y estado `$fleetResult['fleet_mess'] != 1`.
- Ajusta el tiempo de retorno exacto en `%%FLEETS%%`, `%%FLEETS_EVENT%%` y `%%LOG_FLEETS%%`.

### 5.3 Encolado de Astillero (`ShowShipyardPage`)
- Valida con `BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array())`.
- Valida disponibilidad máxima con `BuildFunctions::getMaxConstructibleElements($USER, $PLANET, $Element)`.
- Calcula coste con `BuildFunctions::getElementPrice($USER, $PLANET, $Element, false, $Count)`.
- Añade a la cola serializada `$PLANET['b_hangar_id'] = serialize($BuildArray)`.

### 5.4 Encolado de Edificios (`ShowBuildingsPage`)
- Valida campos libres con `CalculateMaxPlanetFields($PLANET)`.
- Valida slots con `$config->max_elements_build + $USER['factor']['BuildSlots']`.
- Valida requisitos con `BuildFunctions::isTechnologieAccessible()`.
- Cobra recursos y almacena en `$PLANET['b_building_id']` y `$PLANET['b_building']`.

### 5.5 Encolado de Investigaciones (`ShowResearchPage`)
- Valida slots con `$config->max_elements_tech + $USER['factor']['ResearchSlots']`.
- Valida laboratorio disponible y no en ampliación (`CheckLabSettingsInQueue`).
- Almacena en `$USER['b_tech_queue']`, `$USER['b_tech_planet']`, `$USER['b_tech']` y `$PLANET['b_tech_id']`.

---

## 6. Visibilidad de Flotas Entrantes (Niebla de Guerra)

Determinado en `FlyingFleetsTable::CreateFleetPopupedFleetLink()` (`includes/classes/class.FlyingFleetsTable.php`):

1. **Nivel de Espionaje < 4:**
   - El defensor **no ve ninguna información de naves**. Solo se le notifica que una flota hostil se aproxima (`cff_no_fleet_data`). No conoce el total de naves ni qué tipos vienen.
2. **Nivel de Espionaje entre 4 y 7 (inclusive):**
   - El defensor ve el **número total de naves** (`fleet_amount`) y los **tipos de naves** presentes en la flota (`$LNG['tech'][$ShipID]`), pero **no ve la cantidad de cada tipo**.
3. **Nivel de Espionaje >= 8 (o presencia de Falange de Sensor en el sistema):**
   - El defensor ve la **composición exacta al 100%**: cada tipo de nave junto a su cantidad precisa (`pretty_number($Ship[1])`).

---

## 7. Bonos, Factores y Tope Suave (Soft Cap)

- La función central es `getFactors($USER, $Type = 'basic', $TIME = NULL)` en `includes/GeneralFunctions.php`.
- **Tope Suave (Soft Cap) Activo:**
  - Se encuentra implementado mediante `PremiumEconomy::softCap($temp[$k], $capMap[$k])`.
  - Recursos (`Resource`): Tope suave en 4.0 (+400%).
  - Combate (`Attack`, `Defensive`, `Shield`): Tope suave en 3.0 (+300%).
  - Velocidades (`Sbuild`, `Stech`, `Sfleet`, `FlyTime`): Tope suave en 3.0 (+300%).
  - Atenuación de raíz cuadrada en Arsenal y Detalles: `sqrt($elementLevel) * $bonus`.
  - Topes duros de reducción de coste (`CostR*`), Escombros (`Debris`) y Recuperación (`DefRecovery`) en 0.30 (30%).

---

## 8. Datos Públicos del Universo

1. **Estadísticas (`%%STATPOINTS%%`):**
   - Tipos de estadística: `stat_type = 1` (jugadores), `stat_type = 2` (alianzas).
   - Métricas accesibles públicamente: `total_points`, `total_rank`, `fleet_points`, `fleet_rank`, `defs_points`, `defs_rank`, `build_points`, `build_rank`, `tech_points`, `tech_rank`.
2. **Vista de Galaxia (`GalaxyRows`):**
   - Propietario del planeta y alianza.
   - Presencia de luna y su diámetro.
   - Campo de escombros actual en la posición (metal y cristal).
   - Indicador de actividad:
     - `< 15 min`: `*` (actividad reciente).
     - `15 .. 59 min`: `(X min)` (tiempo desde la última acción).
     - `>= 60 min`: Sin marcador.
3. **Sensores de Falange:**
   - Alcance del escaneo en galaxia: `(Nivel_Falange ^ 2) - 1` sistemas solares.

---

## 9. Hallazgos Críticos de Ejecución y Paridad de Reglas

1. **Retorno de `FleetFunctions::sendFleet`:**
   - Originalmente la función no contenía una sentencia `return $fleetId;` al final del método (retornaba `NULL`), impidiendo a los emisores programar tareas de recall o registrar el ID de la flota creada. Se corrigió añadiendo `return $fleetId;`.
2. **Deducción de Recursos Transportados vs Combustible:**
   - `FleetFunctions::sendFleet` únicamente descuenta las naves y el deuterio consumido como combustible (`consumption`) en `%%PLANETS%%`. No deduce el metal, cristal ni deuterio de carga (`fleetResource`). Por ende, `ActionGateway` debe descontar explícitamente los recursos de carga del planeta emisor antes o después de invocar `sendFleet` para evitar la duplicación de recursos desde el vacío.
3. **Capacidad de Carga y Límites de Slots:**
   - Un jugador real está restringido por `FleetFunctions::GetMaxFleetSlots($USER)` frente a `FleetFunctions::GetCurrentFleets($userId)` y por `FleetFunctions::GetFleetRoom($fleetArray)`. `ActionGateway` valida y respeta ambas restricciones antes del despegue.
4. **Ciclo de Investigación en `%%USERS%%`:**
   - La resolución de investigaciones en `ResourceUpdate` requiere que `b_tech_id` en `%%USERS%%` almacene el ID del elemento tecnológico en curso (mientras que `b_tech_id` en `%%PLANETS%%` almacena el timestamp de finalización). La omisión de este campo en `%%USERS%%` causaba que las investigaciones nunca subieran de nivel al expirar el tiempo.
5. **Captura Orgánica de Informes de Espionaje:**
   - Para que el almacén de inteligencia (`uni1_bot_intel`) opere bajo estricta niebla de guerra sin hacer trampas en la base de datos, `MissionCaseSpy` incluye un gancho de notificación que persiste los datos efectivamente escaneados si el emisor de la misión es un bot registrado y activo.
