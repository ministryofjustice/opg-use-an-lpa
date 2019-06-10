<?php

declare(strict_types=1);

namespace Common\Entity;

class Address
{
    /** @var int */
    protected $id;

    /** @var string|null */
    protected $town = null;

    /** @var string|null */
    protected $county = null;

    /** @var string|null */
    protected $postcode = null;

    /** @var string|null */
    protected $country = null;

    /** @var string|null */
    protected $type = null;

    /** @var string|null */
    protected $addressLine1 = null;

    /** @var string|null */
    protected $addressLine2 = null;

    /** @var string|null */
    protected $addressLine3 = null;

    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    public function getTown() : ?string
    {
        return $this->town;
    }

    public function setTown(string $town) : void
    {
        $this->town = $town;
    }

    public function getCounty() : ?string
    {
        return $this->county;
    }

    public function setCounty(string $county) : void
    {
        $this->county = $county;
    }

    public function getPostcode() : ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode) : void
    {
        $this->postcode = $postcode;
    }

    public function getCountry() : ?string
    {
        return $this->country;
    }

    public function setCountry(string $country) : void
    {
        $this->country = $country;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(string $type) : void
    {
        $this->type = $type;
    }

    public function getAddressLine1() : ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1) : void
    {
        $this->addressLine1 = $addressLine1;
    }

    public function getAddressLine2() : ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2) : void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getAddressLine3() : ?string
    {
        return $this->addressLine3;
    }

    public function setAddressLine3(string $addressLine3) : void
    {
        $this->addressLine3 = $addressLine3;
    }
}