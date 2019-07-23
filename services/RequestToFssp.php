<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 28.11.18
 * Time: 13:19
 */

namespace services;


use GuzzleHttp\Client;
use GuzzleHttp;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RequestToFssp
{
    private $log;
    private $token;
    private $task;
    private $responses;
    private $client;
    private $attempt = 0;
    private $maxAttemptRequestToGetResults = 500;
    private $maxAttemptPostSearchGroupTask = 5;

    public function __construct($token)
    {
        $pathLog = dirname(__DIR__) . DIRECTORY_SEPARATOR . getenv('LOG');

        $this->log = new Logger('requestToFssp');
        try {
            $this->log->pushHandler(new StreamHandler($pathLog, Logger::DEBUG));
        } catch (\Exception $e) {
            exit(-1);
        }

        $this->token = $token;
        $this->client = new Client();

        $this->log->debug('class initialization');
    }

    //делает групповой запрос на поиск информации в БДИП, возврощает код ответа
    function postSearchGroupTask($customers)
    {
        if ($this->attempt == $this->maxAttemptPostSearchGroupTask) {
            return -1;
        }

        $this->task = null;

        sleep(3 + $this->attempt);
        $this->attempt++;

        $response = $this->client->post(
            'https://api-ip.fssprus.ru/api/v1.0/search/group',
            [
                GuzzleHttp\RequestOptions::JSON => [
                    'token' => $this->token,
                    'request' => $customers
                ]
            ]
        )->getBody();

        $responseArray = json_decode($response, true);

        if ($responseArray['code'] == 0) {
            $this->task = $responseArray['response']['task'];
            $this->log->debug("Успешно отправил групповой запрос и получил task {$this->task}");
            $this->attempt = 0;
        }
        return $responseArray['code'];
    }

    //делает запрос на поиск сведений о физическом лице, возврощает код ответа
    function requestToGetSearchPhysicalTask($firstName, $lastName, $birthDate = '', $region = -1, $secondName = '')
    {
        $this->task = null;

        sleep(3);
        $response = $this->client->get(
            'https://api-ip.fssprus.ru/api/v1.0/search/physical',
            [
                GuzzleHttp\RequestOptions::QUERY => [
                    'token' => $this->token,
                    'region' => $region,
                    'firstname' => $firstName,
                    'secondname' => $secondName,
                    'lastname' => $lastName,
                    'birthdate' => $birthDate,
                ]
            ]
        )->getBody();

        $responseArray = json_decode($response, true);

        if ($responseArray['code'] == 0) {
            $this->task = $responseArray['response']['task'];
            $this->log->debug("Успешно отправил запрос и получил task", [$this->task]);
        }
        return $responseArray['code'];
    }

    //Проверка статуса поданного запроса, возврощает статус 0 или прогресс
    function requestToGetStatus()
    {
        sleep(3);
        $response = $this->client->get(
            'https://api-ip.fssprus.ru/api/v1.0/status',
            [
                GuzzleHttp\RequestOptions::QUERY => [
                    'token' => $this->token,
                    'task' => $this->task,
                ]
            ]
        )->getBody();
        $responseArray = json_decode($response, true);

        return $responseArray['response']['status'] == 0 ? $responseArray['response']['status'] : $responseArray['response']['progress'];
    }

    //Получение результатов поданного запроса, возврощает код ответа
    function requestToGetResults()
    {
        if ($this->attempt == $this->maxAttemptRequestToGetResults) {
            return -1;
        }

        $this->log->debug("Попытка получения результатов номер {$this->attempt}", [$this->task]);

        sleep(3 + $this->attempt);
        $this->attempt++;

        $response = $this->client->get(
            'https://api-ip.fssprus.ru/api/v1.0/result',
            [
                'query' => [
                    'token' => $this->token,
                    'task' => $this->task,
                ]
            ]
        )->getBody();

        $responseArray = json_decode($response, true);

        if ($responseArray['response']['status'] == 0) {
            $this->attempt = 0;
            $this->responses = $responseArray['response'];
            $this->log->debug("Результат готов и получен", [$this->task]);
        }

        return $responseArray['response']['status'];
    }

    function getResultById($id)
    {
        if (empty($this->responses)) {
            return null;
        }

        return $this->responses['result'][$id];
    }

    function getParamsById($id)
    {
        if (empty($this->responses)) {
            return null;
        }
        return $this->responses['result'][$id]['query']['params'];
    }

    function setTask($task)
    {
        $this->task = $task;
    }

    function getTask()
    {
        return $this->task;
    }

}