# NovaRush MMO 🚀🌌

**NovaRush MMO** es una edición personalizada y avanzada del clásico juego de estrategia y conquista espacial en navegador, desarrollado sobre las bases de **New-Star** y **2Moons**.

Esta versión incorpora un motor de combate táctico multicapa rediseñado, un ecosistema de **Inteligencia Artificial para bots autónomos**, modo espectador en tiempo real, eventos dinámicos y un rebalanceo económico integral.

---

## 🙏 Créditos & Agradecimientos Especiales (Credits & Acknowledgements)

NovaRush MMO no hubiera sido posible sin el talento y la dedicación de los desarrolladores originales que construyeron y modernizaron los cimientos de este motor:

* **Jan Kröpke ([@jkroepke](https://github.com/jkroepke))** — Creador y arquitecto original de **[2Moons](https://github.com/jkroepke/2Moons)** (y las bases históricas de *XG-Project*), quien diseñó la infraestructura del juego en PHP/MySQL que definió a toda una generación de servidores espaciales de código abierto.
* **Tsvira Yaroslav ([@Yaro2709](https://github.com/Yaro2709))** — Creador de **[New-Star](https://github.com/Yaro2709/New-Star)**, proyecto que redefinió 2Moons v1.8.0 con una interfaz moderna, nuevo diseño visual, compatibilidad actualizada y expansiones mecánicas fundamentales.

NovaRush toma ese gran legado como punto de partida y lo evoluciona hacia una experiencia MMO propia, adaptada a nuestros gustos y con sistemas propietarios de juego continuo.

---

## ✨ Características Principales de NovaRush MMO

### 🧠 1. Motor de Inteligencia Artificial para Bots (AI Engine)
* **Toma de Decisiones Autónomas:** Sistema de IA militar y económica capaz de simular jugadores reales en el universo.
* **Asedios Tácticos y Misiles Interplanetarios (MIP):** Coordinación de salvas de desgaste contra defensas antibalísticas (ABM) antes de lanzar asaltos de flota.
* **Simulador de Rentabilidad en Tiempo Real:** Algoritmo que evalúa escombros generados, botín robable y pérdidas netas antes de autorizar misiones de ataque y reciclaje coordinado.
* **Personalidades Tácticas:** Bots con roles específicos, incluyendo perfiles de alta agresividad táctica (ej. *LaParca*).

### ⚔️ 2. Motor de Batalla Avanzado & Matriz de Daño
* **Matriz de Penetración de Armas:** Sistema de 3 capas (Escudo con regeneración, Blindaje ligero/medio/pesado y Casco).
* **Armamento Especializado:** Efectividad diferenciada para armamento Láser, Iónico, Plasma y Gravitatorio.
* **Rebalanceo de Naves Híbridas:** Integración de naves como la *Fragata Pesada* y el *Nómada Negro* para combate sin puntos ciegos.

### 👁️ 3. Modo Espectador en Primera Persona (Spectator Mode)
* Herramienta administrativa y de depuración integrada (`game.php?page=spectate`) para supervisar en vivo la vista de cualquier jugador o bot sin perder la sesión del administrador.

### 💎 4. Economía Rebalanceada & Contenedores
* **Contenedores de Producción Real:** Apertura basada en la producción horaria dinámica del imperio ($MSE_M, MSE_C, MSE_D$).
* **Economía Premium Equilibrada:** Gestión controlada de Materia Oscura y Antimateria sin desvirtuar la progresión militar.
* **Automatización Industrial:** Soporte para colas de desarrollo optimizadas y gestión eficiente de recursos excedentes.

### 🌐 5. Infraestructura & Conectividad
* Servidor configurado para alta disponibilidad con túnel persistente Cloudflare Zero Trust con reconexión automática y soporte multiplataforma.

---

## 📂 Estructura del Repositorio

```text
├── cache/            # Almacenamiento temporal de plantillas compiladas
├── chat/             # Sistema de mensajería y chat en tiempo real
├── includes/         # Núcleo del juego, clases, APIs, bots y misiones
│   ├── classes/      # Clases del motor (Database, FleetFunctions, etc.)
│   │   ├── bot/      # Núcleo de IA (Kernel, Intel, EngineSimulator, Gateway)
│   │   ├── cronjob/  # Tareas programadas de bots, eventos y estadísticas
│   │   └── missions/ # Casos de misión (Ataques, Reciclajes, MIP, Espionaje)
├── install/          # Asistente de instalación de base de datos
├── language/         # Traducciones del sistema (Español, Inglés, etc.)
├── scripts/          # Lógica del cliente y scripts de automatización
├── styles/           # Temas visuales, estilos CSS, plantillas TPL y recursos
└── tools/            # Herramientas de mantenimiento, auditoría e inspección
```

---

## 🛠️ Requisitos de Instalación

* **Servidor Web:** Apache 2.4 o Nginx
* **PHP:** PHP 7.4+ (con extensiones `pdo_mysql`, `gd`, `curl`, `mbstring`, `json`)
* **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+

---

## 📜 Licencias & Reconocimientos

NovaRush MMO se distribuye bajo los términos y licencias de código abierto heredados de **2Moons** (GNU GPL v3 / Licencias correspondientes) y **New-Star**. Todos los derechos sobre las marcas y conceptos originales pertenecen a sus respectivos autores.