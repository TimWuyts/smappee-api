<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpMqtt\Client\Facades\MQTT;

class SmappeeController extends Controller
{
    protected const API_BASE = 'https://app1pub.smappee.net/dev';

    protected const AGGREGATION_5MINS = 1;
    protected const AGGREGATION_10MINS = 6;
    protected const AGGREGATION_15MINS = 7;
    protected const AGGREGATION_20MINS = 8;
    protected const AGGREGATION_30MINS = 9;
    protected const AGGREGATION_HOURLY = 2;
    protected const AGGREGATION_DAILY = 3;
    protected const AGGREGATION_MONTHLY = 4;
    protected const AGGREGATION_QUARTERLY = 5;

    private $refreshToken;
    private $accessToken;

    private $serviceLocation;
    private $serviceLocationId;
    private $serviceLocationUuid;

    public function __construct()
    {
        $this->serviceLocationId = env('SMAPPEE_SERVICE_LOCATION');

        $this->authenticate(
            env('SMAPPEE_USERNAME'),
            env('SMAPPEE_PASSWORD'),
            env('SMAPPEE_CLIENT_ID'),
            env('SMAPPEE_CLIENT_SECRET')
        );
    }

    public function getServiceLocations(Request $request)
    {
        $jsonResponse = $this->handleResponseType($request);

        return $this->doGet('servicelocation', $jsonResponse);
    }

    public function getServiceLocation(Request $request, $serviceLocationId = null)
    {
        if (empty($serviceLocationId)) {
            return $this->serviceLocation;
        }

        $jsonResponse = $this->handleResponseType($request);

        return $this->doGet('servicelocation/' . $serviceLocationId . '/info', null, $jsonResponse);
    }

    public function getConsumption(Request $request, $id = null)
    {
        $this->handleServiceLocation($id);

        $timezone = new \DateTimeZone('UTC');
        $from = new \DateTime($request->get('from', '-1 day'), $timezone);
        $to = new \DateTime($request->get('to', 'now'), $timezone);
        $aggregation = $request->get('aggregation', self::AGGREGATION_HOURLY);

        $params = [
            'aggregation' => $aggregation,
            'from' => $from->getTimestamp() * 1000,
            'to' => $to->getTimestamp() * 1000
        ];

        $response = $this->doGet('servicelocation/' . $this->serviceLocationId . '/consumption', $params);
        $data = data_get($response, 'consumptions', []);

        return $data;
    }

    public function getCurrentConsumption(Request $request, $id = null)
    {
        $this->handleServiceLocation($id);

        $request->merge([
            'json' => false,
            'aggregation' => self::AGGREGATION_5MINS,
            'from' => '-15 minutes',
            'to' => 'now'
        ]);

        $response = $this->getConsumption($request);

        return end($response);
    }

    public function authenticate($username, $password, $clientId, $clientSecret)
    {
        if ($this->refreshToken) {
            // TODO: use refresh token
        } else {
            $params = [
                'grant_type' => 'password',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password
            ];

            $response = $this->doPost('oauth2/token', $params);

            $this->refreshToken = data_get($response, 'refresh_token');
            $this->accessToken = data_get($response, 'access_token');
        }
    }

    public function publish($id = null)
    {
        $request = new Request(['json' => false]);

        $this->handleServiceLocation($id);

        $this->doPublish('locationInfo', $this->serviceLocation);
        $this->doPublish('currentConsumption', $this->getCurrentConsumption($request));

        MQTT::disconnect();
    }

    protected function doPublish($subject, $data)
    {
        $topic = sprintf('servicelocation/%s/extensions/%s', $this->serviceLocationUuid, $subject);

        if (!is_string($data)) {
            $data = json_encode($data);
        }

        MQTT::publish($topic, $data);
    }

    protected function doGet($uri, $query = null, $json = true)
    {
        $request = Http::withToken($this->accessToken)
            ->get($this->getUrl($uri), $query);

        return $this->handleResponse($request, $json);
    }

    protected function doPost($uri, $data = [], $json = true)
    {
        $request = Http::asForm()
            ->post($this->getUrl($uri), $data);

        return $this->handleResponse($request, $json);
    }

    protected function handleServiceLocation($id = null)
    {
        $serviceLocationId = $id ?: $this->serviceLocationId;

        if (empty($serviceLocationId)) {
            throw new \Exception('No service location ID specified.');
        }

        if ($serviceLocationId !== $this->serviceLocationId || empty($this->serviceLocation)) {
            $this->serviceLocation = $this->getServiceLocation(new Request(), $serviceLocationId);
            $this->serviceLocationId = data_get($this->serviceLocation, 'serviceLocationId');
            $this->serviceLocationUuid = data_get($this->serviceLocation, 'serviceLocationUuid');
        }
    }

    protected function handleResponseType(Request $request)
    {
        return $request->get('json', true);
    }

    protected function handleResponse($response, $json = true)
    {
        if ($response->ok()) {
            if ($json) {
                return $response->json();
            } else {
                return $response->body();
            }
        }

        $response->throw();
    }

    protected function getUrl($uri)
    {
        return self::API_BASE . '/' . env('SMAPPEE_API_VERSION', 'v3') . '/' . $uri;
    }
}
