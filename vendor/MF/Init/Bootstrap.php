<?php

    namespace MF\Init;

    //Class que contem metódos relativos ao framework
    abstract class Bootstrap{

        private $routes;

        //Quando o objeto for instanciado no index será ativido automaticamente o construtor da class
        public function __construct(){
            //Será ativado a função initRoutes dando inicio em todo o processamento do site
            $this->initRoutes();
            //Logo em seguida chamaremos o metódo run, passando como paramero o metódo que retorna o path acesso pelo usuario
            $this->run($this->getUrl());
        }

        public function getRoutes(){
            return $this->routes;
        }

        public function setRoutes(array $routes){
            $this->routes = $routes;
        }

        //Faz com que a class que herdar o Bootstrap, inicialize um metódo initRoutes.
        abstract protected function initRoutes();

        protected function run($url){

            foreach ($this->getRoutes() as $path => $route){

                //Verificando a url recebida do index e redirecionando para a pasta Controller
                if ($url == $route['route']){
                    //Instanciando a class indexController de forma dinamica
                    //A função ucfirst faz com que a primeira letra do nosso controller fique maiscula
                    $class = "App\\Controllers\\".ucfirst($route['controller']);
                    $controller = new $class;

                    //Disparando o metódo da class indexController de forma dinamica
                    $action = $route['action'];
                    $controller->$action();
                };
            }
        }

        //Recupera o path que esta na URL
        protected function getUrl(){
            return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
    }

?>