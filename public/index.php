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

$inCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_IN'));
$inCsv->setFlags(SplFileObject::READ_CSV);
$outCsv = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_OUT'), 'a');
$outError = new SplFileObject(dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('FILE_ERROR'), 'a');

$maxLimit = 500;
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
                'lastname' => $lastName,
                'firstname' => $firstName,
                'secondname' => $secondName,
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
            $log->debug("Проход номер {$count}, успешно отработал запрос результата", [$status]);
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

        $outArray = $params;
        array_unshift($outArray, $customerId);

        $status = $result['status'];

        if ($status != 0) {
            $log->debug("Проход номер {$count}, не обработал {$outArray}");
            $outError->fputcsv($outArray);
            continue;
        }

        $foundInformation = $result['result'];

        if (empty($foundInformation)) {
            $outArray[] = 'Нет записи в ФССП по данным параметрам';
            $outCsv->fputcsv($outArray);
            continue;
        }

        foreach ($foundInformation as $information) {
            $outCsv->fputcsv(array_merge($outArray, $information));
        }
    }
}

$log->debug('Время выполнения скрипта: ' . round(microtime(true) - $start, 4) . ' сек.');
