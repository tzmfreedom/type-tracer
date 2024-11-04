# TypeTracer

Type Tracer is Rector Custom Rule to add type declaration.

## Install

```bash
$ composer require --dev tzmfreedom/type-tracer
```

## Usage

1. Generate func trace files.

If you use Laravel, [FuncTraceMiddleware](src/Laravel/FuncTraceMiddleware.php) is available for func trace.

2. Aggregate func trace files.

```bash
$ vendor/bin/trace-aggregate 'App\' type-aggregate.json '/tmp/trace.*'
```

3. Run Rector

```php
<?php

use Tzmfreedom\TypeTracer\Rector\TypeDeclarationRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(TypeDeclarationRector::class, [
        'mixedTypeCount' => 4,
        'file' => 'type-aggregate.json',
    ]);
```


## How it works

1. Record PHP function execution traces with arguments by Xdebug Func Trace.
2. Aggregate function traces.
3. Add type declaration with aggregated function traces by Rector.
