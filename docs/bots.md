# Documentación de Arquitectura e Inteligencia Artificial: NovaRush Bots (v4.1.1)

## 1. Visión General de la Versión 4.1 y 4.1.1

La versión **4.1.1** de la IA de NovaRush consolida la reingeniería integral sobre el sistema presupuestario, la priorización de infraestructura económica y la física temporal de producción militar. Esta versión corrige defectos sutiles en la whitelist de campos coloniales, restituye la fórmula canónica de hangar sub-segundo sin multiplicadores artificiales e introduce la reubicación de créditos desplazados para blindar los presupuestos de flota y defensa ante desembolsos de investigación global.

---

## 2. Cambios Arquitectónicos por Módulo

### 2.1 BotBudgetManager (`includes/classes/bot/Forces/BotBudgetManager.class.php`)
- **Trueque Simétrico MSE-Neutro en `rebalanceStagnantBuckets()`:**
  - Sustitución de la confiscación arbitraria del 65% por un algoritmo de intercambio simétrico a ratio 4:2:1 (Metal : Cristal : Deuterio).
  - Conservación exacta de valor: el excedente se transforma entre cubos respetando estrictamente $\Delta \text{MSE} = 0.0$.
- **Reconciliación Física e Invariante de Stock (`reconcilePhysicalInvariant` y `reconcilePlanetResource`):**
  - Eliminación total del antiguo "Caso B", el cual reinyectaba inflación fantasma en el sistema contable virtual al detectar stock físico superior.
  - Implementación de inicialización limpia basada en perfiles macroscópicos del bot y verificación forzada de cota superior: los créditos virtuales nunca pueden superar el stock físico disponible en el planeta o luna ($\sum V_i \le P_i$).
- **Reubicación de Créditos Desplazados (v4.1.1):**
  - Cuando una investigación global consume físicamente recursos concentrados en el planeta Laboratorio central, cualquier reducción forzada a los cubos locales de dicho planeta (`mines`, `fleet`, `defense`) se redistribuye automáticamente hacia las colonias con respaldo físico libre (`curPhys - sumVirt > 0`). Esto garantiza que el gasto científico jamás destruya ahorros de flota o defensa a nivel imperial.
  - Reconciliación del techo virtual de `research` acotada contra la capacidad global de almacenes (`totalEmpireCap * r_research`) y el respaldo físico disponible en todo el imperio.
- **Blindaje Estricto de Swaps Internos (`tryInternalBucketSwap`):**
  - Supresión de bypasses de investigación gratuita.
  - Eliminación de la confiscación forzada del Paso C y absorción unilateral del Paso A.
  - Protección de piso de combustible para naves militares: salvaguarda de reserva táctica de deuterio para flotas de guerra.

### 2.2 ProductionAllocator (`includes/classes/bot/Forces/ProductionAllocator.class.php`)
- **Filtro de Desbloqueo por Madurez Económica (`computePrerequisiteUnlockBoosts`):**
  - Se omiten objetivos de investigación o naves de alto tier si el coste de sus prerequisitos excede en más de 4.0 veces la producción horaria de todo el imperio (`hourlyEmpireMSE * 4.0`), protegiendo a colonias tempranas del sobrecoste de infraestructura.
- **Facturación Coherente de Satélites de Gravitón:**
  - Los satélites solares destinados a desbloquear Tecnología de Gravitón se cargan al presupuesto de minas (`mines`), evitando desfalcos al presupuesto militar de flota (`fleet`).
- **Curva de Payback y Armonización Intra-Colonial de Minas:**
  - Escalamiento del score de retorno de inversión de minas multiplicando por `120000.0`.
  - Regla áurea intra-planetaria de minas: $L_{\text{Metal}} : L_{\text{Metal}} - 2 : L_{\text{Metal}} - 4$ (para Cristal y Deuterio respectivamente).
  - Techos relativos estrictos para infraestructuras auxiliares:
    - Astillero (#21) limitado dinámicamente según nivel de mina de metal ($L_M < 10 \to 2/4$, $L_M < 16 \to 6$, $L_M < 20 \to 8$, $L_M < 26 \to 10$).
    - Módulos militares y defensivos (#82, #83) supeditados a minas $\ge 20$ y coste acotado.
    - Silo de misiles (#44) condicionado por personalidad y nivel de minas.
    - Aceleradores (Fábrica de Robots #14, Nanobots #15, Laboratorio #31, Universidad #6, Módulo de Investigación #84) limitados estrictamente por coste respecto a minas y pertenencia a la Red Intergaláctica de Investigación (IRN).
    - Almacenes (#22, #23, #24) restringidos a umbral de saturación $\ge 90\%$.
- **Preservación de Minas en Colonias con Pocos Campos (v4.1.1):**
  - En la whitelist de `$freeFields <= 18`, se incluyen obligatoriamente las Minas (`1, 2, 3`) y Plantas de Energía (`4, 12`).
  - Orden estricto de resolución: `$freeFields <= 0` (bloqueo circular) $\to$ `$freeFields <= 2` (garantía militar de hangar/robots) $\to$ `$freeFields <= 18`. Esto impide el bloqueo accidental de minas entre 3 y 18 campos libres.
  - Erradicación de cálculos redundantes de `$mineAverages` en `allocate()`.
- **Racionalización de Investigación:**
  - Supresión de bucles de mercader en colonias secundarias.
  - Poda de ramas tecnológicas redundantes (Láser > 12, Iónica > 5, Hiperespacio > 8).
  - Cota máxima de coste por tecnología (`maxTechCostHP` $\le 3.5$ a $6.0$ horas de producción).

### 2.3 ForcePlanner (`includes/classes/bot/Forces/ForcePlanner.class.php`)
- **Precisión Sub-Segundo Canónica en Tiempos de Hangar (v4.1.1):**
  - Función `getUnitBuildTimeMs()` adaptada estrictamente a la ecuación oficial de `BuildFunctions::getBuildingTime()`, eliminando multiplicadores artificiales (`* 50.0`).
  - Resolución dinámica de nivel de astillero y nanobots tanto en entidades reales de base de datos como en objetos mock de pruebas unitarias.
  - En universos de alta velocidad donde el tiempo unitario cae por debajo de 1.0s, mantiene resolución exacta de punto flotante en milisegundos.
  - Sincronización en `calculateQueueRemainingSeconds()` (`$ms / 1000.0`) y `getConstructibleBatch()`, permitiendo un encolamiento fluido y realista en ventanas de 30 segundos sin desbordar ni bloquear la cola.

### 2.4 BotPremiumStrategist (`includes/classes/bot/Forces/BotPremiumStrategist.class.php`)
- **Control Restrictivo del Mercader:**
  - `detectPlanetTraderDemand()` ahora opera únicamente ante requerimientos explícitos (`trader_demand_category` o bloqueo de ahorro `savings_lock`).
  - Erradicación de rebalanceos automáticos basados en heurísticas imprecisas que consumían materia oscura innecesariamente.

---

## 3. Protocolos de Verificación y Certificación

1. **Pruebas Unitarias Automatizadas:** Ejecución de `certify_budget_and_allocation.php` cubriendo 27 aserciones sobre aislamiento de presupuestos, trueque 4:2:1, priorización de minas en $\le 18$ campos libres, escalamiento de hangar sub-segundo canónico y reubicación de créditos desplazados.
2. **Prueba de Campo Daemon:** Verificación continua o unitaria mediante `php bot_daemon.php --once`.
3. **Invariante Fundamental:** Ningún bot utiliza accesos directos de base de datos para generar recursos o naves; todas las acciones pasan por `BotActionGateway`, respetando tiempos, costes y validaciones canónicas del juego.
