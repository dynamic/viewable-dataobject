<?php

namespace Dynamic\ViewableDataObject\Extensions;

use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use SilverStripe\View\HTML;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\View\SSViewer;

/**
 * Class ViewableDataObject
 * @package Dynamic\ViewableDataObject\Extensions
 *
 * @property string $Title
 * @property string $MenuTitle
 * @property string $URLSegment
 * @property string $MetaTitle
 * @property string $MetaDescription
 */
class ViewableDataObject extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'MenuTitle' => 'Varchar(255)',
        'URLSegment' => 'Varchar(255)',
        'MetaTitle' => 'Varchar(255)',
        'MetaDescription' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $defaults = array(
        'Title' => 'New Item'
    );

    /**
     * @var array
     */
    private static $indexes = [
        'URLSegment' => true,
    ];

    /**
     * @var array
     */
    private static $casting = array(
        'Breadcrumbs' => 'HTMLFragment',
        'Link' => 'Text',
        'RelativeLink' => 'Text',
        'AbsoluteLink' => 'Text',
        'MetaTags' => 'HTMLFragment',
    );

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'MenuTitle',
            'URLSegment',
            'MetaTitle',
            'MetaDescription',
        ));

        $fields->insertAfter(
            TextField::create('MenuTitle'),
            'Title'
        );

        if ($page = $this->hasParentPage()) {
            $fields->insertAfter(
                SiteTreeURLSegmentField::create('URLSegment')
                    ->setURLPrefix($page->Link() . $this->hasViewAction() . '/'),
                'MenuTitle'
            );
        }

        $fields->addFieldToTab(
            'Root.Main',
            ToggleCompositeField::create(
                'Metadata',
                'Metadata',
                array(
                    new TextField('MetaTitle', $this->owner->fieldLabel('MetaTitle')),
                    new TextareaField('MetaDescription', $this->owner->fieldLabel('MetaDescription')),
                )
            )
        );
    }

    /**
     * @return bool
     */
    public function hasParentPage()
    {
        if ($this->owner->hasMethod('getParentPage')) {
            return $this->owner->getParentPage();
        }

        return false;
    }

    /**
     * @return string
     */
    public function hasViewAction()
    {
        if ($this->owner->hasMethod('getViewAction')) {
            return $this->owner->getViewAction();
        }

        return 'view';
    }

    /**
     * @param null $action
     *
     * @return bool|string
     */
    public function Link($action = null)
    {
        if ($this->hasParentPage()) {
            return Controller::join_links(
                $this->hasParentPage()->Link(),
                $this->hasViewAction(),
                $this->owner->RelativeLink($action)
            );
        }

        return false;
    }

    /**
     * @param null $action
     *
     * @return string
     */
    public function AbsoluteLink($action = null)
    {
        if ($this->owner->hasMethod('alternateAbsoluteLink')) {
            return $this->owner->alternateAbsoluteLink($action);
        } else {
            return Director::absoluteURL($this->owner->Link($action));
        }
    }

    /**
     * @param null $action
     *
     * @return string
     */
    public function RelativeLink($action = null)
    {
        if ($this->owner->ParentID && SiteConfig::current_site_config()->nested_urls) {
            $parent = $this->owner->Parent();
            // If page is removed select parent from version history (for archive page view)
            if ((!$parent || !$parent->exists()) && !$this->owner->isOnDraft()) {
                $parent = Versioned::get_latest_version($this->owner->ClassName, $this->owner->ParentID);
            }
            $base = $parent->RelativeLink($this->owner->URLSegment);
        } elseif (!$action && $this->owner->URLSegment == RootURLController::get_homepage_link()) {
            // Unset base for root-level homepages.
            // Note: Homepages with action parameters (or $action === true)
            // need to retain their URLSegment.
            $base = null;
        } else {
            $base = $this->owner->URLSegment;
        }

        $this->owner->extend('updateRelativeLink', $base, $action);

        // Legacy support: If $action === true, retain URLSegment for homepages,
        // but don't append any action
        if ($action === true) {
            $action = null;
        }

        return Controller::join_links($base, '/', $action);
    }

    /**
     * @return bool|mixed
     */
    public function validURLSegment()
    {
        if (SiteConfig::current_site_config()->nested_urls && $parent = $this->owner->Parent()) {
            if ($controller = ModelAsController::controller_for($parent)) {
                if ($controller instanceof Controller && $controller->hasAction($this->owner->URLSegment)) {
                    return false;
                }
            }
        }

        if (!SiteConfig::current_site_config()->nested_urls || !$this->owner->ParentID) {
            if (class_exists($this->owner->URLSegment) &&
                is_subclass_of($this->owner->URLSegment, RequestHandler::class)) {
                return false;
            }
        }

        // Filters by url, id, and parent
        $table = DataObject::getSchema()->tableForField($this->owner->ClassName, 'URLSegment');
        $filter = array('"' . $table . '"."URLSegment"' => $this->owner->URLSegment);
        if ($this->owner->ID) {
            $filter['"' . $table . '"."ID" <> ?'] = $this->owner->ID;
        }
        if (SiteConfig::current_site_config()->nested_urls) {
            $filter['"' . $table . '"."ParentID"'] = $this->owner->ParentID ? $this->owner->ParentID : 0;
        }

        // If any of the extensions return `0` consider the segment invalid
        $extensionResponses = array_filter(
            (array)$this->owner->extend('augmentValidURLSegment'),
            function ($response) {
                return !is_null($response);
            }
        );
        if ($extensionResponses) {
            return min($extensionResponses);
        }

        // Check existence
        return !DataObject::get($this->owner->ClassName, $filter)->exists();
    }

    /**
     * @param $title
     *
     * @return string
     */
    public function generateURLSegment($title)
    {
        $filter = URLSegmentFilter::create();
        $t = $filter->filter($title);
        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (!$t || $t == '-' || $t == '-1') {
            $t = "page-$this->owner->ID";
        }
        // Hook for extensions
        $this->owner->extend('updateURLSegment', $t, $title);

        return $t;
    }

    /**
     * Generate custom meta tags to display on the DataObject view page.
     *
     * @param bool $includeTitle
     *
     * @return DBField
     */
    public function MetaTags($includeTitle = true)
    {
        $tags = array();

        if ($includeTitle && strtolower($includeTitle) != 'false') {
            $title = $this->owner->MetaTitle
                ? $this->owner->MetaTitle
                : $this->owner->Title;
            $tags[] = HTML::createTag('title', array(), $title);
        }
        $generator = trim(Config::inst()->get(SiteTree::class, 'meta_generator'));
        if (!empty($generator)) {
            $tags[] = HTML::createTag('meta', array(
                'name' => 'generator',
                'content' => $generator,
            ));
        }

        $charset = ContentNegotiator::config()->uninherited('encoding');
        $tags[] = HTML::createTag('meta', array(
            'http-equiv' => 'Content-Type',
            'content' => 'text/html; charset=' . $charset,
        ));

        if ($this->owner->MetaDescription) {
            $tags[] = HTML::createTag('meta', array(
                'name' => 'description',
                'content' => $this->owner->MetaDescription,
            ));
        }

        $this->owner->extend('updateMetaTagsArray', $tags);
        $tagString = implode("\n", $tags);
        $this->owner->extend('updateMetaTags', $tagString);

        return DBField::create_field('HTMLFragment', $tagString);
    }

    /**
     * Produce the correct breadcrumb trail for use on the DataObject Item Page.
     *
     * @param int $maxDepth
     * @param bool $unlinked
     * @param bool $stopAtPageType
     * @param bool $showHidden
     *
     * @return DBHTMLText
     */
    public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false)
    {
        $page = Controller::curr();
        $pages = array();
        $pages[] = $this->owner;
        while ($page
            && (!$maxDepth || count($pages) < $maxDepth)
            && (!$stopAtPageType || $page->ClassName != $stopAtPageType)
        ) {
            if ($showHidden || $page->ShowInMenus || ($page->ID == $this->owner->ID)) {
                $pages[] = $page;
            }
            $page = $page->Parent;
        }
        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->owner->customise(new ArrayData(array(
            'Pages' => new ArrayList(array_reverse($pages)),
        ))));
    }

    /**
     *
     */
    public function onBeforeWrite()
    {
        if (!$this->owner->URLSegment) {
            $siteTree = singleton(SiteTree::class);
            $this->owner->URLSegment = $siteTree->generateURLSegment($this->owner->Title);
        }

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while (!$this->owner->validURLSegment()) {
            $this->owner->URLSegment = preg_replace('/-[0-9]+$/', null, $this->owner->URLSegment) . '-' . $count;
            ++$count;
        }

        parent::onBeforeWrite();
    }
}
