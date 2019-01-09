<?php

/*
 * This file is part of the overtrue/weather.
 *
 * (c) wooze
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace Woo\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Woo\Weather\Exceptions\HttpException;
use Woo\Weather\Exceptions\InvalidArgumentException;
use Woo\Weather\Weather;

class WeatherTest extends TestCase
{
//    public function testGetWeatherWithInvalidType()
//    {
//        $w = new Weather('mock-key');
//
//        $this->expectException(InvalidArgumentException::class);
//        $this->expectExceptionMessage('Invalid type value(base/all): foo');
//        $w->getWeather('深圳', 'foo');
//        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
//    }

    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('moc-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response format: array');
        $w->getLiveWeather('深圳', 'array');

        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeather()
    {
        $response = new Response(200, [], '{"success": true}');

        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        // 将 `getHttpClient` 方法替换为上面创建的 http client 为返回值的模拟方法。
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        // 然后调用 `getWeather` 方法，并断言返回值为模拟的返回值。
        $this->assertSame(['success' => true], $w->getWeather('深圳'));

        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);

        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('深圳');
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        $w->setGuzzleOptions(['timeout' => 500]);

        $this->assertSame(500, $w->getHttpClient()->getConfig('timeout'));
    }

//    public function testGetLiveWeather()
//    {
//        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
//        $w->expects()->getLiveWeather('深圳')->andReturn(['success' => true]);
//
//        $this->assertSame(['success' => true], $w->getLiveWeather('深圳'));
//    }
}
