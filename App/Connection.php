<?php

    namespace App;

    class Connection{

        public static function getDb(){

            //Variaveis de conexão
            $servidor = "localhost";
            $usuario =  "root";
            $senha = "";
            $banco = "mvc";

            try{
                //Instanciando minha conexão
                $conexao = new \PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario, $senha);

                return $conexao; 
            } catch(\PDOException $erro){
                echo 'Falha na Conexão: ' . $erro->getMessage();
            }
        }
        
    }

?>