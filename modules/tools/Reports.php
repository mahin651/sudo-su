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
ini_set('memory_limit', '120000000000M');
ini_set('max_execution_time', '50000000');
if ($_REQUEST['func'] == 'Basic') {
    $num_colleges = DBGet(DBQuery('SELECT COUNT(ID) as TOTAL_COLLEGES FROM colleges'));
    $num_colleges = $num_colleges[1]['TOTAL_COLLEGES'];

    $num_students = DBGet(DBQuery('SELECT COUNT(COLLEGE_ROLL_NO) as TOTAL_STUDENTS FROM students WHERE COLLEGE_ROLL_NO IN (SELECT DISTINCT COLLEGE_ROLL_NO FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ')'));
    $num_students = $num_students[1]['TOTAL_STUDENTS'];
    $male = DBGet(DBQuery('SELECT COUNT(COLLEGE_ROLL_NO) as MALE FROM students WHERE GENDER=\'Male\' AND COLLEGE_ROLL_NO IN (SELECT DISTINCT COLLEGE_ROLL_NO FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ')'));
    $male = $male[1]['MALE'];
    $female = DBGet(DBQuery('SELECT COUNT(COLLEGE_ROLL_NO) as FEMALE FROM students WHERE GENDER=\'Female\' AND COLLEGE_ROLL_NO IN (SELECT DISTINCT COLLEGE_ROLL_NO FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ')'));
    $female = $female[1]['FEMALE'];
    $num_staff = 0;
    $num_teacher = 0;
    $num_users = DBGet(DBQuery('SELECT COUNT(DISTINCT s.STAFF_ID) as TOTAL_USER,IF(PROFILE_ID=2,\'Teacher\',\'Staff\') as PROFILEID FROM staff s,staff_college_relationship ssr WHERE s.STAFF_ID=ssr.STAFF_ID AND SYEAR = ' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ' AND COLLEGE_ID IN (SELECT ID FROM colleges ) GROUP BY PROFILEID'));

    foreach ($num_users as $gt_dt) {
        if ($gt_dt['PROFILEID'] == 'Staff')
            $num_staff = $gt_dt['TOTAL_USER'];
        else
            $num_teacher = $gt_dt['TOTAL_USER'];
    }

    $num_parent = DBGet(DBQuery('SELECT COUNT(distinct p.STAFF_ID) as TOTAL_PARENTS FROM people p,students_join_people sjp WHERE sjp.PERSON_ID=p.STAFF_ID AND sjp.COLLEGE_ROLL_NO IN (SELECT DISTINCT COLLEGE_ROLL_NO FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ')'));
    if ($num_parent[1]['TOTAL_PARENTS'] == '')
        $num_parent = 0;
    else
        $num_parent = $num_parent[1]['TOTAL_PARENTS'];
    echo '<div class="panel panel-default">';
    echo '<div class="tabbable">';
    echo '<ul class="nav nav-tabs nav-tabs-bottom no-margin-bottom"><li class="active" id="tab[]"><a href="javascript:void(0);">At a Glance</a></li></ul>';
    echo '<div class="panel-body institute-report">';
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-college"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">Institutions<span class="display-block no-margin text-success">'.$num_colleges.'</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-student"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">Students<span class="display-block no-margin text-success">'.$num_students.' <small class="no-margin">(Male : '.$male.'  &nbsp; | &nbsp;  Female : '.$female.')</small></span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-teacher"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">Teachers<span class="display-block no-margin text-success">'.$num_teacher.'</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-staff"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">Staff<span class="display-block no-margin text-success">'.$num_staff.'</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-parent"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">Parents<span class="display-block no-margin text-success">'.$num_parent.'</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '</div>'; //.row
//    echo '<div id="d"><TABLE align=center cellpadding=5 cellspacing=5>';
//    echo '<tr><td><b>Number of Institutions</b></td><td>:</td><td>&nbsp ' . $num_colleges . ' &nbsp </td></tr>';
//    echo '<tr><td><b>Number of Students</b></td><td>:</td><td>&nbsp ' . $num_students . ' &nbsp </td><td> &nbsp Male : ' . $male . ' &nbsp| &nbspFemale : ' . $female . '</td></tr>';
//    echo '<tr><td><b>Number of Teachers</b></td><td>:</td><td colspan=2>&nbsp ' . $num_teacher . '</td></tr>';
//    echo '<tr><td><b>Number of Staff</b></td><td>:</td><td colspan=2>&nbsp ' . $num_staff . '</td></tr>';
//    echo '<tr><td><b>Number of Parents</b></td><td>:</td><td colspan=2>&nbsp ' . $num_parent . '</td></tr>';
//    echo '</TABLE></div>';
    echo '</div>';
    echo '</div>';//.tabbable
    echo '</div>';//.panel
}

if ($_REQUEST['func'] == 'Ins_r') {
    if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'save') {
        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
        echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">Institute Reports</td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
        echo "<table >";


        $arr = array();

        if ($_REQUEST['fields']) {
            $i = 0;
            foreach ($_REQUEST['fields'] as $field => $on) {
                $columns .= $field . ',';
                $arr[$field] = $field;
            }

            $columns = substr($columns, 0, -1);
            foreach ($arr as $m => $n) {

                if ($m == 'E_MAIL')
                    $arr[$m] = 'Email';
                elseif ($m == 'TITLE')
                    $arr[$m] = 'College Name';
                elseif ($m == 'REPORTING_GP_SCALE')
                    $arr[$m] = 'Base Grading Scale';
                elseif ($m == 'MAIL_ADDRESS')
                    $arr[$m] = 'Mailling Address';
                elseif ($m == 'MAIL_CITY')
                    $arr[$m] = 'Mailling City';
                elseif ($m == 'MAIL_STATE')
                    $arr[$m] = 'Malling State';
                elseif ($m == 'MAIL_ZIP')
                    $arr[$m] = 'Malling Zip';
                elseif ($m == 'WWW_ADDRESS')
                    $arr[$m] = 'Website';
                else {
                    $col = explode('_', $m);
                    if ($col[0] == 'CUSTOM' && $col[1] != '')
                        $get_field_name = DBGet(DBQuery('SELECT TITLE FROM college_custom_fields WHERE ID=' . $col[1]));
                    foreach ($col as $col_i => $col_d) {

                        $f_c = substr($col_d, 0, 1);
                        $r_c = substr($col_d, 1);
                        $txt = $f_c . strtolower($r_c);
                        unset($f_c);
                        unset($r_c);

                        $col[$col_i] = $txt;
                        unset($txt);
                    }
                    unset($col_i);
                    unset($col_d);
                    $col = implode(' ', $col);

                    if ($get_field_name[1]['TITLE'] != '')
                        $arr[$m] = $get_field_name[1]['TITLE'];
                    else
                        $arr[$m] = $col;
                    unset($get_field_name);
                }
            }
            echo '<br>';
            // print_r($arr);print_r($arr);
            // echo 'SELECT ID,' . $columns . ' FROM colleges';
            $get_college_info = DBGet(DBQuery('SELECT ID,' . $columns . ' FROM colleges'));

            echo '<br>';
            foreach ($get_college_info as $key => $value) {

                foreach ($value as $i => $j) {


                    $column_check = explode('_', $i);
                    if ($column_check[0] == 'CUSTOM') {
                        $check_validity = DBGet(DBQuery('SELECT COUNT(*) as REC_EX FROM college_custom_fields WHERE ID=' . $column_check[1] . ' AND (COLLEGE_ID=' . $get_college_info[$key]['ID'].' OR COLLEGE_ID=0)'));
                        if ($check_validity[1]['REC_EX'] == 0)
                            $j = 'NOT_AVAILABLE_FOR';
                    }
                    $get_college_info[$key][$i] = trim($j);
                }
            }
            $show_legend = 'no';
            foreach ($get_college_info as $key => $value) {

                foreach ($value as $i => $j) {

                    if ($j == 'NOT_AVAILABLE_FOR') {
                        $show_legend = 'yes';
                        $get_college_info[$key][$i] = "<img src='assets/not_available.png' title='Not Applicable'/>";
                    }
                }
            }
            // print_r($get_college_info);

            echo "<html><link rel='stylesheet' type='text/css' href='styles/Export.css'><body style=\" font-family:Arial; font-size:12px;\">";
            ListOutputPrint_Institute_Report($get_college_info, $arr);

            echo "</body></html>";
        }
    } else {
        echo "<FORM action=ForExport.php?modname=$_REQUEST[modname]&head_html=Institute+Report&modfunc=save&_openSIS_PDF=true method=POST target=_blank>";
        echo '<DIV id=fields_div></DIV>';
        echo '<br/>';

        $fields_list['Available College Fields'] = array('TITLE' => 'College Name', 'ADDRESS' => 'Address', 'CITY' => 'City', 'STATE' => 'State', 'ZIPCODE' => 'Zipcode', 'PHONE' => 'Telephone', 'PRINCIPAL' => 'Principal', 'REPORTING_GP_SCALE' => 'Base Grading Scale', 'E_MAIL' => 'Email', 'WWW_ADDRESS' => 'Website');
        $get_colleges_cf = DBGet(DBQuery('SELECT * FROM college_custom_fields'));
        if (count($get_colleges_cf) > 0) {
            foreach ($get_colleges_cf as $gsc) {
                $fields_list['Available College Fields']['CUSTOM_' . $gsc[ID]] = $gsc['TITLE'];
            }
        }        
	echo '<div class="row">';
        echo '<div class="col-md-8">';
        PopTable('header','<i class=\"glyphicon glyphicon-tasks\"></i> &nbsp;Select Fields To Generate Report');

        foreach ($fields_list as $category => $fields) {

            echo '<h5 class="text-primary">' . $category . '</h5>';
            $i = 0;
            $j = 0;
            foreach ($fields as $field => $title) {
                if($i==0 && $j==0){
                    echo '<div class="row">';
                }elseif($i==0 && $j>0){
                    echo '</div><div class="row">';
                }
                echo '<div class="col-md-6"><label class="checkbox-inline"><INPUT type=checkbox onclick="addHTML(\'<LI>' . $title . '</LI>\',\'names_div\',false);addHTML(\'<INPUT type=hidden name=fields[' . $field . '] value=Y>\',\'fields_div\',false);addHTML(\'\',\'names_div_none\',true);this.disabled=true">' . $title . '<label></div>';
                
                /*if ($i % 2 == 0)
                    echo '</TR><TR>';*/
                $i++;
                if($i==2){
                    $i = 0;
                }
                $j++;
            }
            echo '</div>';
            /*if ($i % 2 != 0) {
                echo '<TD></TD></TR><TR>';
                $i++;
            }*/
        }
        PopTable('footer');
        echo '</div><div class="col-md-4">';
        PopTable("header","<i class=\"glyphicon glyphicon-saved\"></i> &nbsp;Selected Fields");
        echo '<div id="names_div_none" class="error_msg" style="padding:6px 0px 0px 6px;">No fields selected</div><ol id=names_div class="selected_report_list"></ol>';
        
        $btn = '<INPUT type=submit value=\'Create Report for Institutes\' class="btn btn-primary">';
        PopTable('footer', $btn);
        echo '</div>'; //.col-md-6
        echo '</div>'; //.row
        echo "</FORM>";
    }
}
if ($_REQUEST['func'] == 'Ins_cf') {
    $get_colleges_cf = DBGet(DBQuery('SELECT s.TITLE AS COLLEGE,s.ID,sc.* FROM colleges s,college_custom_fields sc WHERE s.ID=sc.COLLEGE_ID ORDER BY sc.COLLEGE_ID'));
    foreach ($get_colleges_cf as $cf_i => $cf_d) {
        foreach ($cf_d as $cfd_i => $cfd_d) {
            if ($cfd_i == 'TYPE') {
                $fc = substr($cfd_d, 0, 1);
                $lc = substr($cfd_d, 1);
                $cfd_d = strtoupper($fc) . $lc;
                $get_colleges_cf[$cf_i][$cfd_i] = $cfd_d;
                unset($fc);
                unset($lc);
            }
            if ($cfd_i == 'SELECT_OPTIONS' && $cf_d['TYPE'] != 'text') {

                for ($i = 0; $i < strlen($cfd_d); $i++) {
                    $char = substr($cfd_d, $i, 1);
                    if (ord($char) == '13')
                        $char = '<br/>';
                    $new_char[] = $char;
                }

                $cfd_d = implode('', $new_char);
                $get_colleges_cf[$cf_i][$cfd_i] = $cfd_d;
                unset($char);
                unset($new_char);
            }
            if ($cfd_i == 'SYSTEM_FIELD' || $cfd_i == 'REQUIRED') {
                if ($cfd_d == 'N')
                    $get_colleges_cf[$cf_i][$cfd_i] = 'No';
                if ($cfd_d == 'Y')
                    $get_colleges_cf[$cf_i][$cfd_i] = 'Yes';
            }
        }
        unset($cfd_i);
        unset($cfd_d);
    }
    foreach ($get_colleges_cf as $g_i => $gd) {
        $gt_fld_v = DBGet(DBQuery('SELECT CUSTOM_' . $gd['ID'] . ' as FIELD from colleges WHERE ID=' . $gd['COLLEGE_ID']));
        $get_colleges_cf[$g_i]['C_VALUE'] = $gt_fld_v[1]['FIELD'];
    }

    $column = array('COLLEGE' => 'College', 'TYPE' => 'Custom Field Type', 'TITLE' => 'Custom Field Name', 'SELECT_OPTIONS' => 'Options', 'SYSTEM_FIELD' => 'System Field', 'REQUIRED' => 'Required Field');

    echo '<div class="panel panel-default">';
    ListOutput($get_colleges_cf, $column, 'Custom Field', 'Custom Fields');
    echo '</div>';
}
?>