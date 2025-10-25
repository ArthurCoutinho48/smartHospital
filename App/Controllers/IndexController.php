<?php

    namespace App\Controllers;

    //Logica de funcionamento da class indexController está na class abastrata Action
    use MF\Controller\Action;
    //Logica para instanciar objeto para recuperação das informações no banco de dados
    use MF\Models\Container;

    //Models
    use App\Models\Produto;
    use App\Models\Info;


    class indexController extends Action{

        public function index(){

            //Passando para o metodo estatico o nome do model para que seja feita a coneção com banco de dados
            $produto = Container::getModel('Produto');

            //Chamando o metodo para recuperar as informações do banco de dados
            $produtos = $produto->getProdutos();

            //Dados recebidos pela pasta Model
            $this->view->dados = $produtos;

            //Redirecionando para a página solicitada
            $this->render('index', 'layout1');
        }

        public function sobreNos(){

            //Passando para o metodo estatico o nome do model para que seja feita a coneção com banco de dados
            $info = Container::getModel('Info');

            //Chamando o metodo para recuperar as informações do banco de dados
            $informacoes = $info->getInfo();

            //Dados recebidos pela pasta Model
            $this->view->dados = $informacoes;

            //Redirecionando para a página solicitada, passando o nome do arquivo e os dados
            $this->render('sobreNos', 'layout1');
        }
    }
?>