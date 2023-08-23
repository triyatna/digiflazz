<?php

namespace App\Controllers\Provider;

use App\Controllers\BaseController;
use App\Models\Settings;

class DigiFlazz extends BaseController
{
    private $url, $settings, $username, $api;


    public function __construct()
    {
        $this->url = 'https://api.digiflazz.com/v1/';

        $this->settings = new Settings();
        $this->username = ''; // username digiflazz
        $this->api = ''; // api key digiflazz
    }

    // Callback digiflazz
    public function callback()
    {
        $secret_key = $this->api;
        $data = file_get_contents('php://input');
        $sign = hash_hmac('sha1', $data, $secret_key);
        $data1 = json_decode($data, true);


        // check header x-hub-signature
        if (isset($header['x-hub-signature']) && request()->header('x-hub-signature') == 'sha1=' . $sign) {
            // JSON Decode Getcontent
            $data = json_decode($data, true);
            // Log data
            $response = [
                'status' => true,
                'message' => 'Success',
                'data' => json_encode($data),
            ];
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => [
                'sign' => $sign,
                'result' => json_encode($data1) == "null" || null ? '' : json_encode($data1),
            ],
        ];
        return $response;
    }

    public function request(string $url, string $code, array $data = [])
    {
        $sign = md5($this->username . $this->api . $code);
        $data = array_merge($data, [
            'username' => $this->username,
            'sign' => $sign,
            'testing' => true
        ]);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function cekSaldo()
    {
        $data = [
            'cmd' => 'deposit'
        ];
        $act = $this->request('cek-saldo', 'depo', $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }

    public function priceList(string $cmd, string $code = null)
    {
        if ($cmd != 'prepaid' && $cmd != 'pasca') {
            $response = [
                'status' => false,
                'message' => 'CMD harus berisi data prepaid atau pasca',
                'data' => [],
            ];
            return $response;
        }
        $data = [
            'cmd' => $cmd,
        ];
        if ($code != null) {
            $data['code'] = $code;
        }
        $act = $this->request('price-list', 'pricelist', $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }

    public function deposit(int $amount, string $bank, string $name)
    {
        if ($bank != 'BCA' && $bank != 'MANDIRI' && $bank != 'BRI') {
            $response = [
                'status' => false,
                'message' => 'Bank harus berisi data BCA, MANDIRI atau BRI',
                'data' => [],
            ];
            return $response;
        }
        $data = [
            'amount' => $amount,
            'Bank' => $bank,
            'owner_name' => $name,
        ];
        $act = $this->request('deposit', 'deposit', $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }

    public function topup(string $codeservice, string $customer, string $reff, string $maxprice = null, string $callback = null)
    {
        $data = [
            'buyer_sku_code' => $codeservice,
            'customer_no' => $customer,
            'ref_id' => $reff
        ];
        if ($maxprice != null) {
            $data['max_price'] = $maxprice;
        }
        if ($callback != null) {
            $data['cb_url'] = $callback;
        }
        $act = $this->request('transaction', $reff, $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            if (json_decode($act, true)['data']['rc'] == '') {
                $response['message'] = 'Terjadi kesalahan pada server';
                $response['data'] = json_decode($act, true)['data'];
            }
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }

    public function checkPasca(string $codeservice, string $customer, string $reff)
    {
        $data = [
            'commands' => 'inq-pasca',
            'buyer_sku_code' => $codeservice,
            'customer_no' => $customer,
            'ref_id' => $reff
        ];

        $act = $this->request('transaction', $reff, $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            if (json_decode($act, true)['data']['rc'] == '') {
                $response['message'] = 'Terjadi kesalahan pada server';
                $response['data'] = json_decode($act, true)['data'];
            }
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }


    public function payPasca(string $codeservice, string $customer, string $reff)
    {
        $data = [
            'commands' => 'pay-pasca',
            'buyer_sku_code' => $codeservice,
            'customer_no' => $customer,
            'ref_id' => $reff
        ];

        $act = $this->request('transaction', $reff, $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            if (json_decode($act, true)['data']['rc'] == '') {
                $response['message'] = 'Terjadi kesalahan pada server';
                $response['data'] = json_decode($act, true)['data'];
            }
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }
    public function checkPLN(string $customer)
    {
        $data = [
            'commands' => 'pln-subscribe',
            'customer_no' => $customer,
        ];

        $act = $this->request('transaction', 'pln', $data);
        if (isset(json_decode($act, true)['data']['rc']) && json_decode($act, true)['data']['rc'] != '00') {
            $response = [
                'status' => false,
                'message' => json_decode($act, true)['data']['message'],
                'data' => [],
            ];
            if (json_decode($act, true)['data']['rc'] == '') {
                $response['message'] = 'Terjadi kesalahan pada server';
                $response['data'] = json_decode($act, true)['data'];
            }
            return $response;
        }
        if (json_decode($act, true)['data']['meter_no'] == '') {
            $response = [
                'status' => false,
                'message' => 'Meter tidak ditemukan',
                'data' => [],
            ];
            return $response;
        }
        $response = [
            'status' => true,
            'message' => 'Success',
            'data' => json_decode($act, true)['data'],
        ];
        return $response;
    }
}
