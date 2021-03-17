<?php

namespace MKDF\Sparql;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;

return [
    'controllers' => [
        'factories' => [
            Controller\SparqlController::class => Controller\Factory\SparqlControllerFactory::class
        ],
    ],
    'service_manager' => [
        'aliases' => [
        ],
        'factories' => [
            Feature\SparqlFeature::class => Feature\Factory\SparqlFeatureFactory::class
        ]
    ],
    'router' => [
        'routes' => [
            'sparql' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/dataset/sparql/:action/:id',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\SparqlController::class,
                        'action' => 'query'
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Stream' => __DIR__ . '/../view',
        ],
    ],
    // The 'access_filter' key is used by the User module to restrict or permit
    // access to certain controller actions for unauthenticated visitors.
    'access_filter' => [
        'options' => [
            // The access filter can work in 'restrictive' (recommended) or 'permissive'
            // mode. In restrictive mode all controller actions must be explicitly listed
            // under the 'access_filter' config key, and access is denied to any not listed
            // action for users not logged in. In permissive mode, if an action is not listed
            // under the 'access_filter' key, access to it is permitted to anyone (even for
            // users not logged in. Restrictive mode is more secure and recommended.
            'mode' => 'restrictive'
        ],
        'controllers' => [
            Controller\SparqlController::class => [
                // Allow anyone to visit "index" and "about" actions
                //['actions' => ['index'], 'allow' => '@'],
                ['actions' => ['details'], 'allow' => '*'],
                // Allow authenticated users to ...
                //['actions' => ['add','edit','delete','delete-confirm'], 'allow' => '@']
            ],
        ]
    ],
    'navigation' => [
        //'default' => [
        //    [
                //'label' => 'Stream',
                //'route' => 'dataset',
        //    ],
        //],
    ],
];
