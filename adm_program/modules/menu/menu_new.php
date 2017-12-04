<?php
/**
 ***********************************************************************************************
 * Create and edit categories
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * men_id: Id of the menu that should be edited
 *
 ****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/login_valid.php');

// Initialize and check the parameters
$getMenId = admFuncVariableIsValid($_GET, 'men_id', 'int');

// Rechte pruefen
if(!$gCurrentUser->isAdministrator())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// set module headline
$headline = $gL10n->get('SYS_MENU');

// set headline of the script
if($getMenId > 0)
{
    $headline = $gL10n->get('SYS_EDIT_VAR', array($headline));
}
else
{
    $headline = $gL10n->get('SYS_CREATE_VAR', array($headline));
}

// create menu object
$menu = new TableMenu($gDb);

// systemcategories should not be renamed
$roleViewSet[] = 0;

if($getMenId > 0)
{
    $menu->readDataById($getMenId);

    // Read current roles rights of the menu
    $display = new RolesRights($gDb, 'menu_view', $getMenId);
    $roleViewSet = $display->getRolesIds();
}

if(isset($_SESSION['menu_request']))
{
    // due to incorrect input, the user has returned to this form
    // Now write the previously entered content into the object
    $menu->setArray($_SESSION['menu_request']);
    unset($_SESSION['menu_request']);
}

$gNavigation->addUrl(CURRENT_URL, array($headline));

$menuArray = array(0 => 'MAIN');

/**
 * die Albenstruktur fuer eine Auswahlbox darstellen und das aktuelle Album vorauswählen
 * @param int    $parentId
 * @param string $vorschub
 * @param        $menu
 */
function subfolder($parentId, $vorschub, $menu)
{
    global $gDb, $gCurrentOrganization, $menuArray;

    $vorschub .= '&nbsp;&nbsp;&nbsp;';
    $sqlConditionParentId = '';
    $parentMenu = new TableMenu($gDb);

    $queryParams = array($menu->getValue('men_id'));
    // Erfassen des auszugebenden Albums
    if ($parentId > 0)
    {
        $sqlConditionParentId .= ' AND men_men_id_parent = ? -- $parentId';
        $queryParams[] = $parentId;
    }
    else
    {
        $sqlConditionParentId .= ' AND men_men_id_parent IS NULL';
    }

    $sql = 'SELECT *
              FROM '.TBL_MENU.'
             WHERE men_id    <> ? -- $menu->getValue(\'men_id\')
               AND men_node = 1
                   '.$sqlConditionParentId;
    $childStatement = $gDb->queryPrepared($sql, $queryParams);

    while($admPhotoChild = $childStatement->fetch())
    {
        $parentMenu->clear();
        $parentMenu->setArray($admPhotoChild);

        // add entry to array of all photo albums
        $menuArray[$parentMenu->getValue('men_id')] = $vorschub.'&#151; '.$parentMenu->getValue('men_name');

        subfolder($parentMenu->getValue('men_id'), $vorschub, $menu);
    }//while
}//function

// create html page object
$page = new HtmlPage($headline);

// add back link to module menu
$menuCreateMenu = $page->getMenu();
$menuCreateMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// alle aus der DB aus lesen
$sqlRoles =  'SELECT *
                FROM '.TBL_ROLES.'
          INNER JOIN '.TBL_CATEGORIES.'
                  ON cat_id = rol_cat_id
               WHERE rol_valid  = 1
                 AND rol_system = 0
            ORDER BY rol_name';
$rolesViewStatement = $gDb->query($sqlRoles);

while($rowViewRoles = $rolesViewStatement->fetchObject())
{
    // Jede Rolle wird nun dem Array hinzugefuegt
    $parentRoleViewSet[] = array($rowViewRoles->rol_id, $rowViewRoles->rol_name, $rowViewRoles->cat_name);
}

// show form
$form = new HtmlForm('menu_edit_form', $g_root_path.'/adm_program/modules/menu/menu_function.php?men_id='.$getMenId.'&amp;mode=1', $page);

subfolder(null, '', $menu);

$form->addInput(
    'men_name', $gL10n->get('SYS_NAME'), $menu->getValue('men_name', 'database'), 
    array('maxLength' => 100, 'property'=> FIELD_REQUIRED, 'helpTextIdLabel' => 'MEN_NAME_DESC')
);

if($getMenId > 0)
{
    $form->addInput(
        'men_name_intern', $gL10n->get('SYS_INTERNAL_NAME'), $menu->getValue('men_name_intern', 'database'), 
        array('maxLength' => 100, 'property' => HtmlForm::FIELD_DISABLED, 'helpTextIdLabel' => 'SYS_INTERNAL_NAME_DESC')
    );
}

$form->addMultilineTextInput(
    'men_description', $gL10n->get('SYS_DESCRIPTION'), $menu->getValue('men_description', 'database'), 2,
    array('maxLength' => 4000)
);

$form->addSelectBox(
    'men_men_id_parent', $gL10n->get('MEN_MENU_LEVEL'), $menuArray, 
    array(
        'property'                       => FIELD_REQUIRED,
        'defaultValue'                   => $menu->getValue('men_men_id_parent'),
        'showContextDependentFirstEntry' => false,
        'helpTextIdLabel'                => array('MEN_MENU_LEVEL_DESC', 'MAIN')
    )
);

$form->addSelectBox(
    'menu_view', $gL10n->get('SYS_VISIBLE_FOR'), $parentRoleViewSet, 
    array('defaultValue' => $roleViewSet, 'multiselect'  => true)
);

if((bool) $menu->getValue('men_node') === false)
{
    $form->addInput(
        'men_url', $gL10n->get('ORG_URL'), $menu->getValue('men_url', 'database'), 
        array('maxLength' => 100, 'property' => FIELD_REQUIRED)
    );
}

$arrayIcons  = admFuncGetDirectoryEntries(THEME_ADMIDIO_PATH . '/icons');
$defaultIcon = array_search($menu->getValue('men_icon', 'database'), $arrayIcons);
$form->addSelectBox(
    'men_icon', $gL10n->get('SYS_ICON'), $arrayIcons, 
    array('defaultValue' => $defaultIcon, 'showContextDependentFirstEntry' => true)
);

$form->addSubmitButton(
    'btn_save', $gL10n->get('SYS_SAVE'), 
    array('icon' => THEME_PATH.'/icons/disk.png')
);

// add form to html page and show page
$page->addHtml($form->show(false));
$page->show();
