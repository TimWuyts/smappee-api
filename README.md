# Smappee API

A small Laravel based API wrapper for (local) Smappee devices, used to run on local server in order to proxy requests to other clients/devices.

## Initialisation

### REST API & MQTT access

Local API access to your Smappee device is possible by default, however the provided information is limited.

An MQTT client [can be enabled](https://support.smappee.com/hc/en-gb/articles/360045278392-Can-I-get-access-to-the-data-via-MQTT-) from within the local device configuration.

Access to the [Smappee REST API](https://smappee.atlassian.net/wiki/spaces/DEVAPI/overview) is not available by default, but [can be requested](https://support.smappee.com/hc/en-gb/articles/360045704651-Can-I-get-access-to-the-data-via-API-) by contacting the support team.

### Installation

1. Rename the `.env.example` file to `.env` & provide the proper information.

2. Run `composer install` in the root of the project, which installs all required dependencies.

3. Make sure the Laravel scheduler is [run using a cronjob](https://laravel.com/docs/10.x/scheduling#running-the-scheduler). 

Your server configuration should meet the [requirements](https://laravel.com/docs/10.x/deployment#server-requirements) as specified in the documentation of the Laravel framework.

## Overview

### Endpoints

The following routes are available as GET requests, which are essentially proxying the available (local) API endpoints.

`/local/system`

An overview of parameters exposed by your local Smappee device.

`/local/sockets`

An overview of sockets connected to your local Smappee device.

`/local/sockets/{key}/{action?}`

Trigger a specific action ("on" or "off") for a socket, using the "key" as unique identifier. The "off" action is set as the default.

`/service-locations`

An overview of the available service locations related to your account.

`/service-location/{id?}`

Detailed information for a certain service-location, using the id as unique identifier. This parameter is optional when the "SMAPPEE_SERVICE_LOCATION" environment variable is set.

`/consumption/{id?}`

Consumption information for a certain service-location, using the id as unique identifier. This parameter is optional when the "SMAPPEE_SERVICE_LOCATION" environment variable is set.

The following optional query parameters can be used:

- **from**: DateTime compatible start date, defaults to "-1 day".
- **to**: DateTime compatible end date, defaults to "now".
- **aggregation**: Numeric value of the [aggregation level](https://smappee.atlassian.net/wiki/spaces/DEVAPI/pages/526581813/Get+Electricity+Consumption), defaults to "2" (hourly values).


### MQTT

When enabled, an `extensions` topic will be created in the same namespace as the default MQTT topics provided by your Smappee device with additional information about the service location & current consumption.

This information will be updated every 5 minutes using a scheduled task that triggers the `mqtt:publish` artisan command.

Additional MQTT related [configuration](https://github.com/php-mqtt/laravel-client/blob/master/config/mqtt-client.php) is possible using environment variables.
