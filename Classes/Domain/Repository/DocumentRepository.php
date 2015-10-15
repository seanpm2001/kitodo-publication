<?php
namespace EWW\Dpf\Domain\Repository;


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
 * The repository for Documents
 */
class DocumentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {
  
  public function getObjectIdentifiers() {
    
    $query = $this->createQuery();
    $query->statement("SELECT * FROM tx_dpf_domain_model_document where object_identifier != '' and object_identifier IS NOT NULL and deleted = 0");
    
    $result = $query->execute();  
    
    $objectIdentifiers = array();
    
    foreach ($result as $document) {
      $objectIdentifiers[$document->getObjectIdentifier()] = $document->getObjectIdentifier();          
    }
    
    return $objectIdentifiers;          
  }

  
  public function getNewDocuments($storagePID) {      
          
    $query = $this->createQuery();   
    $query->statement("SELECT * FROM tx_dpf_domain_model_document where length(trim(object_identifier))=0 and length(trim(remote_action))=0 AND deleted = 0 AND hidden = 0 AND pid =".$storagePID );
    
    
    
    return $query->execute();                           
  }
  
  public function getInProgressDocuments($storagePID) {      
    $query = $this->createQuery();
    $query->statement("SELECT * FROM tx_dpf_domain_model_document where (length(trim(object_identifier))>0 OR length(trim(remote_action))>0) AND deleted = 0 AND hidden = 0 AND pid =".$storagePID );
    
    return $query->execute();                           
  }
  
}