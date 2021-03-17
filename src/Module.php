<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MKDF\Sparql;

use MKDF\Datasets\Service\DatasetsFeatureManagerInterface;
use MKDF\Sparql\Feature\SparqlFeature;
use Zend\Mvc\MvcEvent;

class Module
{
    public function getConfig()
    {
        $config = [];
        $moduleConfig = include __DIR__ . '/../config/module.config.php';
        return $moduleConfig;
    }

    /**
     * This method is called once the MVC bootstrapping is complete and allows
     * to register event listeners.
     */
    public function onBootstrap(MvcEvent $event)
    {
        $featureManager = $event->getApplication()->getServiceManager()->get(DatasetsFeatureManagerInterface::class);
        $featureManager->registerFeature($event->getApplication()->getServiceManager()->get(SparqlFeature::class));
    }
}