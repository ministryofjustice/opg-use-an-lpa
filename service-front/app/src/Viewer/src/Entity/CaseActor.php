<?php

declare(strict_types=1);

namespace Viewer\Entity;

use \DateTime;

class CaseActor
{
    /** @var int*/
    protected $id;
    
    /** @var string */
    protected $uId;
    
    /** @var string */
    protected $email;
    
    /** @var DateTime */
    protected $dob;
    
    /** @var string */
    protected $salutation;
    
    /** @var string */
    protected $firstname;
    
    /** @var string */
    protected $middlenames;
    
    /** @var string */
    protected $surname;
    
    /** @var string */
    protected $companyName;
    
    /** @var Address[] */
    protected $addresses;

	public function getId() : int
	{
		return $this->id;
	}

	public function setId(int $id) : void
	{
		$this->id = $id;
	}

	public function getUId() : string
	{
		return $this->uId;
	}

	public function setUId(string $uId) : void
	{
		$this->uId = $uId;
	}

	public function getEmail() : string
	{
		return $this->email;
	}

	public function setEmail(string $email) : void
	{
		$this->email = $email;
	}

	public function getDob() : DateTime
	{
		return $this->dob;
	}

	public function setDob(DateTime $dob) : void
	{
		$this->dob = $dob;
	}

	public function getSalutation() : string
	{
		return $this->salutation;
	}

	public function setSalutation(string $salutation) : void
	{
		$this->salutation = $salutation;
	}

	public function getFirstname() : string
	{
		return $this->firstname;
	}

	public function setFirstname(string $firstname) : void
	{
		$this->firstname = $firstname;
	}

	public function getMiddlenames() : string
	{
		return $this->middlenames;
	}

	public function setMiddlenames(string $middlenames) : void
	{
		$this->middlenames = $middlenames;
	}

	public function getCompanyName() : string
	{
		return $this->companyName;
	}

	public function setCompanyName(string $companyName) : void
	{
		$this->companyName = $companyName;
	}

	public function getSurname() : string
	{
		return $this->surname;
	}

	public function setSurname(string $surname) : void
	{
		$this->surname = $surname;
	}

	public function getAddresses() : array
	{
		return $this->addresses;
	}

	public function setAddresses(array $addresses) : void
	{
		$this->addresses = $addresses;
	}
}