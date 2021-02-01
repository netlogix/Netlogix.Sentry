# Netlogix.Sentry

## About
This package provides a Flow integration for the [sentry.io](https://sentry.io) PHP SDK. Some basic
information about the Flow application is added to the sentry Event by default, but you can easily
configure and extend this package to fit your needs.

## Installation
`composer require netlogix/sentry`

Currently the following Flow versions are supported:

* `^6.3`

## Setup

The sentry DSN Client Key has to be configured. Get it from your project settings (SDK Setup -> Client Keys (DSN)).
```yaml
Netlogix:
  Sentry:
    dsn: 'https://fd5c649e6e4d41dd8ca729b15cc5d1c7@o01392.ingest.sentry.io/123456789'
```

Then simply run `./flow sentry:test` to log an exception to sentry.
While this is technically all you **have to** do, you might want to adjust the providers - see below. 

## Configuration

This package allows you to configure which data should be added to the sentry event by changing the providers
for each scope. Currently, the available scopes are `environment`, `extra`, `release`, `tags` and `user`.

Providers can be sorted using the [PositionalArraySorter](https://github.com/neos/utility-arrays/blob/master/Classes/PositionalArraySorter.php#L15) position strings.
For the scopes `extra`, `tags` and `user`, all data provided will be merged together. The scopes `environment` and `release` only support a **single** value (you can still configure more than one provider, but the last one wins).


```yaml
Netlogix:
  Sentry:
    scope:
      extra: []

      release:
        # If you don't need a specific order, you can simply set the provider to true
        'Netlogix\Sentry\Scope\Release\PathPattern': true

      tags:
        # Numerical order can be used 
        'Netlogix\Sentry\Scope\Tags\FlowEnvironment': '10'
        'Your\Custom\TagProvider': '20'

      user:
        'Your\Custom\UserProvider': 'start 1000'
        # If you don't want to add the currently authenticated Flow Account to the Event, simply disable the provider
        'Netlogix\Sentry\Scope\User\FlowAccount': false
```

## Environments
The sentry SDK will search for the environment variable `SENTRY_ENVIRONMENT` and use it's value as the current environment. This is still the default, however
you can configure the `Netlogix\Sentry\Scope\Environment\FlowSettings` provider to use a different value:

```yaml
Netlogix:
  Sentry:
    environment:
      setting: '%env:SENTRY_ENVIRONMENT%'
```

## Release tracking
You can use the `Netlogix\Sentry\Scope\Release\PathPattern` `ReleaseProvider` to extract your current release from
the app directory. By default, the configured `pathPattern` is matched against the `FLOW_PATH_ROOT` constant:

````yaml
Netlogix:
  Sentry:

    # Used by Netlogix\Sentry\Scope\Release\PathPattern
    release:
      # Path to use for extraction of release
      pathToMatch: '%FLOW_PATH_ROOT%'

      # Pattern to extract current release from file path
      # This pattern is matched against pathToMatch
      pathPattern: '~/releases/(\d{14})$~'
````

## Custom Providers

For each scope, you can implement your own providers. Each scope requires it's own interface:

* Scope `environment` => `Netlogix\Sentry\Scope\Environment\EnvironmentProvider`
* Scope `extra` => `Netlogix\Sentry\Scope\Extra\ExtraProvider`
* Scope `release` => `Netlogix\Sentry\Scope\Release\ReleaseProvider`
* Scope `tags` => `Netlogix\Sentry\Scope\Tags\TagProvider`
* Scope `user` => `Netlogix\Sentry\Scope\User\UserProvider`

Then simply add them to the configuration.

## Manually logging exceptions to sentry

If you need to manually send exceptions to sentry (inside a `catch` block for example), you can use the
`Netlogix\Sentry\ThrowableStorage\SentryStorage`:

```php
<?php

use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\ThrowableStorage\SentryStorage;

class LoggingManually {

    /**
     * @Flow\Inject
     * @var SentryStorage
     */
    protected $sentryStorage;
    
    public function log(): void {
        $exception = new \RuntimeException('foo', 1612114936);

        $this->sentryStorage->logThrowable($exception, ['some' => ['additional', 'data']]);
    }

}
```

## Ignoring exceptions

If you need to skip sending a specific exception to sentry, you can use Flow's `renderingGroups`. Simply create one
that matches your exception and set `logException` to `false`: 

```yaml
Neos:
  Flow:
    error:
      exceptionHandler:
        renderingGroups:

          ignoredExceptions:
            matchingStatusCodes: [418]
            matchingExceptionClassNames: ['Your\Ignored\Exception']
            options:
              logException: false
```

Please note that this also disables logging of this exception to `Data/Logs/Exceptions`.
