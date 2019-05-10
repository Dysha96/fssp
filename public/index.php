<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$start = microtime(true);
set_time_limit(0);
$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

$pathLog = dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('LOG');
$log = new Logger('index');
try {
    $log->pushHandler(new StreamHandler($pathLog, Logger::DEBUG));
} catch (\Exception $e) {
    exit(-1);
}

$log->debug('Старт программы');

$outError = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_ERROR'), 'a');

//        $customerInfo[$key] = mb_convert_encoding($value, 'utf-8', 'cp1251');
//    var_dump('END') or die();

$DB_HOST = getenv('DB_HOST');
$DB_NAME = getenv('DB_NAME');
$DB_USERNAME = getenv('DB_USERNAME');
$DB_PASSWORD = getenv('DB_PASSWORD');

$sql = 'SELECT * FROM customers where customer_id>3171325';
$pdo = new PDO("mysql:host={$DB_HOST}; dbname={$DB_NAME}; charset=utf8mb4", $DB_USERNAME, $DB_PASSWORD);
$customers = $pdo->query($sql)->fetchAll();

$sqlInsert = "INSERT INTO data_fssp (customer_id, debt,end_date, article,part,paragraph) VALUES (?,?,?,?,?,?)";
$dataFsspInsert = $pdo->prepare($sqlInsert);

$limitSearchGroup = 50;
$type = 1;
$chunksCustomers = array_chunk($customers, $limitSearchGroup);

$token = getenv('TOKEN');
$requestToFssp = new services\RequestToFssp($token);

foreach ($chunksCustomers as $key => $chunkCustomers) {

    $infoForRequest = [];

    $log->debug("Проход номер {$key}");

    foreach ($chunkCustomers as $customer) {
        $region = $customer['region'];
        $firstName = $customer['first_name'];
        $secondName = $customer['middle_name'];
        $lastName = $customer['last_name'];
        $inBirthDate = explode('-', $customer['birthday']);
        $outBirthDate = $inBirthDate[2] . '.' . $inBirthDate[1] . '.' . $inBirthDate[0];

        $infoForRequest[] = [
            'type' => $type,
            'params' => [
                'region' => $region,
                'lastname' => $lastName,
                'firstname' => $firstName,
                'secondname' => $secondName,
                'birthdate' => $outBirthDate,
            ]
        ];
    }


    $log->debug("Проход номер {$key}, подготовил infoForRequest", [$limitSearchGroup * $key]);

    try {
        $postSearchStatus = $requestToFssp->PostSearchGroupTask($infoForRequest);
    } catch (RequestException $e) {
        $log->warning("Проход номер {$key}, ошибка при отправке групового запроса");
        $log->warning(nl2br(Psr7\str($e->getRequest())));
        if ($e->hasResponse()) {
            $log->warning(nl2br(Psr7\str($e->getResponse())));
        }
    }

    while ($postSearchStatus != 0 && $postSearchStatus != -1) {
        try {
            $postSearchStatus = $requestToFssp->PostSearchGroupTask($customers);
        } catch (RequestException $e) {
            $log->warning("Проход номер {$key}, снова ошибка при отправке групового запроса");
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
            }
        }
    }

    if ($postSearchStatus < 0) {

        $log->debug("Проход номер {$key}, обработка запроса завершилась со статусом {$postSearchStatus},завершение программы");
        foreach ($chunkCustomers as $customer) {
            $outError->fputcsv([$customer['customer_id']]);
        }
        exit(429);
    }

    try {
        $requestToGetStatus = $requestToFssp->requestToGetResults();
        $log->debug("Проход номер {$key}, успешно отработал запрос результата", [$requestToGetStatus]);
    } catch (RequestException $e) {
        $log->warning("Проход номер {$key}, ошибка при получении результата");
        $requestToGetStatus = -1;
        $log->warning(nl2br(Psr7\str($e->getRequest())));
        if ($e->hasResponse()) {
            $log->warning(nl2br(Psr7\str($e->getResponse())));
        }
    }

    while ($requestToGetStatus != 0 && $requestToGetStatus != -1) {
        try {
            $requestToGetStatus = $requestToFssp->requestToGetResults();
            $log->debug("Проход номер {$key}, успешно отработал запрос результата", [$requestToGetStatus]);
        } catch (RequestException $e) {
            $log->warning("Проход номер {$key}, ошибка при получении результата");
            $requestToGetStatus = -1;
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
            }
        }
    }

    if ($requestToGetStatus < 0 || $requestToGetStatus == 3) {

        $log->debug("Проход номер {$key}, обработка запроса завершилась со статусом {$requestToGetStatus},завершение программы");
        foreach ($chunkCustomers as $customer) {
            $outError->fputcsv([$customer['customer_id']]);
        }
        exit(429);
    }

    foreach ($chunkCustomers as $id => $customer) {
        $result = $requestToFssp->getResultById($id);

        if (empty($result)) {
            continue;
        }

        $params = $requestToFssp->getParamsById($id);

        $outArray = $params;
        array_unshift($outArray, $customer['customer_id']);

        $status = $result['status'];

        if ($status != 0) {
            $log->debug("Проход номер {$key}, не обработал {$outArray}");
            $outError->fputcsv($outArray);
            continue;
        }

        $foundInformation = $result['result'];

        if (empty($foundInformation)) {
            continue;
        }

        foreach ($foundInformation as $information) {
            $debt = null;
            $article = null;
            $part = null;
            $paragraph = null;
            $endDate = null;

            if (!empty($information['ip_end'])) {
                [$endDate, $article, $part, $paragraph] = explode(', ', $information['ip_end']);
            }

            if (!empty($information['subject'])) {
                $explodeStr = explode(' ', $information['subject']);
                $indexRub = -1;

                foreach ($explodeStr as $index => $value) {
                    if ($value == 'руб.') {
                        $indexRub = $index;
                        break;
                    }
                }

                if ($indexRub > 0) {
                    $debt = $explodeStr[$indexRub - 1];
                }
            }

            $dataFsspInsert->execute([$customer['customer_id'], $debt, $endDate, $article, $part, $paragraph]);
        }
    }
}

$log->debug('Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.');
