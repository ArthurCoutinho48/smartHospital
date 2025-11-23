<?php

namespace App\Models;

// Importa classe base que contém a conexão PDO e estrutura padrão de Models
use MF\Models\Model;

class dashboardModel extends Model {

    // Getter genérico (método mágico)
    public function __get($atributo){
        return $this->$atributo;
    }

    // Setter genérico (método mágico)
    public function __set($atributo, $valor){
        $this->$atributo = $valor;
    }

    /*
    |--------------------------------------------------------------------------
    | VELOCITY
    |--------------------------------------------------------------------------
    | Soma dos pontos de todas as tarefas no backlog.
    | Usado para calcular produtividade total.
    */
    public function getVelocity() {
        $query = 'SELECT
                    COALESCE(SUM(pontos), 0) AS velocity_pontos
                  FROM
                    backlog_simple';

        $stmt = $this->bancoDados->query($query);
        return $stmt->fetch(\PDO::FETCH_ASSOC); // retorna array com "velocity_pontos"
    }

    /*
    |--------------------------------------------------------------------------
    | CPI – Cost Performance Index
    |--------------------------------------------------------------------------
    | Fórmula: CPI = EV / AC (Earned Value / Actual Cost)
    | Calculado globalmente para todas as sprints.
    */
    public function getCPI() {

        // Primeiro verifica se existem registros para evitar divisões inválidas
        $query = 'SELECT COUNT(*) FROM sprint_financeiro';

        $cntStmt = $this->bancoDados->query($query);
        $cnt = (int) $cntStmt->fetchColumn();

        // Só calcula se houver dados
        if ($cnt > 0) {

            $query = 'SELECT CASE WHEN
                        SUM(ac) = 0
                    THEN NULL ELSE
                        ROUND(SUM(ev) / SUM(ac), 2)
                    END AS cpi
                    FROM sprint_financeiro';

            $stmt = $this->bancoDados->query($query);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Retorna valor tratado
            return ['cpi' => isset($row['cpi']) ? (float)$row['cpi'] : null];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SPI – Schedule Performance Index
    |--------------------------------------------------------------------------
    | Fórmula: SPI = EV / PV (Earned Value / Planned Value)
    */
    public function getSPI() {

        // Verifica se existem registros
        $query = 'SELECT COUNT(*) FROM sprint_financeiro';

        $cntStmt = $this->bancoDados->query($query);
        $cnt = (int) $cntStmt->fetchColumn();

        if ($cnt > 0) {

            $query = 'SELECT CASE WHEN
                        SUM(pv) = 0
                    THEN NULL ELSE
                        ROUND(SUM(ev) / SUM(pv), 2)
                    END AS spi
                    FROM sprint_financeiro';

            $stmt = $this->bancoDados->query($query);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return ['spi' => isset($row['spi']) ? (float)$row['spi'] : null];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BURNDOWN
    |--------------------------------------------------------------------------
    | Retorna duas listas:
    |  - Ideal (ideal_points)
    |  - Real (actual_points)
    | Ambas agrupadas por sprint_id e ordenadas por data.
    | O frontend reconstrói o gráfico com esses dados.
    */
    public function getBurndown() {

        // LINHA IDEAL
        $qIdealAll = 'SELECT sprint_id, log_date, ideal_points
                      FROM burndown_simple
                      ORDER BY sprint_id ASC, log_date ASC';

        $idealStmt = $this->bancoDados->query($qIdealAll);
        $idealRows = $idealStmt->fetchAll(\PDO::FETCH_ASSOC);

        // LINHA REAL
        $qRealAll = 'SELECT sprint_id, log_date, actual_points
                     FROM burndown_simple
                     ORDER BY sprint_id ASC, log_date ASC';

        $realStmt = $this->bancoDados->query($qRealAll);
        $realRows = $realStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Mapeia dados da linha ideal
        $ideal = array_map(function($r){
            return [
                'sprint_id' => (int)$r['sprint_id'],
                'log_date'  => $r['log_date'],
                'points'    => (int)$r['ideal_points']
            ];
        }, $idealRows);

        // Mapeia dados da linha real
        $real = array_map(function($r){
            return [
                'sprint_id' => (int)$r['sprint_id'],
                'log_date'  => $r['log_date'],
                'points'    => (int)$r['actual_points']
            ];
        }, $realRows);

        return [
            'ideal' => $ideal,
            'real'  => $real
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BACKLOG
    |--------------------------------------------------------------------------
    | Retorna lista completa de tarefas, organizadas por sprint e ordem de criação.
    | Contém título, descrição, prioridade, story points e data de conclusão.
    */
    public function getBacklog() {

        $query = 'SELECT 
                    id, sprint_id, codigo, titulo, descricao,
                    prioridade, pontos, concluido_em
                  FROM backlog_simple
                  ORDER BY sprint_id ASC, id ASC';

        $stmt = $this->bancoDados->query($query);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | MATRIZ DE RISCOS
    |--------------------------------------------------------------------------
    | Retorna a lista de riscos registrados para cada sprint.
    */
    public function getRiscos() {

        $query = 'SELECT
                    id, sprint_id, risco, probabilidade,
                    impacto, acao_mitigacao
                  FROM riscos_simple
                  ORDER BY sprint_id ASC, id ASC';

        $stmt = $this->bancoDados->query($query);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}

?>
