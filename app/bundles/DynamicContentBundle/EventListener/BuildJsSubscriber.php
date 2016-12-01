<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mautic\FormBundle\Model\FormModel;
use Mautic\CoreBundle\Helper\TemplatingHelper;

/**
 * Class BuildJsSubscriber.
 */
class BuildJsSubscriber extends CommonSubscriber
{
    /**
     * @var
     */
    protected $formModel;


    /**
     * @var TemplatingHelper
     */
    protected $templatingHelper;


    /**
     * BuildJsSubscriber constructor.
     *
     * @param FormModel $formModel
     */
    public function __construct(
        FormModel $formModel,
        TemplatingHelper $templatingHelper)
    {
        $this->formModel = $formModel;
        $this->templatingHelper    = $templatingHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 200],
        ];
    }

    /**
     * Adds the MauticJS definition and core
     * JS functions for use in Bundles. This
     * must retain top priority of 1000.
     *
     * @param BuildJsEvent $event
     */
    public function onBuildJs(BuildJsEvent $event)
    {

       $script = strip_tags($this->templatingHelper->getTemplating()->render(
            'MauticFormBundle:Builder:script.html.php'
        ));

        $dwcUrl = $this->router->generate('mautic_api_dynamicContent_action', ['objectAlias' => 'slotNamePlaceholder'], UrlGeneratorInterface::ABSOLUTE_URL);

        $js = <<<JS
        
            if (typeof MauticDomain == 'undefined') {
                var MauticDomain = '{$this->request->getSchemeAndHttpHost()}';
            }            
            if (typeof MauticLang == 'undefined') {
                var MauticLang = {
                     'submittingMessage': "{$this->translator->trans('mautic.form.submission.pleasewait')}"
        };
            }
MauticJS.replaceDynamicContent = function () {
    var dynamicContentSlots = document.querySelectorAll('.mautic-slot');
    if (dynamicContentSlots.length) {
        MauticJS.iterateCollection(dynamicContentSlots)(function(node, i) {
            var slotName = node.dataset.slotName;
            var url = '{$dwcUrl}'.replace('slotNamePlaceholder', slotName);
            MauticJS.makeCORSRequest('GET', url, {}, function(response, xhr) {
                if (response.length) {
                    node.innerHTML = response;
                    if (response.search("mauticform_wrapper") > 0 && typeof MauticSDKLoaded != 'undefined') { 
                         MauticSDK.onLoad(); 
                    }
                }
            });
        });
        {$script}
    }
};

MauticJS.pixelLoaded(MauticJS.replaceDynamicContent);
JS;
        $event->appendJs($js, 'Mautic Dynamic Content');
    }
}
