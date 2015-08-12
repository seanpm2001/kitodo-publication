<?php
namespace EWW\Dpf\Controller;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ClientController
 */
class ClientController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * sysLanguageRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\SysLanguageRepository
	 * @inject
	 */
	protected $sysLanguageRepository = NULL;

        
        /**
         * clientRepository
         *
         * @var \EWW\Dpf\Domain\Repository\ClientRepository
         * @inject
         */
        protected $clientRepository = NULL;
        
        
        /**
         * InputOptionRepository
         *
         * @var \EWW\Dpf\Domain\Repository\InputOptionRepository
         * @inject
         */
        protected $inputOptionRepository = NULL;
        
        
        /**
         * InputOptionListRepository
         *
         * @var \EWW\Dpf\Domain\Repository\InputOptionListRepository
         * @inject
         */
        protected $inputOptionListRepository = NULL;

        
        /**
         * persistence manager
         *
         * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
         * @inject
         */
        protected $persistenceManager;
        
        
        // TypoScript settings 
        protected $settings = array();
        
        // Id of the selected page in the page tree
        protected $selectedPageUid;
        
        // Page information of selected page 
        protected $pageInfo;
                                
 
        protected function initializeAction() {
            
            $this->selectedPageUid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

            $this->pageInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->selectedPageUid, $GLOBALS['BE_USER']->getPagePermsClause(1));
 
            $configManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
 
            $this->settings = $configManager->getConfiguration(
                $this->request->getControllerExtensionName(),
                $this->request->getPluginName()
            );
        }
        
        
        protected function getPageInfo($pageUid) {                        
            return \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($pageUid, $GLOBALS['BE_USER']->getPagePermsClause(1));                
        }
                
                                
        protected function initializeView($view) {
            parent::initializeView($view);
            
            
            
            
        }
        
        
        /**
         * start action
         * 
         * @param \EWW\Dpf\Domain\Model\Client $newClient         
         */
        public function newAction(\EWW\Dpf\Domain\Model\Client $newClient=NULL) {    
            if ($this->isValidClientFolder()) { 
                
                $this->addFlashMessage(
                        "",
                        $messageTitle = 'Der ausgewählte Ordner enthält noch keine Mandanten-Konfiguration!',
                        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
                        $storeInSession = TRUE
                    );  
                
                $this->view->assign('isValidClientFolder',$this->isValidClientFolder());            
                $this->view->assign('newClient',$newClient);
            }    
           
        }
        
                               
        /**
         * initializeClient action
         * 
         * @param \EWW\Dpf\Domain\Model\Client $newClient
         */
        public function createAction(\EWW\Dpf\Domain\Model\Client $newClient) {
           
            
            if ($this->isValidClientFolder()) {
               // $newClient = $this->objectManager->get('EWW\\Dpf\\Domain\\Model\\Client');
                                            
                //$newClient->setClient("Ein neuer Mandant");
                //$newClient->setPid($this->selectedPageUid);                                              
                $newClient->setPid($this->selectedPageUid);
                $this->clientRepository->add($newClient);      
                
                $this->addBaseInputOptionLists($this->selectedPageUid);
                                
                
                $this->addFlashMessage(
                        "Mittels des Listen-Moduls können Sie nun die weitere Konfiguration durchführen.",
                        $messageTitle = 'Der QUCOSA-Client wurde erfolgreich angelegt!',
                        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
                        $storeInSession = TRUE
                    );                                   
                $this->redirect('default');
            }
                          
            $this->redirect('new');                       
        }
             
        
        /**
         * default action
         * 
         */
        public function defaultAction() {
            
        }
        
        
        
        protected function isValidClientFolder() {
            
            
            if (!$this->selectedPageUid) {
               $this->addFlashMessage(
                    "Bitte wählen Sie im Seitenbaum einen Systemordner aus, der als QUCOSA-Client initialisiert werden soll.",
                    $messageTitle = 'Bitte wählen Sie einen Zielordner aus!',
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::INFO,
                    $storeInSession = TRUE
                );
                return FALSE; 
            } else {   
                
                // check if the selected page already contains a QUCOSA-Client or if it is a subpage of a Client.
                $client = $this->clientRepository->findAllByPid($this->selectedPageUid)->current();
                                                             
                $pageInfo = $this->getPageInfo($this->selectedPageUid); 
                while ($pageInfo['uid'] != 0 && !$client) {
                    $client = $this->clientRepository->findAllByPid($pageInfo['uid'])->current();                                
                    $pageInfo = $this->getPageInfo($pageInfo['pid']); 
                }

            
                if ($client) {
                   $this->addFlashMessage(
                        "Dieser Ordner ist bereits Bestandteil eines initialisierten QUCOSA-Clients.",
                        $messageTitle = 'Eine Initialisierung ist hier leider nicht möglich!',
                        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                        $storeInSession = TRUE
                    );
                    return FALSE; 
                }

                if ($this->pageInfo['doktype'] != 254) {               
                    $this->addFlashMessage(
                        "Bitte wählen Sie einen Systemordner aus, nur diese können als QUCOSA-Client verwendet werden.",
                        $messageTitle = 'Eine Initialisierung ist hier leider nicht möglich!',
                        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                        $storeInSession = TRUE
                    );    
                    return FALSE;    
                }            
            }            
           
            return TRUE;
        }
        
        
        /**
         * adds all default input options
         * 
         * @param integer $storagePid
         */        
        protected function addBaseInputOptionLists($storagePid) {
                       
            
            $inputOptionTranslator = $this->objectManager->get('EWW\\Dpf\\Helper\\InputOptionTranslator');
            $inputOptionTranslator->init('iso-639-2b');
                                    
            $iso6392b = $this->objectManager->get('EWW\\Dpf\\Configuration\\InputOption\\Iso6392b');            
            
            foreach ($iso6392b->getOptions() as $option) {

                // create input option for the default language                                                           
                $defaultLangInputOption = $this->objectManager->get('EWW\\Dpf\\Domain\\Model\\InputOption');                                
                $defaultLangInputOption->setName($option);                       
                $defaultLangInputOption->setDisplayName($inputOptionTranslator->translate($option));                                    
                $defaultLangInputOption->setValue($option);                
                $defaultLangInputOption->setPid($storagePid);                
                $defaultLangInputOption->setSysLanguageUid(0);                
                $this->inputOptionRepository->add($defaultLangInputOption);                
                $this->persistenceManager->persistAll();
                                                 
                // create input option for all other languages                
                $installedlanguages = $this->sysLanguageRepository->findInstalledLanguages();                                
                foreach ($installedlanguages as $installedLanguage) {                                        
                    $languageInputOption = $this->objectManager->get('EWW\\Dpf\\Domain\\Model\\InputOption');   
                    $languageInputOption->setDisplayName($inputOptionTranslator->translate($option,$installedLanguage->getFlag()));
                    $languageInputOption->setPid($storagePid);
                    $languageInputOption->setSysLanguageUid($installedLanguage->getUid());                   
                    $languageInputOption->setL10nParent($defaultLangInputOption->getUid());                    
                    $this->inputOptionRepository->add($languageInputOption);   
                }     
                    
            }
                                                                                                                     
            $this->persistenceManager->persistAll();                                               
        }
        
}

?>
