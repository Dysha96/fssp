<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.11.2018
 * Time: 21:19
 */
$start = microtime(true);
set_time_limit(0);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();


$token = getenv('TOKEN');

//function getSearchPhysicalTask($token, $firstName, $lastName, $birthDate = '', $region = -1, $secondName = '')
//{
//    $client = new Client();
//
//    $response = $client->get(
//        'https://api-ip.fssprus.ru/api/v1.0/search/physical',
//        [
//            GuzzleHttp\RequestOptions::QUERY => [
//                'token' => $token,
//                'region' => $region,
//                'firstname' => $firstName,
//                'secondname' => $secondName,
//                'lastname' => $lastName,
//                'birthdate' => $birthDate,
//            ]
//        ]
//    )->getBody();
//    $obj = json_decode($response, true);
//
//    return $obj['response']['task'];
//}
//
//function postSearchGroupTask($token, $customers)
//{
//    $client = new Client();
//
//    $response = $client->post(
//        'https://api-ip.fssprus.ru/api/v1.0/search/group',
//        [
//            GuzzleHttp\RequestOptions::JSON => [
//                'token' => $token,
//                'request' => $customers
//            ]
//        ]
//    )->getBody();
//    $obj = json_decode($response, true);
//
//    return $obj['response']['task'];
//}
//
//function getStatus($token, $task)
//{
//    $client = new Client();
//    $response = $client->get(
//        'https://api-ip.fssprus.ru/api/v1.0/status',
//        [
//            GuzzleHttp\RequestOptions::QUERY => [
//                'token' => $token,
//                'task' => $task,
//            ]
//        ]
//    )->getBody();
//    $obj = json_decode($response, true);
//
//    return $obj['response']['status'];
//}
//
//function getResults($token, $task)
//{
//    $client = new Client();
//    $response = $client->get(
//        'https://api-ip.fssprus.ru/api/v1.0/result',
//        [
//            'query' => [
//                'token' => $token,
//                'task' => $task,
//            ]
//        ]
//    )->getBody();
//    $obj = json_decode($response, true);
//    return $obj['response'];
//}

$inCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_IN'));
$inCsv->setFlags(SplFileObject::READ_CSV);
$outCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_OUT'), 'a');
$outError = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_ERROR'), 'a');

//Пропуск первой строки
$maxLimit = 500;
//$count = 0;

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

//try {
//    while ($inCsv->valid() && $count < 1) {
////        $count++;
//
//        $customers = [];
//        $outArray = [];
//        $type = 1;
//
//        for ($i = 0; $i < 50 && $inCsv->valid(); $i++) {
//            $customerInfo = $inCsv->fgetcsv();
//            $customerId = $customerInfo[0];
//            $region = $customerInfo[1];
//            $firstName = $customerInfo[2];
//            $secondName = $customerInfo[3];
//            $lastName = $customerInfo[4];
//            $inBirthDate = explode('-', $customerInfo[5]);
//            $outBirthDate = $inBirthDate[2] . '.' . $inBirthDate[1] . '.' . $inBirthDate[0];
//
//            $customer = [
//                'type' => $type,
//                'params' => [
//                    'region' => $region,
//                    'firstname' => $firstName,
//                    'secondname' => $secondName,
//                    'lastname' => $lastName,
//                    'birthdate' => $outBirthDate,
//                ]
//            ];
//
//            $customers[$i] = $customer;
//            $outArray [$i][] = $customerId;
//        }
//
//        sleep(3);
//        $task = postSearchGroupTask($token, $customers);
//        sleep(3);
//        $response = getResults($token, $task);
//        $status = $response['status'];
//
//        $attempt = 0;
//        while ($status != 0 && $attempt < $maxLimit) {
//            var_dump('$attempt' . $attempt);
//            sleep($attempt + 5);
//            try {
//                $response = getResults($token, $task);
//            } catch (RequestException $e) {
//                echo nl2br(Psr7\str($e->getRequest()));
//                if ($e->hasResponse()) {
//                    echo nl2br(Psr7\str($e->getResponse()));
//                }
//            }
//            $status = $response['status'];
//            $outError->fputcsv([$task]);
//            $attempt++;
//        }
//
//        if ($status != 0) {
//            foreach ($customers as $key => $customer) {
//                $customerError = $customer['params'];
//                array_unshift($customerError, $outArray[$key][0]);
//                $outError->fputcsv($customerError);
//            }
//            $outError->fputcsv([$task]);
//            exit(429);
//        }
//
//        $overallResults = $response['result'];
//
//        foreach ($overallResults as $key => $result) {
//            $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));
//
//            $status = $result['status'];
//            if ($status != 0) {
//                $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
//                $outError->fputcsv($outArray);
//                continue;
//            }
//
//            $foundInformation = $result['result'];
//            $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
//            if (empty($foundInformation)) {
//                $outArray[$key][] = 'Нет записи в ФССП по данным параметрам';
//            } else {
//                $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));
//                $iteratorFoundInformation = new RecursiveIteratorIterator(new RecursiveArrayIterator($foundInformation));
//                $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorFoundInformation, false));
//            }
//            $outCsv->fputcsv($outArray[$key]);
//        }
//
//    }
//} catch (RequestException $e) {
//    echo nl2br(Psr7\str($e->getRequest()));
//    if ($e->hasResponse()) {
//        echo nl2br(Psr7\str($e->getResponse()));
//    }
//}

//если упадёт, запускаю это с последним токеном
//for ($i = 0; $i < 50 && $inCsv->valid(); $i++) {
//    $customerInfo = $inCsv->fgetcsv();
//    $customerId = $customerInfo[0];
//
//    $outArray [$i][] = $customerId;
//}
//$task ='';
//$response = getResults($token, $task);
//var_dump($response);
//
//$overallResults = $response['result'];
//
//foreach ($overallResults as $key => $result) {
//    $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));
//
//    $status = $result['status'];
//    if ($status != 0) {
//        $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
//        $outError->fputcsv($outArray);
//        continue;
//    }
//
//    $foundInformation = $result['result'];
//    var_dump($foundInformation);
//
//    $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorParams, false));
//    if (empty($foundInformation)) {
//        $outArray[$key][] = 'Нет записи в ФССП по данным параметрам';
//    } else {
//        $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($result['query']['params']));
//        $iteratorFoundInformation = new RecursiveIteratorIterator(new RecursiveArrayIterator($foundInformation));
//        $outArray[$key] = array_merge($outArray[$key], iterator_to_array($iteratorFoundInformation, false));
//    }
//    $outCsv->fputcsv($outArray[$key]);
//}

$limitSearchGroup = 50;
$dirName = dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('LOG');

$log = new Logger('index');
try {
    $log->pushHandler(new StreamHandler($dirName, Logger::DEBUG));
} catch (\Exception $e) {
    exit(-1);
}

$log->debug('Старт программы');

$requestToFssp = new services\requestToFssp($token);
$count = 0;
while ($inCsv->valid() && $count < 1) {
    $count++;

    $customers = [];
    $customersId = [];
    $type = 1;

    $log->debug("Проход номер {$count}");

    for ($i = 0; $i < $limitSearchGroup && $inCsv->valid(); $i++) {
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
        $customersId [$i] = $customerId;
    }

    $log->debug("Проход номер {$count}, подготовил params", [$limitSearchGroup * $count]);

    try {
        $status = $requestToFssp->PostSearchGroupTask($customers);
        $log->debug("Проход номер {$count}, успешно отправил групповой запрос");
    } catch (RequestException $e) {
        $log->warning("Проход номер {$count}, ошибка при отправке групового запроса");
        $status = -1;
        $log->warning(nl2br(Psr7\str($e->getRequest())));
        if ($e->hasResponse()) {
            $log->warning(nl2br(Psr7\str($e->getResponse())));
        }
    }

    while ($status != 0) {
        try {
            $status = $requestToFssp->PostSearchGroupTask($customers);
            $log->debug("Проход номер {$count}, смог успешно отправить групповой запрос");
        } catch (RequestException $e) {
            $log->warning("Проход номер {$count}, снова ошибка при отправке групового запроса");
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
            }
        }
    }

    try {
        $status = $requestToFssp->requestToGetResults();
        $log->debug("Проход номер {$count}, успешно отработал запрос результата");
    } catch (RequestException $e) {
        $log->warning("Проход номер {$count}, ошибка при получении результата");
        $status = -1;
        $log->warning(nl2br(Psr7\str($e->getRequest())));
        if ($e->hasResponse()) {
            $log->warning(nl2br(Psr7\str($e->getResponse())));
        }
    }

    while ($status != 0 || $status == -1) {
        try {
            $status = $requestToFssp->requestToGetResults();
            $log->debug("Проход номер {$count}, успешно отработал запрос результата");
        } catch (RequestException $e) {
            $log->warning("Проход номер {$count}, ошибка при получении результата");
            $status = -1;
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
            }
        }
    }

    if ($status == -1) {
        $log->debug("Проход номер {$count}, долгое ожидание обработки запроса,завершение программы");
        foreach ($customers as $key => $customer) {
            $customerError = $customer['params'];
            array_unshift($customerError, $customersId[$key]);
            $outError->fputcsv($customerError);
        }
        exit(429);
    }

    foreach ($customersId as $key => $customerId) {
        $result = $requestToFssp->getResultById($key);
        $params = $requestToFssp->getParamsById($key);

        $iteratorParams = new RecursiveIteratorIterator(new RecursiveArrayIterator($params));
        $outArray[$key] = iterator_to_array($iteratorParams, false);
        $outArray[$key] = array_unshift($outArray[$key], $customerId);

        $status = $result['status'];

        if ($status != 0) {
            $log->debug("Проход номер {$count}, не обработал {$outArray[$key]}");
            $outError->fputcsv($outArray[$key]);
            continue;
        }

        $foundInformation = $result['result'];

        if (empty($foundInformation)) {
            $outArray[$key][] = 'Нет записи в ФССП по данным параметрам';
            $outCsv->fputcsv($outArray[$key]);
            continue;
        }

        foreach ($foundInformation as $information) {
            $iteratorInformation = new RecursiveArrayIterator($information);
            $outArray[$key] = iterator_to_array($iteratorInformation, false);
            $outArray[$key] = array_unshift($outArray[$key], $customerId);

            $outCsv->fputcsv($outArray[$key]);
        }
    }
}

$log->debug('Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.');
