<?php

declare(strict_types=1);

namespace Tzmfreedom\TypeTracer\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Tzmfreedom\TypeTracer\Parser;
use Tzmfreedom\TypeTracer\Trace;
use Webmozart\Assert\Assert;

final class TypeDeclarationRector extends AbstractScopeAwareRector implements ConfigurableRectorInterface
{
    /** @var Trace[] $traces */
    private array $traces;
    private int $mixedTypeCount;
    private string $targetPrefix = '';

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactorWithScope(Node $node, Scope $scope)
    {
        $methodName = $this->getName($node->name);
        if (!$scope->getClassReflection()) {
            return null;
        }
        $className = $scope->getClassReflection()->getName();

        $separator = match(true) {
            $node->isStatic() => '::',
            default => '->',
        };
        $key = "{$className}{$separator}{$methodName}";
        if (!array_key_exists($key, $this->traces)) {
            return null;
        }
        $aggregateTypes = array_fill(0, count($node->params), []);
        foreach ($this->traces[$key] as $matchTrace) {
            foreach ($matchTrace->argTypes as $index => $argType) {
                if ($argType === '') {
                    continue;
                }
                if (!array_search($argType, $aggregateTypes[$index], true)) {
                    $aggregateTypes[$index][] = $argType;
                }
            }
        }

        foreach ($node->params as $index => $param) {
            if ($param->type !== null) {
                continue;
            }
            if (count($aggregateTypes[$index]) > 0) {
                $param->type = match(true) {
                    count($aggregateTypes[$index]) >= $this->mixedTypeCount => new Identifier('mixed'),
                    default => new Identifier(implode('|', $aggregateTypes[$index])),
                };
            }
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('...', []);
    }

    /**
     * @param array{targetPrefix: string, files: list<string>, mixedTypeCount?: int} $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::keyExists($configuration, 'files');
        Assert::allString($configuration['files']);
        if (array_key_exists('targetPrefix', $configuration)) {
            Assert::string($configuration['targetPrefix']);
            $this->targetPrefix = $configuration['targetPrefix'];
        }
        $parser = new Parser($this->targetPrefix);
        $traces = [];
        foreach ($configuration['files'] as $pattern) {
            $files = $this->rglob($pattern);
            if ($files === false) {
                continue;
            }
            foreach ($files as $file) {
                $traces = [...$traces, ...$parser->parse($file)];
            }
        }
        $this->traces = $this->groupByTraceKey($traces);

        if (array_key_exists('mixedTypeCount', $configuration)) {
            Assert::integer($configuration['mixedTypeCount']);
            $this->mixedTypeCount = $configuration['mixedTypeCount'];
        } else {
            $this->mixedTypeCount = PHP_INT_MAX;
        }
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
