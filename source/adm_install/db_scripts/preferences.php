<?php
/******************************************************************************
 * Systemeinstellungen fuer eine Organisation
 *
 * WICHTIG: Neue Einstellungen muessen auch im jeweiligen Updatescript 
 *          eingetragen werden !!!
 *
 * Copyright    : (c) 2004 - 2007 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : http://www.gnu.org/licenses/gpl-2.0.html GNU Public License 2
 *
 *****************************************************************************/

$orga_preferences = array(

    // System
    'enable_rss'      => '1',
    'enable_bbcode'   => '1',
    'logout_minutes'  => '20',
    'enable_system_mails' => '1',
    'email_administrator' => 'webmaster@'. $_SERVER['HTTP_HOST'],
    'user_css'        => 'user.css',
    'system_align'    => 'center',
    
    // Registrierung
    'registration_mode'              => '1',
    'enable_registration_captcha'    => '1',
    'enable_registration_admin_mail' => '1',

    // Ankuendigungen
    'enable_announcements_module' => '1',

    // Downloads
    'enable_download_module' => '1',
    'max_file_upload_size'   => '3072',
        
    // Fotomodul
    'enable_photo_module' => '1',
    'photo_save_scale'    => '640',
    'photo_thumbs_column' => '5',
    'photo_thumbs_row'    => '5',
    'photo_thumbs_scale'  => '100',
    'photo_show_width'    => '500',
    'photo_show_height'   => '380',
    'photo_image_text'    => '1',
    'photo_preview_scale' => '100',
    'photo_show_mode'   => '1',
    
    // Gaestebuch
    'enable_guestbook_module'   => '1',
    'enable_guestbook_captcha'  => '1',
    'flooding_protection_time'  => '60',
    'enable_gbook_comments4all' => '0',
    
    // Listen
    'lists_roles_per_page'   => '10',
    'lists_members_per_page' => '20',
    
    // Mailmodul
    'enable_mail_module'        => '1',
    'max_email_attachment_size' => '1024',
    'enable_mail_captcha'       => '1',

    // Profil
    'default_country'          => 'Deutschland',
    'enable_roles_view'        => '1',
    'enable_former_roles_view' => '1',
    'enable_extern_roles_view' => '1',
        
    // Termine
    'enable_dates_module' => '1',
    
    // Weblinks
    'enable_weblinks_module' => '1'
 )
?>
