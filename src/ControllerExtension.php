<?php

namespace Dynamic\ViewableDataObject\Extensions;


use SilverStripe\Core\Extension;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Class ControllerExtension
 * @package Dynamic\ViewableDataObject\Extensions
 *
 * @property \SilverStripe\Control\Controller $owner
 */
class ControllerExtension extends Extension
{
    /**
     * @param array|string $templates
     * @param array|\SilverStripe\View\ArrayData $customFields
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function renderWithLayout($templates, $customFields = [])
    {
        $templates = $this->getLayoutTemplates($templates);

        $this->owner->extend('updateLayoutFields', $customFields);

        $viewer = new SSViewer($this->owner->getViewerTemplates());
        $viewer->setTemplateFile('Layout', ThemeResourceLoader::inst()->findTemplate(
            $templates,
            SSViewer::get_themes()
        ));

        return $viewer->process(
            $this->owner->customise($customFields)
        );
    }

    /**
     * @param array|string $templates
     * @return array
     */
    private function getLayoutTemplates($templates)
    {
        if (is_string($templates)) {
            $templates = [$templates];
        }

        // Always include page template as fallback
        if ($templates[count($templates) - 1] !== \Page::class) {
            array_push($templates, \Page::class);
        }

        // otherwise it renders funny
        $templates = ['type' => 'Layout'] + $templates;
        $this->owner->extend('updateLayoutTemplates', $templates);

        return $templates;
    }
}
