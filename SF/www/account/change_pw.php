<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/account.php');

$LANG->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!$GLOBALS['Update']) {
		return 0;
	}
	
	// check against old pw
	$res = db_query("SELECT user_pw, status FROM user WHERE user_id=" . user_getid());
	if (! $res) {
	  $GLOBALS['register_error'] = "Internal error: Cannot locate user in database.";
	  return 0;
	}
	
	$row_pw = db_fetch_array();
	if ($row_pw[user_pw] != md5($GLOBALS['form_oldpw'])) {
		$GLOBALS['register_error'] = "Old password is incorrect.";
		return 0;
	}

	if (($row_pw[status] != 'A')&&($row_pw[status] != 'R')) {
		$GLOBALS['register_error'] = "Account must be active to change password.";
		return 0;
	}

	if (!$GLOBALS['form_pw']) {
		$GLOBALS['register_error'] = "You must supply a password.";
		return 0;
	}
	if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
		$GLOBALS['register_error'] = "Passwords do not match.";
		return 0;
	}
	if (!account_pwvalid($GLOBALS['form_pw'])) {
		return 0;
	}
	
	// if we got this far, it must be good
	$res = db_query("UPDATE user SET user_pw='" . md5($GLOBALS['form_pw']) . "',"
		. "unix_pw='" . account_genunixpw($GLOBALS['form_pw']) . "',"
		. "windows_pw='" . account_genwinpw($GLOBALS['form_pw']) . "' WHERE "
		. "user_id=" . user_getid());

	if (! $res) {
	  $GLOBALS['register_error'] = "Internal error: Could not update password.";
	  return 0;
	}

	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
    $HTML->header(array(title=>$LANG->getText('account_change_pw', 'title_success')));
    $d = getdate(time());
    $h = ($sys_crondelay - 1) - ($d[hours] % $sys_crondelay);
    $m= 60 - $d[minutes];
?>
<p><b><? echo $LANG->getText('account_change_pw', 'title_success'); ?></b>
<p><? echo $LANG->getText('account_change_pw', 'message', array($GLOBALS['sys_name'],$h,$m)); ?

<p>[ <? echo $LANG->getText('global', 'back_home');?> ]
<?php
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>));

?>
<p><b><? echo $LANG->getText('account_change_pw', 'title'); ?></b>
<?php if ($register_error) print "<p><span class=\"highlight\"><b>$register_error</b></span>"; ?>
<form action="change_pw.php" method="post">
<p><? echo $LANG->getText('account_change_pw', 'old_password'); ?>:
<br><input type="password" name="form_oldpw">
<p><? echo $LANG->getText('account_change_pw', 'new_password'); ?>:
<br><input type="password" name="form_pw">
<p><? echo $LANG->getText('account_change_pw', 'new_password2'); ?>:
<br><input type="password" name="form_pw2">
<p><input type="submit" name="Update" value="<? echo $LANG->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
