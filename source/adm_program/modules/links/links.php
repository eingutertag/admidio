<?php 
/******************************************************************************
 * Links auflisten
 *
 * Copyright    : (c) 2004 - 2007 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Daniel Dieckelmann
 *
 * start     - Angabe, ab welchem Datensatz Links angezeigt werden sollen
 * headline  - Ueberschrift, die ueber den Links steht
 *             (Default) Links
 * id        - Nur einen einzigen Link anzeigen lassen.
 *
 *
 ******************************************************************************
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *****************************************************************************/

require("../../system/common.php");
require("../../system/bbcode.php");

// pruefen ob das Modul ueberhaupt aktiviert ist
if ($g_preferences['enable_weblinks_module'] != 1)
{
    // das Modul ist deaktiviert
    $g_message->show("module_disabled");
}


// Uebergabevariablen pruefen

if (array_key_exists("start", $_GET))
{
    if (is_numeric($_GET["start"]) == false)
    {
        $g_message->show("invalid");
    }
}
else
{
    $_GET["start"] = 0;
}

if (array_key_exists("id", $_GET))
{
    if (is_numeric($_GET["id"]) == false)
    {
        $g_message->show("invalid");
    }
}
else
{
    $_GET["id"] = 0;
}

if (array_key_exists("headline", $_GET))
{
    $_GET["headline"] = strStripTags($_GET["headline"]);
}
else
{
    $_GET["headline"] = "Links";
}

if ($g_preferences['enable_bbcode'] == 1)
{
    // Klasse fuer BBCode initialisieren
    $bbcode = new ubbParser();
}

// Navigation initialisieren - Modul f?ngt hier an.
$_SESSION['navigation']->clear();
$_SESSION['navigation']->addUrl($g_current_url);

unset($_SESSION['links_request']);

// Hier eingerichtet, damit es später noch in den Orga-Einstellungen verwendet werden kann
$linksPerPage = 10;

echo "
<!-- (c) 2004 - 2007 The Admidio Team - http://www.admidio.org -->\n
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
    <title>$g_current_organization->longname - ". $_GET["headline"]. "</title>
    <link rel=\"stylesheet\" type=\"text/css\" href=\"$g_root_path/adm_config/main.css\">";

    if ($g_preferences['enable_rss'] == 1)
    {
        echo "<link type=\"application/rss+xml\" rel=\"alternate\" title=\"$g_current_organization->longname - Links\"
        href=\"$g_root_path/adm_program/modules/links/rss_links.php\">";
    }

    echo "
    <!--[if lt IE 7]>
    <script type=\"text/javascript\" src=\"$g_root_path/adm_program/system/correct_png.js\"></script>
    <![endif]-->";

    require("../../../adm_config/header.php");
echo "</head>";

require("../../../adm_config/body_top.php");
    echo "<div style=\"margin-top: 10px; margin-bottom: 10px;\" align=\"center\">
        <h1>". strspace($_GET["headline"]). "</h1>";


        // falls eine id fuer einen bestimmten Link uebergeben worden ist...
        if ($_GET['id'] > 0)
        {
            $sql1    = "SELECT * FROM ". TBL_LINKS. "
                       JOIN ". TBL_CATEGORIES ."
                       ON lnk_cat_id = cat_id
                       WHERE lnk_id = {0}
                       AND lnk_org_id = '$g_current_organization->id'
                       AND cat_org_id = '$g_current_organization->id'";

            $sql1    = prepareSQL($sql1, array($_GET['id']));
        }
        //...ansonsten alle fuer die Gruppierung passenden Links aus der DB holen.
        else
        {
            // Links bereits nach den Namen ihrer Kategorie sortiert.
            $sql1    = "SELECT * FROM ". TBL_LINKS. " AS L
                       JOIN ". TBL_CATEGORIES ." AS C
                       ON L.lnk_cat_id = C.cat_id
                       WHERE L.lnk_org_id = '$g_current_organization->id'
                       AND C.cat_org_id = '$g_current_organization->id'
                       AND C.cat_type = 'LNK'
                       ORDER BY C.cat_name, L.lnk_name, lnk_timestamp DESC
                       LIMIT {0}, 10 ";

            $sql1    = prepareSQL($sql1, array($_GET['start']));
        }

        $links_result = mysql_query($sql1, $g_adm_con);
        db_error($links_result);

        // Gucken wieviele Linkdatensaetze insgesamt fuer die Gruppierung vorliegen...
        // Das wird naemlich noch fuer die Seitenanzeige benoetigt...
        if ($g_session_valid == false)
        {
            // Wenn User nicht eingeloggt ist, Kategorien, die hidden sind, aussortieren
            $sql    = "SELECT COUNT(*) FROM ". TBL_LINKS. " AS L
                      JOIN ". TBL_CATEGORIES ." AS C
                      ON L.lnk_cat_id = C.cat_id
                      WHERE L.lnk_org_id = '$g_current_organization->id'
                      AND C.cat_org_id = '$g_current_organization->id'
                      AND C.cat_type = 'LNK' 
                      AND C.cat_hidden = '0'
                      ORDER BY L.lnk_name DESC";    
        } 
        else
        {   
            // Alle Kategorien anzeigen
            $sql    = "SELECT COUNT(*) FROM ". TBL_LINKS. "
                      WHERE lnk_org_id = '$g_current_organization->id'
                      ORDER BY lnk_name DESC";
        }
        
        $result = mysql_query($sql, $g_adm_con);
        db_error($result);
        $row = mysql_fetch_array($result);
        $numLinks = $row[0];

        // Icon-Links und Navigation anzeigen

        if ($_GET['id'] == 0 && ($g_current_user->editWeblinksRight() || $g_preferences['enable_rss'] == true))
        {
            // Neuen Link anlegen
            if ($g_current_user->editWeblinksRight())
            {
                echo "<p>
                    <span class=\"iconLink\">
                        <a class=\"iconLink\" href=\"links_new.php?headline=". $_GET["headline"]. "\"><img
                        class=\"iconLink\" src=\"$g_root_path/adm_program/images/add.png\" style=\"vertical-align: middle;\" border=\"0\" alt=\"Neu anlegen\"></a>
                        <a class=\"iconLink\" href=\"links_new.php?headline=". $_GET["headline"]. "\">Neu anlegen</a>
                    </span>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <span class=\"iconLink\">
                        <a class=\"iconLink\" href=\"$g_root_path/adm_program/administration/roles/categories.php?type=LNK\"><img
                        src=\"$g_root_path/adm_program/images/application_double.png\" style=\"vertical-align: middle;\" border=\"0\" alt=\"Kategorien pflegen\"></a>
                        <a class=\"iconLink\" href=\"$g_root_path/adm_program/administration/roles/categories.php?type=LNK\">Kategorien pflegen</a>
                    </span>
                </p>";
            }

            // Navigation mit Vor- und Zurueck-Buttons
            $baseUrl = "$g_root_path/adm_program/modules/links/links.php?headline=". $_GET["headline"];
            echo generatePagination($baseUrl, $numLinks, 10, $_GET["start"], TRUE);
        }

        if (mysql_num_rows($links_result) == 0)
        {
            // Keine Links gefunden
            if ($_GET['id'] > 0)
            {
                echo "<p>Der angeforderte Eintrag exisitiert nicht (mehr) in der Datenbank.</p>";
            }
            else
            {
                echo "<p>Es sind keine Eintr&auml;ge vorhanden.</p>";
            }
        }
        else
        {

            // Zählervariable für Anzahl von mysql_fetch_object
            $j = 0;
            // Zählervariable für Anzahl der Links in einer Kategorie
            $i = 0;
            // ?berhaupt etwas geschrieben? -> Wichtig, wenn es nur versteckte Kategorien gibt.
            $did_write_something = false;
            // Vorherige Kategorie-ID.
            $previous_cat_id = -1;
            // Kommt jetzt eine neue Kategorie?
            $new_category = true;
            // Schreibe diese Kategorie nicht! Sie ist versteckt und der User nicht eingeloggt
            $dont_write = false;
                
                // Solange die vorherige Kategorie-ID sich nicht ver?ndert...
                // Sonst in die neue Kategorie springen
                while (($row = mysql_fetch_object($links_result)) && ($j<$linksPerPage))
                {

                if ($row->lnk_cat_id != $previous_cat_id)
                {
                    if (($row->cat_hidden == 1) && ($g_session_valid == false))
                    {
                        // Nichts anzeigen, weil Kategorie versteckt ist und User nicht eingeloggt
                        $dont_write = true;
                    } else {
                        $dont_write = false;
                    }
                    
                    if (!$dont_write)
                    {
                        $i = 0;
                        $new_category = true;
                        $did_write_something = true;
                        if ($j>0)
                        {
                            echo "</div><br />";
                        }
                        echo "<div class=\"formHead\">$row->cat_name</div>
                        <div class=\"formBody\" style=\"overflow: hidden;\">";
                    }
                }

                if (!$dont_write)
                {
                    if($i > 0)
                    {
                        echo "<hr width=\"98%\" />";
                    }
                    echo "
                    <div style=\"text-align: left;\">
                        <div style=\"text-align: left;\">
                            <a href=\"$row->lnk_url\" target=\"_blank\">
                                <img src=\"$g_root_path/adm_program/images/globe.png\" style=\"vertical-align: top;\"
                                    alt=\"Gehe zu $row->lnk_name\" title=\"Gehe zu $row->lnk_name\" border=\"0\"></a>
                            <a href=\"$row->lnk_url\" target=\"_blank\">$row->lnk_name</a>
                        </div>
                        <div style=\"margin-top: 10px; text-align: left;\">";

                            // wenn BBCode aktiviert ist, die Beschreibung noch parsen, ansonsten direkt ausgeben
                            if ($g_preferences['enable_bbcode'] == 1)
                            {
                                echo strSpecialChars2Html($bbcode->parse($row->lnk_description));
                            }
                            else
                            {
                                echo nl2br(strSpecialChars2Html($row->lnk_description));
                            }
                        echo "</div>";
                        
                        if($g_current_user->editWeblinksRight())
                        {
                            echo "
                            <div style=\"margin-top: 10px; font-size: 8pt; text-align: left;\">";
                                // aendern & loeschen duerfen nur User mit den gesetzten Rechten
                                if ($g_current_user->editWeblinksRight())
                                {
                                    echo "<img src=\"$g_root_path/adm_program/images/edit.png\" style=\"cursor: pointer; vertical-align: middle;\" width=\"16\" height=\"16\" border=\"0\" alt=\"Bearbeiten\" title=\"Bearbeiten\"
                                        onclick=\"self.location.href='links_new.php?lnk_id=$row->lnk_id&amp;headline=". $_GET['headline']. "'\">

                                        <img src=\"$g_root_path/adm_program/images/cross.png\" style=\"cursor: pointer; vertical-align: middle;\" width=\"16\" height=\"16\" border=\"0\" alt=\"L&ouml;schen\" title=\"L&ouml;schen\"
                                         onclick=\"self.location.href='links_function.php?lnk_id=$row->lnk_id&amp;mode=4'\">";
                                }
                                $user_create = new User($g_adm_con);
                                $user_create->getUser($row->lnk_usr_id);
                                echo "Angelegt von ". strSpecialChars2Html($user_create->first_name). " ". strSpecialChars2Html($user_create->last_name).
                                " am ". mysqldatetime("d.m.y h:i", $row->lnk_timestamp);

                                if($row->lnk_usr_id_change > 0)
                                {
                                    $user_change = new User($g_adm_con);
                                    $user_change->getUser($row->lnk_usr_id_change);
                                    echo "<br>Zuletzt bearbeitet von ". strSpecialChars2Html($user_change->first_name). " ". strSpecialChars2Html($user_change->last_name).
                                    " am ". mysqldatetime("d.m.y h:i", $row->lnk_last_change);
                                }
                            echo "</div>";
                        }
                    echo "</div>";
                 }  // Ende Wenn !dont_write

                 $i++;
                 $j++;

                 // Jetzt wird die jtzige die vorherige Kategorie
                 $previous_cat_id = $row->lnk_cat_id;

                 $new_category = false;

                 }  // Ende While-Schleife

             // Es wurde noch gar nichts geschrieben ODER ein einzelner Link ist versteckt
             if (!$did_write_something)
             {
                echo "<!-- Versteckte Kategorie -->
                      <p>Es sind keine Eintr&auml;ge vorhanden.</p>";
             }
             
             echo "</div>";
        } // Ende Wenn mehr als 0 Datensätze

        if (mysql_num_rows($links_result) > 2)
        {
            // Navigation mit Vor- und Zurueck-Buttons
            // erst anzeigen, wenn mehr als 2 Eintraege (letzte Navigationsseite) vorhanden sind
            $baseUrl = "$g_root_path/adm_program/modules/links/links.php?headline=". $_GET["headline"];
            echo generatePagination($baseUrl, $numLinks, 10, $_GET["start"], TRUE);
        }
    echo "</div>";

    require("../../../adm_config/body_bottom.php");
echo "</body>
</html>";
?>
