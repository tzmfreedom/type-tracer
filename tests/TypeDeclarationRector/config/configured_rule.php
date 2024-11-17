<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        \Tzmfreedom\TypeTracer\Rector\TypeDeclarationRector::class,
        [
            'targetPrefix' => 'Tzmfreedom\\',
            'file' => __DIR__ . '/aggregate.json',
        ],
    );
};
