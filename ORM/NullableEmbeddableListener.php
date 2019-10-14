<?php

declare(strict_types=1);

namespace Tarifhaus\Doctrine\ORM;

use Tarifhaus\Doctrine\ORM\NullableEmbeddable\NullatorInterface;
use Tarifhaus\Doctrine\ORM\NullableEmbeddable\EvaluatorInterface;

/**
 * @see https://github.com/doctrine/doctrine2/issues/4568
 * @see https://github.com/doctrine/doctrine2/pull/1275
 */
final class NullableEmbeddableListener
{
    private $evaluator;
    private $nullator;

    /**
     * @var string[][]
     */
    private $propertyMap = [];

    public function __construct(EvaluatorInterface $evaluator, NullatorInterface $nullator)
    {
        $this->evaluator = $evaluator;
        $this->nullator = $nullator;
    }

    public function addMapping(string $entity, string $propertyPath)
    {
        if (empty($this->propertyMap[$entity])) {
            $this->propertyMap[$entity] = [];
        }

        $this->propertyMap[$entity][] = $propertyPath;
    }

    public function postLoad($object)
    {
        $entity = get_class($object);
        $classes = [$entity] + class_parents($object);
        $entries = null;

        foreach ($classes as $class) {
            if (!empty($this->propertyMap[$class])) {
                $entries = $this->propertyMap[$class];
                break;
            }
        }

        if (null === $entries) {
            return;
        }

        foreach ($entries as $property) {
            if ($this->evaluator->isNull($object, $property)) {
                $this->nullator->setNull($object, $property);
            } else {
                $embeddable = $this->evaluator->getValue($object, $property);
                if (! empty($this->propertyMap[get_class($embeddable)])) {
                    $this->postLoad($embeddable);
                }
            }
        }
    }
}
