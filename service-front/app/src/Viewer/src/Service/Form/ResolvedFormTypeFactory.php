<?php

declare(strict_types=1);

namespace Viewer\Service\Form;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeFactory as SymfonyResolvedFormTypeFactory;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class ResolvedFormTypeFactory extends SymfonyResolvedFormTypeFactory
{
    /**
     * {@inheritdoc}
     */
    public function createResolvedType(FormTypeInterface $type, array $typeExtensions, ResolvedFormTypeInterface $parent = null)
    {
        return new ResolvedFormType($type, $typeExtensions, $parent);
    }
}
