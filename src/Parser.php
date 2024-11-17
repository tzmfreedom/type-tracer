<?php

namespace Tzmfreedom\TypeTracer;

readonly class Parser
{
    public function __construct(
        private string $targetPrefix = '',
    )
    {}

    /**
     * @return Trace[]|false
     */
    public function parse(string $file): array|false
    {
        if (str_ends_with($file, '.gz')) {
            $handle = gzopen($file, 'r');
        } else {
            $handle = fopen($file, 'r');
        }
        if ($handle === false) {
            return false;
        }
        // ignore header
        fgets($handle);
        fgets($handle);
        // parse tsv body
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
            $traces[] = (new Trace(functionName: $data[5], args: $args))->toArray();
        }
        fclose($handle);
        return $traces;
    }

    public function writeTypeTraceFile($filename, $pattern): void
    {
        $files = $this->rglob($pattern);
        $traces = [];
        foreach ($files as $file) {
            $traces = [...$traces, ...$this->parse($file)];
        }
        file_put_contents($filename, json_encode($this->groupByTraceKey($traces)));
    }

    /**
     * @param string $pattern
     * @return list<string>
     */
    private function rglob(string $pattern): array
    {
        $files = glob($pattern);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = [
                ...$files,
                ...$this->rglob($dir . "/" . basename($pattern)),
            ];
        }
        return $files;
    }

    /**
     * @param Trace[] $traces
     * @return array<string, Trace[]>
     */
    private function groupByTraceKey(array $traces): array
    {
        $res = [];
        foreach ($traces as $trace) {
            if (array_key_exists($trace->functionName, $res)) {
                $res[$trace->functionName][] = $trace;
            } else {
                $res[$trace->functionName] = [$trace];
            }
        }
        return $res;
    }
}
