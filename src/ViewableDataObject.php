<?php

class ViewableDataObject extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'MenuTitle' => 'Varchar(255)',
        'URLSegment' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'URLSegment' => true,
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'MenuTitle',
            'URLSegment',
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
     * Returns a link via the beer primary category for the object view
     *
     * @access public
     * @return string
     * @link http://api.silverstripe.org/3.1/class-Controller.html#_join_links
     *
     */
    public function getLink()
    {
        if ($this->hasParentPage()) {
            return Controller::join_links($this->hasParentPage()->Link(), $this->hasViewAction(), $this->owner->URLSegment);
        }
        return false;
    }

    /**
     * @return string
     */
    public function Link()
    {
        return $this->getLink();
    }

    /**
     * function that gets the absolute
     * version of the event's {@link getlink()}
     *
     * @return String
     */
    public function getAbsoluteLink()
    {
        return Controller::join_links(Director::absoluteBaseURL(), $this->getLink());
    }

    /**
     * Returns true if this object has a URLSegment value that does not conflict with any other objects. This method
     * checks for:
     *  - A page with the same URLSegment that has a conflict
     *  - Conflicts with actions on the parent page
     *  - A conflict caused by a root page having the same URLSegment as a class name
     *
     * @return bool
     */
    public function validURLSegment()
    {
        if (SiteConfig::current_site_config()->nested_urls && $parent = $this->owner->Parent()) {
            if ($controller = ModelAsController::controller_for($parent)) {
                if ($controller instanceof Controller && $controller->hasAction($this->owner->URLSegment)) return false;
            }
        }

        if (!SiteConfig::current_site_config()->nested_urls || !$this->owner->ParentID) {
            if (class_exists($this->owner->URLSegment) && is_subclass_of($this->owner->URLSegment, 'RequestHandler')) return false;
        }

        // Filters by url, id, and parent
        $filter = array('"' . $this->owner->Classname . '"."URLSegment"' => $this->owner->URLSegment);
        if ($this->owner->ID) {
            $filter['"' . $this->owner->Classname . '"."ID" <> ?'] = $this->owner->ID;
        }

        if (SiteConfig::current_site_config()->nested_urls) {
            $filter['"' . $this->owner->Classname . '"."ParentID"'] = $this->owner->ParentID ? $this->owner->ParentID : 0;
        }

        $votes = array_filter(
            (array)$this->owner->extend('augmentValidURLSegment'),
            function ($v) {
                return !is_null($v);
            }
        );

        if ($votes) {
            return min($votes);
        }

        // Check existence
        $existingPage = DataObject::get_one($this->owner->Classname, $filter);
        if ($existingPage) return false;
        return !($existingPage);
    }
    /**
     * Generate a URL segment based on the title provided.
     *
     * If {@link Extension}s wish to alter URL segment generation, they can do so by defining
     * updateURLSegment(&$url, $title).  $url will be passed by reference and should be modified. $title will contain
     * the title that was originally used as the source of this generated URL. This lets extensions either start from
     * scratch, or incrementally modify the generated URL.
     *
     * @param string $title Page title
     * @return string Generated url segment
     */
    public function generateURLSegment($title)
    {
        $filter = URLSegmentFilter::create();
        $t = $filter->filter($title);
        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (!$t || $t == '-' || $t == '-1') $t = "page-$this->owner->ID";
        // Hook for extensions
        $this->owner->extend('updateURLSegment', $t, $title);
        return $t;
    }
    /**
     * Gets the URL segment for the latest draft version of this page.
     *
     * @return string
     */
    public function getStageURLSegment()
    {
        $stageRecord = Versioned::get_one_by_stage($this->owner->Classname, 'Stage', array(
            '"' . $this->owner->Classname . '"."ID"' => $this->owner->ID
        ));
        return ($stageRecord) ? $stageRecord->URLSegment : null;
    }

    /**
     * Gets the URL segment for the currently published version of this page.
     *
     * @return string
     */
    public function getLiveURLSegment()
    {
        $liveRecord = Versioned::get_one_by_stage($this->owner->Classname, 'Live', array(
            '"' . $this->owner->Classname . '"."ID"' => $this->owner->ID
        ));
        return ($liveRecord) ? $liveRecord->URLSegment : null;
    }

    /**
     * Produce the correct breadcrumb trail for use on the DataObject Item Page
     *
     * @param int $maxDepth
     * @param bool $unlinked
     * @param bool $stopAtPageType
     * @param bool $showHidden
     * @return HTMLText
     */
    public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false)
    {
        $page = Controller::curr();
        $pages = array();
        $pages[] = $this->owner;
        while (
            $page
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
            'Pages' => new ArrayList(array_reverse($pages))
        ))));
    }

    /**
     * function that performs pre-write tasks,
     * calls parent function to ensure any changes
     * are also called up the hierarchy
     */
    public function onBeforeWrite()
    {
        if (!$this->owner->URLSegment) {
            $siteTree = singleton('SiteTree');
            $this->owner->URLSegment = $siteTree->generateURLSegment($this->owner->Title);
        }

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while (!$this->owner->validURLSegment()) {
            $this->owner->URLSegment = preg_replace('/-[0-9]+$/', null, $this->owner->URLSegment) . '-' . $count;
            $count++;
        }

        parent::onBeforeWrite();
    }
}