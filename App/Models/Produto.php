<?php

    namespace App\Models;

    //Logica de funcionamento da class Info está na class abastrata Model
    use MF\Models\Model;

    class Produto extends Model{

        public function getProdutos(){

            $query = "SELECT id, descricao, preco
                    FROM tb_produtos";

            return $this->bancoDados->query($query)->fetchAll();
        }
    }

?>