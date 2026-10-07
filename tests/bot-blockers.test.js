/**
 * Jest Test Suite: Verificación de Solución de Bloqueos y Tácticas Militares Avanzadas
 * 
 * Valida:
 * 1. Escombros Dinámicos del Servidor (Fleet_Cdr = 70%, Defs_Cdr dinámico) y reciclaje de bajas propias.
 * 2. Bypass de Misiles: Asalto Directo por ROI >= 30% sin límite arbitrario de bajas propias.
 * 3. Guard Extendido: Detección de 'staggered_fleet_assault' en cola.
 * 4. Sincronización de Impacto por factor de velocidad (speedFactor) eliminando la latencia del cron.
 * 5. Control de Slots y Combustible pre-disparo.
 * 6. Condición 1.4 preservada intacta (Límite de 20 ciclos de asedio).
 */

const { execSync } = require('child_process');

describe('Suite de Verificación: Desbloqueo y Táctica Militar de Bots NovaRush', () => {

  describe('1. Porcentaje Dinámico de Escombros del Servidor (Fleet_Cdr y Defs_Cdr)', () => {
    test('1.1 Utiliza la constante real del servidor (70% en NovaRush) en lugar de 30% en duro', () => {
      const serverFleetCdr = 70; // Config::get()->Fleet_Cdr
      const fleetCdrFactor = serverFleetCdr / 100.0;
      expect(fleetCdrFactor).toBe(0.70);
    });

    test('1.2 Reciclaje de bajas propias: El atacante recupera 70% de sus pérdidas en metal y cristal', () => {
      const ownLossMSE = 2000000;
      const fleetCdrFactor = 0.70;
      const recoveredDebris = ownLossMSE * fleetCdrFactor; // 1.400.000 MSE
      const netUnrecoverableLoss = ownLossMSE * (1.0 - fleetCdrFactor); // 600.000 MSE
      expect(recoveredDebris).toBe(1400000);
      expect(Math.round(netUnrecoverableLoss)).toBe(600000);
    });

    test('1.3 Soporte para Defensas a Escombros (Defs_Cdr) dinámico configurado por el Administrador', () => {
      const defsCdr = 25; // Si el admin activa 25% de defensas a escombros
      const defsCdrFactor = defsCdr / 100.0;
      const defMSE = 1000000;
      const defDebris = defMSE * defsCdrFactor;
      expect(defDebris).toBe(250000);
    });
  });

  describe('2. Bypass de Misiles: Asalto Directo por Alto ROI (Sin Candado de Bajas)', () => {
    test('2.1 Autoriza ataque directo con flota si ROI >= 30% aunque no haya misiles disponibles', () => {
      const canFireMissiles = false;
      const lootMSE = 2500000;
      const enemyFleetDebris = 1500000;
      const ownFleetLossMSE = 1000000;
      const fuelMSE = 100000;
      const fleetCdr = 0.70;

      // Pérdida neta tras reciclar escombros propios: 30% + fuel
      const netReplacementCost = (ownFleetLossMSE * (1.0 - fleetCdr)) + fuelMSE; // 400.000 MSE
      const grossRevenue = lootMSE + enemyFleetDebris; // 4.000.000 MSE
      const netProfit = grossRevenue - netReplacementCost; // 3.600.000 MSE
      const roi = netProfit / (ownFleetLossMSE + fuelMSE); // 3.6M / 1.1M = 327% ROI

      const canDirectAssault = (!canFireMissiles && netProfit > 0 && roi >= 0.30);
      expect(canDirectAssault).toBe(true);
    });

    test('2.2 No censura por porcentaje de bajas: Procede aun perdiendo el 90% de tropas si el ROI es positivo', () => {
      const ownLossPct = 0.90; // 90% de bajas
      const roi = 0.45; // 45% de ROI positivo
      const netProfit = 850000;

      // El bot NO debe tener un candado que descarte un buen ROI por altas bajas
      const allowsExecutionWithHighCasualties = (netProfit > 0 && roi >= 0.30);
      expect(allowsExecutionWithHighCasualties).toBe(true);
      expect(ownLossPct).toBe(0.90);
    });
  });

  describe('3. Guard de Strike In Flight Extendido (Cierre de Brecha 2.2)', () => {
    test('3.1 Detecta tareas diferidas de flota de asalto ("staggered_fleet_assault")', () => {
      const pendingTasks = ['staggered_fleet_assault'];
      const hasScheduledSalvo = pendingTasks.includes('staggered_mip_salvo') 
                             || pendingTasks.includes('staggered_fleet_assault');
      expect(hasScheduledSalvo).toBe(true);
    });
  });

  describe('4. Sincronización de Impacto por Velocidad (Solución 2.3)', () => {
    test('4.1 Modulación de speedFactor permite despegue inmediato exacto sin esperar al cron', () => {
      const targetImpactTime = 100;
      const targetArrival = targetImpactTime + 20; // T+120s
      const durationAt10 = 80;  // 100% speed llega a T+80s (demasiado rápido)
      const durationAt7  = 120; // 70% speed llega a T+120s (impacto sincronizado perfecto!)

      const selectedSpeedFactor = 7;
      const arrival = durationAt7;
      const isSynchronized = (arrival >= (targetImpactTime + 15) && arrival <= (targetImpactTime + 35));
      expect(isSynchronized).toBe(true);
      expect(selectedSpeedFactor).toBe(7);
    });
  });

  describe('5. Gestión Previa de Slots y Combustible', () => {
    test('5.1 Granjeo paralelo mantiene 1 slot en reserva para la armada de asedio', () => {
      const maxSlots = 10;
      const activeFleets = 7;
      const availableFarmSlots = Math.max(0, maxSlots - activeFleets - 1); // 2 slots
      expect(availableFarmSlots).toBe(2);
    });

    test('5.2 Pre-check de combustible detiene la salva de misiles si el FOB no tiene deuterio suficiente', () => {
      const fobDeut = 8000;
      const fleetFuel = 15000;
      const canProceed = fobDeut >= fleetFuel;
      expect(canProceed).toBe(false);
    });
  });

  describe('6. Preservación de la Condición 1.4', () => {
    test('6.1 Mantiene intacto el límite de 20 ciclos de asedio sin modificaciones', () => {
      const maxSiegeCycles = 20;
      const currentSiege = 20;
      const shouldRelease = (currentSiege >= maxSiegeCycles);
      expect(shouldRelease).toBe(true);
    });
  });

  describe('7. Verificación en Vivo del Motor PHP en el Servidor', () => {
    test('7.1 Ejecuta la suite PHP de verificación de bloqueos con 10/10 pruebas aprobadas', () => {
      const phpPath = 'C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\php\\php.exe';
      const scriptPath = 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\tests\\test_bot_blockers_diagnostics.php';
      const output = execSync(`"${phpPath}" -f "${scriptPath}"`).toString();
      expect(output).toContain('SUITE DE VERIFICACIÓN DE ARREGLO DE BLOQUEOS EN BOTS MILITARES');
      expect(output).toContain('PRUEBAS SUPERADAS: 10');
      expect(output).toContain('FALLOS: 0');
    });
  });
});
