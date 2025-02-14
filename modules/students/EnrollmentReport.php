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
include 'modules/grades/ConfigInc.php';
if($_REQUEST['modfunc']=='save')
{
 $cur_session_RET=DBGet(DBQuery('SELECT YEAR(start_date) AS PRE,YEAR(end_date) AS POST FROM college_years WHERE COLLEGE_ID=\''.UserCollege().'\' AND SYEAR=\''.UserSyear().'\''));
 if($cur_session_RET[1]['PRE']==$cur_session_RET[1]['POST'])
 {
    $cur_session=$cur_session_RET[1]['PRE'];
 }
 else
 {
    $cur_session=$cur_session_RET[1]['PRE'].'-'.$cur_session_RET[1]['POST'];
 }
	if(count($_REQUEST['st_arr']))
	{	
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
        $RET=DBGet(DBQuery('SELECT CONCAT(s.LAST_NAME,\''.', ' .'\',coalesce(s.COMMON_NAME,s.FIRST_NAME)) AS FULL_NAME,s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.COLLEGE_ROLL_NO,s.PHONE,ssm.COLLEGE_ID,s.ALT_ID,ssm.COLLEGE_ID AS LIST_COLLEGE_ID,ssm.GRADE_ID,ssm.START_DATE,ssm.END_DATE,
                (SELECT sec.title FROM  student_enrollment_codes sec where ssm.enrollment_code=sec.id)AS ENROLLMENT_CODE,
                (SELECT sec.title FROM  student_enrollment_codes sec where ssm.drop_code=sec.id) AS DROP_CODE,ssm.COLLEGE_ID 
                FROM  students s , student_enrollment ssm
                WHERE ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND s.COLLEGE_ROLL_NO IN ('.$st_list.')  
                ORDER BY FULL_NAME ASC,START_DATE DESC'),array('START_DATE'=>'ProperDate','END_DATE'=>'ProperDate','COLLEGE_ID'=>'GetCollege','GRADE_ID'=>'GetGrade'),array('COLLEGE_ROLL_NO'));
        if(count($RET))
	{
            $columns = array('START_DATE'=>'Start Date','ENROLLMENT_CODE'=>'Enrollment Code','END_DATE'=>'Drop Date','DROP_CODE'=>'Drop Code','COLLEGE_ID'=>'College Name');
		$handle = PDFStart();
		foreach($RET as $college_roll_no=>$value)
		{
			echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
			echo "<tr><td width=105>".DrawLogo()."</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">". GetCollege(UserCollege()).' ('.$cur_session.')'."<div style=\"font-size:12px;\">Student Enrollment Report</div></td><td align=right style=\"padding-top:20px\">". ProperDate(DBDate()) ."<br \>Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
			echo '<!-- MEDIA SIZE 8.5x11in -->';
			
				unset($_openSIS['DrawHeader']);
                            unset($enroll_RET);
                            $i = 0;
                            foreach($value as $key=>$enrollment)
                            {
				
                                $i++;
                                $enroll_RET[$i]['START_DATE'] = ($enrollment['START_DATE']?$enrollment['START_DATE']:'--');
                                $enroll_RET[$i]['ENROLLMENT_CODE'] = ($enrollment['ENROLLMENT_CODE']?$enrollment['ENROLLMENT_CODE']:'--');
                                $enroll_RET[$i]['END_DATE'] = ($enrollment['END_DATE']?$enrollment['END_DATE']:'--');
                                $enroll_RET[$i]['DROP_CODE'] = ($enrollment['DROP_CODE']?$enrollment['DROP_CODE']:'--');
                                $enroll_RET[$i]['COLLEGE_ID'] = ($enrollment['COLLEGE_ID']?$enrollment['COLLEGE_ID']:'--');
                            }
				echo '<table border=0>';
				echo '<tr><td>Student Name :</td>';
				echo '<td>'.$enrollment['FULL_NAME'].'</td></tr>';
				echo '<tr><td>College Roll No :</td>';
				echo '<td>'.$college_roll_no.'</td></tr>';
                                echo '<tr><td>Alternate ID :</td>';
				echo '<td>'.$enrollment['ALT_ID'].'</td></tr>';
				echo '<tr><td>Student Grade :</td>';
                                $grade=DBGet(DBQuery('SELECT GRADE_ID FROM student_enrollment WHERE SYEAR='.UserSyear().' AND COLLEGE_ID='.UserCollege().' AND COLLEGE_ROLL_NO='.$college_roll_no.' AND (END_DATE>=\''.date('Y-m-d').'\' OR END_DATE IS NULL OR END_DATE=\'0000-00-00\')  '),array('GRADE_ID'=>'GetGrade'));
				echo '<td>'.$grade[1]['GRADE_ID'].'</td></tr>';
				echo '</table>';
                            
                            ListOutputPrint($enroll_RET,$columns,'','',array(),array(),array('print'=>false));
                echo '<span style="font-size:13px; font-weight:bold;"></span>';
				echo '<!-- NEW PAGE -->';
				echo "<div style=\"page-break-before: always;\"></div>";
		}
		PDFStop($handle);
            }
        }
	else
		BackPrompt('You must choose at least one student.');
}

if(!$_REQUEST['modfunc'])
{
	DrawBC("Student > ".ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo "<FORM action=ForExport.php?modname=$_REQUEST[modname]&modfunc=save&include_inactive=$_REQUEST[include_inactive]&_openSIS_PDF=true&head_html=Student+Report+Card method=POST target=_blank>";
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.COLLEGE_ROLL_NO AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	// $extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'unused\');"><A>');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type=checkbox value=Y name=controller onclick="checkAllDtMod(this,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	

	Search('college_roll_no',$extra,'true');
	if($_REQUEST['search_modfunc']=='list')
	{
		if($_SESSION['count_stu']!=0)
		echo '<div class="text-right p-b-20 p-r-20"><INPUT type=submit class="btn btn-primary" value=\'Create Enrollment Report for Selected Students\'></div>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
//	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
    //     global $THIS_RET;
    // return "<input name=unused[$THIS_RET[COLLEGE_ROLL_NO]] value=" . $THIS_RET[COLLEGE_ROLL_NO] . "  type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckboxStudents(\"st_arr[]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";
	global $THIS_RET;
	//	return '<INPUT type=checkbox name=st_arr[] value='.$value.' checked>';
			
			return "<input  class=fd name=unused_var[$THIS_RET[COLLEGE_ROLL_NO]] value=" . $THIS_RET[COLLEGE_ROLL_NO] . " type='checkbox' id=$THIS_RET[COLLEGE_ROLL_NO] onClick='setHiddenCheckboxStudents(\"st_arr[$THIS_RET[COLLEGE_ROLL_NO]]\",this,$THIS_RET[COLLEGE_ROLL_NO]);' />";
		
}

function _makeTeacher($teacher,$column)
{
	return substr($teacher,strrpos(str_replace(' - ',' ^ ',$teacher),'^')+2);
}
?>