<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Facile\OpenIDClient\Issuer\IssuerBuilderInterface;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use \Facile\OpenIDClient\Issuer\IssuerBuilder as FacileIssuerBuilder;

class IssuerBuilder implements IssuerBuilderInterface
{
    private FacileIssuerBuilder $issuerBuilder;

    public function __construct()
    {
        $this->issuerBuilder = new FacileIssuerBuilder();
    }

    public function setMetadataProviderBuilder(?MetadataProviderBuilder $metadataProviderBuilder): self
    {
        $this->issuerBuilder->setMetadataProviderBuilder($metadataProviderBuilder);
        return $this;
    }

    public function build(string $resource): IssuerInterface
    {
        return $this->issuerBuilder->build($resource);
    }
}
