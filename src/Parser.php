<?php

namespace Tzmfreedom\TypeTracer;

class Parser
{
    public function __construct(
        public string $targetPrefix = '',
    )
    {}

    public function parse(string $file): array|false
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return false;
        }
        fgets($handle);
        fgets($handle);
        $traces = [];
        while (($data = fgetcsv($handle, null, "\t")) !== false) {
            if (
                $data[0] === null ||
                str_starts_with($data[0], 'TRACE ') ||
                $data[2] !== '0'
            ) {
                continue;
            }
            if ($this->targetPrefix !== '' && !str_starts_with($data[5], $this->targetPrefix)) {
                continue;
            }
            $args = [];
            for ($i = 11; $i < count($data); $i++) {
                $args[] = $data[$i];
            }
            $traces[] = new Trace(functionName: $data[5], args: $args);
        }
        fclose($handle);
        return $traces;
    }
}
