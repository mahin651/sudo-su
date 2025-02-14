<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  colleges from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
include('../../RedirectModulesInc.php');
//print_r($_REQUEST);
if ($_REQUEST['modfunc'] == 'save') {
    if (count($_REQUEST['st_arr'])) {

        if ($_REQUEST['_search_all_colleges'] == 'Y') {

            $_REQUEST['_search_all_colleges'] = 'Y';
        }
        $st_list = '\'' . implode('\',\'', $_REQUEST['st_arr']) . '\'';
        $extra['WHERE'] = " AND s.COLLEGE_ROLL_NO IN ($st_list)";

        if ($_REQUEST['day_include_active_date'] && $_REQUEST['month_include_active_date'] && $_REQUEST['year_include_active_date']) {
            $date = $_REQUEST['day_include_active_date'] . '-' . $_REQUEST['month_include_active_date'] . '-' . $_REQUEST['year_include_active_date'];
            $date_extra = 'OR (\'' . $date . '\' >= sr.START_DATE AND sr.END_DATE IS NULL)';
        } else {
            $date = DBDate();
            $date_extra = 'OR sr.END_DATE IS NULL';
        }

        if (isset($_REQUEST['date1']) && $_REQUEST['date1'] != '') {
            $new_date = $_REQUEST['date1'];
        } else {
            $new_date = DBDate();
        }

        $columns = array('COURSE' => 'Course', 'MARKING_PERIOD_ID' => 'Term', 'DAYS' => 'Days', 'PERIOD_TITLE' => 'Period - Teacher', 'DURATION' => 'Time', 'ROOM' => 'Room');

        $extra['SELECT'] .= ',p_cp.COURSE_PERIOD_ID,r.TITLE as ROOM,CONCAT(sp.START_TIME,\'' . ' to ' . '\', sp.END_TIME) AS DURATION,sp.TITLE AS PERIOD,cpv.DAYS,p_cp.SCHEDULE_TYPE,c.TITLE AS COURSE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID';
        $extra['FROM'] .= ' LEFT OUTER JOIN schedule sr ON (sr.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO),courses c,course_periods p_cp,course_period_var cpv,college_periods sp,rooms r ';

//        if($_REQUEST['include_inactive']!='Y')
//        {
//            if(isset($_REQUEST['date1']) && $_REQUEST['date1']!='')
//            {
//                $extra['WHERE'].=' AND (p_cp.MARKING_PERIOD_ID IN ('.GetAllMP_mod(GetMPTable(GetMP($_REQUEST['mp_id'],'TABLE')),$_REQUEST['mp_id']).') OR (p_cp.MARKING_PERIOD_ID IS NULL AND p_cp.BEGIN_DATE<=\''.date('Y-m-d',strtotime($_REQUEST['date1'])).'\' AND p_cp.END_DATE>=\''.date('Y-m-d',strtotime($_REQUEST['date1'])).'\')) ' ;   
//            }
//            else
//            {
//                $extra['WHERE'].=' AND (p_cp.MARKING_PERIOD_ID IN ('.GetAllMP_mod(GetMPTable(GetMP($_REQUEST['mp_id'],'TABLE')),$_REQUEST['mp_id']).') OR (p_cp.MARKING_PERIOD_ID IS NULL AND p_cp.BEGIN_DATE<=\''.date('Y-m-d',strtotime($date)).'\' AND p_cp.END_DATE>=\''.date('Y-m-d',strtotime($date)).'\')) ' ;   
//            }
//        }

        if ((isset($_REQUEST['date1']) && $_REQUEST['date1'] != '')) {
            $extra['WHERE'] .= ' AND POSITION(\'' . get_db_day(date('l', strtotime($_REQUEST['date1']))) . '\' IN cpv.days)>0 AND (cpv.COURSE_PERIOD_DATE=\'' . date('Y-m-d', strtotime($_REQUEST['date1'])) . '\' OR cpv.COURSE_PERIOD_DATE IS NULL)';
        }
        $extra['WHERE'] .= ' AND sr.END_DATE>=\'' . date('Y-m-d') . '\' AND cpv.ROOM_ID=r.ROOM_ID AND ssm.SYEAR=sr.SYEAR AND sr.COURSE_ID=c.COURSE_ID AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID AND cpv.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID AND sp.PERIOD_ID = cpv.PERIOD_ID ';

//        if($_REQUEST['include_inactive']!='Y')
//        {
//                if((isset($_REQUEST['date1']) && $_REQUEST['date1']!=''))
//                {                
//                $extra['WHERE'] .= '   AND (\''.date('Y-m-d',strtotime($_REQUEST['date1'])).'\' BETWEEN sr.START_DATE AND sr.END_DATE  OR (sr.END_DATE IS NULL AND sr.START_DATE<=\''.date('Y-m-d',strtotime($_REQUEST['date1'])).'\')) ';
//                }
//                else
//                {
//                $extra['WHERE'] .= ' AND (\''.date('Y-m-d',strtotime($date)).'\' BETWEEN sr.START_DATE AND sr.END_DATE OR (sr.END_DATE IS NULL AND sr.START_DATE<=\''.date('Y-m-d',strtotime($date)).'\')) ';
//                }
//        }
//                  if($_REQUEST['mp_id'] && (User('PROFILE_ID')!=0 && User('PROFILE_ID')!=3 && User('PROFILE_ID')!=4) ){
//                      $extra['WHERE'] .= ' AND sr.MARKING_PERIOD_ID='.$_REQUEST['mp_id'].'';
//                          }
//                  else
//                  {
        if ($_REQUEST['_search_all_colleges'] == 'Y') {
            $extra['WHERE'] .= ' AND (sr.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM marking_periods WHERE SYEAR='. UserSyear().') or sr.MARKING_PERIOD_ID is NULL)';
        }
        else
        $extra['WHERE'] .= ' AND (sr.MARKING_PERIOD_ID IN (' . GetAllMP_mod(GetMPTable(GetMP(UserMP(), 'TABLE')), UserMP()) . ') or sr.MARKING_PERIOD_ID is NULL)';
       if ($_REQUEST['_search_all_colleges'] == 'Y')
        $extra['functions'] = array('MARKING_PERIOD_ID' => 'GetMPAllCollege');
        else
            $extra['functions'] = array('MARKING_PERIOD_ID' => 'GetMP');
        $extra['group'] = array('COLLEGE_ROLL_NO');
//        $extra['order']=array('SORT_ORDER','MARKING_PERIOD_ID');
        $extra['ORDER'] .= ',p_cp.COURSE_PERIOD_ID,cpv.days';
        $RET = GetStuList($extra);

        foreach ($RET as $ri => $rd) {
            foreach ($rd as $rdi => $rdd) {

                $get_det = DBGet(DBQuery('SELECT cpv.DAYS,cpv.COURSE_PERIOD_DATE,CONCAT(sp.START_TIME,\'' . ' to ' . '\', sp.END_TIME) AS DURATION,r.TITLE as ROOM FROM course_period_var cpv,college_periods sp,rooms r WHERE sp.PERIOD_ID=cpv.PERIOD_ID AND cpv.ROOM_ID=r.ROOM_ID AND cpv.COURSE_PERIOD_ID=' . $rdd['COURSE_PERIOD_ID']));

                if ($rdd['SCHEDULE_TYPE'] == 'FIXED') {
                    $RET[$ri][$rdi]['DAYS'] = _makeDays($get_det[1]['DAYS']);
                    $time = explode(' to ', $get_det[1]['DURATION']);
                    $get_det[1]['DURATION'] = date("g:i A", strtotime($time[0])) . ' to ' . date("g:i A", strtotime($time[1]));
                    unset($time);
                    $RET[$ri][$rdi]['DURATION'] = $get_det[1]['DURATION'];
                    $RET[$ri][$rdi]['ROOM'] = $get_det[1]['ROOM'];
                }
//                else
//                {  
//                    $temp_days=array();
//                    $temp_duration=array();
//                    $temp_room=array();
//               
//                    foreach($get_det as $gi=>$gd)
//                    {
//                       if($rdd['SCHEDULE_TYPE']=='VARIABLE')
//                       $temp_days[$gd['DAYS']]=$gd['DAYS'];
//                       elseif($rdd['SCHEDULE_TYPE']=='BLOCKED')
//                       $temp_days[$gd['DAYS']]=DaySname(date('l',$gd['COURSE_PERIOD_DATE']));
//                       
//                       $time=explode(' to ',$gd['DURATION']);
//                       $gd['DURATION']=date("g:i A", strtotime($time[0])).' to '.date("g:i A", strtotime($time[1]));
//                       unset($time);
//                       $temp_duration[$gd['DURATION']]=$gd['DURATION'];
//                       $temp_room[$gd['ROOM']]=$gd['ROOM'];
//                        
//                    }
//                    $RET[$ri][$rdi]['DAYS']=_makeDays(implode('',$temp_days));
//                    $RET[$ri][$rdi]['DURATION']=implode(',',$temp_duration);
//                    $RET[$ri][$rdi]['ROOM']=implode(',',$temp_room);
//                }
            }
        }
//      
//        foreach($RET as $college_roll_no =>$RET1)
//        {
//            $ex_cp_arr=array();
//            foreach($RET1 as $key => $val)
//            {
//                if(!in_array($val['COURSE_PERIOD_ID'],$ex_cp_arr))
//                {
//                    $ex_cp_arr[]=$val['COURSE_PERIOD_ID'];
//                }
// else {
//
//     unset($RET[$college_roll_no][$key]);
// }
//            }
//        }
//        $RET_new=array();
//        foreach($RET as $college_roll_no =>$RET1)
//        {
//            $k=1;
//            $ex_cp_arr=array();
//            foreach($RET1 as $key => $val)
//            {
//               $RET_new[$college_roll_no][$k]=$val;
//               $k++;
//
//            }
//        }
        // $RET=$RET_new;

        if (count($RET)) {
            $handle = PDFStart();

            foreach ($RET as $college_roll_no => $courses) {
                echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetCollege(UserCollege()) . "<div style=\"font-size:12px;\">Student Schedules Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";

                unset($_openSIS['DrawHeader']);
                echo '<br>';
                echo '<table  border=0>';
                echo '<tr><td>College Roll No:</td>';
                echo '<td>' . $courses[1]['COLLEGE_ROLL_NO'] . '</td></tr>';
                echo '<tr><td>Student Name:</td>';
                echo '<td>' . $courses[1]['FULL_NAME'] . '</td></tr>';
                echo '<tr><td>Student Grade:</td>';
                echo '<td>' . $courses[1]['GRADE_ID'] . '</td></tr>';
                if ($_REQUEST['mailing_labels'] == 'Y') {
                    $mail_address = DBGet(DBQuery('SELECT STREET_ADDRESS_1,STREET_ADDRESS_2,CITY,STATE,ZIPCODE FROM student_address WHERE TYPE=\'Mail\' AND  COLLEGE_ROLL_NO=' . $courses[1]['COLLEGE_ROLL_NO']));
                    $mail_address = $mail_address[1]['STREET_ADDRESS_1'] . ($mail_address[1]['STREET_ADDRESS_2'] != '' ? ' ' . $mail_address[1]['STREET_ADDRESS_2'] : ' ') . '<br>' . $mail_address[1]['CITY'] . ', ' . $mail_address[1]['STATE'] . ' ' . $mail_address[1]['ZIPCODE'];
                    echo '<tr><td>Mailing Details:</td>';
                    echo '<td>' . ($mail_address != '' ? $mail_address : 'N/A') . '</td></tr>';
                }
                echo '</table>';

//			 print_r($courses);
                ListOutputPrint_sch($courses, $columns, 'Course', 'Courses', array(), array(), array('center' => false, 'print' => false));
                echo '<div style="page-break-before: always;">&nbsp;</div><!-- NEW PAGE -->';
            }
            PDFStop($handle);
        } else
            BackPrompt('No Students were found.');
    } else
        BackPrompt('You must choose at least one student.');
}

if (!$_REQUEST['modfunc']) {
    DrawBC("Scheduling > " . ProgramTitle());

    if ($_REQUEST['search_modfunc'] == 'list') {
        $mp_RET = DBGet(DBQuery('SELECT MARKING_PERIOD_ID,TITLE,SORT_ORDER,1 AS TBL FROM college_years WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SORT_ORDER,2 AS TBL FROM college_semesters WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SORT_ORDER,3 AS TBL FROM college_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY TBL,SORT_ORDER'));
        $mp_select = '<SELECT class="form-control" name=mp_id><OPTION value="">N/A';
        foreach ($mp_RET as $mp)
            $mp_select .= '<OPTION value=' . $mp['MARKING_PERIOD_ID'] . '>' . $mp['TITLE'];
        $mp_select .= '</SELECT>';

        echo "<FORM class=form-horizontal name=sch id=sch action=ForExport.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&head_html=Student+Schedules+Report&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_openSIS_PDF=true method=POST target=_blank>";

        Widgets('mailing_labels', true);
        $extra['extra_header_left'] = '<div class="row">';
        $extra['extra_header_left'] .= '<div class="col-md-3"><label class="control-label col-md-4">Marking Period</label><div class="col-md-8">' . $mp_select . '</div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-3"><label class="control-label col-md-4">Include only courses active as of</label><div class="col-md-8">' . DateInputAY('', 'include_active_date', 1) . '</div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-3">' . $extra['search'] . '</div>';
        $extra['search'] = '';
        $extra['extra_header_left'] .= '</div>';
    }

    $extra['link'] = array('FULL_NAME' => false);
    $extra['SELECT'] = ',s.COLLEGE_ROLL_NO AS CHECKBOX';
    $extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
//    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
    $extra['options']['search'] = false;
    $extra['new'] = true;

    $extra['search'] .= '<div class="row">';
    $extra['search'] .= '<div class="col-lg-6">';
    Widgets('request_mod');
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '<div class="col-lg-6">';
    Widgets('course');
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '</div>'; //.row

    Search('college_roll_no', $extra);

    if ($_REQUEST['search_modfunc'] == 'list') {
        if ($_SESSION['count_stu'] != 0)
            echo '<div class="text-right p-r-20 p-b-20"><INPUT type=submit class="btn btn-primary" value="Create Schedules for Selected Students"></div>';
        echo "</FORM>";
    }
}

function _makeDays($value, $column = '') {
    foreach (array('U', 'M', 'T', 'W', 'H', 'F', 'S') as $day)
        if (strpos($value, $day) !== false)
            $return .= $day;
        else
            $return .= '-';
    return $return;
}

function _makeChooseCheckbox($value, $title) {
    global $THIS_RET;

    return '<INPUT type=checkbox name=st_arr[] value=' . $value . '>';
    
//   return "<input name=unused[$THIS_RET[COLLEGE_ROLL_NO]]  type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckbox(\"values[STUDENTS][$THIS_RET[COLLEGE_ROLL_NO]]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";

//   return "<input name=unused[$THIS_RET[COLLEGE_ROLL_NO]] value=" . $THIS_RET[COLLEGE_ROLL_NO] . "  type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckboxStudents(\"st_arr[]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";
   
}

function get_db_day($day) {
    switch ($day) {
        case 'Sunday':
            $return = 'U';
            break;
        case 'Monday':
            $return = 'M';
            break;
        case 'Tuesday':
            $return = 'T';
            break;
        case 'Wednesday':
            $return = 'W';
            break;
        case 'Thursday':
            $return = 'H';
            break;
        case 'Friday':
            $return = 'F';
            break;
        case 'Saturday':
            $return = 'S';
            break;
    }
    return $return;
}
?>

