<?php
    namespace App;

    //Logica de funcionamento da class Route(Rotas) está na class abastrata Bootstrap
    use MF\Init\Bootstrap;

    //Class somente com requisitos funcionais da aplicação
    class Route extends Bootstrap{

        protected function initRoutes(){
            $routes['index'] = array(
                'route' => '/',
                'controller' => 'indexController',
                'action' => 'index'
            );

            $routes['autenticar'] = array(
                'route' => '/autenticar',
                'controller' => 'authController',
                'action' => 'autenticar'
            );

            $routes['home'] = array(
                'route' => '/home',
                'controller' => 'dashboardController',
                'action' => 'home'
            );

            $routes['sair'] = array(
                'route' => '/sair',
                'controller' => 'authController',
                'action' => 'sair'
            );

            $routes['iotReceive_index'] = array(
                'route' => '/index.php/iot/receive',
                'controller' => 'dashboardController',
                'action' => 'receiveIoT'
            );

            $routes['iotData_index'] = array(
                'route' => '/index.php/iot/data',
                'controller' => 'dashboardController',
                'action' => 'getIoTData'
            );

            $routes['generateReport'] = array(
                'route' => '/api/generate-report',
                'controller' => 'dashboardController',
                'action' => 'generateReport'
            );

            $routes['generateLessons'] = array(
                'route' => '/api/generate-lessons',
                'controller' => 'dashboardController',
                'action' => 'generateLessons'
            );

            //Populando o atributo routes herdado da class Bootstrap
            $this->setRoutes($routes);
        }
   
    }
?>