<?php

require_once("check_login.php");
require_once("models/draft_object.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_REQUEST['action']);
DEFINE('DRAFT_ID', intval($_REQUEST['did']));

$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}
// </editor-fold>

switch(ACTION) {
	case 'addManagers':
		// <editor-fold defaultstate="collapsed" desc="addManagers Logic">
		$MANAGERS = array();
		$MANAGERS[] = new manager_object();
		
		$CURRENT_MANAGERS = manager_object::getManagersByDraftId(DRAFT_ID, true);
		require_once('/views/draft/add_managers.php');
		// </editor-fold>
		break;
	
	case 'saveManagers':
		// <editor-fold defaultstate="collapsed" desc="saveManagers Logic">
		$managers = $_POST['managers'];
		foreach($managers as $manager_request) {
			$new_manager = new manager_object();
			$new_manager->draft_id = DRAFT_ID;
			$new_manager->manager_name = $manager_request['manager_name'];
			$new_manager->manager_email = $manager_request['manager_email'];
			
			if(!$new_manager->saveManager()) {
				return "SERVER_ERROR";
				exit(1);
			}
		}
		
		echo "SUCCESS";
		// </editor-fold>
		break;
	
	case 'updateVisibility':
		// <editor-fold defaultstate="collapsed" desc="updateVisibility Logic">
		$new_password = $_POST['password'];
		
		if($DRAFT->draft_password == $new_password) {
			echo "SUCCESS";
			exit(0);
		}
		
		$DRAFT->draft_password = $new_password;
		
		if($DRAFT->saveDraft())
			echo "SUCCESS";
		else
			echo "FAILURE";
		// </editor-fold>
		break;
	
	case 'changeStatus':
		// <editor-fold defaultstate="collapsed" desc="changeStatus Logic">
		require_once("/views/draft/edit_status.php");
		// </editor-fold>
		break;
	
	case 'updateStatus':
		// <editor-fold defaultstate="collapsed" desc="updateStatus Logic">
		$new_status = $_POST['draft_status'];
		
		if($DRAFT->draft_status == $new_status) {
			define("PAGE_HEADER", "Status Unchanged");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Your draft's status was unchanged. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the draft's main page, or <a href=\"draft.php?action=changeStatus&did=" . DRAFT_ID . "\">click here</a> to change it's status.");
			require_once("/views/generic_result_view.php");
			exit(0);
		}
		
		if(!draft_object::checkStatus($new_status)) {
			$ERRORS = array();
			$ERRORS[] = "Draft status is of the incorrect value. Please correct this and try again.";
			require_once("/views/draft/edit_status.php");
			exit(1);
		}
		
		$success = $DRAFT->updateStatus($new_status);
		
		if($success) {
			if($DRAFT->isInProgress())
				$extra_message = "<br/><br/><a href=\"draft_room.php?did=" . DRAFT_ID . "\">Click here to be taken to the Draft Room - Your Draft Has Started!</a>";
			
			define("PAGE_HEADER", "Draft Status Updated");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Your draft's status has been successfully updated. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to its main page." . $extra_message);
			require_once("/views/generic_result_view.php");
			exit(0);
		}else {
			$ERRORS = array();
			$ERRORS[] = "An error occurred and your draft's status could not be updated.  Please try again.";
			require_once("/views/draft/edit_status.php");
			exit(1);
		}
		// </editor-fold>
		break;
	
	case 'editDraft':
		// <editor-fold defaultstate="collapsed" desc="editDraft Logic">
		if($DRAFT->isCompleted() || $DRAFT->isInProgress()) {
			define("PAGE_HEADER", "You Cannot Edit This Draft");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Because this draft is either in progress or completed, you are unable to edit the details of this draft. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to go back to the draft\'s homepage.");
			require_once("/views/generic_result_view.php");
			exit(1);
		}
		require_once("/views/draft/edit_draft.php");
		// </editor-fold>
		break;
	
	case 'updateDraft':
		if($DRAFT->isCompleted() || $DRAFT->isInProgress()) {
			define("PAGE_HEADER", "You Cannot Edit This Draft");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Because this draft is either in progress or completed, you are unable to edit the details of this draft. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to go back to the draft\'s homepage.");
			require_once("/views/generic_result_view.php");
			exit(1);
		}
		
		$DRAFT->draft_name = trim($_POST['draft_name']);
		$DRAFT->draft_sport = trim($_POST['draft_sport']);
		$DRAFT->draft_style = trim($_POST['draft_style']);
		$DRAFT->draft_rounds = intval($_POST['draft_rounds']);
		
		$object_errors = $DRAFT->getValidity();
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once("/views/draft/edit_draft.php");
			exit(1);
		}
		
		if($DRAFT->saveDraft() == false) {
			$ERRORS[] = "Draft could not be saved, please try again.";
			DEFINE("CONTROL_PANEL_ACTION", "ADD");
			require_once("/views/control_panel/create_draft.php");
			exit(1);
		}
		
		define("PAGE_HEADER", "Draft Edited Successfully!");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft " . $DRAFT->draft_name . " has been edited successfully. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the draft's homepage, or <a href=\"draft.php?action=editDraft&did=" . DRAFT_ID . "\">click here</a> to edit the draft again.");
		require_once("/views/generic_result_view.php");
		break;
	
	case 'deleteDraft':
		//TODO: Add view here?  Not sure if this can be AJAX-based or not.
		break;
	
	case '':
	default:
		// <editor-fold defaultstate="collapsed" desc="Main Draft Page Logic">
		require_once("models/manager_object.php");
		
		$MANAGERS = manager_object::getManagersByDraftId(DRAFT_ID, true);
		
		DEFINE('NUMBER_OF_MANAGERS', count($MANAGERS));
		DEFINE('HAS_MANAGERS', NUMBER_OF_MANAGERS > 0);
		DEFINE('LOWEST_ORDER', $MANAGERS[NUMBER_OF_MANAGERS - 1]->draft_order);
		
		require_once('/views/draft/index.php');
		// </editor-fold>
		break;
} ?>