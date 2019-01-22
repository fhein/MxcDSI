<?php

namespace MxcDropshipInnocigs\Models\Mapping;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use MxcDropshipInnocigs\Models\Current\Article;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_article_supplier_brand")
 */
class ArticleSupplierBrandMapping
{
    use BaseModelTrait;
    /**
     * @var string $code
     * @ORM\Column(type="string", nullable=false)
     */
    private $code;

    /**
     * @var string $name
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @var string $supplier
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var string $brand
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;

    public static function fromArticle(Article $article) {
        $instance = new self();
        $instance->setName($article->getName());
        $instance->setCode($article->getCode());
        $instance->setBrand($article->getBrand());
        $instance->setSupplier($article->getSupplier());
        return $instance;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return null|string
     */
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * @param null|string $supplier
     */
    public function setSupplier(?string $supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * @return null|string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param null|string $brand
     */
    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }
}