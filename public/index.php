<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

CONST TO_MANY_REQUEST = 429;

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
$TABLE_DATA_FSSP = getenv('TABLE_DATA_FSSP');
$TABLE_CUSTOMERS = getenv('TABLE_CUSTOMERS');

$sql = "SELECT *
            FROM {$TABLE_CUSTOMERS}
            where customer_id > (select max({$TABLE_DATA_FSSP}.customer_id)
                                 from {$TABLE_DATA_FSSP}
                                        join {$TABLE_CUSTOMERS} on {$TABLE_CUSTOMERS}.customer_id = {$TABLE_DATA_FSSP}.customer_id)
            group by customer_id
            order by customer_id";
$pdo = new PDO("mysql:host={$DB_HOST}; dbname={$DB_NAME}; charset=utf8mb4", $DB_USERNAME, $DB_PASSWORD);
$customers = $pdo->query($sql)->fetchAll();

$sqlInsert = "INSERT INTO {$TABLE_DATA_FSSP} (
                                customer_id,
                                debt,end_date,
                                article, part,
                                paragraph,
                                exe_production_number,
                                exe_production_date,
                                details,
                                subject,
                                department,
                                bailiff
                                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

$limitSearchGroup = 50;
$type = 1;
$chunksCustomers = array_chunk($customers, $limitSearchGroup);

$token = getenv('TOKEN');
$requestToFssp = new services\RequestToFssp($token);

foreach ($chunksCustomers as $key => $chunkCustomers) {

    $infoForRequest = [];

    $log->debug("Проход номер {$key}");

    foreach ($chunkCustomers as $customer) {
        $region = $customer['registration_region'];
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

    $postSearchStatus = 1;

    while ($postSearchStatus > 0) {
        try {
            $postSearchStatus = $requestToFssp->PostSearchGroupTask($infoForRequest);
        } catch (RequestException $e) {
            $log->warning("Проход номер {$key}, ошибка при отправке групового запроса");
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
                if ($e->getResponse()->getStatusCode() == TO_MANY_REQUEST) {
                    sleep(15);
                } else {
                    $postSearchStatus = -$e->getResponse()->getStatusCode();
                }
            } else {
                $postSearchStatus = -2;
            }
        }
    }

    if (is_null($postSearchStatus) || $postSearchStatus < 0) {
        $log->debug("Проход номер {$key}, обработка запроса завершилась со статусом {$postSearchStatus},завершение программы");
        foreach ($chunkCustomers as $customer) {
            $outError->fputcsv([$customer['customer_id']]);
        }
        exit($postSearchStatus);
    }

    $requestToGetStatus = 2;

    while ($requestToGetStatus > 0) {
        try {
            $requestToGetStatus = $requestToFssp->requestToGetResults();
            $log->debug("Проход номер {$key}, успешно отработал запрос результата", [$requestToGetStatus]);
        } catch (RequestException $e) {
            $log->warning("Проход номер {$key}, ошибка при получении результата");
            $log->warning(nl2br(Psr7\str($e->getRequest())));
            if ($e->hasResponse()) {
                $log->warning(nl2br(Psr7\str($e->getResponse())));
                if ($e->getResponse()->getStatusCode() == TO_MANY_REQUEST) {
                    sleep(15);
                } else {
                    $requestToGetStatus = -$e->getResponse()->getStatusCode();
                }
            } else {
                $requestToGetStatus = -2;
            }
        }
    }

    if ($requestToGetStatus < 0 || $requestToGetStatus == 3) {

        $log->debug("Проход номер {$key}, обработка запроса завершилась со статусом {$requestToGetStatus},завершение программы");
        foreach ($chunkCustomers as $customer) {
            $outError->fputcsv([$customer['customer_id']]);
        }
        exit($requestToGetStatus);
    }

    $pdo = new PDO("mysql:host={$DB_HOST}; dbname={$DB_NAME}; charset=utf8mb4", $DB_USERNAME, $DB_PASSWORD);
    $dataFsspInsert = $pdo->prepare($sqlInsert);
    $dataFsspInsert->execute([
        0,
        $key,
        null,
        null,
        null,
        null,
        null,
        null,
        'test',
        'test',
        'test',
        'test',
    ]);

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
            $exeProductionNumber = null;
            $exeProductionDate = null;

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

            if (!empty($information['exe_production'])) {
                [$exeProductionNumber, $exeProductionDate] = explode(' от ', $information['exe_production']);
            }
            $exeProductionDate = implode('-', array_reverse(explode('.', $exeProductionDate)));

            $dataFsspInsert->execute([
                $customer['customer_id'],
                $debt,
                $endDate,
                $article,
                $part,
                $paragraph,
                $exeProductionNumber,
                $exeProductionDate,
                $information['details'],
                $information['subject'],
                $information['department'],
                $information['bailiff'],
            ]);
        }
    }
}

$log->debug('Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.');
