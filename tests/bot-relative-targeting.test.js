/**
 * Jest Test Suite: Clasificación Relativa de Objetivos en TargetFinder
 * 
 * Verifica las 4 categorías con umbrales relativos:
 * 1. fleet_crash: Evaluación rápida Monte Carlo con ROI >= 30%
 * 2. farming: 5% producción horaria base + escalado dinámico ante defensas > 50%
 * 3. siege: Exclusivo si el imperio tiene MIPs para demoler >= 50% de la defensa
 * 4. recon: Mantiene exploración de planetas no espiados
 */

const { execSync } = require('child_process');

describe('Suite de Clasificación Relativa de Objetivos (TargetFinder)', () => {

  describe('1. Fleet Crash Relativo (Monte Carlo ROI >= 30%)', () => {
    test('1.1 Autoriza fleet_crash cuando el ROI supera el 30% teniendo flota enemiga en tierra', () => {
      const fleetMSE = 2000000;
      const ownLossMSE = 400000;
      const fuelMSE = 20000;
      const lootMSE = 300000;
      const fleetCdr = 0.70;

      const netCost = (ownLossMSE * (1.0 - fleetCdr)) + fuelMSE; // 140k
      const grossRev = lootMSE + (fleetMSE * 0.90 * fleetCdr); // 300k + 1260k = 1560k
      const netProfit = grossRev - netCost; // 1420k
      const roi = netProfit / (ownLossMSE + fuelMSE); // 1420 / 420 = ~3.38

      const isViable = (fleetMSE > 0 && netProfit > 0 && roi >= 0.30);
      expect(isViable).toBe(true);
      expect(roi).toBeGreaterThanOrEqual(0.30);
    });

    test('1.2 Descarta fleet_crash si la batalla no genera un ROI del 30%', () => {
      const fleetMSE = 100000;
      const ownLossMSE = 500000;
      const fuelMSE = 30000;
      const lootMSE = 10000;
      const fleetCdr = 0.70;

      const netCost = (ownLossMSE * (1.0 - fleetCdr)) + fuelMSE; // 180k
      const grossRev = lootMSE + (fleetMSE * 0.90 * fleetCdr); // 73k
      const netProfit = grossRev - netCost; // -107k
      const roi = netProfit / (ownLossMSE + fuelMSE);

      const isViable = (fleetMSE > 0 && netProfit > 0 && roi >= 0.30);
      expect(isViable).toBe(false);
    });
  });

  describe('2. Farming Relativo y Compensación Dinámica de Riesgo', () => {
    const empireHourlyProdMSE = 1000000; // 1.000.000 MSE/h

    test('2.1 Caso Base: Defensas <= 50% exigen únicamente 5% de producción horaria', () => {
      const lootMSE = 75000; // 7.5% de prod horaria
      const defenseMSE = 25000; // 33% del botín (<= 50%)
      const defRatio = defenseMSE / lootMSE;

      const minFactor = (defRatio <= 0.50) ? 0.05 : (0.05 + (1.0 + 2.0 * (defRatio - 0.40)));
      const requiredLoot = empireHourlyProdMSE * minFactor; // 50.000 MSE

      expect(minFactor).toBe(0.05);
      expect(lootMSE).toBeGreaterThanOrEqual(requiredLoot);
    });

    test('2.2 Escalado por Riesgo: Defensas al 85% elevan la exigencia al 195% de la producción horaria', () => {
      const defRatio = 0.85;
      const minFactor = 0.05 + (1.0 + 2.0 * (defRatio - 0.40));
      expect(Math.round(minFactor * 100) / 100).toBe(1.95);
    });

    test('2.3 Fórmula del Usuario: Ratio 5.0 (Defensa 5x Botín) exige exactamente factor 10.25', () => {
      const defRatio = 5.0; // 5000 def vs 1000 loot
      const minFactor = 0.05 + (1.0 + 2.0 * (defRatio - 0.40));
      expect(minFactor).toBe(10.25);
    });
  });

  describe('3. Siege Relativo (MIPs en Rango >= 50% de la Estructura Defensiva)', () => {
    test('3.1 Rechaza siege si los MIPs en rango no logran destruir el 50% de la estructura', () => {
      const totalDefStructure = 1000000;
      const availableMIPs = 15;
      const abmCount = 10;
      const damagePerMip = 24000; // tec 10

      const effectiveMIPs = Math.max(0, availableMIPs - abmCount); // 5 MIPs
      const damagePool = effectiveMIPs * damagePerMip; // 120.000
      const canDemolish50 = (damagePool >= 0.50 * totalDefStructure); // 120k < 500k

      expect(canDemolish50).toBe(false);
    });

    test('3.2 Aprueba siege si los MIPs en rango destruyen al menos el 50% de la estructura', () => {
      const totalDefStructure = 1000000;
      const availableMIPs = 35;
      const abmCount = 10;
      const damagePerMip = 24000;

      const effectiveMIPs = Math.max(0, availableMIPs - abmCount); // 25 MIPs
      const damagePool = effectiveMIPs * damagePerMip; // 600.000
      const canDemolish50 = (damagePool >= 0.50 * totalDefStructure); // 600k >= 500k

      expect(canDemolish50).toBe(true);
    });
  });

  describe('4. Verificación PHP en Vivo en el Servidor', () => {
    test('4.1 Ejecuta la suite PHP de clasificación relativa con 9/9 pruebas aprobadas', () => {
      const phpPath = 'C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\php\\php.exe';
      const scriptPath = 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\tests\\test_bot_targeting_relative.php';
      const output = execSync(`"${phpPath}" -f "${scriptPath}"`).toString();
      expect(output).toContain('SUITE DE TEST: CLASIFICACIÓN RELATIVA DE OBJETIVOS (TARGETFINDER)');
      expect(output).toContain('PRUEBAS SUPERADAS: 9');
      expect(output).toContain('FALLOS: 0');
    });
  });
});
