<?php

namespace Dynamic\ViewableDataObject\Test\TestOnly;

use Dynamic\ViewableDataObject\Extensions\ViewableDataObject;
use Dynamic\ViewableDataObject\VDOInterfaces\ViewableDataObjectInterface;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ViewableTestObject extends DataObject implements TestOnly, ViewableDataObjectInterface
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
    ];

    /**
     * @var string
     */
    private static $table_name = 'ViewableTestObject';

    /**
     * @var array
     */
    private static $extensions = [
        ViewableDataObject::class,
    ];

    /**
     * @return DataObject
     */
    public function getParentPage()
    {
        return \Page::get()->first();
    }

    /**
     * @return string
     */
    public function getViewAction()
    {
        return 'view';
    }
}
