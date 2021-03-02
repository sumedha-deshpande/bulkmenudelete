<?php
/**
 * @file
 * Contains \Drupal\bulkmenudelete\Form\BulkMenuDeleteForm.
 * 
 */
namespace Drupal\bulkmenudelete\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class BulkMenuDeleteForm extends FormBase {
    /** 
     * (@inheritdoc)
    */
    public function getFormID(){
        return 'bulkmenu_delete_form';     
    }

    /** 
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
          $header = array(
            'menu_title' => t('Title'),
            'menu_description' => t('Description'),
          );
          $options = array();
          
          $options = $this->_get_menu_items();
          $form['table'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $options,
            '#empty' => t('No users found'),
          );

          $msg = t('This action can not be undone.Do you really want to delete?');
          $form['submit'] = array(
            '#type' => 'submit',
            '#id' => 'confirm_submit',
            '#value' => t('Delete Selected'),
            '#attributes' => array('onclick' => 'if(!confirm("' . $msg . ' ")){return false;}'),
          );

        return $form;
    }
    /** 
     * Helper funtion to populatate form table element with menu links.
     * called by buildForm()
     * returns $options
     */
    private function _get_menu_items() {
        //As per the requirement, this will populate links form Main menu only 
        $menu_name = 'main';
        $menu_tree = \Drupal::menuTree();
        $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
        $parameters->setMinDepth(0);

        $tree = $menu_tree->load($menu_name, $parameters);
        $manipulators = array(
            array('callable' => 'menu.default_tree_manipulators:checkAccess'),
            array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
        );
        $tree = $menu_tree->transform($tree, $manipulators);
        foreach ($tree as $item) {
          $options[$item->link->getDerivativeId()] = array(
              'menu_title' => $item->link->getTitle(),
              'menu_description' => $item->link->getDescription() . ' ',     
          );
        }
        return $options;
    }
     /**
     * (@inheritdoc)
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      $count = 0;
      foreach($form_state->getValue('table') as $uuid=>$menulink) {
        //check all the selected items. 
        //Skip the  system generated ($uuid = null) form deletion
        if($uuid == $menulink && $menulink != null ) {
          $count += 1;
        }
      }
      
      if($count == 0)  {
          //the table is empty
          $form_state->setErrorByName('Menu Link',t('First select the manu items to be deleted.'));
      }
    }
     /**
     * (@inheritdoc)
     * Deletes the seleted menu links form main menu.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) { 
        $message = "";  //To customize the message appropriately
        $count = 0; //Check if there are system generate links under selected list 
        if ($form_state->getValue('table') !== null) {
            foreach($form_state->getValue('table') as $uuid=>$menulink) {
              //check all the selected items. 
              //Skip the  system generated ($uuid = null) form deletion
              if($uuid == $menulink && $menulink != null ) {
                $link = \Drupal::service('entity.repository')
                        ->loadEntityByUuid('menu_link_content', $uuid);
                $link->delete();
              }
              if($uuid == null) {
                $count +=  1;
              }
            }
            if( $count >= 1) {
              $message =  "There are some system generted links which can not be deleted. Rest of the menu item(s) are deleted successfully.";
            }
            else {
              $message = "Selected menu item(s) are deleted successfully.";
            }
        }
        \Drupal::messenger()->addStatus(t($message . ''));
        //Redirect to the same form.
        $form_state->setRedirect('bulkmenudelete.form');
    }
}


