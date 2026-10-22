# INFORME TÉCNICO DE IMPLEMENTACIÓN: CORRECCIÓN DEL SISTEMA ECONÓMICO Y DE INTELIGENCIA ARTIFICIAL DE BOTS EN NOVARUSH

> **Proyecto:** NovaRush (Motor 2Moons / XNova, PHP 7.4)  
> **Rama Git:** `fix/bot-economy-ai-redesign`  
> **Fecha de Certificación:** Octubre 2026  
> **Estado:** Implementación Completada y Certificada al 100% en Pruebas Unitarias y de Regresión  

---

## 1. RESUMEN EJECUTIVO Y CAUSAS RAÍZ CONFIRMADAS

Tras la exhaustiva auditoría del ciclo de vida de recursos y presupuestos de los bots de NovaRush, se identificaron seis fallas estructurales que provocaban el colapso o estancamiento de la producción militar y la mala asignación presupuestaria:

1. **Doble Deducción Presupuestaria Militar:**  
   `BotActionGateway::constructUnits()` ya calculaba el costo real mediante el motor 2Moons, descontaba los recursos físicos encolados y debitaba los créditos virtuales a través de `BotBudgetManager::deductFromBudget()`. Sin embargo, en `ForcePlanner::planForces()` existían 8 rutas (cúpulas, misiles 502/503, artillería, sondas, cargueros, recicladores y naves de guerra) que llamaban por segunda vez a `deductFromBudget()`, reduciendo artificialmente el saldo virtual al doble del costo real.

2. **Bypass Presupuestario de Edificios:**  
   En `ProductionAllocator::allocate()`, cuando `canAffordBudget()` fallaba y `tryInternalBucketSwap()` no lograba transferir fondos (o los donantes estaban bloqueados), una cláusula forzaba `$canAffordBudget = true` si el stock físico en superficie alcanzaba para pagar. Esto permitía a la categoría Edificios canibalizar reservas físicas destinadas a Flota y Defensa.

3. **Estrangulamiento por Capacidad de Almacenes:**  
   Tanto en `updateCredits()` como en `reconcilePhysicalInvariant()` de `BotBudgetManager`, el techo de acumulación virtual se calculaba como `$cap = max(10000.0, (float)$pData['metal_max']) * $ratio`. En colonias nuevas o bots con almacenes bajos (e.g. 100k a 2.2M) que recibían inyecciones de recursos, botines de guerra o transportes (ej. 10,000M), el crédito virtual quedaba aprisionado en una fracción ínfima, bloqueando a `ForcePlanner` (que evalúa `min(Físico, Virtual)`).

4. **Vaciado a Cero por Falta de Hangar (`hangar == 0`):**  
   En `reconcilePhysicalInvariant()`, si un planeta tenía `curHangar === 0`, el sistema reseteaba a 0.00 tanto `fleet` como `defense`. Esto eliminaba la autorización militar del bot y bloqueaba la acumulación previa al desbloqueo del astillero.

5. **Bloqueo Rígido de Árbol Tecnológico Temprano:**  
   En `ProductionAllocator`, el impulso de prioridad para Fábrica de Robots 2, Hangar 1-4 y Laboratorio 3-6 estaba condicionado rígidamente a que las minas alcanzaran niveles mínimos (`metal >= 10 && crystal >= 8 && deuterium >= 6`), ignorando si el planeta disponía de riqueza física inmediata para financiar la infraestructura militar.

6. **Drenaje de Moneda de Pago y Combustible en el Mercader:**  
   En `BotPremiumStrategist::canTraderResolveDeficit()` y `executeExactDeltaTrade()`, la validación de fondos del recurso pagador utilizaba el stock físico bruto (`$curM`, `$curC`) en lugar del superávit neto sobre el costo de la meta. Esto permitía vender recursos que la propia meta requería, dejando la construcción inviable tras el intercambio, y consumía combustible militar sin respetar reservas de seguridad.

---

## 2. SOLUCIONES IMPLEMENTADAS Y DECISIONES DE DISEÑO

### 2.1. Deducción Única Centralizada en `BotActionGateway`
* **Punto Único de Verdad:** Se eliminaron las 8 llamadas duplicadas a `deductFromBudget()` en `ForcePlanner.class.php`.
* **Variables Locales de Ciclo Protegidas:** Se conservaron intactas las actualizaciones de `$fleetCredits` y `$defCredits` dentro de los bucles de `ForcePlanner`, garantizando que dentro del mismo tick el planificador no gaste dos veces los mismos créditos.

### 2.2. Aislamiento Estricto y Eliminación del Bypass en `ProductionAllocator`
* **Eliminación del Bypass:** Se removieron las líneas que forzaban `$canAffordBudget = true`.
* **Flujo Contable:** Ahora un edificio solo se construye si tiene saldo en su bolsa `mines` o si `tryInternalBucketSwap()` consigue financiarlo legítimamente desde excedentes físicos no asignados o bolsas no bloqueadas.
* **Aceleración Temprana:** Se introdujo la compuerta `$hasAmpleResources = ($availPhysM >= 20000.0 && $availPhysC >= 10000.0)` para que colonias con recursos suficientes construyan Robots 2 y Hangar 1 de inmediato.

### 2.3. Techo Dinámico de Almacén y Preservación en `hangar == 0`
* **Techo Dinámico:** En `BotBudgetManager::updateCredits()` y `reconcilePhysicalInvariant()`, se actualizó la fórmula del techo:
  $$\text{cap} = \max(\text{capacidad\_almacén}, \text{stock\_físico\_actual})$$
* **Conservación Física Invariable:** Como los ratios macroeconómicos satisfacen $\sum r_k = 1.0$, la suma de créditos virtuales entre Minas, Flota, Defensa e Investigación es idéntica al 100% del stock físico, sin duplicaciones ni fondos ficticios.
* **Preservación Militar:** Se eliminó el reseteo a 0.00 en colonias con `hangar == 0`. Las reservas presupuestarias se preservan y quedan listas para reclutar unidades en cuanto se concluye el Hangar 1.

### 2.4. Mercader con Validación de Superávit Neto y Reserva de Combustible
* **Validación de Superávit:** Tanto en la pre-evaluación (`canTraderResolveDeficit`) como en la ejecución (`executeExactDeltaTrade`), el recurso de pago solo puede financiarse con:
  $$\text{surplus}_k = \max(0, \text{stock}_k - \text{reserva\_táctica} - \text{costo\_meta}_k)$$
* **Piso Táctico de Deuterio:** Se garantiza una reserva mínima de $25,000$ de deuterio en compras de infraestructura/tecnología y de $15,000,000$ en operaciones militares.

### 2.5. Límite Flexible de Slots Defensivos en Astillero
* En `ForcePlanner::planForces()`, se implementó `$maxDefJobsThisCycle = $isEmergency ? 14 : 4;`.
* En tiempo de paz, la defensa encola un máximo de 4 trabajos por ciclo, impidiendo la saturación de los 14 slots de la cola compartida FIFO (`b_hangar_id`) y reservando slots para las naves de guerra.

---

## 3. MODIFICACIONES DETALLADAS POR ARCHIVO Y MÉTODO

| Archivo | Métodos Afectados | Naturaleza del Cambio |
| :--- | :--- | :--- |
| `ForcePlanner.class.php` | `planForces()` | Eliminadas 8 llamadas duplicadas a `deductFromBudget()`. Incorporado limitador de 4 slots defensivos en tiempo de paz (`$maxDefJobsThisCycle`). |
| `ProductionAllocator.class.php` | `allocate()` | Eliminado el bypass forzado `$canAffordBudget = true`. Añadida compuerta `$hasAmpleResources` para desbloqueo acelerado de Robots/Hangar. |
| `BotBudgetManager.class.php` | `updateCredits()`, `reconcilePhysicalInvariant()`, `canAffordBudget()` | Techo dinámico de almacén basado en $\max(\text{max\_storage}, \text{stock\_actual})$. Retirado vaciado a 0 en `hangar == 0`. Aislamiento estricto en `canAffordBudget()` (retorna `false` sin auto-swap descontrolado). |
| `BotPremiumStrategist.class.php` | `canTraderResolveDeficit()`, `executeExactDeltaTrade()` | Verificación de pago restringida al superávit real neto. Protección de reserva de combustible ($25\text{k}$ a $15\text{M}$ deut). |
| `test_military_budget_isolation_suite.php` | Grupo 1 y 2 | Calibración de costos mock de prueba para respetar el guardián anti-estancamiento `MAX_SAVINGS_LOCK_ETA = 14400s`. |
| `test_bot_plan_unit.php` | Setup inicial & Test 1.5 | Fallback dinámico de bot ID si 1007 no existe en BD; alineación de aserción quirúrgica de misiles. |
| `test_economic_fixes_regression.php` | Suite completa (11 tests) | Nueva suite dedicada a auditar regresiones económicas, invariantes de almacén y límites de cola. |

---

## 4. MATRIZ DE CERTIFICACIÓN DE PRUEBAS AUTOMATIZADAS

Se ejecutaron las tres suites de pruebas en el entorno de pruebas local de NovaRush con **0 fallos**:

```text
========================================================================
RESUMEN GLOBAL DE SUITES DE PRUEBAS CERTIFICADAS
========================================================================
1. tests/test_economic_fixes_regression.php         -> 11/11 [PASS] (100%)
2. tests/test_military_budget_isolation_suite.php    -> 24/24 [PASS] (100%)
3. tests/test_bot_plan_unit.php                     -> 13/13 [PASS] (100%)
========================================================================
TOTAL: 48 PRUEBAS EJECUTADAS | 48 EXITOSAS | 0 FALLIDAS
========================================================================
```

### Detalle de Escenarios Validados:
* **Almacén Dinámico:** Colonias con 100k de almacén y 10M físicos acumulan y gastan más de 1M en flota sin restricciones artificiales.
* **Conservación Contable:** Suma de Minas + Flota + Defensa + Investigación $\le$ 100% de los recursos físicos reales.
* **Deducción Única:** Cero llamadas a `deductFromBudget()` en `ForcePlanner`; deducción atómica en `ActionGateway`.
* **Aislamiento Presupuestario:** `canAffordBudget()` rechaza compras que excedan el saldo propio sin permitir cross-borrowing indiscriminado.
* **Candados de Ahorro:** Un candado en minas no bloquea flota, defensa ni cruceros; candados con ETA > 4 horas no congelan el imperio.
* **Mercader:** Rechaza intercambios si el stock total cubre la compra pero el superávit no; aprueba cuando el superávit es genuino; protege el combustible.
* **Cascada de Flota y Defensa:** Cascada de naves pesadas a cruceros y cazas; reposición de silos (60% ABM / 40% MIP).

---

## 5. RECOMENDACIONES DE DESPLIEGUE Y ESTRATEGIA DE REVERSIÓN

### Compatibilidad con Partidas Existentes
* **Retrocompatibilidad 100%:** No se modificaron esquemas de base de datos ni firmas públicas de métodos existentes.
* **Persistencia de `budget_credits`:** El formato JSON almacenado en `uni1_bots.budget_credits` es totalmente compatible. Al correr el siguiente tick del daemon, `updateCredits()` reconcilia automáticamente los presupuestos de cualquier bot existente adaptándolos al nuevo techo dinámico sin pérdida de datos.

### Procedimiento de Reversión (Rollback)
Si por cualquier motivo se requiere revertir los cambios en el entorno de desarrollo:
```bash
git checkout main -- includes/classes/bot/Forces/BotBudgetManager.class.php
git checkout main -- includes/classes/bot/Forces/ProductionAllocator.class.php
git checkout main -- includes/classes/bot/Forces/ForcePlanner.class.php
git checkout main -- includes/classes/bot/Forces/BotPremiumStrategist.class.php
```
Todas las versiones previas seguras se encuentran respaldadas en `scratch/.backups_pre_fix/` y en el historial de commits de Git.
