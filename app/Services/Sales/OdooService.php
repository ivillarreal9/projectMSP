<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Http;

class OdooService
{
    private $url;
    private $db;
    private $username;
    private $apiKey;

    public function __construct()
    {
        $this->url = env('ODOO_URL');
        $this->db = env('ODOO_DB');
        $this->username = env('ODOO_USERNAME');
        $this->apiKey = env('ODOO_API_KEY');
    }

    private function call($service, $method, $args)
    {
        $response = Http::post($this->url, [
            "jsonrpc" => "2.0",
            "method" => "call",
            "params" => [
                "service" => $service,
                "method" => $method,
                "args" => $args
            ],
            "id" => now()->timestamp
        ]);

        return $response->json();
    }

    public function login()
    {
        $result = $this->call("common", "login", [
            $this->db,
            $this->username,
            $this->apiKey
        ]);

        return $result['result'] ?? null;
    }

    public function getSalesOrders()
    {
        $uid = $this->login();

        if (!$uid) return [];

        $result = $this->call("object", "execute_kw", [
            $this->db,
            $uid,
            $this->apiKey,
            "sale.order",
            "search_read",
            [[]],
            [
                "fields" => ["name", "amount_total", "state"],
                "limit" => 10
            ]
        ]);

        return $result['result'] ?? [];
    }
}