<?php

    namespace App\Controllers;

    //Logica de funcionamento da class indexController está na class abastrata Action
    use MF\Controller\Action;
    //Logica para instanciar objeto para recuperação das informações no banco de dados
    use MF\Models\Container;

    class indexController extends Action{

        public function index(){

            //Redirecionando para a página solicitada
            $this->render('index', 'layout');
            
        }
    }
?>