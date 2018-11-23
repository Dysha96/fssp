<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.11.2018
 * Time: 21:19
 */
$start = microtime(true);
set_time_limit(0);

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
    $obj = json_decode($response, true);

    return $obj['response']['task'];
}

function postSearchGroupTask($token, $customers)
{
    $client = new Client();

    $response = $client->post(
        'https://api-ip.fssprus.ru/api/v1.0/search/group',
        [
            GuzzleHttp\RequestOptions::JSON => [
                'token' => $token,
                'request' => $customers
            ]
        ]
    )->getBody();
    $obj = json_decode($response, true);

    return $obj['response']['task'];
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
    $obj = json_decode($response, true);
    var_dump($obj);
    return $obj['response']['status'];
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
    $obj = json_decode($response, true);
    return $obj['response'];
}

$inCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_IN'));
$inCsv->setFlags(SplFileObject::READ_CSV);
$outCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_OUT'), 'a');
$outError = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_ERROR'), 'a');

//Пропуск первой строки
$maxLimit = 15;
$count = 0;

///обработка через get запросы, больше 100 обработать проблематично, выходи 429
//try {
//    while ($inCsv->valid() && $count < -1) {
//        $count++;
//
//        $inArray = $inCsv->fgetcsv();
//        $customerId = $inArray[0];
//        $region = $inArray[1];
//        $firstName = $inArray[2];
//        $middleName = $inArray[3];
//        $lastName = $inArray[4];
//        $inBirthDate = explode('-', $inArray[5]);
//        $outBirthDate = $inBirthDate[2] . '.' . $inBirthDate[1] . '.' . $inBirthDate[0];
//
//        $outArray = ['https://www.zaymer.ru/operator/client_info/' . $customerId];
//
//        $task = getSearchPhysicalTask($token, $firstName, $lastName, $outBirthDate, $region, $secondName);
//
//
//        sleep(3);
//        $results = getResults($token, $task)['result'];
//        $status = $results[0]['status'];
//
//        $attempt = 0;
//        while ($status != 0 && $attempt < $maxLimit) {
//            $attempt++;
//
//            sleep(3);
//            $results = getResults($token, $task)['result'];
//            $status = $results[0]['status'];
//        }
//
//        if ($status != 0) {
//            echo 'https://www.zaymer.ru/operator/client_info/' . $customerId . ' Был пропушен';
//            $outError->fputcsv($inArray);
//            continue;
//        }
//
//
//        if (empty($result)) {
//            $outArray[] = 'Нет записи в ФССП по региону ' . $region;
//        } else {
//            $outArray[] = json_encode($result);
//        }
//
//        $outCsv->fputcsv($outArray);
//    }
//} catch (RequestException $e) {
//    echo nl2br(Psr7\str($e->getRequest()));
//    if ($e->hasResponse()) {
//        echo nl2br(Psr7\str($e->getResponse()));
//    }
//}

try {
    while ($inCsv->valid() && $count < 1) {
//        $count++;

        $customers = [];
        $outArray = [];
        $type = 1;

        for ($i = 0; $i < 65 && $inCsv->valid(); $i++) {
            $customerInfo = $inCsv->fgetcsv();
            $customerId = $customerInfo[0];
            $region = $customerInfo[1];
            $firstName = $customerInfo[2];
            $secondName = $customerInfo[3];
            $lastName = $customerInfo[4];
            $inBirthDate = explode('-', $customerInfo[5]);
            $outBirthDate = $inBirthDate[2] . '.' . $inBirthDate[1] . '.' . $inBirthDate[0];

            $customer = [
                'type' => $type,
                'params' => [
                    'region' => $region,
                    'firstname' => $firstName,
                    'secondname' => $secondName,
                    'lastname' => $lastName,
                    'birthdate' => $outBirthDate,
                ]
            ];

            $customers[$i] = $customer;
            $outArray [$i][] = $customerId;
        }

        sleep(5);
        $task = postSearchGroupTask($token, $customers);
        sleep(5);
        $response = getResults($token, $task);
        $status = $response['status'];
        $status = 1;

        $attempt = 0;
        while ($status != 0 && $attempt < $maxLimit) {

            sleep($attempt + 5);
            $response = getResults($token, $task);
            $status = $response['status'];
            $attempt++;
        }

        if ($status != 0) {
            foreach ($customers as $key => $customer) {
                $customerError = $customer['params'];
                array_unshift($customerError, $outArray[$key][0]);
                $outError->fputcsv($customerError);
            }

            continue;
        }

        $overallResults = $response['result'];

        foreach ($overallResults as $key => $result) {
            $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));

            $status = $result['status'];
            if ($status != 0) {
                $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
                $outError->fputcsv($outArray);
                continue;
            }

            $foundInformation = $result['result'];
            $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
            if (empty($foundInformation)) {
                $outArray[$key][] = 'Нет записи в ФССП по данным параметрам';
            } else {
                $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));
                $iteratorFoundInformation = new RecursiveIteratorIterator(new RecursiveArrayIterator($foundInformation));
                $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorFoundInformation, false));
            }
            $outCsv->fputcsv($outArray[$key]);
        }

    }
} catch (RequestException $e) {
    echo nl2br(Psr7\str($e->getRequest()));
    if ($e->hasResponse()) {
        echo nl2br(Psr7\str($e->getResponse()));
    }
}

echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.';
