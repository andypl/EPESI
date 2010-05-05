<?php
/**
 * Roundcube bindings
 * @author pbukowski@telaxus.com
 * @copyright Telaxus LLC
 * @license GPL
 * @version 0.1
 * @package epesi-CRM
 * @subpackage Roundcube
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class CRM_RoundcubeInstall extends ModuleInstall {

    public function install() {
        $this->create_data_dir();
		Base_ThemeCommon::install_default_theme($this -> get_type());

        if(DATABASE_DRIVER=='mysqlt')
            $f = file_get_contents('modules/CRM/Roundcube/src/SQL/mysql.initial.sql');
        else
            $f = file_get_contents('modules/CRM/Roundcube/src/SQL/postgres.initial.sql');
        foreach(explode(';',$f) as $q) {
            $q = trim($q);
            if(!$q) continue;
            DB::Execute($q);
        }

        Utils_CommonDataCommon::new_array('CRM/Roundcube/Security', array('tls'=>'TLS','ssl'=>'SSL'),true,true);

        $fields = array(
            array('name'=>'Epesi User',             'type'=>'integer', 'extra'=>false, 'visible'=>true, 'required'=>true, 'display_callback'=>array('CRM_RoundcubeCommon', 'display_epesi_user'), 'QFfield_callback'=>array('CRM_RoundcubeCommon', 'QFfield_epesi_user')),
            array('name'=>'Server',             'type'=>'text', 'extra'=>false, 'visible'=>true, 'param'=>'255', 'required'=>true),
            array('name'=>'Login',              'type'=>'text', 'required'=>true, 'param'=>'255', 'extra'=>false, 'visible'=>true),
            array('name'=>'Password',           'type'=>'text', 'required'=>true,'extra'=>false, 'param'=>'255', 'visible'=>false, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_password')),
            array('name'=>'Security',           'type'=>'commondata', 'param'=>array('CRM/Roundcube/Security'), 'extra'=>false, 'visible'=>false),

            array('name'=>'SMTP Server',             'type'=>'text', 'extra'=>false, 'visible'=>false, 'param'=>'255', 'required'=>true),
            array('name'=>'SMTP Auth',             'type'=>'checkbox', 'extra'=>false, 'visible'=>false, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_smtp_auth')),
            array('name'=>'SMTP Login',              'type'=>'text', 'required'=>false, 'param'=>'255', 'extra'=>false, 'visible'=>false, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_smtp_login')),
            array('name'=>'SMTP Password',           'type'=>'text', 'extra'=>false, 'param'=>'255', 'visible'=>false, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_smtp_password')),
            array('name'=>'SMTP Security',           'type'=>'commondata', 'param'=>array('CRM/Roundcube/Security'), 'extra'=>false, 'visible'=>false, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_smtp_security')),

            array('name'=>'Default Account',             'type'=>'checkbox', 'extra'=>false, 'visible'=>true, 'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_default_account'))
        );
        Utils_RecordBrowserCommon::install_new_recordset('rc_accounts', $fields);
        Utils_RecordBrowserCommon::set_caption('rc_accounts', 'Mail accounts');
        Utils_RecordBrowserCommon::set_processing_callback('rc_accounts', array('CRM_RoundcubeCommon', 'submit_account'));

        $fields = array(
            array(
                'name'=>'Recordset',
                'type'=>'text',
                'param'=>'64',
                'extra'=>false,
                'visible'=>false,
                'required'=>true
            ),
            array(
                'name'=>'Object',
                'type'=>'integer',
                'extra'=>false,
                'visible'=>false,
                'required'=>true,
                'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_object')
            ),
            array(
                'name'=>'Subject',
                'type'=>'text',
                'param'=>'256',
                'extra'=>false,
                'visible'=>true,
                'required'=>false,
                'display_callback'=>array('CRM_RoundcubeCommon','display_subject'),
            ),
            array(
                'name'=>'Employee',
                'type'=>'crm_contact',
                'param'=>array('field_type'=>'select'),
                'extra'=>false,
                'visible'=>true,
                'required'=>false
            ),
            array(
                'name'=>'Date',
                'type'=>'timestamp',
                'extra'=>false,
                'visible'=>true,
                'required'=>false
            ),
            array(
                'name'=>'Attachments',
                'type'=>'calculated',
                'extra'=>false,
                'visible'=>true,
                'display_callback'=>array('CRM_RoundcubeCommon','display_attachments'),
                'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_attachments')
            ),
            array(
                'name'=>'Headers Data',
                'type'=>'long text',
                'extra'=>false,
                'visible'=>false,
                'required'=>false
            ),
            array(
                'name'=>'Headers',
                'type'=>'calculated',
                'extra'=>false,
                'visible'=>false,
                'required'=>false,
                'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_headers')
            ),
            array(
                'name'=>'Body',
                'type'=>'long text',
                'extra'=>false,
                'visible'=>false,
                'required'=>false,
                'QFfield_callback'=>array('CRM_RoundcubeCommon','QFfield_body')
            )
        );
        Utils_RecordBrowserCommon::install_new_recordset('rc_mails', $fields);
        Utils_RecordBrowserCommon::set_caption('rc_mails', 'Mails');
        Utils_RecordBrowserCommon::set_access_callback('rc_mails', array('CRM_RoundcubeCommon', 'access_mails'));

        DB::CreateTable('rc_mails_attachments','
            mail_id I4 NOTNULL,
            type C(32),
            name C(255),
            mime_id I4,
            attachment I1 DEFAULT 1',
            array('constraints'=>', FOREIGN KEY (mail_id) REFERENCES rc_mails_data_1(ID)'));

        Utils_RecordBrowserCommon::new_addon('contact', 'CRM/Roundcube', 'addon', 'Mails');
        Utils_RecordBrowserCommon::new_addon('company', 'CRM/Roundcube', 'addon', 'Mails');
        return true;
    }

    public function uninstall() {
        DB::DropTable('rc_users');
        DB::DropTable('rc_identities');
        DB::DropTable('rc_contacts');
        DB::DropTable('rc_contactgroups');
        DB::DropTable('rc_contactgroupmembers');
        DB::DropTable('rc_session');
        DB::DropTable('rc_cache');
        DB::DropTable('rc_messages');

        Utils_RecordBrowserCommon::delete_addon('contact', 'CRM/Roundcube', 'addon');
        Utils_RecordBrowserCommon::delete_addon('company', 'CRM/Roundcube', 'addon');
//        $rss = DB::GetCol('SELECT f_recordset FROM rc_mails_data_1 GROUP BY f_recordset');
  //      foreach($rss as $rs)
    //        Utils_RecordBrowserCommon::delete_addon($rs, 'CRM/Roundcube', 'addon');
        DB::DropTable('rc_mails_attachments');
        Utils_RecordBrowserCommon::uninstall_recordset('rc_mails');
        Utils_RecordBrowserCommon::uninstall_recordset('rc_accounts');
        Utils_CommonDataCommon::remove('CRM/Roundcube/Security');

		Base_ThemeCommon::uninstall_default_theme($this -> get_type());

        return true;
    }

    public function version() {
        return array("0.1");
    }

    public function requires($v) {
        return array(array('name'=>'Utils/RecordBrowser','version'=>0),
                    array('name'=>'CRM/Contacts','version'=>0));
    }

    public static function info() {
        return array(
            'Description'=>'Roundcube bindings',
            'Author'=>'pbukowski@telaxus.com',
            'License'=>'GPL');
    }

    public static function simple_setup() {
        return true;
    }

}

?>