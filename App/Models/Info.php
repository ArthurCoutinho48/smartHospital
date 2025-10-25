<?php

    namespace App\Models;

    //Logica de funcionamento da class Info está na class abastrata Model
    use MF\Models\Model;

    class Info extends Model{

        public function getInfo(){

            $query = "SELECT titulo, descricao
                    FROM tb_info";

            return $this->bancoDados->query($query)->fetchAll();
        }
    }


?>