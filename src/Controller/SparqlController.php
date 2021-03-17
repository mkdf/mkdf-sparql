<?php
namespace MKDF\Sparql\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Datasets\Service\DatasetPermissionManager;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter;
use Zend\View\Model\ViewModel;
use Zend\Session\SessionManager;
use Zend\Session\Container;

class SparqlController extends AbstractActionController
{
    private $_config;
    private $_repository;
    private $_dataset_repository;
    private $_keys_repository;
    private $_permissionManager;

    public function __construct(MKDFKeysRepositoryInterface $keysRepository, MKDFDatasetRepositoryInterface $datasetRepository, MKDFStreamRepositoryInterface $repository, array $config, DatasetPermissionManager $permissionManager)
    {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_dataset_repository = $datasetRepository;
        $this->_keys_repository = $keysRepository;
        $this->_permissionManager = $permissionManager;
    }

    public function queryAction() {

        return new ViewModel([
            'message'   =>  'hello from the SPARQL controller'
        ]);
    }

}
