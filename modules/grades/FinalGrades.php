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
require_once('modules/grades/DeletePromptX.fnc.php');
if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'delete') {
    if (($dp = DeletePromptX('final grade'))) {
        DBQuery('DELETE FROM student_report_card_grades WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ROLL_NO=\'' . $_REQUEST['college_roll_no'] . '\' AND COURSE_PERIOD_ID=\'' . $_REQUEST['course_period_id'] . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\'');
        DBQuery('DELETE FROM student_report_card_comments WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ROLL_NO=\'' . $_REQUEST['college_roll_no'] . '\' AND COURSE_PERIOD_ID=\'' . $_REQUEST['course_period_id'] . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\'');
        $_REQUEST['modfunc'] = 'save';
    } elseif ($dp === false)
        $_REQUEST['modfunc'] = 'save';
}
if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'save') {
    if (count($_REQUEST['mp_arr']) && count($_REQUEST['st_arr'])) {
        $mp_list = '\'' . implode('\',\'', $_REQUEST['mp_arr']) . '\'';
        $last_mp = end($_REQUEST['mp_arr']);
        $st_list = '\'' . implode('\',\'', $_REQUEST['st_arr']) . '\'';
        $extra['WHERE'] = ' AND s.COLLEGE_ROLL_NO IN (' . $st_list . ')';

        $extra['SELECT'] .= ',rpg.TITLE as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.COMMENT as COMMENT_TITLE,sg1.COLLEGE_ROLL_NO,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,rc_cp.TITLE AS TEACHER,rc_cp.TEACHER_ID,sp.SORT_ORDER';
        if ($_REQUEST['elements']['period_absences'] == 'Y')
            $extra['SELECT'] .= ',cpv.DOES_ATTENDANCE,
                                    (SELECT count(*) FROM attendance_period ap,attendance_codes ac
                                    WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE=\'A\' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND ap.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO) AS YTD_ABSENCES,
                                    (SELECT count(*) FROM attendance_period ap,attendance_codes ac
                                    WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE=\'A\' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.MARKING_PERIOD_ID=ap.MARKING_PERIOD_ID AND ap.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO) AS MP_ABSENCES';
        if ($_REQUEST['elements']['comments'] == 'Y')
            $extra['SELECT'] .= ',sg1.MARKING_PERIOD_ID AS COMMENTS_RET';
        $extra['FROM'] .= ',student_report_card_grades sg1 LEFT OUTER JOIN report_card_grades rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID),
                                    course_periods rc_cp,course_period_var cpv,courses c,college_periods sp';
        $extra['WHERE'] .= ' AND sg1.MARKING_PERIOD_ID IN (' . $mp_list . ')
                             AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND cpv.COURSE_PERIOD_ID=rc_cp.COURSE_PERIOD_ID AND c.COURSE_ID = rc_cp.COURSE_ID AND sg1.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO AND sp.PERIOD_ID=cpv.PERIOD_ID';
        if(User('PROFILE')=='teacher')
        {
            $extra['WHERE'] .= ' AND (rc_cp.TEACHER_ID='.User('STAFF_ID').' OR rc_cp.SECONDARY_TEACHER_ID='.User('STAFF_ID').')';
        }
        $extra['ORDER'] .= ',sp.SORT_ORDER,c.TITLE';
        $extra['functions']['TEACHER'] = '_makeTeacher';
        if ($_REQUEST['elements']['comments'] == 'Y')
            $extra['functions']['COMMENTS_RET'] = '_makeComments';
        $extra['group'] = array('COLLEGE_ROLL_NO');
        $extra['group'][] = 'COURSE_PERIOD_ID';
        $extra['group'][] = 'MARKING_PERIOD_ID';

        $RET = GetStuList($extra);

        // GET THE COMMENTS
        if ($_REQUEST['elements']['comments'] == 'Y')
            $comments_RET = DBGet(DBQuery('SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\''), array(), array('ID'));

        if ($_REQUEST['elements']['mp_tardies'] == 'Y' || $_REQUEST['elements']['ytd_tardies'] == 'Y') {
            // GET THE ATTENDANCE
            unset($extra);
            $extra['WHERE'] = ' AND s.COLLEGE_ROLL_NO IN (' . $st_list . ')';
            $extra['SELECT_ONLY'] .= 'ap.COLLEGE_DATE,ap.COURSE_PERIOD_ID,ac.ID AS ATTENDANCE_CODE,ap.MARKING_PERIOD_ID,ssm.COLLEGE_ROLL_NO';
            $extra['FROM'] .= ',attendance_codes ac,attendance_period ap';
            $extra['WHERE'] .= ' AND ac.ID=ap.ATTENDANCE_CODE AND (ac.DEFAULT_CODE!=\'Y\' OR ac.DEFAULT_CODE IS NULL) AND ac.SYEAR=ssm.SYEAR AND ap.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO';
            $extra['group'][] = 'COLLEGE_ROLL_NO';
            $extra['group'][] = 'ATTENDANCE_CODE';
            $extra['group'][] = 'MARKING_PERIOD_ID';

            $extra['search'] .= '<div class="row">';
            $extra['search'] .= '<div class="col-lg-6">';
            Widgets('course');
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '</div>'; //.row


            $extra['search'] .= '<div class="row">';
            $extra['search'] .= '<div class="col-lg-6">';
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            $extra['search'] .= '<div class="pl-10">';
            Widgets('gpa');
            $extra['search'] .= '</div>';
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            Widgets('class_rank');
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '<div class="col-lg-6">';
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            Widgets('letter_grade');
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '</div>'; //.row

            $attendance_RET = GetStuList($extra);
        }

        if ($_REQUEST['elements']['mp_absences'] == 'Y' || $_REQUEST['elements']['ytd_absences'] == 'Y') {
            // GET THE DAILY ATTENDANCE
            unset($extra);
            $extra['WHERE'] = ' AND s.COLLEGE_ROLL_NO IN (' . $st_list . ')';
            $extra['SELECT_ONLY'] .= 'ad.COLLEGE_DATE,ad.MARKING_PERIOD_ID,ad.STATE_VALUE,ssm.COLLEGE_ROLL_NO';
            $extra['FROM'] .= ',attendance_day ad';
            $extra['WHERE'] .= ' AND ad.COLLEGE_ROLL_NO=ssm.COLLEGE_ROLL_NO AND ad.SYEAR=ssm.SYEAR AND (ad.STATE_VALUE=\'0.0\' OR ad.STATE_VALUE=\'.5\') AND ad.COLLEGE_DATE<=\'' . GetMP($last_mp, 'END_DATE') . '\'';
            $extra['group'][] = 'COLLEGE_ROLL_NO';
            $extra['group'][] = 'MARKING_PERIOD_ID';

            $extra['search'] .= '<div class="row">';
            $extra['search'] .= '<div class="col-lg-6">';
            Widgets('course');
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '</div>'; //.row


            $extra['search'] .= '<div class="row">';
            $extra['search'] .= '<div class="col-lg-6">';
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            $extra['search'] .= '<div class="pl-10">';
            Widgets('gpa');
            $extra['search'] .= '</div>';
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            Widgets('class_rank');
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '<div class="col-lg-6">';
            $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
            Widgets('letter_grade');
            $extra['search'] .= '</div>'; //.well
            $extra['search'] .= '</div>'; //.col-lg-6
            $extra['search'] .= '</div>'; //.row

            $attendance_day_RET = GetStuList($extra);
        }

        if (count($RET)) {
            DrawBC("Gradebook > " . ProgramTitle());

            $columns = array('FULL_NAME' => 'Student', 'COURSE_TITLE' => 'Course');
            if ($_REQUEST['elements']['teacher'] == 'Y')
                $columns += array('TEACHER' => 'Teacher');
            if ($_REQUEST['elements']['period_absences'] == 'Y')
                $columns['ABSENCES'] = 'Abs<BR>YTD / MP';
            foreach ($_REQUEST['mp_arr'] as $mp) {
                if ($_REQUEST['elements']['percents'] == 'Y')
                    $columns[$mp . '%'] = '%';
                $columns[$mp] = GetMP($mp);
            }
            if ($_REQUEST['elements']['comments'] == 'Y')
                $columns['COMMENT'] = 'Comment';
            $i = 0;
            foreach ($RET as $college_roll_no => $course_periods) {
                $course_period_id = key($course_periods);
                $grades_RET[$i + 1]['FULL_NAME'] = '<div style="white-space: nowrap;">'.$course_periods[$course_period_id][key($course_periods[$course_period_id])][1]['FULL_NAME'].'</div>';

                $grades_RET[$i + 1]['bgcolor'] = 'FFFFFF';
                foreach ($course_periods as $course_period_id => $mps) {

                    $i++;
                    $grades_RET[$i]['COLLEGE_ROLL_NO'] = $college_roll_no;
                    $grades_RET[$i]['COURSE_PERIOD_ID'] = $course_period_id;
                    $grades_RET[$i]['MARKING_PERIOD_ID'] = key($mps);

                    $grades_RET[$i]['COURSE_TITLE'] = $mps[key($mps)][1]['COURSE_TITLE'];
                    if ($mps[$last_mp][1]['TEACHER'] == '' && $course_period_id != '') {
                        $get_teacher = DBGet(DBQuery('SELECT s.FIRST_NAME,s.LAST_NAME FROM staff s,course_periods cp WHERE cp.COURSE_PERIOD_ID=' . $course_period_id . ' AND s.STAFF_ID=cp.TEACHER_ID'));
                        $grades_RET[$i]['TEACHER'] = '<div style="white-space: nowrap;">'.$get_teacher[1]['FIRST_NAME'] . ' ' . $get_teacher[1]['LAST_NAME'].'</div>';
                    } else
                        $grades_RET[$i]['TEACHER'] = $mps[$last_mp][1]['TEACHER'];


                    foreach ($_REQUEST['mp_arr'] as $mp) {
                        if ($mps[$mp]) {
                            if ($mps[$mp][1]['GRADE_PERCENT'] != Null || $mps[$mp][1]['GRADE_PERCENT'] != '')
                                $grades_RET[$i][$mp] = $grades_RET[$i][$mp] = _makeLetterGrade($mps[$mp][1]['GRADE_PERCENT'] / 100, $course_period_id, $mps[$last_mp][1]['TEACHER_ID'], "") . '&nbsp;';
                            if ($_REQUEST['elements']['percents'] == 'Y' && $mps[$mp][1]['GRADE_PERCENT'] > 0 && $mps[$mp][1]['GRADE_PERCENT'] != NULl) {

                                if ($mps[$mp][1]['GRADE_PERCENT'] != '')
                                    $grades_RET[$i][$mp . '%'] = _makeLetterGrade($mps[$mp][1]['GRADE_PERCENT'] / 100, $course_period_id, $mps[$last_mp][1]['TEACHER_ID'], "%") . '%';
                            }
                            $last_mp = $mp;
                        }
                    }
                    if ($_REQUEST['elements']['period_absences'] == 'Y')
                        if ($mps[$last_mp][1]['DOES_ATTENDANCE'])
                            $grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'] . ' / ' . $mps[$last_mp][1]['MP_ABSENCES'];
                        else
                            $grades_RET[$i]['ABSENCES'] = 'n/a';
                    if ($_REQUEST['elements']['comments'] == 'Y') {
                        $sep = '';
                        foreach ($mps[$last_mp][1]['COMMENTS_RET'] as $comment) {
                            $grades_RET[$i]['COMMENT'] .= $sep . $comments_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
                            if ($comment['COMMENT'])
                                $grades_RET[$i]['COMMENT'] .= '(' . ($comment['COMMENT'] != ' ' ? $comment['COMMENT'] : '&middot;') . ')';
                            $sep = ', ';
                        }
                        if ($mps[$last_mp][1]['COMMENT_TITLE'])
                            $grades_RET[$i]['COMMENT'] .= $sep . $mps[$last_mp][1]['COMMENT_TITLE'];
                    }
                }
            }

            if (count($_REQUEST['mp_arr']) == 1) {
                $tmp_REQUEST = $_REQUEST;
                unset($tmp_REQUEST['modfunc']);
                $link['remove']['link'] = PreparePHP_SELF($tmp_REQUEST) . "&modfunc=delete";

                $link['remove']['variables'] = array('college_roll_no' => 'COLLEGE_ROLL_NO', 'course_period_id' => 'COURSE_PERIOD_ID', 'marking_period_id' => 'MARKING_PERIOD_ID');
            }
            echo '<div class="panel panel-default">';
            ListOutputGrade($grades_RET, $columns, 'Course', 'Courses', $link);
            echo '</div>';
        } else {

            if (User('PROFILE') == 'parent' || User('PROFILE') == 'student')
                ShowErr('No grade found.');
            else
                ShowErr('No Students were found.');
            for_error();
        }
    }
    else {

        ShowErr('You must choose at least one student and marking period');
        for_error();
    }
}


/*
 * Course Selection Modal Start
 */
if (!$_REQUEST['modfunc']) {
    echo '<div id="modal_default" class="modal fade">';
    echo '<div class="modal-dialog modal-lg">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<button type="button" class="close" data-dismiss="modal">×</button>';
    echo '<h4 class="modal-title">Choose course</h4>';
    echo '</div>';

    echo '<div class="modal-body">';
    echo '<div id="conf_div" class="text-center"></div>';
    echo '<div class="row" id="resp_table">';
    echo '<div class="col-md-4">';
    $sql = "SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE COLLEGE_ID='" . UserCollege() . "' AND SYEAR='" . UserSyear() . "' ORDER BY TITLE";
    $QI = DBQuery($sql);
    $subjects_RET = DBGet($QI);

    echo '<h6>' . count($subjects_RET) . ((count($subjects_RET) == 1) ? ' Subject was' : ' Subjects were') . ' found.</h6>';
    if (count($subjects_RET) > 0) {
        echo '<table class="table table-bordered"><thead><tr class="alpha-grey"><th>Subject</th></tr></thead>';
        foreach ($subjects_RET as $val) {
            echo '<tr><td><a href=javascript:void(0); onclick="chooseCpModalSearch(' . $val['SUBJECT_ID'] . ',\'courses\')">' . $val['TITLE'] . '</a></td></tr>';
        }
        echo '</table>';
    }
    echo '</div>';
    echo '<div class="col-md-4" id="course_modal"></div>';
    echo '<div class="col-md-4" id="cp_modal"></div>';
    echo '</div>'; //.row
    echo '</div>'; //.modal-body
    echo '</div>'; //.modal-content
    echo '</div>'; //.modal-dialog
    echo '</div>'; //.modal





    DrawBC("Gradebook > " . ProgramTitle());

    if ($_REQUEST['search_modfunc'] == 'list') {
        $_openSIS['allow_edit'] = true;

        echo "<FORM action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=save&include_inactive=" . strip_tags(trim($_REQUEST[include_inactive])) . " method=POST>";

        $attendance_codes = DBGet(DBQuery('SELECT SHORT_NAME,ID FROM attendance_codes WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND (DEFAULT_CODE!=\'Y\' OR DEFAULT_CODE IS NULL) AND TABLE_NAME=\'0\''));
        //PopTable_wo_header('header');
        $extra['extra_header_left'] = '<h5 class="text-primary no-margin-top">Include on Grade List:</h5>';
        $extra['extra_header_left'] .= '<div class="row">';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[teacher] value=Y CHECKED><span></span>Teacher</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[comments] value=Y CHECKED><span></span>Comments</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[percents] value=Y><span></span>Percents</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[ytd_absences] value=Y CHECKED><span></span>Year-to-date Daily Absences</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[mp_absences] value=Y' . (GetMP(UserMP(), 'SORT_ORDER') != 1 ? ' CHECKED' : '') . '><span></span>Daily Absences this quarter</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4 form-inline"><div class="form-group mb-15"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[ytd_tardies] value=Y><span></span> Other Attendance Year-to-date:</label></div> <SELECT name="ytd_tardies_code" class="form-control input-xs">';
        foreach ($attendance_codes as $code)
            $extra['extra_header_left'] .= '<OPTION value=' . $code['ID'] . '>' . $code['SHORT_NAME'] . '</OPTION>';
        $extra['extra_header_left'] .= '</SELECT></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4 form-inline"><div class="form-group mb-15"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[mp_tardies] value=Y><span></span>Other Attendance this quarter:</label></div> <SELECT name="mp_tardies_code" class="form-control input-xs">';
        foreach ($attendance_codes as $code)
            $extra['extra_header_left'] .= '<OPTION value=' . $code['ID'] . '>' . $code['SHORT_NAME'] . '</OPTION>';
        $extra['extra_header_left'] .= '</SELECT></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[period_absences] value=Y><span></span>Period-by-period absences</label></div></div></div>';
        $extra['extra_header_left'] .= '</div>';


        $mps_RET = DBGet(DBQuery('SELECT SEMESTER_ID,MARKING_PERIOD_ID,SHORT_NAME FROM college_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY SORT_ORDER'), array(), array('SEMESTER_ID'));

        $MP_TYPE = 'QTR';
        if (!$mps_RET) {
            $MP_TYPE = 'SEM';
            $mps_RET = DBGet(DBQuery('SELECT YEAR_ID,MARKING_PERIOD_ID,SHORT_NAME FROM college_semesters WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY SORT_ORDER'), array(), array('YEAR_ID'));
        }

        if (!$mps_RET) {
            $MP_TYPE = 'FY';
            $mps_RET = DBGet(DBQuery('SELECT MARKING_PERIOD_ID,SHORT_NAME FROM college_years WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY SORT_ORDER'), array(), array('MARKING_PERIOD_ID'));
        }

        $extra['extra_header_left'] .= '<h5 class="text-primary">Marking Periods</h5>';
        $extra['extra_header_left'] .= '<div class="form-inline">';
        foreach ($mps_RET as $sem => $quarters) {
            foreach ($quarters as $qtr) {
                $qtr1=$qtr['MARKING_PERIOD_ID'];
                $pro = GetChildrenMP('PRO', $qtr['MARKING_PERIOD_ID']);
                if ($pro) {
                    $pros = explode(',', str_replace("'", '', $pro));

                    foreach ($pros as $pro)
                        if (GetMP($pro, 'DOES_GRADES') == 'Y')
                            $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $pro . '>' . GetMP($pro, 'SHORT_NAME') . '</label></div>';
                }
                $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $qtr['MARKING_PERIOD_ID'] . ' ' . (($qtr['MARKING_PERIOD_ID'] == GetCurrentMP($MP_TYPE, DBDate())) ? 'checked' : '') . '>' . $qtr['SHORT_NAME'] . '</label></div>';
            
                if (GetMP($qtr1, 'DOES_EXAM') == 'Y')
                $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $qtr1 . '>' . GetMP($qtr1 ,'SHORT_NAME') . ' Exam</label></div>';
                }
            

            if (GetMP($sem, 'DOES_EXAM') == 'Y')
                if (GetMP($sem, 'DOES_EXAM') == 'Y')
                    $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $sem . '>' . GetMP($sem, 'SHORT_NAME') . ' Exam</label></div>';
            if (GetMP($sem, 'DOES_GRADES') == 'Y' && $sem != $quarters[1]['MARKING_PERIOD_ID'])
                $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $sem . '>' . GetMP($sem, 'SHORT_NAME') . '</label></div>';
        }
        if ($sem) {
            $fy = GetParentMP('FY', $sem);
            if (GetMP($fy, 'DOES_EXAM') == 'Y')
                $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $fy . '>' . GetMP($fy, 'SHORT_NAME') . ' Exam</label></div>';
            if (GetMP($fy, 'DOES_GRADES') == 'Y')
                $extra['extra_header_left'] .= '<div class="form-group"><label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $fy . '>' . GetMP($fy, 'SHORT_NAME') . '</label></div>';
        }
        $extra['extra_header_left'] .= '</div>';
    }

    $extra['link'] = array('FULL_NAME' => false);
    $extra['SELECT'] = ",s.COLLEGE_ROLL_NO AS CHECKBOX";
    $extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
     $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
   // $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'unused\');"><A>');
  //  $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAllDtMod(this,\'st_arr\');"><A>');
    $extra['new'] = true;
    $extra['options']['search'] = false;
    $extra['force_search'] = true;

    $extra['search'] .= '<div class="row">';
    $extra['search'] .= '<div class="col-lg-6">';
    Widgets('course');
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '</div>'; //.row


    $extra['search'] .= '<div class="row">';
    $extra['search'] .= '<div class="col-lg-6">';
    $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
    $extra['search'] .= '<div class="pl-10">';
    Widgets('gpa');
    $extra['search'] .= '</div>';
    $extra['search'] .= '</div>'; //.well
    $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
    Widgets('class_rank');
    $extra['search'] .= '</div>'; //.well
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '<div class="col-lg-6">';
    $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
    Widgets('letter_grade');
    $extra['search'] .= '</div>'; //.well
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '</div>'; //.row

    if ($_REQUEST['search_modfunc'] == 'list') {
        //if ($_SESSION['count_stu'] != 0) {
            $extra['footer'] = '<div class="panel-footer text-right p-r-20">' . SubmitButtonModal('Create Grade Lists for Selected Students', '', 'class="btn btn-primary"') . '</div>';
        //}
    }
    Search('college_roll_no', $extra, 'true');

    if ($_REQUEST['search_modfunc'] == 'list') {
        
        if ($_SESSION['count_stu'] != 0) {
            unset($_SESSION['count_stu']);
        }
        echo "</FORM>";
    }
}

//function _makeChooseCheckbox($value, $title) {
//    global $THIS_RET;
////    return '<INPUT type=checkbox name=st_arr[] value=' . $value . ' checked>';
//    
//    return "<input name=unused[$THIS_RET[COLLEGE_ROLL_NO]] value=" . $THIS_RET[COLLEGE_ROLL_NO] . "  type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckboxStudents(\"st_arr[]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";
//}

function _makeChooseCheckbox($value, $title) {
    global $THIS_RET;
    return '<INPUT type=checkbox name=st_arr[] value=' . $value . '>';
   // return "<input name=unused_var[$THIS_RET[COLLEGE_ROLL_NO]] value=" . $THIS_RET[COLLEGE_ROLL_NO] . "  type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckboxStudents(\"st_arr[$THIS_RET[COLLEGE_ROLL_NO]]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";
}
function _makeTeacher($teacher, $column) {
    return substr($teacher, strrpos(str_replace(' - ', ' ^ ', $teacher), '^') + 2);
}

function _makeComments($value, $column) {
    global $THIS_RET;

    return DBGet(DBQuery('SELECT COURSE_PERIOD_ID,REPORT_CARD_COMMENT_ID,COMMENT,(SELECT SORT_ORDER FROM report_card_comments WHERE REPORT_CARD_COMMENT_ID=ID) AS SORT_ORDER FROM student_report_card_comments WHERE COLLEGE_ROLL_NO=\'' . $THIS_RET['COLLEGE_ROLL_NO'] . '\' AND COURSE_PERIOD_ID=\'' . $THIS_RET['COURSE_PERIOD_ID'] . '\' AND MARKING_PERIOD_ID=\'' . $value . '\' ORDER BY SORT_ORDER'));
}

?>