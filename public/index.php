<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.11.2018
 * Time: 21:19
 */
$start = microtime(true);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();


$token = getenv('TOKEN');

function getSearchPhysicalTask($token, $firstName, $lastName, $birthDate = '', $region = -1, $secondName = '')
{
    $client = new Client();

    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/search/physical',
        [
            GuzzleHttp\RequestOptions::QUERY => [
                'token' => $token,
                'region' => $region,
                'firstname' => $firstName,
                'secondname' => $secondName,
                'lastname' => $lastName,
                'birthdate' => $birthDate,
            ]
        ]
    )->getBody();
    $obj = json_decode($response);

    return $obj->{'response'}->{'task'};
}

function postSearchGroupTask($token, $firstName, $lastName, $birthDate = '', $region = -1, $secondName = '')
{
    $client = new Client();
    $type = 1;
    $response = $client->post(
        'https://api-ip.fssprus.ru/api/v1.0/search/group',
        [
            GuzzleHttp\RequestOptions::QUERY => [
                'token' => $token,
                'request' => [
                    [
                        'type' => $type,
                        'params' => [
                            'region' => $region,
                            'firstname' => $firstName,
                            'secondname' => $secondName,
                            'lastname' => $lastName,
                            'birthdate' => $birthDate,
                        ]
                    ],
                    [
                        'type' => $type,
                        'params' => [
                            'region' => $region,
                            'firstname' => $firstName,
                            'secondname' => $secondName,
                            'lastname' => $lastName,
                            'birthdate' => $birthDate,
                        ]
                    ],
                ]
            ]
        ]
    )->getBody();
    $obj = json_decode($response);

    return $obj->{'response'}->{'task'};
}

function getStatus($token, $task)
{
    $client = new Client();
    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/status',
        [
            GuzzleHttp\RequestOptions::QUERY => [
                'token' => $token,
                'task' => $task,
            ]
        ]
    )->getBody();
    $obj = json_decode($response);
    return $obj->{'response'}->{'status'};
}

function getProgress($token, $task)
{
    $client = new Client();
    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/status',
        [
            GuzzleHttp\RequestOptions::QUERY => [
                'token' => $token,
                'task' => $task,
            ]
        ]
    )->getBody();
    $obj = json_decode($response);
    return $obj->{'response'}->{'progress'};
}

function getResult($token, $task)
{
    $client = new Client();
    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/result',
        [
            GuzzleHttp\RequestOptions::QUERY => [
                'token' => $token,
                'task' => $task,
            ]
        ]
    )->getBody();
    $obj = json_decode($response);

    return $obj->{'response'}->{'result'}[0]->{'result'};
}

function getResults($token, $task)
{
    $client = new Client();
    $response = $client->get(
        'https://api-ip.fssprus.ru/api/v1.0/result',
        [
            'query' => [
                'token' => $token,
                'task' => $task,
            ]
        ]
    )->getBody();
    $obj = json_decode($response);

    return $obj->{'response'}->{'result'};
}


$firstName = 'Андрей';
$lastName = 'Русанов';
$birthDate = '12.07.1996';
$region = 42;
$secondName = 'Павлович';
//var_dump(getSearchPhysicalTask($token, $firstName, $lastName, $birthDate, $region, $secondName));
$type = 1;

//var_dump(postSearchGroupTask($token, $firstName, $lastName, $birthDate, $region, $secondName));
//var_dump(getStatus($token,'0f352f6d-2252-4c16-8a39-92c343682c6e'));
//var_dump(getResults($token, '0f352f6d-2252-4c16-8a39-92c343682c6e'));

//$task = '624e2789-7e38-4041-8eb3-c8f19677cf17';
//var_dump(getResult($token, 'ff5e97b2-5b0a-4392-a829-32d4d5061891'));
//if (!empty(getResult($token, $task))) {
//    var_dump('Есть запись в ФССП');
//} else {
//    var_dump('Нет записи в ФССП');
//}
//
//$task = 'd9dfa40b-28a6-4be1-a68b-a191bc4552f3';
////var_dump(getResult($token,$task));
//if (!empty(getResult($token, $task))) {
//    var_dump('Есть запись в ФССП');
//} else {
//    var_dump('Нет записи в ФССП');
//}
//
//
//$task = '496edb2c-02ce-4283-8f91-2690482a78a8';
//if (!empty(getResult($token, $task))) {
//    var_dump('Есть записись в ФССП');
//} else {
//    var_dump('Нет записи в ФССП');
//}

$inCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_IN'));
$inCsv->setFlags(SplFileObject::READ_CSV);
$outCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_OUT'), 'w');
$outError = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_ERROR'), 'w');


//Пропуск первой строки
$inCsv->seek(1);
$maxLimit = 5;
$count = 0;

try {
    while ($inCsv->valid() && $count < 3) {
//    $count++;

        $inArray = $inCsv->fgetcsv();
        $customerId = $inArray[0];
        $region = $inArray[1];
        $firstName = $inArray[2];
        $middleName = $inArray[3];
        $lastName = $inArray[4];
        $inBirthDate = explode('-', $inArray[5]);
        $outBirthDate = $inBirthDate[2] . '.' . $inBirthDate[1] . '.' . $inBirthDate[0];

        $outArray = ['https://www.zaymer.ru/operator/client_info/' . $customerId];

        $task = getSearchPhysicalTask($token, $firstName, $lastName, $birthDate, $region, $secondName);

        sleep(2);
        $status = getStatus($token, $task);

        $attempt = 0;
        while (getStatus($token, $task) != 0 && $attempt < $maxLimit) {
            $attempt++;

            sleep(2);
            $status = getStatus($token, $task);
        }

        if ($status != 0) {
            echo 'https://www.zaymer.ru/operator/client_info/' . $customerId . ' Был пропушен';
            $outError->fputcsv($inArray);
            continue;
        }

        sleep(2);
        $result = getResult($token, $task);

        if (!empty($result)) {
            $outArray[] = json_encode($result);
        } else {
            $outArray[] = 'Нет записи в ФССП по региону ' . $region;
        }

        $outCsv->fputcsv($outArray);
    }
} catch (RequestException $e) {
    echo Psr7\str($e->getRequest());
    if ($e->hasResponse()) {
        echo Psr7\str($e->getResponse());
    }
}
echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
