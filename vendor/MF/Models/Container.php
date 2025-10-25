<?php

    namespace MF\Models;

    use App\Connection;

    //Class chamada em IndexController
    class Container{
        
        public static function getModel($model){

            //Instanciando modelo de formar interativa
            $class = "\\App\\Models\\".ucfirst($model);

            //Instancia de conexão
            $conexao = Connection::getDb();

            return new $class($conexao);
        }
    }


?>