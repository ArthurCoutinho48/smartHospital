<?php

namespace App\Controllers;

// Lógica de funcionamento do controlador base (Action) está na classe abstrata Action
use MF\Controller\Action;

// Container responsável por instanciar os models e gerenciar dependências
use MF\Models\Container;

// Model específico
use App\Models\Usuario;

// Este controller realiza todas as autenticações do sistema
class authController extends Action {

    // Método responsável pela primeira autenticação do usuário (login)
    public function autenticar() {

        // Instancia o model de usuário usando o Container
        $usuario = Container::getModel('UsuarioModel');

        // Recebe os dados do formulário de login (e-mail e senha)
        // Usa os métodos mágicos __set do model para popular os atributos
        $usuario->__set('email', $_POST['email']);
        $usuario->__set('password', $_POST['password']); 

        // Executa o método autenticar() no model, que verifica o usuário no banco de dados
        $usuario->autenticar();

        // Se o ID de usuário foi retornado, significa que autenticou com sucesso
        if ($usuario->__get('id_usuario') != '') {

            // Inicia uma sessão e salva o ID do usuário logado
            session_start();
            $_SESSION['id_usuario'] = $usuario->__get('id_usuario');

            // Retorna a rota de redirecionamento pós-login
            echo '/home';

        } else {
            // Caso não haja autenticação válida, retorna rota de erro
            echo '/erro';
        }
    }

    // Método responsável por encerrar a sessão do usuário
    public function sair() {
        session_start();      // Garante que a sessão esteja ativa
        session_destroy();    // Destrói a sessão atual
        header('Location: /'); // Redireciona para a página inicial
    }
}

?>