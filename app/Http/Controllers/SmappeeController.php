<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SmappeeController extends Controller
{
    protected const API_BASE = '/gateway/apipublic/';

    public function __construct()
    {
        $this->authenticate();
    }

    public function measurements(Request $request)
    {
        return $this->doPost('instantaneous', 'loadInstantaneous')->json();
    }

    public function measurement(Request $request, $key)
    {
        $measurements = collect($this->measurements($request));
        $measurement = $measurements->firstWhere('key', $key);

        if (empty($measurement)) {
            throw new \Exception('Specified key does not exist.');
        }

        return $measurement['value'];
    }

    public function totalLoad(Request $request)
    {
        $measurements = collect($this->measurements($request));

        return $this->getCombinedValue($measurements, ['phase0ActivePower', 'phase1ActivePower', 'phase2ActivePower']);
    }

    public function totalSolar(Request $request)
    {
        $measurements = collect($this->measurements($request));

        return $this->getCombinedValue($measurements, ['phase3ActivePower', 'phase4ActivePower', 'phase5ActivePower']);
    }

    public function totalCombined(Request $request)
    {
        return 'load=' . $this->totalLoad($request) . ';solar=' . $this->totalSolar($request);
    }

    protected function getCombinedValue($measurements, $keys)
    {
        $measurements = $measurements->filter(function($measurement) use($keys) {
            return in_array($measurement['key'], $keys);
        });

        return max(($measurements->sum('value') / 1000), 0);
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
        $password = env('SMAPPEE_PASSWORD');

        if(empty($password)) {
            throw new \Exception('You must set the Smappee password to login.');
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
        $host = env('SMAPPEE_IP');

        if(empty($host)) {
            throw new \Exception('You must set the local Smappee device address to access it.');
        }

        return 'http://' . $host . self::API_BASE . $uri;
    }
}
