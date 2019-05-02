<?php

declare(strict_types=1);

namespace Viewer\Service\Form;

use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Interop\Container\ContainerInterface;
use DI\Definition\FactoryDefinition;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validation;
use Viewer\Form\ShareCode;

class FormFactory
{
    public function __invoke(ContainerInterface $container, $definition)
    {

        $tokenManager = new TokenManager();

        // Set up the Validator component
        $validator = Validation::createValidator();

        $ffb = Forms::createFormFactoryBuilder();

        // The beginning of the chain to customise this
        $ffb->setResolvedTypeFactory(new ResolvedFormTypeFactory);

        $ffb->addExtension(new ValidatorExtension($validator));
        $ffb->addExtension(new CsrfExtension($tokenManager));

        // TODO as a part of UML-105
        // $ffb->addExtension(new PSR7RequestHandler());

        $ffb->addType(new ShareCode());

        $formFactory = $ffb->getFormFactory();

        //---

        $className = $definition->getName();

        $type = $className::getType();

       $form = $formFactory->create(
            $type, null, []
        );

       $form->setCsrfTokenManager($tokenManager);

       return $form;
    }
}
