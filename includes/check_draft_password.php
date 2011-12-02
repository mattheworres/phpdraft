<?php
/**
 * Used to restrict access to "public" draft pages that are password-protected.
 */

$_SESSION['draft_id'] = isset($_SESSION['draft_id']) ? (int)$_SESSION['draft_id'] : 0;
$_SESSION['draft_password'] = isset($_SESSION['draft_password']) ? $_SESSION['draft_password'] : "";
$DRAFT_ID = isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : 0;

$pageURL = "http://";
$pageURL .= $_SERVER["SERVER_PORT"] != "80" ? $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

$DESTINATION = $pageURL;

$DRAFT = new draft_object($DRAFT_ID);

if($DRAFT->isPasswordProtected()) {
	if(!$DRAFT->checkDraftPublicLogin()) {
		require("views/shared/draft_login.php");
		exit(0);
	}
}
//If we have gotten to this point, the user is properly logged in and we can continue.
?>