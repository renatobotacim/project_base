<?php

namespace App\Helpers;

use App\Models\Event;
use App\Repositories\CouponRepositoryInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class Payment
{

    private Client $client;

    public $response;

    public $method;
    public $uri;
    public $data;


    public function __construct()
    {
        $this->client = new Client();
    }

    public function setCustomer($data)
    {
        $this->uri = "customers";
        $this->method = 'POST';
        $this->data = json_encode($data);
        $this->executeAssas();
        return $this->response;
    }

    public function getStatus($data)
    {
        $this->uri = "payments/{$data['payment_id']}/status";
        $this->method = 'GET';
        $this->data = json_encode($data);
        $this->executeAssas();
        return $this->response;
    }

    public function setRefund($data)
    {
        $this->uri = "payments/{$data['payment_id']}/refund";
        $this->method = 'POST';
        $this->data = json_encode($data);
        $this->executeAssas();
        return $this->response;
    }

    public function setPayment($data)
    {
        $this->uri = "payments";
        $this->method = 'POST';
        $this->data = json_encode($data);
        $this->executeAssas();
        return $this->response;
    }

    public function getPixQrCode($paymentId)
    {
        $this->uri = "payments/{$paymentId}/pixQrCode";
        $this->method = 'GET';
        $this->data = '';
        $this->executeAssas();
        return $this->response;
    }

    public function executeAssas()
    {
        try {
            $aux = $this->client->request($this->method, env("ASSAS_URI") . '/v3/' . $this->uri, [
                'body' => $this->data,
                'headers' => [
                    'accept' => 'application/json',
                    'access_token' => env("ASSAS_API_KEY"),
                    'content-type' => 'application/json'
                ]
            ]);
            $this->response = json_decode($aux->getBody());
        } catch (\Exception $e) {
            return response()->json((array)$e, 500);
        }
    }

}
