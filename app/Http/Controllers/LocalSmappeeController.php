<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocalSmappeeController extends Controller
{
    protected const API_BASE = '/gateway/apipublic/';

    public function __construct()
    {
        $this->authenticate();
    }

    public function listSockets(Request $request)
    {
        return $this->doPost('commandControlPublic', 'load')->json();
    }

    public function toggleSocket(Request $request, $key, $action = 'off')
    {
        $action = strtoupper($action);

        if (!in_array($action, ['ON', 'OFF'])) {
            throw new \Exception('Specified action does not exist.');
        }

        $command = 'control,{"controllableNodeId":"' . urlencode($key) . '","action":"' . $action . '"}';

        return $this->doPost('commandControlPublic', $command)->json();
    }

    public function system(Request $request)
    {
        return $this->doGet('statisticsPublicReport')->json();
    }

    public function authenticate()
    {
        $password = env('SMAPPEE_LOCAL_PASSWORD');

        if(empty($password)) {
            throw new \Exception('You must set the Smappee (admin) password to login.');
        }

        $result = $this->doPost('logon', $password);

        if (isset($result['error'])) {
            throw new \Exception($result['error']);
        } else {
            return $result;
        }
    }

    protected function doGet($uri) {
        return Http::get($this->getUrl($uri));
    }

    protected function doPost($uri, $body) {
        return Http::withBody($body, 'application/json')->post($this->getUrl($uri));
    }

    protected function getUrl($uri) {
        $host = env('SMAPPEE_LOCAL_IP');

        if(empty($host)) {
            throw new \Exception('You must set the local Smappee device address to access it.');
        }

        return 'http://' . $host . self::API_BASE . $uri;
    }
}
