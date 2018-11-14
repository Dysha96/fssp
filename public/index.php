<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.11.2018
 * Time: 21:19
 */

use GuzzleHttp\Client;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$client = new Client();


//$response = $client->get(
//    'https://api-ip.fssprus.ru/api/v1.0/search/physical',
//    [
//        'query' => [
//            'token' => 'judyVpLo0EJV',
//            'region' => '42',
//            'firstname' => 'Андрей',
//            'secondname' => 'Павлович',
//            'lastname  ' => 'Русанов',
//            'birthdate' => '12.07.1996',
//        ]
//    ]
//)->getBody();
//$obj = json_decode($response);
//
//var_dump($obj);

$response = $client->get(
    'https://api-ip.fssprus.ru/api/v1.0/status',
    [
        'query' => [
            'token' => 'judyVpLo0EJV',
            'task' => '919a6471-59e6-4aba-867e-625fec379f3f',
        ]
    ]
)->getBody();
$obj = json_decode($response);
var_dump($obj->{'response'}->{'status'});


if ($obj->{'response'}->{'status'} = 0) {
    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/result',
        [
            'query' => [
                'token' => 'judyVpLo0EJV',
                'task' => '919a6471-59e6-4aba-867e-625fec379f3f',
            ]
        ]
    )->getBody();
    $obj = json_decode($response);
    var_dump($obj);
}
