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

class JobsController extends AbstractActionController
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

    public function createAction() {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie can they make SPARQL read queries)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id,$dataset->id);
        if ($can_view && $can_read && $can_edit && $userHasKey) {
            if (!$streamExists) {
                $this->flashMessenger()->addMessage('Data API not yet activated');
                return $this->redirect()->toRoute('stream', ['action'=>'details']);
            }

            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];

            if($this->getRequest()->isPost()) {
                $data = $this->params()->fromPost();

                // Check if this job is a CONSTRUCT/REBUILDGRAPH/REBUILDNAMESPACE
                switch ($data['jobType']) {
                    case 'CONSTRUCT':
                        $newDoc = [
                            'dataset'   => $dataset->uuid,
                            'job-type'  => 'CONSTRUCT',
                            'query'     => $data['query'],
                            'target-namespace'  => 'namespace-test',
                            'target-named-graph' => $data['graphName'],
                            'status'    => 'PENDING',
                            'message'   => '',
                            'history'   => [],
                            'scheduled' => '0',
                            'submitted-by'  => $this->identity(),
                            'modified'  => $this->identity()
                        ];
                        $newDocJSON = json_encode($newDoc);
                        $this->pushCreateJob($newDocJSON);
                        $this->flashMessenger()->addMessage('The CONSTRUCT job was added successfully.');
                        return $this->redirect()->toRoute('rdfjobs', ['action'=>'list', 'id'=>$id]);
                    break; // END OF CONSTRUCT CASE

                    case 'REBUILDGRAPH':
                        $newDoc = [
                            'dataset'   => $dataset->uuid,
                            'job-type'  => 'REBUILDGRAPH',
                            'target-namespace'  => '',
                            'target-named-graph' => '',
                            'document-id'   => $data['docID'],
                            'status'    => 'PENDING',
                            'message'   => '',
                            'history'   => [],
                            'scheduled' => '0',
                            'submitted-by'  => $this->identity(),
                            'modified'  => $this->identity()
                        ];
                        $newDocJSON = json_encode($newDoc);
                        $this->pushCreateJob($newDocJSON);
                        $this->flashMessenger()->addMessage('The REBUILD GRAPH job was added successfully.');
                        return $this->redirect()->toRoute('rdfjobs', ['action'=>'list', 'id'=>$id]);
                    break; // END OF REBUILD GRAPH CASE

                    case 'REBUILDDATASET':
                        $newDoc = [
                            'dataset'   => $dataset->uuid,
                            'job-type'  => 'REBUILDDATASET',
                            'target-namespace'  => '',
                            'target-named-graph' => '',
                            'status'    => 'PENDING',
                            'message'   => '',
                            'history'   => [],
                            /*
                             'history'   => [
                                [
                                    'message'   => 'This is message 1',
                                    'timestamp' => 1639498629
                                ],
                                [
                                    'message'   => 'This is message 2',
                                    'timestamp' => 1639495028
                                ],
                            ],
                            */
                            'scheduled' => '0',
                            'submitted-by'  => $this->identity(),
                            'modified'  => $this->identity()
                        ];
                        $newDocJSON = json_encode($newDoc);
                        $this->pushCreateJob($newDocJSON);
                        $this->flashMessenger()->addMessage('The REBUILD DATASET job was added successfully.');
                        return $this->redirect()->toRoute('rdfjobs', ['action'=>'list', 'id'=>$id]);
                    break; //END OF REBUILD NAMESPACE CASE
                } // END OF SWITCH STATEMENT

            }
            else {
                $keys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
                return new ViewModel([
                    'message' => $message,
                    'keys' => $keys,
                    'dataset' => $dataset,
                    'features' => $this->datasetsFeatureManager()->getFeatures($id),
                    'actions' => $actions,
                    'can_edit' => $can_edit,
                    'can_read' => $can_read,
                    'can_edit' => $can_edit,
                    'user_has_key' => $userHasKey,

                ]);
            }



        }
        else{
            $this->flashMessenger()->addMessage('You do not have the required permissions on this dataset');
            return $this->redirect()->toRoute('dataset', ['action'=>'details', 'id'=>$dataset->id]);
        }
    }

    public function detailsAction() {
        $messages = [];
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach($this->flashMessenger->getMessages() as $flashMessage) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $flashMessage
                ];
            }
        }
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $jobId = $this->params()->fromQuery('jobid','');
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        if ($can_view && $can_read && $can_edit) {
            if (!$streamExists) {
                $this->flashMessenger()->addMessage('Data API not yet activated');
                return $this->redirect()->toRoute('stream', ['action'=>'details']);
            }

            $jobJSON = $this->getRDFJob($dataset->uuid, $jobId);
            $job = json_decode($jobJSON);

            return new ViewModel([
                'messages' => $messages,
                'dataset' => $dataset,
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_edit' => $can_edit,
                'can_read' => $can_read,
                'can_edit' => $can_edit,
                'job'       => $job[0]
            ]);
        }
        else{
            $this->flashMessenger()->addMessage('You do not have the required permissions on this dataset');
            return $this->redirect()->toRoute('dataset', ['action'=>'details', 'id'=>$dataset->id]);
        }
    }

    public function listAction() {
        $messages = [];
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach($flashMessenger->getMessages() as $flashMessage) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $flashMessage
                ];
            }
        }
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie can they make SPARQL read queries)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id,$dataset->id);
        if ($can_view && $can_read && $can_edit && $userHasKey) {
            if (!$streamExists) {
                $this->flashMessenger()->addMessage('Data API not yet activated');
                return $this->redirect()->toRoute('stream', ['action'=>'details']);
            }
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            $keys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);

            $jobsJSON = $this->getRDFJobs($dataset->uuid);
            $jobs = json_decode($jobsJSON);

            return new ViewModel([
                'messages' => $messages,
                'keys' => $keys,
                'dataset' => $dataset,
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_edit' => $can_edit,
                'can_read' => $can_read,
                'can_edit' => $can_edit,
                'user_has_key' => $userHasKey,
                'jobs'  => $jobs,
            ]);
        }
        else{
            $this->flashMessenger()->addMessage('You do not have the required permissions on this dataset');
            return $this->redirect()->toRoute('dataset', ['action'=>'details', 'id'=>$dataset->id]);
        }
    }

    private function getRDFJobs($datasetUuid) {
        $jobsDataset = $this->_config['mkdf-sparql']['rdfjobs-dataset'];
        $jobsKey = $this->_config['mkdf-sparql']['rdfjobs-key'];
        $jobsDatasetExists = $this->_repository->getStreamExists($jobsDataset);
        if (!$jobsDatasetExists) {
            $this->_repository->createDataset($jobsDataset,$jobsKey);
        }
        $query = ['dataset' => $datasetUuid];
        $queryJSON = json_encode($query);
        return $this->_repository->getDocuments($jobsDataset,100,$jobsKey,$queryJSON);
    }

    private function getRDFJob($datasetUuid, $jobId) {
        $jobsDataset = $this->_config['mkdf-sparql']['rdfjobs-dataset'];
        $jobsKey = $this->_config['mkdf-sparql']['rdfjobs-key'];
        $jobsDatasetExists = $this->_repository->getStreamExists($jobsDataset);
        if (!$jobsDatasetExists) {
            $this->_repository->createDataset($jobsDataset,$jobsKey);
        }
        $query = [
            'dataset'   => $datasetUuid,
            '_id'       => $jobId
        ];
        $queryJSON = json_encode($query);
        return $this->_repository->getDocuments($jobsDataset,100,$jobsKey,$queryJSON);
    }

    private function pushCreateJob($data) {
        $jobsDataset = $this->_config['mkdf-sparql']['rdfjobs-dataset'];
        $jobsKey = $this->_config['mkdf-sparql']['rdfjobs-key'];
        $jobsDatasetExists = $this->_repository->getStreamExists($jobsDataset);
        if (!$jobsDatasetExists) {
            $this->_repository->createDataset($jobsDataset,$jobsKey);
        }
        $this->_repository->pushDocument ($jobsDataset,$data,$jobsKey);
    }

}