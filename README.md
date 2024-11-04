# TypeTracer

Type Tracer is Rector Custom Rule to add type declaration.

## Install

TBD

## Usage

```php
<?php

use Tzmfreedom\TypeTracer\Rector\TypeDeclarationRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(TypeDeclarationRector::class, [
        'mixedTypeCount' => 4,
        'targetPrefix' => 'App\\',
        'files' => [
            '*.xt'
        ],
    ]);
```


## How it works

1. Record PHP function execution traces with arguments by Xdebug Func Trace.
2. Aggregate function traces.
3. Add type declaration with aggregated function traces by Rector.
