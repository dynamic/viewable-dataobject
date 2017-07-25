<?php

/**
 * Class ViewableTestObject
 */
class ViewableTestObject extends DataObject implements TestOnly, Dynamic\ViewableDataObject\VDOInterfaces\ViewableDataObjectInterface
{

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HtmlText',
    ];

    /**
     * @var array
     */
    private static $extensions = [
        'Dynamic\\ViewableDataObject\\Extensions\\ViewableDataObject',
    ];

    /**
     * @return DataObject
     */
    public function getParentPage()
    {
        return Page::get()->first();
    }

}