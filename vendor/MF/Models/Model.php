<?php

    namespace MF\Models;

    //Class chamada nos Models
    abstract class Model{
        
        protected $bancoDados;

        public function __construct(\PDO $bancoDados){
            $this->bancoDados = $bancoDados;
        } 
    }

?>