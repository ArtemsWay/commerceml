<?php

namespace ArtemsWay\CommerceML\Models;

class Category extends Model
{
    /**
     * @var string $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $parent
     */
    public $parent;

    /**
     * @var array $properties
     */
    public $properties = [];

    /**
     * Create instance from file.
     *
     * @param \SimpleXMLElement $importXml
     */
    public function __construct(\SimpleXMLElement $importXml)
    {
        $this->loadImport($importXml);
    }

    /**
     * Load category data from import.xml.
     *
     * @param \SimpleXMLElement $xml
     * @return void
     */
    public function loadImport($xml)
    {
        $this->id = (string)$xml->Ид;

        $this->name = (string)$xml->Наименование;
    }

    /**
     * Add children category.
     *
     * @param Category $category
     * @return void
     */
    public function addChild($category)
    {
        $category->parent = $this->id;
    }
}
