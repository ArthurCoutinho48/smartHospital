<?php

namespace App\Models;

// Importa a classe base Model, responsável por guardar a conexão PDO
use MF\Models\Model;

/*
|--------------------------------------------------------------------------
| UsuarioModel
|--------------------------------------------------------------------------
| Esse model é utilizado pelo AuthController para realizar autenticação.
| Ele acessa diretamente a tabela "usuarios" no banco de dados.
*/
class usuarioModel extends Model {

    // Atributos privados que representam as colunas do banco
    private $id_usuario;
    private $email;
    private $username;

    // Métodos mágicos para leitura dinâmica de propriedades
    public function __get($atributo){
        return $this->$atributo;
    }

    // Métodos mágicos para escrita dinâmica de propriedades
    public function __set($atributo, $valor){
        $this->$atributo = $valor;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODO autenticar()
    |--------------------------------------------------------------------------
    | Realiza validação de login consultando a tabela "usuarios".
    | Caso e-mail e senha correspondam, popula o objeto com id e username.
    */
    public function autenticar(){

        // Query que verifica usuário com e-mail e senha informados
        $query = 'SELECT
                    id_usuario,
                    usermane,
                    email,
                    senha
                  FROM usuarios
                  WHERE
                    email = :email AND senha = :senha';

        // Prepara a query para evitar SQL injection
        $stmt = $this->bancoDados->prepare($query);

        // Substitui parâmetros pelos valores enviados pelo controller
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':senha', $this->__get('password')); 
        // OBS: senha está sendo checada em texto puro → inseguro em produção!

        $stmt->execute();

        // Retorna registro encontrado ou false
        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Se achou ID e e-mail, autenticação foi bem-sucedida
        if (!empty($usuario['id_usuario']) && !empty($usuario['email'])) {

            // Preenche o model com dados do usuário autenticado
            $this->__set('id_usuario', $usuario['id_usuario']);
            $this->__set('usermane', $usuario['usermane']); // coluna tem nome "usermane"
        }

        // Retorna a própria instância do model
        return $this;
    }

}

?>
