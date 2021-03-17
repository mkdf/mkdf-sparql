<?php

namespace MKDF\Sparql\Feature;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Datasets\Service\DatasetsFeatureInterface;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;

class SparqlFeature implements DatasetsFeatureInterface
{
    private $active = false;

    private $_dataset_repository;
    private $_repository;

    public function __construct(MKDFStreamRepositoryInterface $repository, MKDFDatasetRepositoryInterface $datasetRepository)
    {
        $this->_dataset_repository = $datasetRepository;
        $this->_repository = $repository;
    }

    public function getController() {
        return \MKDF\Sparql\Controller\SparqlController::class;
    }
    public function getViewAction(){
        return 'query';
    }
    public function getEditAction(){
        return 'query';
    }

    public function getViewHref($id){
        return '/dataset/sparql/query/'.$id;
    }

    public function getEditHref($id){
        return '/dataset/sparql/query/'.$id;
    }

    public function hasFeature($id){
        // Make a DB call for this dataset to see if it's a stream dataset
        $dataset = $this->_dataset_repository->findDataset($id);
        if (strtolower($dataset->type) == 'stream') {
            return true;
        }
        else {
            return false;
        }
    }

    public function getLabel(){
        return '<i class="fas fa-project-diagram"></i> SPARQL';
    }

    public function isActive(){
        return $this->active;
    }

    public function setActive($bool){
        $this->active = !!$bool;
    }

    public function initialiseDataset($id) {
        /*
         *  No need for the backend dataset to be created at this stage, it gets activated when
         *  the owner 'activates' it with their chosen master key.
         */
        //$dataset = $this->_dataset_repository->findDataset($id);
        //$uuid = $dataset->uuid;
        //$this->_repository->createDataset($id, null);
        //echo ("initialising stream stuff");
    }

}