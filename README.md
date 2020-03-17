# Enable Elastic APM for your cake php project

For now this plugin supports to collect common events from cake php. Tested in cake php v3.8 .
This plugin is based on the [Elastic APM php agent](https://github.com/philkra/elastic-apm-php-agent)

## Installation

Just use composer to setup the plugin
```
composer require felix-lessoer/cakephp-elasticapm
```

## Configuration
Include this snippet in `config/app.php`

```php
'ElasticApm' => [
        'enabled' => true,
        'rumEnabled' => true,
        'appName' => '<app name>',
        'appVersion' => "1.0",
        'serverUrl' => '<apm server url>',
        'secretToken' => '<apm server secret token>',
        'environment' => "development"
    ]
```
`enabled`: If false the collection gets deactivated

Include this snippet in `src/Application.php` in your function `bootstrap()`

```php
if (Configure::read('ElasticApm.enabled')) {
    $this->addPlugin(ElasticApmPlugin::class);
}
```

## Add RUM agent
The RUM agent is optional, but provides better insights into your page speed.

Download the agent [here](https://github.com/elastic/apm-agent-rum-js/releases) and add it into your `webroot/js` dirctory

Add this to into the `<head>` area of your page
`<?php echo $this->Html->script('elastic-apm-rum.umd.min.js'); ?>`

Add this snipped directly at the beginning of `<body>`:
```
  <script>
    var parts = window.location.pathname.split('?');
    var pageName = window.location.pathname;
    if (parts.length > 0) {
      pageName = parts[0]
    }
    if (<?php echo Configure::read('ElasticApm.rumEnabled'); ?>) {
      elasticApm.init({
        serviceName: '<?php echo Configure::read('ElasticApm.appName'); ?> RUM',
        serverUrl: '<?php echo Configure::read('ElasticApm.serverUrl'); ?>',
        environment: '<?php echo Configure::read('ElasticApm.environment'); ?>',
        serviceVersion: '<?php echo Configure::read('ElasticApm.appVersion'); ?>',
        pageLoadTraceId: '<?= $traceId ?>',
        pageLoadSpanId: '<?= $spanId ?>',
        pageLoadSampled: true,
        pageLoadTransactionName: pageName,
        breakdownMetrics: true,
        //monitorLongtasks: true,
      });
      //Optional adding user context
      elasticApm.setUserContext({
        id: <userid>,
        username: <username>,
      });
    }
  </script>
```
