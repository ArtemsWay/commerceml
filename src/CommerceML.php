<?php

namespace ArtemsWay\CommerceML;

use ArtemsWay\CommerceML\Models\Property;
use ArtemsWay\CommerceML\Models\Category;
use ArtemsWay\CommerceML\Models\PriceType;
use ArtemsWay\CommerceML\Models\Product;
use ArtemsWay\CommerceML\Exceptions\CommerceMLException;

class CommerceML
{
    /**
     * Import xml document.
     *
     * @var \SimpleXMLElement
     */
    private $importXml;

    /**
     * Offers xml document.
     *
     * @var \SimpleXMLElement
     */
    private $offersXml;

    /**
     * Import time from import.xml.
     *
     * @var string
     */
    private $importTime;

    /**
     * Contains only changes
     * from import.xml
     *
     * @var bool
     */
    private $onlyChanges;

    /**
     * Categories from import.xml.
     *
     * @var array
     */
    private $categories = [];

    /**
     * Properties from import.xml.
     *
     * @var array
     */
    private $properties = [];

    /**
     * Products from import.xml.
     *
     * @var array
     */
    private $products = [];

    /**
     * Price types from offers.xml.
     *
     * @var array
     */
    private $priceTypes = [];

    /**
     * Class constructor.
     *
     * @param string $import Path to import.xml.
     * @param string $offers Path to offers.xml.
     */
    public function __construct($import, $offers)
    {
        $this->importXml = $this->loadXml($import);
        $this->offersXml = $this->loadXml($offers);

        $this->parse();
    }

    /**
     * Load XML from file.
     *
     * @param string $path
     * @throws CommerceMLException
     *
     * @return \SimpleXMLElement
     */
    private function loadXml($path)
    {
        if (!is_file($path)) {
            throw new CommerceMLException("Wrong file path: {$path}.");
        }

        libxml_use_internal_errors(true);

        $importXml = simplexml_load_file($path);

        if ($error = libxml_get_last_error()) {
            throw new CommerceMLException("Simple xml load file error: {$error->message}.");
        }

        if (!$importXml) {
            throw new CommerceMLException("File was not loaded: {$importXml}.");
        }

        return $importXml;
    }

    /**
     * Parsing xml files.
     *
     * @return void
     */
    private function parse()
    {
        $this->parseImportTime();
        $this->parseOnlyChanges();

        $this->parseCategories();
        $this->parseProperties();

        $this->parsePriceTypes();

        $this->parseProducts();
    }

    /**
     * Parse import time.
     *
     * @throws CommerceMLException
     * @return void
     */
    private function parseImportTime()
    {
        $importTime = $this->importXml['ДатаФормирования'];

        if (!$importTime) {
            throw new CommerceMLException('Attribute was not set: ДатаФормировния.');
        }

        $this->importTime = (string)$importTime;
    }

    /**
     * Parse contains only changes.
     *
     * @throws CommerceMLException
     * @return void
     */
    private function parseOnlyChanges()
    {
        $onlyChanges = $this->importXml->Каталог['СодержитТолькоИзменения'];

        if (!$onlyChanges) {
            throw new CommerceMLException('Attribute was not set: "СодержитТолькоИзменения".');
        }

        $this->onlyChanges = (string)$onlyChanges;
    }

    /**
     * Parse categories.
     *
     * @param \SimpleXMLElement|null $xmlCategories
     * @param \SimpleXMLElement|null $parent
     *
     * @throws CommerceMLException
     * @return void
     */
    private function parseCategories($xmlCategories = null, $parent = null)
    {
        if (is_null($xmlCategories)) {
            if (!$xmlCategories = $this->importXml->Классификатор->Группы) {
                throw new CommerceMLException('Categories not found.');
            }
        }

        foreach ($xmlCategories->Группа as $xmlCategory) {
            $category = new Category($xmlCategory);

            if (!is_null($parent)) {
                $parent->addChild($category);
            }

            $this->categories[$category->id] = $category;

            if ($xmlCategory->Группы) {
                $this->parseCategories($xmlCategory->Группы, $category);
            }
        }
    }

    /**
     * Parse properties.
     *
     * @return void
     */
    private function parseProperties()
    {
        if ($this->importXml->Классификатор->Свойства) {
            foreach ($this->importXml->Классификатор->Свойства->Свойство as $xmlProperty) {
                $property = new Property($xmlProperty);
                $this->properties[$property->id] = $property;
            }

        }
    }

    /**
     * Parse price types.
     *
     * @return void
     */
    private function parsePriceTypes()
    {
        if ($this->offersXml->ПакетПредложений->ТипыЦен) {
            foreach ($this->offersXml->ПакетПредложений->ТипыЦен->ТипЦены as $xmlPriceType) {
                $priceType = new PriceType($xmlPriceType);
                $this->priceTypes[$priceType->id] = $priceType;
            }
        }
    }

    /**
     * Parse products.
     */
    private function parseProducts()
    {
        $buffer = [
            'products' => []
        ];

        $products = $this->importXml->Каталог->Товары;
        $offers = $this->offersXml->ПакетПредложений->Предложения;

        if (!$products) throw new CommerceMLException('Products not found.');
        if (!$offers) throw new CommerceMLException('Offers not found.');

        // Parse products in import.xml.
        foreach ($products->Товар as $product) {
            $productId = (string)$product->Ид;
            $buffer['products'][$productId]['import'] = $product;
        }

        // Parse offers in offers.xml.
        foreach ($offers->Предложение as $offer) {
            $productId = (string)$offer->Ид;
            $buffer['products'][$productId]['offer'] = $offer;
        }

        // Merge import and offer to one product.
        foreach ($buffer['products'] as $item) {
            $import = isset($item['import']) ? $item['import'] : null;
            $offer = isset($item['offer']) ? $item['offer'] : null;

            if (is_null($import) || is_null($offer)) {
                continue;
            }

            $product = new Product($import, $offer);

            $this->products[$product->id] = $product;

            // Associate properties with the category.
            if (count($properties = $product->properties)) {
                $this->addPropertiesToCategory(
                    $product->category,
                    array_keys($properties)
                );
            }
        }
    }

    /**
     * Add properties to the category.
     *
     * @param string $id
     * @param array $properties
     *
     * @return void
     */
    private function addPropertiesToCategory($id, $properties)
    {
        if (isset($this->categories[$id])) {
            $properties = array_merge($this->categories[$id]->properties, $properties);
            $this->categories[$id]->properties = array_unique($properties);
        }
    }

    /**
     * Get import time.
     *
     * @return string
     */
    public function getImportTime()
    {
        return $this->importTime;
    }

    /**
     * Check if file contains changes only.
     *
     * @return bool
     */
    public function getOnlyChanges()
    {
        return $this->onlyChanges == 'true';
    }

    /**
     * Get categories.
     *
     * @return array|Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Get properties.
     *
     * @return array|Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get price types.
     *
     * @return array|PriceType[]
     */
    public function getPriceTypes()
    {
        return $this->priceTypes;
    }

    /**
     * Get products.
     *
     * @return array|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Get all parsed data.
     *
     * @return array
     */
    public function getData()
    {
        return [
            "importTime"  => $this->getImportTime(),
            "onlyChanges" => $this->getOnlyChanges(),
            "categories"  => $this->getCategories(),
            "properties"  => $this->getProperties(),
            "priceTypes"  => $this->getPriceTypes(),
            "products"    => $this->getProducts(),
        ];
    }
}
