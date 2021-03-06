<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload7c6123880d409e330fe25ca33b5eb299($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'pagenameinvalidcharexception' => '/exceptions/PageNameInvalidCharException.class.php',
            'pagenametoolongexception' => '/exceptions/PageNameTooLongException.class.php',
            'phpwiki' => '/lib/PHPWiki.class.php',
            'phpwiki_permissionsmanager' => '/PermissionsManager.class.php',
            'phpwikiactions' => '/actions/PHPWikiActions.class.php',
            'phpwikiattachment' => '/lib/PHPWikiAttachment.class.php',
            'phpwikiattachmentdao' => '/lib/PHPWikiAttachmentDao.class.php',
            'phpwikiattachmentrevision' => '/lib/PHPWikiAttachmentRevision.class.php',
            'phpwikiattachmentrevisiondao' => '/lib/PHPWikiAttachmentRevisionDao.class.php',
            'phpwikicloner' => '/lib/PHPWikiCloner.class.php',
            'phpwikidao' => '/PHPWikiDao.class.php',
            'phpwikientry' => '/lib/PHPWikiEntry.class.php',
            'phpwikipage' => '/lib/PHPWikiPage.class.php',
            'phpwikipagewrapper' => '/lib/PHPWikiPageWrapper.class.php',
            'phpwikiplugin' => '/phpwikiPlugin.class.php',
            'phpwikiplugindescriptor' => '/PHPWikiPluginDescriptor.class.php',
            'phpwikiplugininfo' => '/PHPWikiPluginInfo.class.php',
            'phpwikiservice' => '/PHPWikiService.class.php',
            'phpwikiserviceactions' => '/actions/PHPWikiServiceActions.class.php',
            'phpwikiserviceadmin' => '/PHPWikiServiceAdmin.class.php',
            'phpwikiserviceadminactions' => '/actions/PHPWikiServiceAdminActions.class.php',
            'phpwikiserviceadminviews' => '/views/PHPWikiServiceAdminViews.class.php',
            'phpwikiserviceviews' => '/views/PHPWikiServiceViews.class.php',
            'phpwikiversiondao' => '/PHPWikiVersionDao.class.php',
            'phpwikiviews' => '/views/PHPWikiViews.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload7c6123880d409e330fe25ca33b5eb299');
// @codeCoverageIgnoreEnd
