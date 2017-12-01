<?php

namespace Dynamic\ViewableDataObject\VDOInterfaces;

/**
 * Interface DataObjectViewInterface.
 */
interface ViewableDataObjectInterface
{
    /**
     * Return an instance of SiteTree to serve as Parent.
     */
    public function getParentPage();
}
