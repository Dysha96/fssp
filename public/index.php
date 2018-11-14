<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.11.2018
 * Time: 21:19
 */

use GuzzleHttp\Client;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

$client = new Client();

$token = getenv('TOKEN');
//echo $token;
//$response = $client->get(
//    'https://api-ip.fssprus.ru/api/v1.0/search/physical',
//    [
//        'query' => [
//            'token' => $token,
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
            'token' => $token,
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
                'token' => $token,
                'task' => '919a6471-59e6-4aba-867e-625fec379f3f',
            ]
        ]
    )->getBody();
    $obj = json_decode($response);
    var_dump($obj);
}
