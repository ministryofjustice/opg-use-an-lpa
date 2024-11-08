<?php

declare(strict_types=1);

namespace Common\Entity;

class Address
{
    protected ?int $id = null;
    protected ?string $town         = null;
    protected ?string $county       = null;
    protected ?string $postcode     = null;
    protected ?string $country      = null;
    protected ?string $type         = null;
    protected ?string $addressLine1 = null;
    protected ?string $addressLine2 = null;
    protected ?string $addressLine3 = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(string $town): self
    {
        $this->town = $town;
        return $this;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function setCounty(string $county): self
    {
        $this->county = $county;
        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): self
    {
        $this->addressLine1 = $addressLine1;
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): self
    {
        $this->addressLine2 = $addressLine2;
        return $this;
    }

    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    public function setAddressLine3(string $addressLine3): self
    {
        $this->addressLine3 = $addressLine3;
        return $this;
    }
}
