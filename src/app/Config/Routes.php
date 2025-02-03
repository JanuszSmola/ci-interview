<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('api/coasters', function($routes) {
    $routes->post('/', 'CoastersController::registerCoaster');
    $routes->post('(:num)/wagons', 'CoastersController::addCart/$1');
    $routes->delete('(:num)/wagons/(:num)', 'CoastersController::removeCart/$1/$2');
    $routes->put('(:num)', 'CoastersController::editCoaster/$1');
});
