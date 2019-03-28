<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_article_mapping")
 * @ORM\Entity(repositoryClass="ArticleMappingRepository")
 */
class ArticleMapping  {

    use BaseModelTrait;

    /**
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $number;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $commonName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $piecesPerPack;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $flavor;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $dosage;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $base;

    public function fromArray(array $settings)
    {
        foreach ($settings as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getIcNumber()
    {
        return $this->icNumber;
    }

    /**
     * @param mixed $icNumber
     */
    public function setIcNumber($icNumber)
    {
        $this->icNumber = $icNumber;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * @param mixed $commonName
     */
    public function setCommonName($commonName)
    {
        $this->commonName = $commonName;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @param mixed $supplier
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * @return mixed
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param mixed $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return mixed
     */
    public function getPiecesPerPack()
    {
        return $this->piecesPerPack;
    }

    /**
     * @param mixed $piecesPerPack
     */
    public function setPiecesPerPack($piecesPerPack)
    {
        $this->piecesPerPack = $piecesPerPack;
    }

    /**
     * @return mixed
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @param mixed $dosage
     */
    public function setDosage($dosage)
    {
        $this->dosage = $dosage;
    }

    /**
     * @return mixed
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param mixed $base
     */
    public function setBase($base)
    {
        $this->base = $base;
    }

    /**
     * @return mixed
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * @param mixed $flavor
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;
    }

    public function getMappedPropertyNames()
    {
        return $this->getPrivatePropertyNames();
    }

}
