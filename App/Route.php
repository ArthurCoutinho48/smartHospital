<?php
    namespace App;

    //Logica de funcionamento da class Route(Rotas) está na class abastrata Bootstrap
    use MF\Init\Bootstrap;

    //Class somente com requisitos funcionais da aplicação
    class Route extends Bootstrap{

        protected function initRoutes(){
            $routes['home'] = array(
                'route' => '/',
                'controller' => 'indexController',
                'action' => 'index'
            );

            $routes['sobre_nos'] = array(
                'route' => '/sobre_nos',
                'controller' => 'indexController',
                'action' => 'sobreNos'
            );

            //Populando o atributo routes herdado da class Bootstrap
            $this->setRoutes($routes);
        }
   
    }
?>