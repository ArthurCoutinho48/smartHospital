<?php

namespace App\Controllers;

// Controller base, com mecanismos de renderização e carregamento de views
use MF\Controller\Action;

// Container responsável por instanciar models e gerenciar dependências
use MF\Models\Container;

// Model auxiliar de IoT
use App\Models\IoTModel;

class dashboardController extends Action {

    // Página principal (Dashboard)
    public function home() {

        // Instancia o model de dashboard, responsável pelas queries principais
        $dashboard = Container::getModel('DashboardModel');

        // Instancia o model de IoT, responsável pelos dados dos sensores
        $iotModel = Container::getModel('IoTModel');

        // Chamadas ao banco de dados para gerar os dados do dashboard
        $dashboardVelocity = $dashboard->getVelocity();
        $dashboardCPI      = $dashboard->getCPI();
        $dashboardSPI      = $dashboard->getSPI();
        $dashboardBurndown = $dashboard->getBurndown();
        $dashboardBacklog  = $dashboard->getBacklog();
        $dashboardRiscos   = $dashboard->getRiscos();

        // Último dado de IoT salvo (JSON), retorna array ou null
        $iotLatest = $iotModel->getLatest();

        // Histórico de até 500 registros
        $history = $iotModel->getHistory(500);

        // Envia os dados para a view
        $this->view->dadoVelocity   = $dashboardVelocity;
        $this->view->dadoCPI        = $dashboardCPI;
        $this->view->dadoSPI        = $dashboardSPI;
        $this->view->dadoBurndown   = $dashboardBurndown;
        $this->view->dadoBacklog    = $dashboardBacklog;
        $this->view->dadoRiscos     = $dashboardRiscos;

        // Conversão do último JSON para string legível pelo frontend
        $this->view->iotDataJson    = $iotLatest ? json_encode($iotLatest, JSON_UNESCAPED_UNICODE) : 'null';

        // Histórico completo também enviado para uso em gráficos
        $this->view->iotHistoryJson = $history;

        // Valida login
        $this->validacaoLogin();

        // Somente usuário ID = 1 pode acessar o dashboard
        if (isset($_SESSION) &&
            !empty($_SESSION['id_usuario']) &&
            $_SESSION['id_usuario'] == 1) {

            // Renderiza home com layout padrão
            $this->render('home', 'layout');
        } else {

            // Usuário sem permissão volta para login
            header('Location: /');
        }
    }

    // Recebe JSON via POST (enviado pelo simulador Python)
    // Responsável por salvar o dado e registrar no histórico
    public function receiveIoT() {
        header("Content-Type: application/json; charset=utf-8");

        // Pega o corpo bruto da requisição POST
        $raw = file_get_contents("php://input");
        if (!$raw) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request vazio']);
            return;
        }

        // Decodifica JSON enviado
        $json = json_decode($raw, true);
        if ($json === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
            return;
        }

        try {
            // Instancia o model e grava os dados
            $iotModel = Container::getModel('IoTModel');

            $saved = $iotModel->saveLatest($json); // salva último dado
            $logged = $iotModel->appendLog($json); // adiciona no histórico

            if ($saved) {
                echo json_encode(['status' => 'ok']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Falha ao salvar']);
            }

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }

    // Retorna via GET o último JSON salvo, para uso do frontend (AJAX)
    public function getIoTData() {
        header_remove();
        header("Content-Type: application/json; charset=utf-8");

        // Caminho do arquivo contendo o último dado JSON
        $file = __DIR__ . '/../../public/iot/iot_latest.json';

        if (!file_exists($file)) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Sem dados ainda']);
            return;
        }

        // Lê e valida JSON
        $raw = file_get_contents($file);
        $json = json_decode($raw, true);

        if ($json === null) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido no arquivo']);
            return;
        }

        // Retorna JSON para o frontend
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Chama o script Python para gerar relatório em JSON
    public function generateReport() {
        header("Content-Type: application/json; charset=utf-8");

        $pyFile = realpath(__DIR__ . '/../../iot/report_generator.py');

        if (!$pyFile || !file_exists($pyFile)) {
            http_response_code(500);
            echo json_encode(["error" => "Script Python não encontrado em: $pyFile"]);
            return;
        }

        // Caminho do Python (Windows)
        $pythonCmd = 'C:\\Users\\arthu\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

        // Pega payload recebido
        $raw = file_get_contents("php://input");
        $payload = $raw ?: "{}";

        // Comando executado via shell
        $cmd = '"' . $pythonCmd . '" "' . $pyFile . '" generate_report';
        $full = "echo " . escapeshellarg($payload) . " | " . $cmd . " 2>&1";

        // Executa script Python
        $output = shell_exec($full);

        if ($output === null) {
            http_response_code(500);
            echo json_encode(["error" => "shell_exec retornou null"]);
            return;
        }

        // Decodifica resposta JSON do Python
        $decoded = json_decode($output, true);

        if ($decoded === null) {
            http_response_code(500);
            echo json_encode(["error" => "Python retornou saída inválida (não JSON)", "raw" => $output]);
            return;
        }

        echo json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    // Gera "insights" ou lições aprendidas via Python
    public function generateLessons() {
        header("Content-Type: application/json; charset=utf-8");

        $pyFile = realpath(__DIR__ . '/../../iot/report_generator.py');

        if (!$pyFile || !file_exists($pyFile)) {
            http_response_code(500);
            echo json_encode(["error" => "Script Python não encontrado em: $pyFile"]);
            return;
        }

        $pythonCmd = 'C:\\Users\\arthu\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

        $raw = file_get_contents("php://input");
        $payload = $raw ?: "{}";

        $cmd = '"' . $pythonCmd . '" "' . $pyFile . '" generate_lessons';
        $full = "echo " . escapeshellarg($payload) . " | " . $cmd . " 2>&1";

        $output = shell_exec($full);

        if ($output === null) {
            http_response_code(500);
            echo json_encode(["error" => "shell_exec retornou null"]);
            return;
        }

        $decoded = json_decode($output, true);

        if ($decoded === null) {
            http_response_code(500);
            echo json_encode(["error" => "Python retornou saída inválida (não JSON)", "raw" => $output]);
            return;
        }

        echo json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    // Verifica se o usuário está logado
    public function validacaoLogin() {
        session_start();

        if (isset($_SESSION) && !empty($_SESSION['id_usuario'])) {

            // Apenas mantém o ID disponível
            $arrayUsermane = [
                'id_usuario' => $_SESSION['id_usuario']
            ];

        } else if (isset($_SESSION)) {

            // Se sessão existe mas não há login, destrói
            session_destroy();
        }
    }
}

?>