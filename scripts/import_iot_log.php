<?php
// scripts/import_iot_log.php (VERSÃO COMPLETA E CORRIGIDA)
// Executar: php scripts\import_iot_log.php

// =========================
// CONFIGURAÇÃO DO BANCO
// =========================
$dbHost = 'localhost';
$dbName = 'smart_hospital';   // altere para o seu DB caso necessário
$dbUser = 'root';             // padrão XAMPP
$dbPass = '';                 // padrão XAMPP (vazio)

// =========================
// CAMINHO DO JSON DE LOG
// =========================
$jsonPath = 'C:\\xampp\\projeto\\smartHospital\\public\\iot\\iot_log.json';

// Arquivo de log de erros da importação
$errLogPath = __DIR__ . '/import_errors.log';

// Commit parcial a cada X registros
$batchSize = 500;

// Remove log antigo
if (file_exists($errLogPath)) unlink($errLogPath);

// =========================
// Função robusta de leitura de timestamps
// =========================
function tryParseTimestamp($raw) {
    if (empty($raw)) return null;

    // epoch numérico
    if (is_numeric($raw)) {
        $v = (int)$raw;
        if ($v > 1000000000000) $v = intval($v/1000); // ms -> s
        return date('Y-m-d H:i:s', $v);
    }

    // string
    if (is_string($raw)) {
        $s = trim($raw);

        // remove Z
        $s = rtrim($s, 'Z');

        if (strpos($s, 'T') !== false) {
            $s2 = substr($s, 0, 19);
            $s2 = str_replace('T', ' ', $s2);
            $t = strtotime($s2);
            if ($t !== false) return date('Y-m-d H:i:s', $t);
        }

        $t = strtotime($s);
        if ($t !== false) return date('Y-m-d H:i:s', $t);
    }

    return null;
}

// =========================
// Importação
// =========================

if (!file_exists($jsonPath)) {
    echo "Arquivo nao encontrado: $jsonPath\n";
    exit(1);
}

try {
    // Conexão PDO
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $json = json_decode(file_get_contents($jsonPath), true);
    if (!is_array($json)) {
        echo "JSON invalido\n";
        exit(1);
    }

    $sql = "INSERT INTO iot_log 
        (device_id, ts, temperature, humidity, oxygen, co2,
         energy_instant, energy_total, energy_peak, raw_json)
        VALUES 
        (:device_id, :ts, :temperature, :humidity, :oxygen, :co2,
         :energy_instant, :energy_total, :energy_peak, :raw_json)";

    $stmt = $pdo->prepare($sql);

    $count = 0;
    $errCount = 0;

    $pdo->beginTransaction();

    foreach ($json as $i => $row) {

        // tenta obter timestamp
        $rawTs = $row['timestamp'] ?? $row['time'] ?? $row['ts'] ?? null;
        $ts = tryParseTimestamp($rawTs);

        if (!$ts) {
            $errCount++;
            file_put_contents(
                $errLogPath,
                "[" . date('c') . "] índice $i: timestamp inválido -> " . var_export($rawTs, true) . "\n",
                FILE_APPEND
            );
            continue;
        }

        $stmt->execute([
            ':device_id' => $row['device_id'] ?? null,
            ':ts' => $ts,
            ':temperature' => $row['temperature'] ?? null,
            ':humidity' => $row['humidity'] ?? null,
            ':oxygen' => $row['oxygen'] ?? null,
            ':co2' => $row['co2'] ?? null,
            ':energy_instant' => $row['energy']['instant'] ?? null,
            ':energy_total' => $row['energy']['total'] ?? null,
            ':energy_peak' => $row['energy']['peak'] ?? null,
            ':raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE)
        ]);

        $count++;

        if ($count % $batchSize === 0) {
            $pdo->commit();
            echo "Commit parcial: $count registros importados...\n";
            $pdo->beginTransaction();
        }
    }

    $pdo->commit();

    echo "=========================\n";
    echo "Importação concluída.\n";
    echo "Inseridos: $count\n";
    echo "Ignorados (timestamp inválido): $errCount\n";
    if ($errCount > 0) {
        echo "Log de erros: $errLogPath\n";
    }
    echo "=========================\n";

} catch (PDOException $e) {
    echo "Erro PDO: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) $pdo->rollBack();
    exit(1);
}
