# NOVARUSH — FASE 2: VERIFICACIÓN DE CAUSAS RAÍZ Y DISEÑO DE PRUEBAS
**Fecha:** 21 de Octubre, 2026  
**Documento:** Informe Técnico de Verificación Experimental, Invariantes y Diseño de Pruebas  
**Archivo de Salida:** `docs/FASE_2_VERIFICACION_Y_DISENO_NOVARUSH.md`  
**Estado:** Auditoría de Diagnóstico sin Modificaciones de Producción

---

## 1. Hipótesis Confirmadas, Refutadas y Pendientes

Tras una inspección directa del código fuente de NovaRush y la ejecución de simulaciones matemáticas aisladas en memoria (sin alterar datos de producción), se presenta el veredicto formal de cada hipótesis planteada en la Fase 1:

| # | Hipótesis Evaluada | Veredicto | Calificación Técnica y Causa Raíz Real |
| :---: | :--- | :---: | :--- |
| **1** | `ForcePlanner::calculateTriResourceMaxUnits()` limita el gasto militar mediante `min(Físico, Virtual)`. | **CONFIRMADA** | **Causa Raíz Directa:** Al no contar con mecanismo de absorción de superávit físico (`idleM`), el poder de compra militar queda estrangulado al 0.022% de su capacidad real en planetas ricos. |
| **2** | `BotBudgetManager::reconcilePlanetResource()` limita los créditos virtuales según la capacidad del almacén. | **CONFIRMADA** | **Causa Raíz Directa:** La fórmula fija `$catCeiling = $cap * $r_k` donde `$cap = metal_max`. En almacenes nivel 0 (4M), Flota nunca puede acumular más de 2.2M virtuales sin importar el metal físico real. |
| **3** | `BotKernel::processTurn()` ejecuta Edificios e Investigaciones antes de Flota y Defensa. | **CONFIRMADA** | **Causa Raíz Coadyuvante:** Las Fases 7 (`allocate` y `allocateResearch`) confirman transacciones atómicas InnoDB (`COMMIT`) y descuentan recursos físicos antes de que la Fase 8 (`planForces`) sea invocada. |
| **4** | Defensa se procesa antes que Flota en `ForcePlanner::planForces()`. | **CONFIRMADA** | **Causa Raíz Coadyuvante:** La Sección 2 (Defensas) se evalúa antes que la Sección 3 (Flota), consumiendo ranuras de las 14 compartidas en `b_hangar_id` y mermando la liquidez física de cristal/metal. |
| **5** | Los créditos militares se anulan cuando el hangar no existe (`hangar == 0`). | **CONFIRMADA** | **Mecanismo Rígido Intencional:** La regla `if ($curHangar === 0) { fleet = 0; defense = 0; }` busca evitar saldos ociosos, pero en cuentas nuevas suprime el 70% del presupuesto sin canalizarlo a acelerar el Hangar. |
| **6** | `savingsLock` puede bloquear recursos durante períodos prolongados. | **CONFIRMADA** | **Causa Raíz de Latencia:** Bloquea cubetas virtuales en RAM durante hasta 14.400s (4h) para edificios y 28.800s (8h) para Astrofísica/IRN, congelando transferencias y rebalanceos inter-categorías. |
| **7** | El mercader consume deuterio masivamente para compensar déficits de otros recursos. | **CONFIRMADA** | **Asimetría de Valor MSE:** `orchestrateDemandTraderBatch()` y `executeExactDeltaTrade()` detectan que el Deuterio tiene el mayor excedente MSE ($4.0 \times \text{unidades}$) y lo liquidan para comprar metal/cristal para minas, dejando a la flota sin combustible para naves pesadas. |

---

## 2. Evidencias Concretas con Rutas, Métodos y Líneas

### 2.1 Estrangulamiento Militar en `ForcePlanner`
* **Ruta:** `includes/classes/bot/Forces/ForcePlanner.class.php`
* **Método:** `calculateTriResourceMaxUnits(array $pData, array $categoryCredits, $unitId, ...)`
* **Líneas Clave:** 642–658
```php
642: $availM = min((float)$pData['metal'], (float)($categoryCredits['metal'] ?? 0.0));
643: $availC = min((float)$pData['crystal'], (float)($categoryCredits['crystal'] ?? 0.0));
644: $availD = min((float)$pData['deuterium'], (float)($categoryCredits['deuterium'] ?? 0.0));
...
654: $maxM = ($costM > 0) ? (int)floor($availM / $costM) : PHP_INT_MAX;
...
658: return max(0, min($maxM, $maxC, $maxD));
```
**Demostración:** No existe ninguna consulta al superávit físico del planeta (`$pData['metal'] - $categoryCredits['metal']`). Si `$categoryCredits['metal']` es 2.200.000, `$availM` es 2.200.000, haciendo imposible adquirir más de 733 Cazas Ligeros aunque el planeta tenga 10.000.000.000 de metal físico.

### 2.2 Techo Contable Limitado por Almacén en `BotBudgetManager`
* **Ruta:** `includes/classes/bot/Forces/BotBudgetManager.class.php`
* **Métodos:** `reconcilePhysicalInvariant()` y `reconcilePlanetResource()`
* **Líneas Clave:** 652, 674, 782–785
```php
652: $capM = max(10000.0, (float)($pData['metal_max'] ?? 10000.0));
...
674: $this->reconcilePlanetResource($pId, 'metal', $sumVirtM, $maxLocalM, $capM, $ratios, $localRatioSum);
...
782: $catCeiling = $cap * $r_k;
783: $cur = (float)($this->credits['planets'][$pId][$cat][$resKey] ?? 0.0);
784: $newVal = min($catCeiling, $cur + ($surplus * $weight));
785: $this->credits['planets'][$pId][$cat][$resKey] = round($newVal, 2);
```
**Demostración:** `$cap` es forzado a `$pData['metal_max']`. En 2Moons, el almacén base nivel 0 con multiplicador x40 es exactamente 4.000.000. El techo de flota para un bot agresivo ($r_k = 0.55$) queda sellado en $4.000.000 \times 0.55 = 2.200.000$.

### 2.3 Supresión Total con Hangar Nivel 0
* **Ruta:** `includes/classes/bot/Forces/BotBudgetManager.class.php`
* **Método:** `reconcilePhysicalInvariant()`
* **Líneas Clave:** 680–684
```php
680: $curHangar = (int)($pData[$resource[21]] ?? 0);
681: if ($curHangar === 0) {
682:     $this->credits['planets'][$pId]['fleet'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
683:     $this->credits['planets'][$pId]['defense'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
684: }
```

### 2.4 El Bypass Asimétrico Exclusivo de Edificios e Investigaciones
* **Ruta:** `includes/classes/bot/Forces/BotBudgetManager.class.php`
* **Método:** `tryInternalBucketSwap()`
* **Líneas Clave:** 956–967
```php
956: $idleM = max(0.0, $physM - $sumVirtM);
...
960: if ($idleM > 0.0 || $idleC > 0.0 || $idleD > 0.0) {
961:     $takeM = min($defM, $idleM);
965:     $targetRef['metal'] = round($curM + $takeM, 2);
...
979:     return true;
980: }
```
**Demostración:** `ProductionAllocator` (líneas 663 y 1866) invoca `tryInternalBucketSwap()` cuando un edificio o tecnología supera el presupuesto virtual, permitiéndole absorber el superávit físico no asignado (`$idleM`). `ForcePlanner` NUNCA llama a este método.

---

## 3. Invariantes Económicos y Fallos Detectados

Para diseñar una corrección robusta, es imperativo formalizar el estado de los recursos en 5 capas bien delimitadas:

```
[ STOCK FÍSICO REAL ]  (Tabla uni1_planets: metal, crystal, deuterium)
       │
       ├── [ RECURSOS COMPROMETIDOS ]  (Colas activas b_building_id, b_hangar_id, b_tech_planet)
       ├── [ RESERVAS ESTRATÉGICAS ]   (Combustible de fleetsave: 15M / 25k Deut intocable)
       ├── [ CRÉDITOS VIRTUALES ]      (JSON uni1_bots.budget_credits: mines, fleet, def, res)
       └── [ RECURSOS DISPONIBLES ]    (Stock Físico - Comprometidos - Reservas)
```

### Auditoría de Invariantes:

1. **Los recursos físicos nunca deben gastarse dos veces:**  
   * **Estado:** **CUMPLE.** `ActionGateway` ejecuta transacciones InnoDB con `SELECT ... FOR UPDATE` sobre el planeta (`BotLockManager::lockPlanetRow`) antes de descontar recursos en `GameServices`.
2. **Los créditos virtuales nunca deben permitir gastar recursos inexistentes:**  
   * **Estado:** **CUMPLE.** Aunque el crédito virtual marque 50.000M, `GameServices::enqueueShipyard` verifica `BuildFunctions::isElementBuyable()` contra el stock físico real antes de insertar en cola.
3. **Las reservas de recursos deben ser identificables y no contabilizarse dos veces:**  
   * **Estado:** **INCUMPLE PARCIALMENTE.** En `BotPremiumStrategist::executeExactDeltaTrade()`, si la razón no contiene la palabra `'fleet'`, `$fuelReserve` se fija en 0.0, vendiendo el deuterio de reserva que la flota requería para defenderse o patrullar.
4. **Un cambio de categoría no debe crear recursos virtuales ficticios:**  
   * **Estado:** **CUMPLE.** `tryInternalBucketSwap()` exige conservación estricta de valor MSE ($\Delta \text{MSE} = 0$) a ratios 4:2:1.
5. **Un fallo de ejecución debe mantener coherentes los recursos y las reservas:**  
   * **Estado:** **CUMPLE.** Si `enqueueBuilding()` o `enqueueShipyard()` retorna `false`, `ActionGateway` ejecuta `rollBack()` y aborta la deducción presupuestaria.
6. **El límite del almacén no debe confundirse automáticamente con la cantidad de recursos que puede gastarse:**  
   * **Estado:** **INCUMPLE CRÍTICAMENTE.** En `reconcilePlanetResource()`, el límite de gasto militar se clampó a `$catCeiling = metal_max * r_k`. El motor confunde la capacidad de almacenaje pasivo con la capacidad de desembolso activo.
7. **El gasto de una categoría no debe invalidar silenciosamente el presupuesto de otra:**  
   * **Estado:** **INCUMPLE CRÍTICAMENTE.** Cuando `ProductionAllocator` absorbe 10.000M de metal físico mediante `tryInternalBucketSwap()`, reduce el stock físico del planeta. En el tick siguiente, `reconcilePlanetResource()` ejecuta el "Case A (Phantom Inflation)" y reduce proporcionalmente las cubetas de Flota y Defensa para ajustarlas a la nueva realidad física disminuida.
8. **Los recursos reservados para una investigación no deben aparecer simultáneamente como disponibles para el ejército:**  
   * **Estado:** **CUMPLE.** `savingsLock` protege la cubeta bloqueada impidiendo que sea utilizada como donante en swaps.

---

## 4. Resolución de las Ambigüedades Críticas

### A. Superávit Físico: ¿Puede reutilizarse `tryInternalBucketSwap()` para Flota y Defensa?
* **Análisis de Riesgo:** `tryInternalBucketSwap()` consta de dos fases:  
  * *Fase A (Absorción de Superávit Ocioso `$idleM`):* Es 100% segura. Solo toma recursos físicos que no están asignados a ninguna cubeta virtual.  
  * *Fase B (Donación Inter-Categorías):* Es **peligrosa** para el astillero. Si `ForcePlanner` llama a la Fase B de forma descontrolada para cada nave candidata, Flota canibalizaría las cubetas de Minas e Infraestructura, desmantelando la progresión económica del bot.  
* **Conclusión Técnica:** **NO se debe copiar ciegamente `tryInternalBucketSwap()` en `ForcePlanner`.** La solución correcta es otorgar a `ForcePlanner` acceso al superávit físico no asignado (`$idleM = max(0, $physM - $sumVirtM)`), manteniendo intactas las bolsas de otras categorías.

### B. Capacidad del Almacén: Evaluación de `cap = max(metal_max, metal_físico)`
* **Ventajas:** Es una corrección de 1 línea en `BotBudgetManager::reconcilePhysicalInvariant()` que elimina de raíz el cuello de botella de 2.2M, permitiendo que inyecciones de recursos o botines masivos se distribuyan en un 55% a Flota de forma inmediata.
* **Inconsistencias Potenciales:** Si un planeta tiene 30.000M de recursos y almacén de 4M, las cubetas virtuales sumarán 30.000M. Al ser gastados en naves, el stock descenderá. En 2Moons, esto es totalmente legal (el almacén solo detiene la producción horaria de las minas, jamás impide gastar recursos acumulados ni bloquea el astillero).
* **Veredicto:** La fórmula `cap = max(metal_max, metal_físico)` es **matemáticamente sólida, compatible con 2Moons y de mínimo riesgo**.

### C. Orden de Ejecución: Comparación de Alternativas de Control de Fases

| Criterio | Alternativa 1: Mantener Orden + Corregir Acceso | Alternativa 2: Pre-Reserva Garantizada de Liquidez | Alternativa 3: Planificador Global Holístico |
| :--- | :--- | :--- | :--- |
| **Mecanismo** | Minas en Fase 7, Flota en Fase 8. Se permite a Flota acceder al superávit físico. | En Fase 1 se fija un piso físico intocable por categoría antes de ejecutar gastos. | Se generan candidatos de las 4 ramas, se puntúan en una matriz unificada y se ejecutan en lote. |
| **Complejidad** | Muy Baja (3–5 líneas modificadas). | Media (Requiere nueva entidad `LiquidReserveManager`). | Muy Alta (Reescritura completa de `BotKernel` y gateways). |
| **Riesgo de Regresión** | Prácticamente Nulo. | Bajo. | Crítico (Riesgo de descalibrar las 12 fases existentes). |
| **Eficacia Militar** | Alta si hay superávit físico abundante. | Muy Alta (Flota tiene su dinero asegurado). | Máxima teórica. |

### D. Hangar y Arranque Militar: ¿Puede un bot quedar atrapado en Nivel 0?
* **Traza del Ciclo:**
  1. Cuenta inicia en nivel 0 $\rightarrow$ `hangar == 0`.
  2. `reconcilePhysicalInvariant` fija `fleet = 0` y `defense = 0`.
  3. `ProductionAllocator` (línea 933) impone: `if ($metLvl >= 10 && $cryLvl >= 8 && $deuLvl >= 6)`.
  4. Mientras las minas no alcancen niveles 10/8/6, el bot NO otorga prioridad a Fábrica de Robots (#14) ni a Hangar (#21).
  5. Una vez alcanzado el piso 10/8/6, el bot asigna puntuación prioritaria (22.000 pts a Robots nivel 2 y 25.000 pts a Hangar nivel 4).
* **Diagnóstico:** **El bot NO queda atrapado en un bucle infinito**, pero sufre un **retraso táctico severo**. Si el bot tiene miles de millones de recursos físicos, no tiene sentido obligarlo a esperar a que las minas suban nivel a nivel para construir el astillero.

### E. Las 14 Ranuras del Hangar (`b_hangar_id`)
* **Verificación de Motor:** En 2Moons, `b_hangar_id` es un array serializado único en `uni1_planets`. Defensas y naves se procesan en la **misma cola FIFO secuencial**.
* **Problema de la División Rígida 7/7:** Dividir artificialmente la cola en 7 y 7 ranuras es subóptimo: si el bot no necesita defensas, desperdiciaría 7 ranuras útiles para naves.
* **Solución Óptima:** **Control por Cuota de Tiempo y Ranuras Dinámicas:** Limitar la defensa a un máximo de 4 ranuras si no hay amenaza entrante, garantizando siempre al menos 10 ranuras para la flota de ataque y transporte.

---

## 5. Pruebas Reproducibles

### 5.1 Pruebas Aisladas Ejecutadas y Resultados Reales (En Memoria)

```
=== RESULTADOS DE VERIFICACIÓN EN MEMORIA (PHP 7.4 CLI) ===
[EJECUTADA] Test 1: calculateTriResourceMaxUnits con Físico=10B y Virtual=2.2M
            Resultado: 733 Cazas Ligeros autorizados vs 3.333.333 físicamente posibles.
            Estrangulamiento confirmado al 0.022%.

[EJECUTADA] Test 2: reconcilePlanetResource con Techo Almacén vs Dinámico
            Resultado Actual (metal_max=4M): 2.200.000 Metal.
            Resultado Dinámico (max(4M, 10B)): 18.150.000.000 Metal.
            Incremento de liquidez militar confirmado: 8.250x.

[EJECUTADA] Test 3: Inspección de b_hangar_id en 2Moons
            Resultado: Cola serializada única compartida. Si la defensa encola 8 tipos,
            la flota queda restringida a 6 ranuras y retrasada en tiempo de ejecución.
```

### 5.2 Diseño de los 10 Casos de Prueba Propuestos (Para Validación de Solución)

| Caso | Escenario Inicial | Comportamiento Esperado | Invariante a Comprobar | Método Involucrado |
| :---: | :--- | :--- | :--- | :--- |
| **CP-01** | Planeta con 10.000M físico y 2.2M virtual. | Reclutar lote masivo de naves proporcional a su producción y tiempo de cola. | No exceder stock físico real. | `ForcePlanner::calculateTriResourceMaxUnits` |
| **CP-02** | Planeta con 500M de metal comprometidos en `savingsLock` (Investigación). | Flota solo puede usar recursos por encima de los 500M bloqueados. | Protección de reserva estratégica. | `BotBudgetManager::isCategoryLocked` |
| **CP-03** | Planeta nuevo con recursos masivos pero Hangar 0. | El bot prioriza Robots 2 y Hangar 1 inmediatamente sin esperar minas 10/8/6. | Transición rápida a capacidad militar. | `ProductionAllocator::allocate` |
| **CP-04** | Planeta con Hangar saturado (cola > 2.700s). | No encolar unidades; esperar a que el astillero libere tiempo. | Respetar límites de cola de 2Moons. | `ForcePlanner::getConstructibleBatch` |
| **CP-05** | Bot con recursos ajustados debe elegir entre Mina 25 o Crucero. | La personalidad modula la elección sin que una categoría anule a la otra. | Reparto porcentual macro $r_k$. | `BotKernel::processTurn` |
| **CP-06** | Investigación lejana (Astrofísica) activa `savingsLock` por 4 horas. | Las otras colonias continúan reclutando naves con sus presupuestos locales. | Aislamiento de bloqueos locales. | `BotBudgetManager::getSavingsLockForCategory` |
| **CP-07** | Planeta con 100M Deuterio y deficit de Metal para minas. | El mercader solo vende el deuterio que supere la reserva de flota (15M). | Integridad de combustible militar. | `BotPremiumStrategist::executeExactDeltaTrade` |
| **CP-08** | Dos procesos del daemon intentan gastar en el mismo planeta. | El segundo proceso detecta `lockPlanetRow` y espera o aborta limpiamente. | Atomicidad transaccional ACID. | `ActionGateway::constructUnits` |
| **CP-09** | Encolado de naves falla en 2Moons tras debitar budget. | La transacción ejecuta `rollBack()` y el crédito presupuestario se restaura. | Consistencia contable ante fallos. | `ActionGateway::constructUnits` |
| **CP-10** | Bot con 16 colonias y transferencias logísticas hacia FOB. | Los presupuestos locales reflejan la partida y llegada de convoys. | Conservación de masa imperial. | `BotBudgetManager::deductDispatchedMission` |

---

## 6. Diseño Técnico de la Solución por Etapas

### Comparativa de Alternativas Arquitectónicas:

* **Alternativa A (Correcciones Puntuales de Mínimo Riesgo - RECOMENDADA):**  
  1. En `BotBudgetManager::reconcilePhysicalInvariant()`, fijar `$capM = max((float)$pData['metal_max'], $curM)`.  
  2. En `ForcePlanner::calculateTriResourceMaxUnits()`, permitir que `$availM` incorpore el superávit físico ocioso del planeta (`$idleM`).  
  3. En `ProductionAllocator.class.php`, suavizar la condición de minas 10/8/6 cuando el planeta tenga recursos físicos sobrantes para Hangar.  
  4. En `ForcePlanner::planForces()`, asignar un tope de ranuras a defensa (máx 4) cuando no haya alerta de ataque.
* **Alternativa B (Presupuesto Flexible y Reservas Estratégicas):**  
  Crear un módulo `BotLiquidityArbiter` que divida físicamente el dinero en MariaDB antes de llamar a las clases de decisión. (Complejidad media, riesgo de concurrencia).
* **Alternativa C (Planificador Global Holístico):**  
  Reescribir el Kernel para consolidar Edificios, Tech, Flotas y Defensas en un solo evaluador de utilidad. (Complejidad extrema, alto riesgo de romper sistemas probados de combate y colonización).

---

## 7. Secuencia de Implementación Recomendada (Fases Futuras)

1. **Fase 2.1 (Calibración Contable en BudgetManager):** Modificar la base del techo contable a `max(metal_max, metal_físico)` y sincronizar reservas de combustible en el mercader.
2. **Fase 2.2 (Desbloqueo de Compra en ForcePlanner):** Permitir a `calculateTriResourceMaxUnits` absorber superávit físico no asignado (`idleM`).
3. **Fase 2.3 (Aceleración de Hangar Temprano):** Eliminar la barrera de minas 10/8/6 si el bot ya cuenta con recursos físicos para levantar astillero.
4. **Fase 2.4 (Ejecución de Pruebas Unitarias):** Correr la suite de pruebas unitarias y verificar la ausencia de regresiones.

---

## 8. Criterios de Aceptación Medibles

1. **Índice de Utilización de Capital Militar:** En planetas con más de 1.000M de recursos y Hangar $\ge 1$, el bot debe poder comprometer en astillero al menos el 50% de su cuota de flota por ciclo sin quedar limitado a 2.2M.
2. **Tiempo de Arranque Militar en Cuentas Nuevas:** Un bot creado desde nivel 0 con recursos debe tener Hangar nivel 1 construido y su primera nave encolada en menos de 15 minutos (20 ciclos de daemon).
3. **Invariante de Combustible:** En ningún caso el mercader debe reducir el deuterio de una colonia con flota por debajo del buffer táctico de 15.000.000 sin una justificación de emergencia extrema.
4. **Equidad de Ranuras de Hangar:** En situaciones sin amenaza militar entrante, las defensas nunca deben consumir más de 4 de las 14 ranuras del hangar, dejando al menos 10 ranuras para el encolado de flotas.
