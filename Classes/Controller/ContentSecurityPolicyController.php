<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Controller;

use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Sentry\SentrySdk;

class ContentSecurityPolicyController extends ActionController
{
    protected $supportedMediaTypes = ['application/csp-report'];

    #[Flow\InjectConfiguration(path: 'csp.enable')]
    protected bool $enabled = true;

    #[Flow\InjectConfiguration(path: 'csp.reports.includedHeaders')]
    protected array $includedHeaders = [];

    #[Flow\Inject]
    protected ThrowableStorageInterface $throwableStorage;

    public function indexAction(): string
    {
        if (!$this->enabled) {
            return '';
        }

        $reportingEndpoint = $this->getSentryReportingEndpoint();
        if ($reportingEndpoint === null) {
            return '';
        }

        // TODO: Only report a limited amount to avoid filling up sentry

        $body = $this->request->getHttpRequest()->getBody();
        $body->rewind();
        $postBody = $body->getContents();

        $client = $this->objectManager->get(ClientInterface::class);
        $requestFactory = $this->objectManager->get(RequestFactoryInterface::class);
        $streamFactory = $this->objectManager->get(StreamFactoryInterface::class);
        $request = $requestFactory->createRequest('POST', $reportingEndpoint)
            ->withBody($streamFactory->createStream($postBody));

        foreach (array_keys(array_filter($this->includedHeaders)) as $header) {
            $headerValue = $this->request->getHttpRequest()->getHeaderLine($header);
            if ($headerValue === '') {
                continue;
            }

            $request = $request
                ->withHeader($header, $headerValue);
        }

        try {
            $client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->throwableStorage->logThrowable($e);
        }

        return '';
    }

    protected function getSentryReportingEndpoint(): ?string
    {
        return SentrySdk::getCurrentHub()->getClient()?->getCspReportUrl();
    }
}
