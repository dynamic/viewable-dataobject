<?php

use Dynamic\ViewableDataObject\VDOInterfaces\ViewableDataObjectInterface;

class ViewableDataObjectTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = array(
        'viewable-dataobject/tests/fixtures.yml',
    );

    /**
     * @var array
     */
    protected $extraDataObjects = array(
        'ViewableTestObject',
    );

    protected function getObject()
    {
        return $this->objFromFixture('ViewableTestObject', 'one');
    }

    public function testUpdateCMSFields()
    {
        $object = $this->getObject();
        $fields = $object->getCMSFields();
        $this->assertInstanceOf('FieldList', $fields);
    }

    public function testHasParentPage()
    {
        $object = $this->getObject();
        $this->assertInstanceOf('Page', $object->getParentPage());
    }

    public function testHasViewAction()
    {
        $object = $this->getObject();
        $this->assertEquals($object->hasViewAction(), 'view');
    }

    public function testLink()
    {
        $object = $this->getObject();
        $page = $this->objFromFixture('Page', 'default');
        $this->assertEquals($page->Link() . 'view/' . $object->URLSegment, $object->Link());
    }

    public function testGetAbsoluteLink()
    {
        $object = $this->getObject();
        $page = $this->objFromFixture('Page', 'default');
        $this->assertEquals($page->AbsoluteLink() . 'view/' . $object->URLSegment, $object->getAbsoluteLink());
    }

    public function testValidURLSegment()
    {
        $object = $this->objFromFixture('ViewableTestObject', 'one');
        $object2 = $this->objFromFixture('ViewableTestObject', 'two');
        $object->URLSegment = $object2->URLSegment;
        $this->assertFalse($object->validURLSegment());
        $object->URLSegment = 'object-one';
        $this->assertTrue($object->validURLSegment());
    }

    public function testBreadcrumbs()
    {
        $object = $this->objFromFixture('ViewableTestObject', 'one');
        $this->assertInstanceOf("HTMLText", $object->Breadcrumbs());
    }
}

class ViewableTestObject extends DataObject implements TestOnly, Dynamic\ViewableDataObject\VDOInterfaces\ViewableDataObjectInterface
{
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HtmlText',
    ];

    private static $extensions = [
        'Dynamic\ViewableDataObject\Extensions\ViewableDataObject',
    ];

    public function getParentPage()
    {
        return Page::get()->first();
    }
}