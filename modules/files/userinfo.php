<?php
   /**
   * Модуль информации по пользователю
   * 
   * @author Dmitrey Browko
   * @copyright Copyright (c)2011 by Kasseler CMS
   * @link http://www.kasseler-cms.net/
   * @version 2.0
   */
   if (!defined('KASSELERCMS')) die("Hacking attempt!");
   global $nextpage, $main;
   function ui_files(){
      global $main,$files,$tpl_create,$nextpage,$navi;
      if(hook_check(__FUNCTION__)) return hook();
      //Выводим навигацию по модулю
      echo $navi;
      $user=addslashes($_GET['user']);
      $catlist=category_array();
      $num = isset($_GET['page']) ? intval($_GET['page']) : "1";
      $offset = ($num-1) * $files['publications_in_page'];
      if($offset<0) kr_http_ereor_logs(404);
      $where_files=" f.status='1' AND f.author like '{$user}' ";
      $main->db->sql_query("select count(f.id) as cnt from ".FILES." AS f where {$where_files}");
      list($numrows)=$main->db->sql_fetchrow();
      //Инициализация переменных для определения закладок
      $favorite_select = (is_user() AND $files['favorite_status']==ENABLED) ? ", fav.id AS favorite_id" : '';
      $favorite_table  = (is_user() AND $files['favorite_status']==ENABLED) ? "LEFT JOIN ".FAVORITE." AS fav ON (fav.post=f.id AND fav.users='{$main->user['user_name']}' AND fav.modul='{$main->module}')" : ''; 
      //Выполняем запрос в БД
      $result = $main->db->sql_query("SELECT f.*, u.uid, u.user_id, u.user_name,r.r_up,r.r_down,r.users{$favorite_select}
      FROM ".FILES." AS f LEFT JOIN ".USERS." AS u ON (f.author = u.user_name) LEFT JOIN ".RATINGS." AS r ON (r.module='files' and r.idm=f.id) {$favorite_table}
      WHERE {$where_files}
      ORDER BY f.id desc LIMIT {$offset}, {$files['publications_in_page']}"); 
      //Узнаем количество полученных публикаций
      $rows = $main->db->sql_numrows($result);
      if($rows>0){
         if($numrows>$files['publications_in_page']){
            //Открываем стилевую таблицу
            open();
            //В зависимости от типа вывода создаем страницы
            pages($numrows, $files['publications_in_page'],$nextpage, true);
            //Закрываем стилевую таблицу
            close();
         }
         $i = (1*$num>1) ? ($files['publications_in_page']*($num-1))+1 : 1*$num;
         $line = "row1";
         //Перебираем результат SQL запроса
         main::init_function('rating');
         while(($row = $main->db->sql_fetchrow($result))){
            //Создаем массив параметров для вывода публикации
            $pub = array(
                'id'         => $row['id'],
                'rewrite_id' => $row['files_id'],
                'title'      => "<a class='sys_link' href='".$main->url(array('module' => $main->module, 'do' => 'more', 'id' => case_id($row['files_id'], $row['id'])))."' title='{$row['title']}'>{$row['title']}</a>",
                'content'    => parse_bb($row['description']),
                'views'      => $row['view'],
                'comment'    => "<a class='sys_link' href='".$main->url(array('module' => $main->module, 'do' => 'more', 'id' => case_id($row['files_id'], $row['id'])))."#comments' title='{$row['title']}'>{$row['comment']}</a>",
                'count_comm' => $row['comment'],
                'more'       => "<a class='sys_link' href='".$main->url(array('module' => $main->module, 'do' => 'more', 'id' => case_id($row['files_id'], $row['id'])))."' title='{$row['title']}'>{$main->lang['more']}</a>",
                'date'       => user_format_date($row['date']),
                'year'       => format_date($row['date'], 'Y'),
                'month'      => format_date($row['date'], 'm'),
                'day'        => format_date($row['date'], 'd'),
                'tags'       => list_tags($row['tags'], $main->module),
                'author'     => (!is_guest_name($row['author']) AND !empty($row['user_id'])) ? "<a class='author' href='".$main->url(array('module' => 'account', 'do' => 'user', 'id' => case_id($row['user_id'], $row['uid'])))."' title='{$main->lang['user_profile']}'>{$row['author']}</a>" : $row['author'],
                'language'   => $row['language'],
                'category'   => cat_parse_new($row['cid'],$catlist),
                'favorite'   => (is_user() AND $files['favorite_status']==ENABLED) ? favorite_button($row['favorite_id'], $row['id']) : '',
                'tags'       => ($files['tags_status']==ENABLED AND !empty($row['tags']))?list_tags($row['tags'], $main->module):'',
                'lang_tags'  => ($files['tags_status']==ENABLED AND !empty($row['tags']))?$main->lang['tags'].":":'',
                'lang_cat'   => $main->lang['category'],
                'lang_view'  => $main->lang['views'],
                'lang_comm'  => $main->lang['comments'],
                'lang_author'=> $main->lang['author'],
                'lang_date'  => $main->lang['date_pub'],
                'lang_title' => $main->lang['name'],
                'lang_month' => lang_month(format_date($row['date'], 'n')),
                'load_tpl'   => $main->tpl,
                'row'        => $line,
                'num_id'     => $i
            ); 
            $line = ($line=="row1") ? "row2" : "row1";
            //Если рейтинг активен  добавляем JS
            $pub = rating_modify_publisher($row['id'], 'files', $row, $pub, $files['ratings']==ENABLED);
            //Выводим публикацию
            publisher($row['id'], $pub);
            $i++;
         }
         if($numrows>$files['publications_in_page']){
            //Открываем стилевую таблицу
            open();
            //В зависимости от типа вывода создаем страницы
            pages($numrows, $files['publications_in_page'],$nextpage, true);
            //Закрываем стилевую таблицу
            close();
         }         
      }
      if(is_ajax()) kr_exit();
   }
   if($_GET['do']=='userinfo'){
      $main->parse_rewrite(array('module', 'do', 'user','page'));
      bcrumb::add($main->lang['userinfo'],$main->url(array('module'=>'account','do'=>'user','id'=>$_GET['user'])));
      bcrumb::add($main->lang['user_list_files']);
      $nextpage=array('module'=>$main->module, 'do'=>$_GET['do'], 'user'=>$_GET['user']);
      ui_files();
   } else kr_http_ereor_logs("404");

?>
