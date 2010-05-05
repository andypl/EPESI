<?php
class epesi_archive extends rcube_plugin
{
  public $task = 'mail';

  function init()
  {
    $rcmail = rcmail::get_instance();

    $this->register_action('plugin.epesi_archive', array($this, 'request_action'));

    if ($rcmail->action == '' || $rcmail->action == 'show') {
      $skin_path = $this->local_skin_path();

      $this->include_script('archive.js');
      $this->add_texts('localization', true);
      $this->add_button(
        array(
            'command' => 'plugin.epesi_archive',
            'imagepas' => $skin_path.'/archive_pas.png',
            'imageact' => $skin_path.'/archive_act.png',
            'title' => 'buttontitle',
            'domain' => $this->ID,
        ),
        'toolbar');
    }
  }

  function request_action()
  {
    global $E_SESSION;
    $this->add_texts('localization');

    $uids = get_input_value('_uid', RCUBE_INPUT_POST);
    $mbox = get_input_value('_mbox', RCUBE_INPUT_POST);

    $rcmail = rcmail::get_instance();
    $msgs = array();
    $uids = explode(',',$uids);
    foreach($uids as $uid) {
        $msg = new rcube_message($uid);
        if (empty($msg->headers)) {
            $rcmail->output->show_message('messageopenerror', 'error');
            $rcmail->output->send();
            return;
        } else {
            $msgs[] = $msg;
        }
    }

    $map = array();
    foreach($msgs as $k=>$msg) {
        //error_log(print_r($msg->mime_parts,true));
        $contact = DB::GetOne('SELECT id FROM contact_data_1 WHERE active=1 AND f_email=%s AND (f_permission<2 OR created_by=%d)',array($msg->sender['mailto'],$E_SESSION['user']));
        if($contact!==false) {
            $map[$k] = array('recordset'=>'contact','id'=>$contact);
            continue;
        }
        $company = DB::GetOne('SELECT id FROM company_data_1 WHERE active=1 AND f_email=%s AND (f_permission<2 OR created_by=%d)',array($msg->sender['mailto'],$E_SESSION['user']));
        if($company!==false) {
            $map[$k] = array('recordset'=>'company','id'=>$company);
            continue;
        }
        $rcmail->output->command('display_message', $this->gettext('contactnotfound'), 'error');
        $rcmail->output->send();
        return;
    }

    $attachments_dir = '../../../../'.DATA_DIR.'/CRM_Roundcube/attachments/';
    if(!file_exists($attachments_dir)) mkdir($attachments_dir);
    foreach($msgs as $k=>$msg) {
        $object = $map[$k];
        if($msg->has_html_part()) {
            $body = $msg->first_html_part();
            foreach ($msg->mime_parts as $mime_id => $part) {
                $mimetype = strtolower($part->ctype_primary . '/' . $part->ctype_secondary);
                if ($mimetype == 'text/html') {
                    if(isset($part->replaces))
                        $cid_map = $part->replaces;
                    else
                        $cid_map = array();
                    break;
                }
            }
            foreach($cid_map as $k=>&$v) {
                $x = strrchr($v,'=');
                if(!$x) unset($cid_map[$k]);
                else $v = 'get.php?'.http_build_query(array('mail_id'=>'__MAIL_ID__','mime_id'=>substr($x,1)));
            }
            $body = rcmail_wash_html($body,array('safe'=>true,'inline_html'=>true),$cid_map);
        } else {
            $body = '<pre>'.$msg->first_text_part().'</pre>';
        }
        $date = $msg->get_header('timestamp');
        $headers = array();
        foreach($msg->headers as $k=>$v) {
            if(is_string($v))
                $headers[] = $k.': '.$v;
        }
        $employee = DB::GetOne('SELECT id FROM contact_data_1 WHERE active=1 AND f_login=%d',array($E_SESSION['user']));
        DB::Execute('INSERT INTO rc_mails_data_1(created_on,created_by,f_recordset,f_object,f_date,f_employee,f_subject,f_body,f_headers_data) VALUES(%T,%d,%s,%d,%T,%d,%s,%s,%s)',array(
                    time(),$E_SESSION['user'],$object['recordset'],$object['id'],$date,$employee,substr($msg->subject,0,256),$body,implode("\n",$headers)));
        $id = DB::Insert_ID('rc_mails_data_1','id');
        foreach($msg->mime_parts as $mid=>$m) {
            if(!$m->disposition) continue;
            if($m->disposition=='inline')
                $attachment = 0;
            else
                $attachment = 1;
            DB::Execute('INSERT INTO rc_mails_attachments(mail_id,type,name,mime_id,attachment) VALUES(%d,%s,%s,%s,%b)',array($id,$m->mimetype,$m->filename,$m->mime_id,$attachment));
            if(!file_exists($attachments_dir.$id)) mkdir($attachments_dir.$id);
            $fp = fopen($attachments_dir.$id.'/'.$m->mime_id,'w');
            $msg->get_part_content($m->mime_id,$fp);
            fclose($fp);
        }
    }

    //$rcmail->output->command('delete_messages');
    $rcmail->output->command('display_message', $this->gettext('archived'), 'confirmation');

    $rcmail->output->send();
  }

}