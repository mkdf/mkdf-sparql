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
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        //FIXME - Also make sure this is a stream dataset that we are retrieving.
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
        if ($can_view && $can_read && $userHasKey) {
            $docCount = 0;
            if ($streamExists) {
                $docCount = $this->_repository->getDocCount($dataset->uuid)['totalDocs'];
            }
            else {
                $this->flashMessenger()->addMessage('Data API not yet activated');
                return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
            }
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            $keys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
            return new ViewModel([
                'message' => $message,
                'doc_count' => $docCount,
                'keys' => $keys,
                'dataset' => $dataset,
                'stream_url' => $this->_repository->getApiReadHref($dataset->uuid),
                'read_url' => $this->_repository->getApiReadHref($dataset->uuid),
                'write_url' => $this->_repository->getApiWriteHref($dataset->uuid),
                'browse_url' => $this->_repository->getApiBrowseHref($dataset->uuid),
                'api_home' => $this->_repository->getApiHome(),
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_edit' => $can_edit,
                'can_read' => $can_read,
                'user_has_key' => $userHasKey,

            ]);
        }
        else{
            $this->flashMessenger()->addMessage('You do not have a read key on this dataset');
            return $this->redirect()->toRoute('dataset', ['action'=>'details', 'id'=>$dataset->id]);
        }
    }

}
