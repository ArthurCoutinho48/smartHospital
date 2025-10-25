<?php

    namespace MF\Controller;

    //Class que contem metódos relativos ao framework
    abstract class Action{

        protected $view;

        public function __construct(){
            //Criando um objeto vazio
            $this->view = new \stdClass();
        }

        protected function render($view, $layout){
            $this->view->page = $view;

            //Caso o layout padrão não exista ou não esteje informado, será redirecionado apenos o arquivo normal
            if(file_exists("../App/Views/$layout.phtml")){
                require_once "../App/Views/$layout.phtml";
            }else{
                $this->content();
            }
            ;
        }

        protected function content(){
            //Recupera o nome do caminho do controlador
            $classAtual = get_Class($this);
            //Apaga toda as palavras até que sobre somente o nome da class do controlador
            $classAtual = str_replace('App\\Controllers\\', '', $classAtual);
            //Retirar a palavra chave da pasta a onde está armazenada a view
            $classAtual = strtolower(str_replace('Controller', '', $classAtual));

            //Faz com que não haja redundancia de dados, fazendo com que o redirecionamento seja feito de forma interativa
            require_once "../App/Views/$classAtual/".$this->view->page.".phtml";
        }
    }
?>