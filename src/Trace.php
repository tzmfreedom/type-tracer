<?php

namespace Tzmfreedom\TypeTracer;

readonly class Trace
{
    public array $argTypes;
    public string $className;
    public string $methodName;

    public function __construct(
        public string $functionName,
        public array $args
    )
    {
        if (str_contains($functionName, '::')) {
            $parts = explode('::', $this->functionName);
            $this->className = $parts[0];
            $this->methodName = $parts[1];
        }
        if (str_contains($this->functionName, '->')) {
            $parts = explode('->', $this->functionName);
            $this->className = $parts[0];
            $this->methodName = $parts[1];
        }
        $this->argTypes = array_map(function($arg) {
            return match(true) {
                str_starts_with($arg, "'") => 'string',
                str_starts_with($arg, 'class') => '\\' . explode(' ', $arg)[1],
                str_starts_with($arg, '[') => 'array',
                in_array($arg, ['TRUE', 'FALSE'], true) => 'bool',
                filter_var($arg, FILTER_VALIDATE_INT) !== false => 'int',
                filter_var($arg, FILTER_VALIDATE_FLOAT) !== false => 'float',
                $arg === 'NULL' => 'null',
                default => '',
            };
        }, $this->args);
    }

    public function toTsv()
    {
        $argString = implode(',', $this->argTypes);
        return "$this->functionName\t$argString";
    }
}
