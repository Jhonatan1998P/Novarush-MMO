# NovaRush — Project Status & Architecture Context

> **Propósito:** Archivo de contexto compacto para inicializar nuevos chats en Antigravity 2.0 sin consumir cuota excesiva. Usar `@STATUS.md` al abrir una nueva conversación.
> 
> **Regla de Gobernanza de STATUS.md (Límite 5k Tokens):** Se puede añadir contenido nuevo al archivo `STATUS.md` hasta un máximo estricto de **5,000 tokens** (~20 KB de texto). Si el archivo se acerca o excede dicho límite, la IA debe **actualizar, consolidar y sintetizar** la información existente en lugar de simplemente anexar más texto. De esta manera, el documento se mantiene siempre fresco, relevante y de bajo consumo sin perder el contexto histórico clave.

---

## 1. Entorno de Ejecución & Infraestructura
* **Proyecto:** NovaRush (servidor OGame basado en *New-Star / 2Moons v1.8.0*).
* **Stack:** PHP 7.4 (XAMPP en Windows), Apache (puerto 80), MySQL/MariaDB (puerto 3306).
* **Rutas Críticas:**
  * Webroot: `C:\Users\Ortega\Downloads\OGAME\ogame` (servido en `http://localhost/novarush` y `http://192.168.100.231/novarush`).
  * PHP CLI: `C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\php\php.exe`.
  * MySQL CLI: `C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\mysql\bin\mysql.exe`.
  * Base de Datos: `ogame2` (usuario: `root`, password: vacío).
* **Exposición Pública & Túnel Persistente:**
  * Dominio propio: `lordasgame.online` (registrado en Spaceship, DNS autoritativo delegado a Cloudflare).
  * URL Pública Activa con SSL: `https://play.lordasgame.online/novarush/`.
  * Túnel: Cloudflare Zero Trust Named Tunnel (`novarush`) ejecutándose como servicio nativo de Windows (`Cloudflared`).
  * Resiliencia: Inicio automático con Windows y reconexión inmediata ante apagones, reinicios o cortes de Wi-Fi.
  * Documentación completa del workflow: `C:\Users\Ortega\Downloads\GUIA_DOMINIO_CLOUDFLARE_NOVARUSH.md`.

---

## 2. Jugadores Clave & Cuentas

### User ID 4 (`-GODWAR-`) — Cuenta Principal
* **Planetas (6):**
  * Sede Principal: `Vampire` `[1:2:12]`
  * Colonias: `Lycan` `[1:82:8]`, `Exilum` `[1:83:8]`, `Renrize` `[1:120:8]`, `Aargon` `[1:83:7]`, `Atenea` `[1:305:8]`.
* **Nivelación Industrial de Minas & Módulos:**
  * Minas: **Nivel 35 Metal, 34 Cristal, 31 Deuterio** en los **6 planetas**.
  * **Módulo de Recursos: Nivel 30** en los **6 planetas**.
* **Investigaciones Clave Recientes:**
  * **Tecnología de Expedición: Nivel 20** (4 slots de expedición base habilitados).
  * **Tecnología de Cristal: Nivel 30** & **Tecnología de Deuterio: Nivel 30** (Logro 5016 "Geólogo" activo: +90% boost de producción global).
* **Recursos Consolidados en `Atenea` `[1:305:8]`:**
  * `~20.94B` Metal, `~5.67B` Cristal, `~1.41B` Deuterio (Ratios ajustados al 55% M / 30% C / 15% D).
* **Flota Activa en `Atenea` `[1:305:8]`:** ~94,983 Cruceros, 16 Grandes Transportes (destinados a expediciones).
* **Balances Especiales:** 250 Antimateria (AM), 604 Materia Oscura (MO), 17 Contenedores.

### User ID 3 (`Edward Newgate`) — Bajo Vigilancia Forense Activa
* **Planetas (17):** 1 Planeta principal (`[1:1:4]`) y 16 colonias industrializadas al 100%.
* **Culminación del Plan Industrial (07:00 AM):**
  * Las **5 nuevas colonias** (`[1:13:7]`, `[1:14:7]`, `[1:15:8]`, `[1:16:7]`, `[1:17:8]`) han sido niveladas a **Minas de Metal 37, Cristal 35 y Deuterio 33**, Plantas Solares 37 y Módulos de Recursos al 20 (salvo `[1:17:8]` al 8).
  * Astrofísica / Expedición: Subida a Nivel 19 (habilita hasta 19 planetas).
  * Consumo neto adicional: `~3.41B` Metal, `~903M` Cristal, `~291M` Deuterio de sus cargamentos.
* **Balances:** 5,897 MO (-70 MO gastadas en 7 aceleraciones `fast_building`), 429 AM, 2 Contenedores (+2 ganados en expedición).
* **Watchdog Forense Activo:** Motor autónomo `C:\Users\Ortega\Downloads\OGAME\edward_watchdog.php` con cron cada 60 min (`task-334`).
  * Bitácora en tiempo real: `C:\Users\Ortega\Downloads\OGAME\edward_audit.log`.

---

## 3. Lógica Económica & Modificaciones de Código

### A. Sistema de Contenedores (`includes/pages/game/ShowConteinerPage.class.php`)
* **Lógica canónica:**
  1. `PremiumEconomy::playerHourlyProduction($userId)` calcula la producción horaria exacta (`metal`, `crystal`, `deuterium`) sumando minas e ingresos base.
  2. Convierte a MSE canónico ($MSE_M = M, MSE_C = 2C, MSE_D = 4D$) y calcula proporciones reales $\%_M, \%_C, \%_D$.
  3. Del presupuesto de 30 minutos ($V = 0.5 \times MSE$):
     * **Metal:** $V \times \%_M$ (1 MSE = 1 Metal).
     * **Cristal:** $(V \times \%_C) \times 0.5$ (1 MSE = 0.5 Cristal).
     * **Deuterio:** $(V \times \%_D) \times 0.25$ (1 MSE = 0.25 Deuterio).
  4. Flota fija por container: 25 Cazas Ligeros (202), 15 Cazas Pesados (203), 10 Cruceros (204).
* **Resultado:** Cada container entrega exactamente el 50% (30 min) de la producción real de las minas del jugador.

### B. Sistema Premium & Ledger (`includes/classes/PremiumEconomy.class.php`)
* **Monedas gestionadas:** `921` (Materia Oscura), `922` (Antimateria), `924` (Contenedores).
* **Configuración (`uni1_premium_settings`):** `ct_open_hours = 0.5`, `auction_hours = 60`, `auction_price_am = 900`.

### C. Ajuste de Eventos & Fortaleza
* **Condición de victoria:** Se registra cumplido cuando una sola oleada/ataque limpia el 100% de las unidades defensoras (0 unidades en pie), evitando el bucle infinito por regeneración.

### D. Motor de Batalla & Matriz de Daño (`includes/classes/missions/functions/calculateAttack.php`)
* **3 Capas:** Escudo (regenera 100% por ronda, regla de rebote si ataque < escudo), Blindaje (`defend`, categoría `light`/`medium`/`heavy`), Casco (`def/10`, acumula bajas).
* **Matriz `type_gun` vs `type_defend`:**
  * `laser`: 65-75% ligero, 15-25% medio, ~0% pesado.
  * `ion`: 55-65% ligero, 65-75% medio, 0-10% pesado.
  * `plasma`: 0-2% ligero, 35-45% medio, 50-60% pesado.
  * `gravity`: 0-5% ligero, 10-20% medio, 65-75% pesado.
* **Bug EDLM vs Cazas:** La EDLM (`gravity, 100%`) redondea a 0 disparos contra blindaje `light` y carece de fuego rápido en `uni1_vars_rapidfire`, causando 0 bajas contra cazas ligeros.
* **Buques híbridos recomendados:** `Fragata Pesada` (227) y `Nómada Negro` (228) por integrar cañones mixtos sin puntos ciegos.
* **Documentación completa:** `C:\Users\Ortega\Downloads\OGAME\MOTOR_DE_BATALLA.md`.

---

## 4. Reglas Críticas del Asistente (GEMINI.md)
* **Preguntas terminadas en `?`:** Estrictamente informativas/analíticas. No modificar archivos ni ejecutar comandos de escritura sin orden explícita ("edita", "aplica", "procede").
* **Tests:** NUNCA ejecutar scripts de prueba o test suites automáticamente a menos que se pida expresamente.

---

## 5. Optimización de Tokens & Cuota en Antigravity 2.0
* **Ciclo de vida del chat:** Cerrar chat y abrir **New Conversation** cada **25-35 mensajes (~150 steps)** para evitar acumulación de contexto (>50k tokens de entrada por turno).
* **Skills redundantes:** Carpetas duplicadas de Minecraft en `skills_backup` para evitar doble inyección en el prompt de sistema.
* **Continuidad de trabajo:** En nuevos chats, invocar `@STATUS.md` seguido de la tarea a realizar.

---

## 6. Automatización de Expediciones a la Nebulosa (REGLA PERMANENTE)

> [!IMPORTANT]
> **ESTA INFORMACIÓN NO DEBE BORRARSE NUNCA DE STATUS.MD.**
> Cualquier agente de Antigravity que reciba una orden del usuario como:
> - *"lanza las 4 expediciones a nebula"*
> - *"envía las expediciones a la nebulosa"*
> - *"lanza expe nebula"*
> debe ejecutar **de inmediato** el script CLI sin pedir confirmación adicional ni rehacer código.

* **Comando de Ejecución Inmediata:**
  ```powershell
  C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\php\php.exe C:\Users\Ortega\Downloads\OGAME\ogame\tools\launch_nebula_expeditions.php
  ```
  *(O alternativamente: `C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\php\php.exe C:\Users\Ortega\Downloads\OGAME\ogame\scripts\launch_nebula_expeditions.php`)*
* **Especificaciones Canónicas de la Misión:**
  * **Script:** [`tools/launch_nebula_expeditions.php`](file:///C:/Users/Ortega/Downloads/OGAME/ogame/tools/launch_nebula_expeditions.php).
  * **Cuenta:** User ID 4 (`-GODWAR-`).
  * **Planeta Origen:** `Atenea` `[1:305:8]` (Planet ID: `34`).
  * **Destino:** Posición `[1:305:16]` (Tipo 1).
  * **Misión:** ID `18` (`MissionCaseMilitaryExpedition` / Expedición Militar Nebulosa).
  * **Sector:** `3` (Antiguos).
  * **Composición por Flota:** 20 Cruceros (ID `206`) + 1 Gran Transporte (ID `217`).
  * **Cantidad por Despacho:** 4 expediciones automáticas (valida dinámicamente slots disponibles en `uni1_fleets` y hangares de Atenea antes de lanzar).
  * **Parámetros Opcionales Soportados:**
    * `--count=N` (número de flotas a enviar)
    * `--sector=N` (1=Piratas, 2=Alienígenas, 3=Antiguos)
    * `--cruisers=N` (cantidad de cruceros por flota)
    * `--transporters=N` (cantidad de grandes transportes por flota)
    * `--dry-run` (simulación previa sin descontar ni insertar)
