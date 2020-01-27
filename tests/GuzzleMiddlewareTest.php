<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Prometheus\Histogram;

class GuzzleMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        $value = null;
        $labels = null;
        $observe = function (float $time, array $data) use (&$value, &$labels) {
            $value = $time;
            $labels = $data;
        };
        $histogram = Mockery::mock(Histogram::class);
        $histogram->shouldReceive('observe')->andReturnUsing($observe);
        $middleware = new GuzzleMiddleware($histogram);
        $stack = new HandlerStack();
        $stack->setHandler(new MockHandler([new Response()]));
        $stack->push($middleware);
        $client = new Client(['handler' => $stack]);
        $client->get('http://example.org');
        $this->assertGreaterThan(0, $value);
        $this->assertSame(['GET', 'example.org', 200, 'No route given'], $labels);
    }

    public function testMiddlewareWithOptions()
    {
        $value = null;
        $labels = null;
        $observe = function (float $time, array $data) use (&$value, &$labels) {
            $value = $time;
            $labels = $data;
        };
        $histogram = Mockery::mock(Histogram::class);
        $histogram->shouldReceive('observe')->andReturnUsing($observe);
        $middleware = new GuzzleMiddleware($histogram);
        $stack = new HandlerStack();
        $stack->setHandler(new MockHandler([new Response()]));
        $stack->push($middleware);
        $client = new Client(['handler' => $stack]);

        $options = [
            'actionURI' => '/test'
        ];
        $client->get('http://example.org', $options);
        $this->assertGreaterThan(0, $value);
        $this->assertSame(['GET', 'example.org', 200, '/test'], $labels);
    }
}
