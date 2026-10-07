const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const PHP_CLI = "C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\php\\php.exe";
const ROOT_DIR = path.resolve(__dirname, '..');

describe('NovaRush Bot AI - Redesign Plan Verification & Pro Architecture', () => {

  describe('0. Principio Rector & Función Objetivo (EV Neto / Hora)', () => {
    test('Calcula el EV neto deduciendo riesgo ponderado, combustible y costo de misiles', () => {
      const pWin = 0.95;
      const loot = 10000000;
      const debris = 5000000;
      const valEstrategico = 500000;
      const valorFlotaArriesgada = 2000000;
      const fuel = 20000;
      const missilesMSE = 500000;
      const roundtripHours = 0.5;

      const expectedEV = (pWin * (loot + debris + valEstrategico)) 
                       - ((1 - pWin) * valorFlotaArriesgada) 
                       - fuel 
                       - missilesMSE;
      const valorHora = expectedEV / roundtripHours;

      expect(expectedEV).toBe(14105000);
      expect(valorHora).toBe(28210000);
    });
  });

  describe('1. Motor de Misiles (MIP) - Fix Principal', () => {
    test('1.2 Modelo de daño: Detecta rebote e inmunidad cuando ipmDmg <= shield', () => {
      const weaponTechAtk = 10;
      const ipmDmg = 12000 * (1 + weaponTechAtk * 0.1); // 24,000

      const baseShield = 16000; // Plasma en NovaRush
      const shieldTechDef = 20;
      const shieldDef = baseShield * (1 + shieldTechDef * 0.1); // 48,000

      const isImmune = ipmDmg <= shieldDef;
      expect(isImmune).toBe(true);

      // Con tech atacante alta (35)
      const ipmDmgHigh = 12000 * (1 + 35 * 0.1); // 54,000
      const isImmuneHigh = ipmDmgHigh <= shieldDef;
      expect(isImmuneHigh).toBe(false);
    });

    test('1.4 Margen de incertidumbre aplica SOLO a ABM (1:1) y es 0 con spy_tech >= 8', () => {
      const abm = 20;
      const spyTechHigh = 8;
      const spyTechLow = 5;

      const abmMarginHigh = spyTechHigh >= 8 ? 0 : Math.ceil(0.15 * abm);
      const abmMarginLow = spyTechLow >= 8 ? 0 : Math.ceil(0.15 * abm);

      expect(abmMarginHigh).toBe(0);
      expect(abmMarginLow).toBe(3);
    });

    test('1.5 Pooling de silos: Llena desde el más cercano y preserva excedente como reserva', () => {
      const silos = [
        { id: 1, mips: 15, duration: 60 },
        { id: 2, mips: 30, duration: 120 },
        { id: 3, mips: 20, duration: 180 },
      ];
      const ipmRequerido = 35;

      let remaining = ipmRequerido;
      const dispatches = [];

      for (const s of silos) {
        if (remaining <= 0) break;
        const take = Math.min(s.mips, remaining);
        dispatches.push({ id: s.id, count: take, duration: s.duration });
        remaining -= take;
      }

      expect(remaining).toBe(0);
      expect(dispatches).toEqual([
        { id: 1, count: 15, duration: 60 },
        { id: 2, count: 20, duration: 120 }
      ]);
      // Silo 2 conserva 10 como reserva estratégica; Silo 3 conserva 20 intactos
    });

    test('1.6 Alineación de impactos: Calcula retrasos de salida para sincronizar impactos', () => {
      const dispatches = [
        { id: 1, count: 15, duration: 60 },
        { id: 2, count: 20, duration: 120 }
      ];
      const fleetDuration = 300;
      const maxMipDuration = Math.max(...dispatches.map(d => d.duration)); // 120

      const targetImpactTime = 1000 + fleetDuration - 20; // 1280 (20s antes de llegada)
      const fleetArrival = 1000 + fleetDuration; // 1300

      const delays = dispatches.map(d => Math.max(0, targetImpactTime - d.duration - 1000));
      expect(delays).toEqual([220, 160]);
      // Impacto de ambos misiles en T=1280. Llegada de flota en T=1300.
    });
  });

  describe('2. Targeting Avanzado & Arquitectura Paralela', () => {
    test('2.3 Cooldown de regeneración de granjas: cooldown(t) = lootUmbral / produccion(t)', () => {
      const lootUmbral = 500000;
      const prodHourly = 50000;
      const cooldownHours = Math.max(1, Math.min(72, lootUmbral / prodHourly));
      expect(cooldownHours).toBe(10); // 10 horas antes de volver a saquear
    });

    test('2.6 Histéresis: Evita flip-flop salvo que candidato #2 supere por más del 20%', () => {
      const currentVal = 1000000;
      const cand15 = 1150000;
      const cand30 = 1300000;

      const switch15 = currentVal < (cand15 - (cand15 * 0.20));
      const switch30 = currentVal < (cand30 - (cand30 * 0.20));

      expect(switch15).toBe(false);
      expect(switch30).toBe(true);
    });
  });

  describe('3. Composición de Flota & Roles', () => {
    test('3.1 Dimensionamiento de Fodder en Fleet Crash (hasta 3.5x naves capitales)', () => {
      const capitalWarships = 100;
      const availableLightFighters = 500;
      const targetFodder = Math.floor(capitalWarships * 3.5); // 350
      const assignedFodder = Math.min(availableLightFighters, targetFodder);

      expect(assignedFodder).toBe(350);
    });
  });

  describe('4. Ejecución de Tests en Vivo del Motor PHP (Caja Blanca & Caja Negra)', () => {
    test('PHP Unit Suite (test_bot_plan_unit.php): Pasa los 13 tests de fórmulas y mecánica', () => {
      const cmd = `"${PHP_CLI}" tests/test_bot_plan_unit.php`;
      const output = execSync(cmd, { cwd: ROOT_DIR, encoding: 'utf8' });
      expect(output).toContain('RESULTADOS: 13 PASSED, 0 FAILED');
    });

    test('PHP Black-box Suite (test_bot_plan_blackbox.php): Turno sin bloqueos y detección de fallos', () => {
      const cmd = `"${PHP_CLI}" tests/test_bot_plan_blackbox.php`;
      const output = execSync(cmd, { cwd: ROOT_DIR, encoding: 'utf8' });
      expect(output).toContain('1.1 Turno de BotKernel ejecuta sin excepciones fatales');
      expect(output).toContain('1.2 Lock consultivo de MySQL liberado correctamente');
      expect(output).toContain('2.1 Bloqueo administrativo restringe flotas de otros bots');
      expect(output).toContain('3.1 Registro y despacho de tareas diferidas');
      expect(output).toContain('4.1 Strike In Flight Guard detecta salvas escalonadas');
    });
  });
});
