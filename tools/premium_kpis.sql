-- ====================================================================
-- TELEMETRÍA Y MONITORIZACIÓN ECONÓMICA DE NOVARUSH (FASE 12)
-- ====================================================================

-- 1. Emisión y destrucción diaria por moneda (Últimos 7 días)
SELECT 
    currency,
    DATE(FROM_UNIXTIME(created_at)) AS dia,
    SUM(CASE WHEN delta > 0 THEN delta ELSE 0 END) AS emitido,
    SUM(CASE WHEN delta < 0 THEN ABS(delta) ELSE 0 END) AS destruido,
    ROUND(SUM(CASE WHEN delta < 0 THEN ABS(delta) ELSE 0 END) / 
          NULLIF(SUM(CASE WHEN delta > 0 THEN delta ELSE 0 END), 0), 2) AS ratio_destruido_emitido
FROM uni1_premium_ledger
WHERE created_at > UNIX_TIMESTAMP() - 7 * 86400
GROUP BY currency, dia
ORDER BY currency, dia;

-- 2. Emisión por Fuente (Top generadores de dinero)
SELECT 
    currency, 
    source, 
    SUM(delta) AS total_emitido, 
    COUNT(*) AS transacciones
FROM uni1_premium_ledger 
WHERE created_at > UNIX_TIMESTAMP() - 7 * 86400 AND delta > 0
GROUP BY currency, source 
ORDER BY currency, total_emitido DESC;

-- 3. Consumo por Sumidero (Top gastos de jugadores)
SELECT 
    currency, 
    source, 
    ABS(SUM(delta)) AS total_gastado, 
    COUNT(*) AS transacciones
FROM uni1_premium_ledger 
WHERE created_at > UNIX_TIMESTAMP() - 7 * 86400 AND delta < 0
GROUP BY currency, source 
ORDER BY currency, total_gastado DESC;

-- 4. Distribución de saldos actual y percentiles
SELECT 
    COUNT(*) AS total_cuentas,
    ROUND(AVG(darkmatter), 0) AS media_mo,
    MAX(darkmatter) AS max_mo,
    ROUND(AVG(antimatter), 0) AS media_am,
    MAX(antimatter) AS max_am,
    ROUND(AVG(stardust), 0) AS media_sd,
    MAX(stardust) AS max_sd,
    ROUND(AVG(container), 0) AS media_ct,
    MAX(container) AS max_ct
FROM uni1_users;
