<?php

declare(strict_types=1);

namespace Tzmfreedom\TypeTracer\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Tzmfreedom\TypeTracer\Trace;
use Webmozart\Assert\Assert;

final class TypeDeclarationRector extends AbstractScopeAwareRector implements ConfigurableRectorInterface
{
    /** @var Trace[] $traces */
    private array $traces;
    private int $mixedTypeCount;

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
            foreach ($matchTrace['argTypes'] as $index => $argType) {
                if ($argType === '') {
                    continue;
                }
                if (!in_array($argType, $aggregateTypes[$index], true)) {
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
        return new RuleDefinition('Add param types from xdebug func trace', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function process($name)
    {
    }
}
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
class SomeClass
{
    public function process(string $name)
    {
    }
}
CODE_SAMPLE
            , [
                'files' => ['*.xt'],
            ])]);
    }

    /**
     * @param array{targetPrefix: string, file: string, mixedTypeCount?: int} $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::keyExists($configuration, 'file');
        Assert::string($configuration['file']);
        $this->traces = json_decode(file_get_contents($configuration['file']), true);
        if (array_key_exists('mixedTypeCount', $configuration)) {
            Assert::integer($configuration['mixedTypeCount']);
            $this->mixedTypeCount = $configuration['mixedTypeCount'];
        } else {
            $this->mixedTypeCount = PHP_INT_MAX;
        }
    }
}
