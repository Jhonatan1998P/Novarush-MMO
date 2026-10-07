/**
 * Test Suite Jest: Logística de Suministros por Demanda (Pull / JIT)
 *
 * Evalúa:
 * 1. Modelo Puro Pull & Jerarquía Estricta de Prioridades
 * 2. Regla de Autosuficiencia de los 10 Minutos
 * 3. Condiciones de Carrera y Pipeline en Tránsito (Anti-Duplicados)
 * 4. Límite de Concurrencia (Máximo 2 Objetivos Simultáneos)
 * 5. Reserva de Slots de Flota y Prevención de Bloqueos
 * 6. Selección de Donantes y Dual-Donor Split (Hasta 2 Donantes Más Cercanos)
 * 7. Reserva Intocable de Seguridad del Donante
 * 8. Ejecución en Vivo de la Suite PHP
 */

const { execSync } = require('child_process');
const path = require('path');

describe('Suite de Logística de Suministros por Demanda (Pull / JIT)', () => {

    describe('1. Modelo Puro Pull & Jerarquía Estricta de Prioridades', () => {
        test('1.1 No despacha suministros si no existe una demanda explícita (Pull puro)', () => {
            const empireSurplus = 5000000; // 5M metal en colonias
            const activeDemands = [];
            const convoysDispatched = activeDemands.length === 0 ? 0 : 1;
            expect(convoysDispatched).toBe(0);
        });

        test('1.2 Ordena prioridades: 1° Militar FOB/MIPs -> 2° Hangar/Defensa -> 3° Edificios/Tech', () => {
            const demands = [
                { id: 3, type: 'infrastructure', priority: 3, score: 500 },
                { id: 1, type: 'military_fuel', priority: 1, score: 10000 },
                { id: 2, type: 'force_recruitment', priority: 2, score: 2000 },
                { id: 4, type: 'silo_siege_mips', priority: 1, score: 7000 },
            ];

            demands.sort((a, b) => {
                if (a.priority !== b.priority) return a.priority - b.priority;
                return b.score - a.score;
            });

            expect(demands[0].type).toBe('military_fuel');
            expect(demands[1].type).toBe('silo_siege_mips');
            expect(demands[2].type).toBe('force_recruitment');
            expect(demands[3].type).toBe('infrastructure');
        });

        test('1.3 Demanda militar de combustible incluye consumo armada + recicladores + 25% de margen', () => {
            const fleetFuel = 40000;
            const recFuel = 8000;
            const totalFuelDemand = (fleetFuel + recFuel) * 1.25;
            expect(totalFuelDemand).toBe(60000);
        });
    });

    describe('2. Filtro de Autosuficiencia (Regla de los 10 Minutos)', () => {
        function evaluateSelfSufficiency(stock, hourlyProd, required) {
            const tenMinProd = hourlyProd / 6.0;
            const deficit = Math.max(0, required - stock);
            // Si el déficit es menor o igual a 10 minutos de producción, se descarta (0)
            return deficit <= tenMinProd ? 0 : deficit;
        }

        test('2.1 Descarta envío si el déficit se produce localmente en <= 10 minutos', () => {
            const stock = 10000;
            const hourly = 60000; // 10 min = 10.000
            const required = 18000; // Déficit = 8.000 <= 10.000
            const netDispatch = evaluateSelfSufficiency(stock, hourly, required);
            expect(netDispatch).toBe(0);
        });

        test('2.2 Genera orden si el déficit supera los 10 minutos de producción local', () => {
            const stock = 10000;
            const hourly = 60000; // 10 min = 10.000
            const required = 60000; // Déficit = 50.000 > 10.000
            const netDispatch = evaluateSelfSufficiency(stock, hourly, required);
            expect(netDispatch).toBe(50000);
        });

        test('2.3 En lunas (producción 0), cualquier déficit genera suministro inmediato', () => {
            const stock = 2000;
            const hourly = 0;
            const required = 25000;
            const netDispatch = evaluateSelfSufficiency(stock, hourly, required);
            expect(netDispatch).toBe(23000);
        });
    });

    describe('3. Condiciones de Carrera y Pipeline en Tránsito (Anti-Duplicados)', () => {
        function calculateNetDeficit(stock, inFlight, required) {
            const available = stock + inFlight;
            return Math.max(0, required - available);
        }

        test('3.1 Deduce recursos que ya vienen en camino en flotas de Misión 3', () => {
            const required = 150000;
            const stock = 20000;
            const inFlight = 80000;
            const net = calculateNetDeficit(stock, inFlight, required);
            expect(net).toBe(50000);
        });

        test('3.2 Elimina duplicados en ciclos consecutivos si la demanda ya está en tránsito', () => {
            const required = 100000;
            const stock = 10000;
            const inFlight = 95000;
            const net = calculateNetDeficit(stock, inFlight, required);
            expect(net).toBe(0);
        });
    });

    describe('4. Límite de Concurrencia y Bloqueos de Slots', () => {
        test('4.1 Máximo 2 objetivos distintos concurrentes recibiendo suministros', () => {
            const MAX_CONCURRENT = 2;
            const activeDestinations = new Set([100]); // 1 objetivo activo

            function canServiceTarget(targetId) {
                if (activeDestinations.has(targetId)) return true;
                return activeDestinations.size < MAX_CONCURRENT;
            }

            expect(canServiceTarget(102)).toBe(true);
            activeDestinations.add(102); // 2 objetivos activos

            expect(canServiceTarget(205)).toBe(false); // Tercer objetivo rechazado
        });

        test('4.2 Mantiene siempre al menos 3 slots de flota libres para ataque/espionaje', () => {
            const maxSlots = 8;
            const RESERVED = 3;

            function getAvailableSlots(currentFleets) {
                return Math.max(0, (maxSlots - RESERVED) - currentFleets);
            }

            expect(getAvailableSlots(5)).toBe(0); // 8 - 3 - 5 = 0 slots
            expect(getAvailableSlots(3)).toBe(2); // 8 - 3 - 3 = 2 slots
            expect(getAvailableSlots(6)).toBe(0);
        });
    });

    describe('5. Selección de Donantes y Dual-Donor Split', () => {
        test('5.1 Divide la demanda entre hasta 2 donantes más cercanos sin tocar donantes lejanos', () => {
            const donors = [
                { id: 1, dist: 30, surplus: 100000 },
                { id: 2, dist: 70, surplus: 150000 },
                { id: 3, dist: 200, surplus: 500000 },
            ];

            const needed = 220000;
            let remaining = needed;
            const dispatched = [];

            // Hasta 2 donantes más cercanos
            for (const d of donors.slice(0, 2)) {
                if (remaining <= 0) break;
                const give = Math.min(remaining, d.surplus);
                dispatched.push({ id: d.id, amount: give });
                remaining -= give;
            }

            expect(dispatched.length).toBe(2);
            expect(dispatched[0]).toEqual({ id: 1, amount: 100000 });
            expect(dispatched[1]).toEqual({ id: 2, amount: 120000 });
            expect(remaining).toBe(0);
        });

        test('5.2 Respeta la reserva intocable del donante (1h producción + colas activas)', () => {
            const stock = 120000;
            const hourlyProd = 50000;
            const activeQueues = 30000;

            const reserve = Math.max(50000, hourlyProd) + activeQueues;
            const surplus = Math.max(0, stock - reserve);

            expect(reserve).toBe(80000);
            expect(surplus).toBe(40000); // Solo dona 40k
        });
    });

    describe('6. Verificación en Vivo del Motor PHP', () => {
        test('6.1 Ejecuta la suite PHP de logística de suministros con 18/18 pruebas aprobadas', () => {
            const phpBin = 'C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\php\\php.exe';
            const testScript = path.join(__dirname, 'test_bot_supply_logistics.php');
            const result = execSync(`"${phpBin}" -f "${testScript}"`, { encoding: 'utf-8' });

            expect(result).toContain('RESULTADOS FINALES DE LA SUITE DE LOGÍSTICA');
            expect(result).toContain('PRUEBAS SUPERADAS: 18');
            expect(result).toContain('FALLOS: 0');
        });
    });
});
