<?php

namespace App\Models;

// Importa classe Model base, que contém PDO e utilidades padrão
use MF\Models\Model;

class IoTModel extends Model {

    // Caminhos para salvar arquivos JSON
    private $dataDir;
    private $latestFile;
    private $logFile;

    // Métodos mágicos para acessar atributos privados
    public function __get($atributo){
        return $this->$atributo;
    }

    public function __set($atributo, $valor){
        $this->$atributo = $valor;
    }

    /*
    |--------------------------------------------------------------------------
    | CONSTRUTOR
    |--------------------------------------------------------------------------
    | Recebe o PDO da classe pai (injeção de dependência).
    | Cria automaticamente a pasta /public/iot caso não exista.
    | Prepara caminhos dos arquivos JSON para armazenar dados.
    */
    public function __construct(\PDO $db){
        parent::__construct($db);

        // Caminho da pasta onde os arquivos JSON serão salvos
        $this->dataDir = __DIR__ . '/../../public/iot';

        // Cria a pasta com permissão total se não existir
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }

        // Arquivo com o último dado recebido do simulador IoT
        $this->latestFile = $this->dataDir . '/iot_latest.json';

        // Arquivo com o histórico de todos os dados recebidos
        $this->logFile   = $this->dataDir . '/iot_log.json';
    }

    /*
    |--------------------------------------------------------------------------
    | SALVA O ÚLTIMO DADO (arquivo JSON)
    |--------------------------------------------------------------------------
    | Grava o JSON mais recente vindo do simulador Python.
    | Adiciona automaticamente timestamp UTC (ISO 8601).
    */
    public function saveLatest(array $payload): bool {
        $payload['received_at'] = gmdate("Y-m-d\TH:i:s\Z"); // timestamp universal

        // Salva em JSON formatado
        return file_put_contents(
            $this->latestFile,
            json_encode($payload, JSON_PRETTY_PRINT)
        ) !== false;
    }

    /*
    |--------------------------------------------------------------------------
    | ADICIONA REGISTRO AO HISTÓRICO (arquivo JSON)
    |--------------------------------------------------------------------------
    | Empilha o novo JSON dentro de iot_log.json,
    | criando o arquivo se ele não existir.
    */
    public function appendLog(array $payload): bool {
        $log = [];

        // Se já existe log, carrega como array
        if (file_exists($this->logFile)) {
            $log = json_decode(file_get_contents($this->logFile), true);
            if (!is_array($log)) $log = [];
        }

        // Adiciona item no final
        $log[] = $payload;

        // Sobrescreve o arquivo com histórico atualizado
        return file_put_contents(
            $this->logFile,
            json_encode($log, JSON_PRETTY_PRINT)
        ) !== false;
    }

    /*
    |--------------------------------------------------------------------------
    | RETORNA O ÚLTIMO REGISTRO DO ARQUIVO
    |--------------------------------------------------------------------------
    | Usado pelo dashboard para exibir dados em tempo real.
    */
    public function getLatest(): ?array {
        if (!file_exists($this->latestFile)) return null;

        $data = json_decode(file_get_contents($this->latestFile), true);

        return is_array($data) ? $data : null;
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCA ÚLTIMO REGISTRO DO BANCO DE DADOS (iot_log)
    |--------------------------------------------------------------------------
    | Para quando o IoT for gravado na tabela SQL ao invés de JSON.
    */
    public function getLatestFromDB(): ?array {

        $query = 'SELECT
                    *
                  FROM iot_log
                  ORDER BY ts DESC
                  LIMIT 1';

        $stmt = $this->bancoDados->prepare($query);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        // Converte formato SQL → formato JSON esperado
        return $this->formatDbRowToPayload($row);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCA HISTÓRICO DO BANCO (até N registros)
    |--------------------------------------------------------------------------
    | Retorna lista crescente (ASC) para facilitar gráficos de linha.
    */
    public function getHistory(int $limit = 100): array {

        $query = 'SELECT
                    ts,
                    temperature,
                    humidity,
                    oxygen,
                    co2,
                    energy_instant,
                    energy_total,
                    energy_peak
                  FROM iot_log
                  ORDER BY ts ASC
                  LIMIT :limit';

        $stmt = $this->bancoDados->prepare($query);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$rows) return [];

        // Converte cada linha do banco em formato JSON padronizado
        return array_map(fn($r) => $this->formatDbRowToPayload($r), $rows);
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERTE REGISTRO DO BANCO PARA O MESMO PADRÃO DO JSON
    |--------------------------------------------------------------------------
    | Útil para manter consistência entre dados vindos do arquivo e da base SQL.
    */
    private function formatDbRowToPayload(array $row): array {
        return [
            'timestamp'   => $row['ts'] ?? null,
            'temperature' => isset($row['temperature']) ? (float)$row['temperature'] : null,
            'humidity'    => isset($row['humidity']) ? (float)$row['humidity'] : null,
            'oxygen'      => isset($row['oxygen']) ? (float)$row['oxygen'] : null,
            'co2'         => isset($row['co2']) ? (int)$row['co2'] : null,
            'energy' => [
                'instant' => isset($row['energy_instant']) ? (float)$row['energy_instant'] : null,
                'total'   => isset($row['energy_total']) ? (float)$row['energy_total'] : null,
                'peak'    => isset($row['energy_peak']) ? (float)$row['energy_peak'] : null,
            ]
        ];
    }


}

?>
