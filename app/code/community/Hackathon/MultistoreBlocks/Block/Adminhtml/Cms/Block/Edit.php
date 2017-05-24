<?php
/**
 * Multistoreview Cms Block edit
 *
 * @author Jeroen Boersma <jeroen@srcode.nl>
 * @author Willem Wigman <info@willemwigman.nl>
 * @author Peter Jaap Blaakmeer <peterjaap@elgentos.nl>
 */


/**
 * @package Hackathon_MultistoreBlocks
 * @category Hackathon
 */
class Hackathon_MultistoreBlocks_Block_Adminhtml_Cms_Block_Edit extends Mage_Adminhtml_Block_Cms_Block_Edit
{

    /**
     * Prepare editing
     */
    public function __construct()
    {
        $this->_objectId = 'block_id';
        $this->_controller = 'cms_block';

        /* For edit screen */
        $blockId = $this->getRequest()->getParam('block_id');

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('cms')->__('Save Block'));
        if($blockId) {
            $this->_updateButton('delete', 'label', Mage::helper('cms')->__('Delete All Blocks'));
            $deleteAllBlocksUrl = Mage::helper('adminhtml')->getUrl('*/*/delete', array('allblocks' => true, 'block_id' => $blockId));
            $this->_updateButton('delete', 'onclick', "deleteConfirm('" . $this->__('Are you sure you want do this?') . "', '" . $deleteAllBlocksUrl . "')");
        }

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        if($blockId) {
            $storeIds = array();
            $block = Mage::registry('cms_block');

            foreach(Mage::app()->getStores() as $store) {
                $storeIds[$store->getId()] = $store->getName();
            }

            $resource = Mage::getSingleton('core/resource');
            $db = $resource->getConnection('core_write');

            $storeIdsWithThisBlock = $db->fetchCol(
                $db->select()
                    ->from(array('bs' => $resource->getTableName('cms/block_store')), 'store_id')
                    ->join(
                        array('b' => $resource->getTableName('cms/block')),
                        'bs.block_id = b.block_id',
                        array(
                            'store_id' => 'bs.store_id'
                        )
                    )
                    ->where('b.identifier = ?', $block->getIdentifier())
            );

            $noSpecificBlocksForStoreIds = array_diff(
                array_keys($storeIds),
                $storeIdsWithThisBlock
            );
            foreach($noSpecificBlocksForStoreIds as $storeId) {
                $this->_addButton('add_for_store_' . $storeId, array(
                    'label'     => Mage::helper('adminhtml')->__('Duplicate block for %s', $storeIds[$storeId]),
                    'onclick'   => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/new', array('original_block_id' => $block->getId(), 'store_id' => $storeId)) .'\')',
                    'class'     => 'add',
                ));
            }
            $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
            if ( ! in_array($storeId, $storeIdsWithThisBlock)) {
                $this->_addButton('add_for_store_' . $storeId, array(
                    'label'     => Mage::helper('adminhtml')->__('Duplicate block for %s', '"all store views"'),
                    'onclick'   => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/new', array('original_block_id' => $block->getId(), 'store_id' => $storeId)) .'\')',
                    'class'     => 'add',
                ));
            }
        }

        /* For add screen */
        $originalBlockId = $this->getRequest()->getParam('original_block_id');
        if($originalBlockId) {
            $storeId = $this->getRequest()->getParam('store_id');
            $originalBlock = Mage::getModel('cms/block')->load($originalBlockId);
            $model = Mage::registry('cms_block');
            $model->setData($originalBlock->getData());
            $model->unsetData('block_id');
            $model->setStoreId($storeId);
        }

        $this->_addScripts();
    }

    /**
     * Add scripts
     *
     * return void
     */
    protected function _addScripts() {

        // Functional scripts
        $this->_formScripts[] = "
            function toggleEditor() {

                var textareas = document.getElementsByTagName('textarea'),
                    forEach = Array.prototype.forEach,
                    regex = /^block_multilanguage_content.*$/;

                forEach.call(textareas, function (contentElem) {
                    if (contentElem.id !== undefined && regex.test(contentElem.id)) {
                        if (tinyMCE.getInstanceById(contentElem.id) == null) {
                            tinyMCE.execCommand('mceAddControl', false, contentElem.id);
                        } else {
                            tinyMCE.execCommand('mceRemoveControl', false, contentElem.id);
                        }
                    }
                })

            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";

    }

}
