<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use TanoWAF\YaDSP\Firewall\FirewallFactory;
use TanoWAF\YaDSP\Proxy\DockerSocketWAF;
use TanoWAF\YaDSP\Proxy\DockerSocketProxy;
use TanoWAF\WAFCore\Filter\Bidirectional\Tracer;
use TanoWAF\WAFCore\Http\CookieParserFactory;
use TanoWAF\WAFCore\Http\HeaderParserFactory;
use TanoWAF\WAFCore\Http\QueryStringParserFactory;
use TanoWAF\WAFCore\Logger\ErrorLogger;
use TanoWAF\WAFCore\Logger\FileLogger;
use TanoWAF\WAFCore\Logger\FrankenPHPLogger;
use TanoWAF\WAFCore\Middleware\Dispatcher;
use TanoWAF\WAFCore\ServerRequest\Psr17\ServerRequestFactory;
use TanoWAF\WAFCore\ServerRequest\Psr7\Creator as ServerRequestCreator;
use TanoWAF\WAFCore\UpstreamClient\UpstreamClientFactory;

$responseEmitter = new SapiEmitter();

try {
    if (array_key_exists('YADSP_LOG_FILE', $_SERVER) && trim($_SERVER['YADSP_LOG_FILE']) !== '') {
        $logger = new FileLogger($_SERVER['YADSP_LOG_FILE'], $_SERVER['YADSP_LOG_LEVEL'] ?? 'warning');
    } else {
        if (function_exists('frankenphp_log')) {
            $logger = new FrankenPHPLogger();
        } else {
            $logger = new ErrorLogger();
        }
    }

    $cookieParserFactory = new CookieParserFactory();
    $headerParserFactory = new HeaderParserFactory();
    $queryStringParserFactory = new QueryStringParserFactory();

    $cookieParser = $cookieParserFactory->fromConfiguration([]);
    $headerParser = $headerParserFactory->fromConfiguration([]);

    $psr17Factory = new Psr17Factory();
    $requestCreator = new ServerRequestCreator(
        $psr17Factory, // UriFactory
        new ServerRequestFactory(
            $psr17Factory, // UploadedFileFactory
            $psr17Factory, // StreamFactory
            $cookieParser,
            $headerParser,
            $queryStringParserFactory->fromConfiguration([])
        )
    );

    $upstream = DockerSocketProxy::DEFAULT_UPSTREAM;
    if (array_key_exists('DOCKER_HOST', $_SERVER) && trim($_SERVER['DOCKER_HOST']) !== '') {
        $upstream = $_SERVER['DOCKER_HOST'];
    }

    $firewallFactory = new FirewallFactory($logger);
    $config = array_key_exists('YADSP_CONFIG', $_SERVER) ? trim($_SERVER['YADSP_CONFIG']) : '';
    $configFile = array_key_exists('YADSP_CONFIG_FILE', $_SERVER) ? trim($_SERVER['YADSP_CONFIG_FILE']) : '';
    if ($configFile !== '') {
        if ($config !== '') {
            throw new \Exception("Can not use at the same time env vars YADSP_CONFIG and YADSP_CONFIG_FILE");
        }
        $firewall = $firewallFactory->fromConfigFile($configFile);
    } else {
        $firewall = $firewallFactory->fromConfigString($config);
    }
    $firewall->setCookieParser($cookieParser)
        ->setHeaderParser($headerParser);

    // NB: the traces files will contain ALL DATA sent to and received from the Docker daemon.
    // This has serious security implications. Please only enable this when troubleshooting / developing the YaDSP itself.
    // NB: the tracer could be injected either in front (before) or in the back of (after) the firewall.
    //     In front, it will log what the Docker client sends/received.
    //     In the back, it will log that ise sent/received to the Docker daemon
    if (array_key_exists('YADSP_TRACE_FILE', $_SERVER) && trim($_SERVER['YADSP_TRACE_FILE']) !== '') {
        $firewall = new Dispatcher([new Tracer($_SERVER['YADSP_TRACE_FILE']), $firewall]);
    }

    $httpClient = (new UpstreamClientFactory())->createClient();
    $upstreamProxy = new DockerSocketProxy($upstream, $httpClient, null, $logger);
    $waf = new DockerSocketWAF($firewall, $upstreamProxy, $logger);

    if (array_key_exists('FRANKENPHP_WORKER', $_SERVER) && (int)$_SERVER['FRANKENPHP_WORKER'] !== 0) {

        $requestHandler = function() use($requestCreator, $waf, $responseEmitter) {
            $serverRequest = $requestCreator->fromGlobals();
            $response = $waf->handle($serverRequest);
            $responseEmitter->emit($response);
        };

        $maxRequests = (int)($_SERVER['MAX_REQUESTS_PER_WORKER'] ?? 0);
        for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {

            // NB: `set_exception_handler` is called only when the worker script ends,
            // which may be unexpected, so we could (should?) catch and handle exceptions inside $handler

            /** @noinspection PhpUndefinedFunctionInspection */
            /** @phpstan-ignore function.notFound */
            $keepRunning = \frankenphp_handle_request($requestHandler);

            // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
            /// @todo do this every N requests?
            gc_collect_cycles();

            if (!$keepRunning) break;
        }

    } else {

        $serverRequest = $requestCreator->fromGlobals();
        $response = $waf->handle($serverRequest);
        $responseEmitter->emit($response);

    }

} catch (\Throwable $e) {
    $responseEmitter->emit(DockerSocketWAF::getErrorResponse($e));
}
