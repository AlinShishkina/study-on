<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use JsonException;

class BillingClient
{
    private string $billing;

    public function __construct()
    {
        $this->billing = $_ENV['BILLING_SERVER']; // http://billing.study-on.local:82
    }

    private function sendRequest(
        string $url,
        string $method,
        ?string $data = null,
        ?string $token = null
    ): array {
        $curl = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($data !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new BillingUnavailableException('Сервис временно недоступен: ' . $error);
        }

        curl_close($curl);

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BillingUnavailableException('Ошибка декодирования JSON: ' . $e->getMessage());
        }
    }

    /**
     * @param array $credentials ['username' => ..., 'password' => ...]
     * @return array
     */
    public function authenticate(array $credentials): array
    {
        $url = $this->billing . '/api/v1/auth';
        return $this->sendRequest($url, 'POST', json_encode($credentials));
    }

    public function getCurrentUser(string $token): array
    {
        $url = $this->billing . '/api/v1/users/current';
        return $this->sendRequest($url, 'GET', null, $token);
    }
}
