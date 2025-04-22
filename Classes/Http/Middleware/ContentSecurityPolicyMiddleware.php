<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Http\Middleware;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    #[Flow\InjectConfiguration(path: 'csp.enable')]
    protected bool $enabled = true;

    #[Flow\InjectConfiguration(path: 'csp.headers.reportOnly')]
    protected bool $reportOnly = true;

    #[Flow\InjectConfiguration(path: 'csp.headers.blacklistedPaths')]
    protected array $blacklistedPaths = [];

    #[Flow\InjectConfiguration(path: 'csp.headers.parts')]
    protected array $parts = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->enabled) {
            return $response;
        }
        if ($this->parts === [] || $this->isUriInBlocklist($request->getUri())) {
            return $response;
        }

        $response = $this->addReportingEndpoints($request, $response);
        $response = $this->addContentSecurityPolicy($request, $response);

        return $response;
    }

    protected function addContentSecurityPolicy(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if ($this->reportOnly) {
            $headerName = 'Content-Security-Policy-Report-Only';
        } else {
            $headerName = 'Content-Security-Policy';
        }

        $defaultParts = [
            'report-uri ' . $this->reportingEndpoint($request),
            'report-to csp-endpoint'
        ];

        // TODO: Add support for nonces
        $parts = array_merge($this->parts, $defaultParts);

        return $response
            ->withHeader($headerName, trim(join('; ', $parts), "; \n\r\t\v\0"));
    }

    protected function addReportingEndpoints(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $reportingEndpoints = [
            'csp-endpoint' => $this->reportingEndpoint($request),
        ];

        $headerValues = array_reduce(array_keys($reportingEndpoints),
            function (array $carry, string $key) use ($reportingEndpoints) {
                $carry[$key] = sprintf('%s="%s"', $key, $reportingEndpoints[$key]);

                return $carry;
            }, []);

        return $response
            ->withHeader('Reporting-Endpoints', join(', ', $headerValues));
    }

    protected function reportingEndpoint(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();

        return Uri::composeComponents(
            $uri->getScheme(),
            $uri->getHost(),
            'api/csp-report',
            '',
            ''
        );
    }

    public function isUriInBlocklist(UriInterface $uri): bool
    {
        $path = $uri->getPath();
        foreach ($this->blacklistedPaths as $rawPattern => $active) {
            if (!$active) {
                continue;
            }
            $pattern = '/' . str_replace('/', '\/', $rawPattern) . '/';

            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
