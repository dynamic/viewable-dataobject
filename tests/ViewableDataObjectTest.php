<?php

namespace Dynamic\ViewableDataObject\Test;

use Dynamic\ViewableDataObject\Test\TestOnly\ViewableTestObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBHTMLText;

class ViewableDataObjectTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = array(
        'fixtures.yml',
    );

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        ViewableTestObject::class,
    ];

    /**
     * @return \SilverStripe\ORM\DataObject
     */
    protected function getObject()
    {
        return $this->objFromFixture(ViewableTestObject::class, 'one');
    }

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $object = $this->getObject();
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testHasParentPage()
    {
        $object = $this->getObject();
        $this->assertInstanceOf(\Page::class, $object->getParentPage());
    }

    /**
     *
     */
    public function testHasViewAction()
    {
        $object = $this->getObject();
        $this->assertEquals($object->hasViewAction(), 'view');
    }

    /**
     *
     */
    public function testLink()
    {
        $object = $this->getObject();
        $page = $this->objFromFixture(\Page::class, 'default');
        $this->assertEquals($page->Link().'view/'.$object->URLSegment.'/', $object->Link());
    }

    /**
     *
     */
    public function testGetAbsoluteLink()
    {
        $object = $this->getObject();
        $page = $this->objFromFixture(\Page::class, 'default');
        $this->assertEquals($page->AbsoluteLink().'view/'.$object->URLSegment.'/', $object->AbsoluteLink());
    }

    /**
     *
     */
    public function testValidURLSegment()
    {
        $object = $this->getObject();
        $object2 = $this->objFromFixture(ViewableTestObject::class, 'two');
        $object->URLSegment = $object2->URLSegment;
        $this->assertFalse($object->validURLSegment());
        $object->URLSegment = 'object-one';
        $this->assertTrue($object->validURLSegment());
    }

    /**
     *
     */
    public function testBreadcrumbs()
    {
        $object = $this->getObject();
        $this->assertInstanceOf(DBHTMLText::class, $object->Breadcrumbs());
    }
}
