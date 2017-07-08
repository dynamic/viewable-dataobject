# SilverStripe Viewable Dataobject
[![Build Status](https://travis-ci.org/dynamic/viewable-dataobject.svg?branch=1.0)](https://travis-ci.org/dynamic/viewable-dataobject)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/?branch=1.0)
[![Code Coverage](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/badges/coverage.png?b=1.0)](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/?branch=1.0)
[![Build Status](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/badges/build.png?b=1.0)](https://scrutinizer-ci.com/g/dynamic/viewable-dataobject/build-status/1.0)
[![codecov](https://codecov.io/gh/dynamic/viewable-dataobject/branch/1.0/graph/badge.svg)](https://codecov.io/gh/dynamic/viewable-dataobject)

[![Latest Stable Version](https://poser.pugx.org/dynamic/viewable-dataobject/version)](https://packagist.org/packages/dynamic/viewable-dataobject)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/viewable-dataobject/v/unstable)](//packagist.org/packages/dynamic/viewable-dataobject)
[![Total Downloads](https://poser.pugx.org/dynamic/viewable-dataobject/downloads)](https://packagist.org/packages/dynamic/viewable-dataobject)
[![License](https://poser.pugx.org/dynamic/viewable-dataobject/license)](https://packagist.org/packages/dynamic/viewable-dataobject)
[![Monthly Downloads](https://poser.pugx.org/dynamic/viewable-dataobject/d/monthly)](https://packagist.org/packages/dynamic/viewable-dataobject)
[![Daily Downloads](https://poser.pugx.org/dynamic/viewable-dataobject/d/daily)](https://packagist.org/packages/dynamic/viewable-dataobject)

DataExtension that easily allows a dataobject to be viewed like a Page

## Requirements

- SilverStripe 3.2

## Installation

`composer require dynamic/viewable-dataobject`

In config.yml;

```yml
MyDataObject:
	extensions:
		- ViewableDataObject

```

## Example usage

On the DataObject you'd like to view as a page:

```php
<?php
	
use Dynamic\ViewableDataObject\VDOInterfaces\ViewableDataObjectInterface;
	
class MyDataObject extends DataObject implements ViewableDataObjectInterface
{
	public function getParentPage()
	{
		return MyDisplayPage::get()->first();
	}
	
	public function getViewAction()
	{
		return 'myobject';
	}
}
```	

On the Page_Controller you'd like to view your DataObject:

```php
<?php
	
class MyDisplayPage_Controller extends Page_Controller
{
    public function myobject(SS_HTTPRequest $request)
    {
        $urlSegment = $request->latestParam('ID');
	
        if (!$object = MyDataObject::get()->filter('URLSegment', $urlSegment)->first()) {
            return $this->httpError(404, "The object you're looking for doesn't seem to be here.");
        }
	
        return $this->customise(new ArrayData([
            'Object' => $object,
            'Title' => $object->Title,
            'MetaTags' => $object->MetaTags(),
            'Breadcrumbs' => $object->Breadcrumbs(),
        ]))->renderWith([
            'MyDataObject',
            'Page',
        ]);
    }
} 	
```