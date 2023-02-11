<?php
namespace App\Http\Controllers;

class reportsController extends Controller {

	var $data = array();
	var $panelInit ;
	var $layout = 'dashboard';
	var $export_override;

	public function __construct(){
		if(app('request')->header('Authorization') != "" || \Input::has('token')){
			$this->middleware('jwt.auth');
		}else{
			$this->middleware('authApplication');
		}

		$this->panelInit = new \DashboardInit();
		$this->data['panelInit'] = $this->panelInit;
		$this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');
		$this->data['users'] = $this->panelInit->getAuthUser();
		if(!isset($this->data['users']->id)){
			return \Redirect::to('/');
		}

		if(!$this->panelInit->can( array("Reports.Reports") )){
			exit;
		}
	}

	public function report(){

		if(\Input::has('export')){
			$this->export_override = \Input::get('export');
		}

		if(\Input::get('stats') == 'usersStats'){
            return $this->usersStats();
        }
        if(\Input::get('stats') == 'stdAttendance' ){
            return $this->stdAttendance(\Input::get('data'));
        }
        if(\Input::get('stats') == 'stfAttendance' ){
            return $this->stfAttendance(\Input::get('data'));
        }
		if(\Input::get('stats') == 'stdVacation' ){
            return $this->stdVacation(\Input::get('data'));
        }
		if(\Input::get('stats') == 'stfVacation' ){
            return $this->stfVacation(\Input::get('data'));
        }
		if(\Input::get('stats') == 'payments'  ){
            return $this->reports(\Input::get('data'));
        }
        if(\Input::get('stats') == 'expenses'  ){
            return $this->expenses(\Input::get('data'));
        }
        if(\Input::get('stats') == 'income'  ){
            return $this->income(\Input::get('data'));
        }
		if(\Input::get('stats') == 'marksheetGenerationPrepare' ){
            return $this->marksheetGenerationPrepare();
        }
        if(\Input::get('stats') == 'biometric_users' ){
            return $this->biometric_users_generate();
        }
        if(\Input::get('stats') == 'payroll' ){
			return $this->payroll_payments(\Input::get('data'));
        }
        if(\Input::get('stats') == 'certPrint' ){
            return $this->certPrint(\Input::get('data'));
		}
		if(\Input::get('stats') == 'cardPrint'){
            return $this->cardPrint(\Input::get('data'));
        }
		if(\Input::get('stats') == 'collection'){
            return $this->collection(\Input::get('data'));
		}
		if(\Input::get('stats') == 'invoiceGeneration'){
            return $this->invoiceGeneration(\Input::get('data'));
		}

	}

    public function usersStats(){
        $toReturn = array();
        $toReturn['admins'] = array();
        $toReturn['admins']['activated'] = \User::where('role','admin')->where('activated','1')->count();
        $toReturn['admins']['inactivated'] = \User::where('role','admin')->where('activated','0')->count();
        $toReturn['admins']['total'] = $toReturn['admins']['activated'] + $toReturn['admins']['inactivated'];

        $toReturn['teachers'] = array();
        $toReturn['teachers']['activated'] = \User::where('role','teacher')->where('activated','1')->count();
        $toReturn['teachers']['inactivated'] = \User::where('role','teacher')->where('activated','0')->count();
        $toReturn['teachers']['total'] = $toReturn['teachers']['activated'] + $toReturn['teachers']['inactivated'];

        $toReturn['students'] = array();
        $toReturn['students']['activated'] = \User::where('role','student')->where('activated','1')->count();
        $toReturn['students']['inactivated'] = \User::where('role','student')->where('activated','0')->count();
        $toReturn['students']['total'] = $toReturn['students']['activated'] + $toReturn['students']['inactivated'];

        $toReturn['parents'] = array();
        $toReturn['parents']['activated'] = \User::where('role','parent')->where('activated','1')->count();
        $toReturn['parents']['inactivated'] = \User::where('role','parent')->where('activated','0')->count();
        $toReturn['parents']['total'] = $toReturn['parents']['activated'] + $toReturn['parents']['inactivated'];

        $toReturn['employee'] = array();
        $toReturn['employee']['activated'] = \User::where('role','employee')->where('activated','1')->count();
        $toReturn['employee']['inactivated'] = \User::where('role','employee')->where('activated','0')->count();
        $toReturn['employee']['total'] = $toReturn['employee']['activated'] + $toReturn['employee']['inactivated'];

        return $toReturn;
    }

    public function preAttendaceStats(){
        $toReturn = array();
		$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get();
		$toReturn['classes'] = array();
		$subjList = array();
		foreach ($classes as $class) {
			$class['classSubjects'] = json_decode($class['classSubjects'],true);
			if(is_array($class['classSubjects'])){
				foreach ($class['classSubjects'] as $subject) {
					$subjList[] = $subject;
				}
			}
			$toReturn['classes'][$class->id] = $class->className ;
		}

		$subjList = array_unique($subjList);
		if($this->data['panelInit']->settingsArray['attendanceModel'] == "subject"){
			$toReturn['subjects'] = array();
			if(count($subjList) > 0){
				$subjects = \subject::whereIN('id',$subjList)->get();
				foreach ($subjects as $subject) {
					$toReturn['subjects'][$subject->id] = $subject->subjectTitle ;
				}
			}
		}

		$toReturn['role'] = $this->data['users']->role;
		$toReturn['attendanceModel'] = $this->data['panelInit']->settingsArray['attendanceModel'];

        return $toReturn;
    }

    public function stdAttendance($data){
		$students = array();
		$studentArray = \User::where('role','student');
		if(isset($data['classId']) AND $data['classId'] != "" ){
			$studentArray = $studentArray->where('studentClass',$data['classId']);
		}
		if(isset($data['sectionId']) AND $data['sectionId'] != "" ){
			$studentArray = $studentArray->where('studentSection',$data['sectionId']);
		}
		if($this->data['panelInit']->settingsArray['studentsSort'] != ""){
			$studentArray = $studentArray->orderByRaw($this->data['panelInit']->settingsArray['studentsSort']);
		}
		$studentArray = $studentArray->get();

		$subjectsArray = \subject::get();
		$subjects = array();
		foreach ($subjectsArray as $subject) {
			$subjects[$subject->id] = $subject->subjectTitle ;
		}

		$sql = "select * from attendance where ";
		$sqlArray = array();
		$toReturn = array();

		if(isset($data['classId']) AND $data['classId'] != "" ){
			$sqlArray[] = "classId='".$data['classId']."'";
		}
		if($this->data['panelInit']->settingsArray['attendanceModel'] == "subject" AND isset($data['subjectId']) AND $data['subjectId'] != ""){
			$sqlArray[] = "subjectId='".$data['subjectId']."'";
		}
		if(isset($data['status']) AND $data['status'] != "All"){
			$sqlArray[] = "status='".$data['status']."'";
		}

		if(isset($data['attendanceDayFrom']) AND $data['attendanceDayFrom'] != "" AND isset($data['attendanceDayTo']) AND $data['attendanceDayTo'] != ""){
			$data['attendanceDayFrom'] = $this->panelInit->date_to_unix($data['attendanceDayFrom']);
			$data['attendanceDayTo'] = $this->panelInit->date_to_unix($data['attendanceDayTo']);
			$sqlArray[] = "date >= '".$data['attendanceDayFrom']."'";
			$sqlArray[] = "date <= '".$data['attendanceDayTo']."'";
		}

		$sql = $sql . implode(" AND ", $sqlArray);
		$sql = $sql . " order by date";
		$attendanceArray = \DB::select( \DB::raw($sql) ); 
		$attendanceList = array();

		foreach ($attendanceArray as $stAttendance) {
			$attendanceList[$stAttendance->studentId][] = $stAttendance;
		}

		$i = 0;
		foreach ($studentArray as $stOne) {
			if(isset($attendanceList[ $stOne->id ])){
				
				foreach($attendanceList[ $stOne->id ] as $value){				
					$toReturn[$i] = $value;
					$toReturn[$i]->studentName = $stOne->fullName;
					if($value->subjectId != "" and isset($subjects[$value->subjectId])){
						$toReturn[$i]->studentSubject = $subjects[$value->subjectId];
					}
					$toReturn[$i]->date = $this->panelInit->unix_to_date($value->date);
					$toReturn[$i]->studentRollId = $stOne->studentRollId;
					$i ++;
				}
			}
		}

		if(isset($data['exportType']) AND $data['exportType'] == "excel"){
			$data = array(1 => array ('Date','Roll Id', 'Full Name','Subject','Status'));

			foreach ($toReturn as $value) {
				if($value->status == 0){
					$value->status = $this->panelInit->language['Absent'];
				}elseif ($value->status == 1) {
					$value->status = $this->panelInit->language['Present'];
				}elseif ($value->status == 2) {
					$value->status = $this->panelInit->language['Late'];
				}elseif ($value->status == 3) {
					$value->status = $this->panelInit->language['LateExecuse'];
				}elseif ($value->status == 4) {
					$value->status = $this->panelInit->language['earlyDismissal'];
				}
				$data[] = array ($value->date, (isset($value->studentRollId)?$value->studentRollId:""),(isset($value->studentName)?$value->studentName:""),(isset($value->studentSubject)?$value->studentSubject:""),$value->status);
			}

			\Excel::create('Students-Atendance', function($excel) use($data) {

			    // Set the title
			    $excel->setTitle('Students Atendance Report');

			    // Chain the setters
			    $excel->setCreator('OraSchool')->setCompany('SolutionsBricks');

				$excel->sheet('Students Atendance', function($sheet) use($data) {
					$sheet->freezeFirstRow();
					$sheet->fromArray($data, null, 'A1', true,false);
				});

			})->download('xls');
		}

		if(isset($data['exportType']) AND $data['exportType'] == "pdf"){
			$header = array ('Date','Roll Id', 'Full Name','Subject','Status');
			$data = array();
			foreach ($toReturn as $value) {
				if($value->status == 0){
					$value->status = $this->panelInit->language['Absent'];
				}elseif ($value->status == 1) {
					$value->status = $this->panelInit->language['Present'];
				}elseif ($value->status == 2) {
					$value->status = $this->panelInit->language['Late'];
				}elseif ($value->status == 3) {
					$value->status = $this->panelInit->language['LateExecuse'];
				}elseif ($value->status == 4) {
					$value->status = $this->panelInit->language['earlyDismissal'];
				}
				$data[] = array ( $value->date, (isset($value->studentRollId)?$value->studentRollId:""),(isset($value->studentName)?$value->studentName:""),(isset($value->studentSubject)?$value->studentSubject:""),$value->status);
			}

			$doc_details = array(
								"title" => "Attendance",
								"author" => $this->data['panelInit']->settingsArray['siteTitle'],
								"topMarginValue" => 10
								);

			if( $this->panelInit->isRTL == "1" ){
				$doc_details['is_rtl'] = true;
			}

			$pdfbuilder = new \PdfBuilder($doc_details);

			$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
		        <thead><tr>";
				foreach ($header as $value) {
					$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";
				}
			$content .="</tr></thead><tbody>";

			foreach($data as $row)
			{
				$content .= "<tr>";
				foreach($row as $col){
					$content .="<td>".$col."</td>";
				}
				$content .= "</tr>";
			}

	        $content .= "</tbody></table>";

			$pdfbuilder->table($content, array('border' => '0','align'=>'') );
			$pdfbuilder->output('Attendance.pdf');

			exit;
		}

		return $toReturn;
    }

    public function stfAttendance($data){
        $sql = "select * from attendance where ";
		$sqlArray = array();
		$toReturn = array();

		$teachers = array();
		$teachersArray = \User::where('role','teacher');

		if($this->data['panelInit']->settingsArray['teachersSort'] != ""){
			$teachersArray = $teachersArray->orderByRaw($this->data['panelInit']->settingsArray['teachersSort']);
		}

		$teachersArray = $teachersArray->get();

		if(isset($data['status']) AND $data['status'] != "All"){
			$sqlArray[] = "status='".$data['status']."'";
		}

		if(isset($data['attendanceDayFrom']) AND $data['attendanceDayFrom'] != "" AND isset($data['attendanceDayTo']) AND $data['attendanceDayTo'] != ""){
			$data['attendanceDayFrom'] = $this->panelInit->date_to_unix($data['attendanceDayFrom']);
			$data['attendanceDayTo'] = $this->panelInit->date_to_unix($data['attendanceDayTo']);
			$sqlArray[] = "date >= (".$data['attendanceDayFrom'].") AND date <= (".$data['attendanceDayTo'].") ";
		}

        $sqlArray[] = "classId = '0'";

		$sql = $sql . implode(" AND ", $sqlArray);
		$sql = $sql ." order by date asc";
		$attendanceArray = \DB::select( \DB::raw($sql) );
		$attendanceList = array();

		foreach ($attendanceArray as $stAttendance) {
			$attendanceList[$stAttendance->studentId][] = $stAttendance;
		}

		$i = 0;
		foreach ($teachersArray as $stOne) {
			if(isset($attendanceList[$stOne->id])){
				foreach($attendanceList[$stOne->id] as $value){
					$toReturn[$i] = $value;
					$toReturn[$i]->date = $this->panelInit->unix_to_date($value->date);
					$toReturn[$i]->studentName = $stOne->fullName;
					$i ++;
				}
			}
		}

		if(isset($data['exportType']) AND $data['exportType'] == "excel"){
			$data = array(1 => array ('Date', 'Full Name','Status'));
			foreach ($toReturn as $value) {
				if($value->status == 0){
					$value->status = $this->panelInit->language['Absent'];
				}elseif ($value->status == 1) {
					$value->status = $this->panelInit->language['Present'];
				}elseif ($value->status == 2) {
					$value->status = $this->panelInit->language['Late'];
				}elseif ($value->status == 3) {
					$value->status = $this->panelInit->language['LateExecuse'];
				}
				$data[] = array ( $value->date , $value->studentName,$value->status);
			}

			\Excel::create('Staff-Atendance', function($excel) use($data) {

			    // Set the title
			    $excel->setTitle('Staff Atendance Report');

			    // Chain the setters
			    $excel->setCreator('OraSchool')->setCompany('SolutionsBricks');

				$excel->sheet('Staff Atendance', function($sheet) use($data) {
					$sheet->freezeFirstRow();
					$sheet->fromArray($data, null, 'A1', true,false);
				});

			})->download('xls');
		}

		if(isset($data['exportType']) AND $data['exportType'] == "pdf"){
			$header = array ('Date', 'Full Name','Status');
			$data = array();
			foreach ($toReturn as $value) {
				if($value->status == 0){
					$value->status = $this->panelInit->language['Absent'];
				}elseif ($value->status == 1) {
					$value->status = $this->panelInit->language['Present'];
				}elseif ($value->status == 2) {
					$value->status = $this->panelInit->language['Late'];
				}elseif ($value->status == 3) {
					$value->status = $this->panelInit->language['LateExecuse'];
				}
				$data[] = array ( $value->date , $value->studentName,$value->status);
			}

			$doc_details = array(
								"title" => "Attendance",
								"author" => $this->data['panelInit']->settingsArray['siteTitle'],
								"topMarginValue" => 10
								);

			if( $this->panelInit->isRTL == "1" ){
				$doc_details['is_rtl'] = true;
			}

			$pdfbuilder = new \PdfBuilder($doc_details);

			$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
				<thead><tr>";
				foreach ($header as $value) {
					$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";
				}
			$content .="</tr></thead><tbody>";

			foreach($data as $row)
			{
				$content .= "<tr>";
				foreach($row as $col){
					$content .="<td>".$col."</td>";
				}
				$content .= "</tr>";
			}

			$content .= "</tbody></table>";

			$pdfbuilder->table($content, array('border' => '0','align'=>'') );
			$pdfbuilder->output('Attendance.pdf');

			exit;
		}

		return $toReturn;
    }

	public function stdVacation($data){
		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$vacationList = \DB::table('vacation')
					->leftJoin('users', 'users.id', '=', 'vacation.userid')
					->select('vacation.id as id',
					'vacation.userid as userid',
					'vacation.vacDate as vacDate',
					'vacation.acceptedVacation as acceptedVacation',
					'users.fullName as fullName')
					->where('vacation.acYear',$this->panelInit->selectAcYear)
					->where('vacation.role','student')
					->where('vacation.vacDate','>=',$data['fromDate'])
					->where('vacation.vacDate','<=',$data['toDate'])
					->get();

		foreach ($vacationList as $key=>$value) {
			$vacationList[$key]->vacDate = $this->panelInit->unix_to_date($vacationList[$key]->vacDate);
		}

		return $vacationList;
	}

	public function stfVacation($data){
		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$vacationList = \DB::table('vacation')
					->leftJoin('users', 'users.id', '=', 'vacation.userid')
					->select('vacation.id as id',
					'vacation.userid as userid',
					'vacation.vacDate as vacDate',
					'vacation.acceptedVacation as acceptedVacation',
					'users.fullName as fullName')
					->where('vacation.acYear',$this->panelInit->selectAcYear)
					->where('vacation.role','teacher')
					->where('vacation.vacDate','>=',$data['fromDate'])
					->where('vacation.vacDate','<=',$data['toDate'])
					->get();

		foreach ($vacationList as $key=>$value) {
			$vacationList[$key]->vacDate = $this->panelInit->unix_to_date($vacationList[$key]->vacDate);
		}

		return $vacationList;

	}

	public function reports($data){

		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$payments = \DB::table('payments')
					->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')
					->where('payments.paymentDate','>=',$data['fromDate'])
					->where('payments.paymentDate','<=',$data['toDate'])
					->select('payments.id as id',
					'payments.paymentTitle as paymentTitle',
					'payments.paymentDescription as paymentDescription',
					'payments.paymentAmount as paymentAmount',
					'payments.paymentDiscounted as paymentDiscounted',
					'payments.paidAmount as paidAmount',
					'payments.paymentStatus as paymentStatus',
					'payments.paymentDate as paymentDate',
					'payments.dueDate as dueDate',
					'payments.paymentStudent as studentId',
					'users.fullName as fullName');

		if(isset($data['payment_status']) AND $data['payment_status'] != "All"){
			$payments = $payments->where('paymentStatus',$data['payment_status']);
		}
		if(isset($data['dueInv']) AND $data['dueInv'] == true){
			$payments = $payments->where('dueDate','<',time())->where('paymentStatus','!=','1');
		}
		$payments = $payments->orderBy('id','DESC')->get();

		foreach ($payments as $key=>$value) {
			$payments[$key]->paymentDate = $this->panelInit->unix_to_date($payments[$key]->paymentDate);
			$payments[$key]->dueDate = $this->panelInit->unix_to_date($payments[$key]->dueDate);
			$payments[$key]->paymentAmount = $payments[$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$payments[$key]->paymentAmount) /100;
			$payments[$key]->paymentDiscounted = $payments[$key]->paymentDiscounted + ($this->panelInit->settingsArray['paymentTax']*$payments[$key]->paymentDiscounted) /100;
		}

		return $payments;
	}

	public function expenses($data){

		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$expenses = \DB::table('expenses')
					->leftJoin('expenses_cat','expenses_cat.id','=','expenses.expenseCategory')
					->where('expenses.expenseDate','>=',$data['fromDate'])
					->where('expenses.expenseDate','<=',$data['toDate'])
					->select('expenses.id as id',
					'expenses.expenseTitle as expenseTitle',
					'expenses.expenseAmount as expenseAmount',
					'expenses.expenseDate as expenseDate',
					'expenses.expenseCategory as expenseCategory',
					'expenses.expenseNotes as expenseNotes',
					'expenses_cat.cat_title as expenses_cat_name');

		$expenses = $expenses->orderBy('id','DESC')->get();

		if($this->export_override == ""){
		
			foreach ($expenses as $key=>$value) {
				$expenses[$key]->expenseDate = $this->panelInit->unix_to_date($expenses[$key]->expenseDate);
			}
			
			return $expenses;

		}else{
			
			if($this->export_override == "excel"){
				$data = array(1 => array ($this->panelInit->language['expenseTitle'],$this->panelInit->language['Category'],$this->panelInit->language['expenseAmount'],$this->panelInit->language['Date'],$this->panelInit->language['notes']));
	
				foreach ($expenses as $key=>$value) {
					$expenses[$key]->expenseDate = $this->panelInit->unix_to_date($expenses[$key]->expenseDate);

					$data[ $key + 2 ] = array();
					$data[ $key + 2 ][] = $expenses[$key]->expenseTitle;
					$data[ $key + 2 ][] = $expenses[$key]->expenses_cat_name; 
					$data[ $key + 2 ][] = $this->data['panelInit']->settingsArray['currency_symbol'].$expenses[$key]->expenseAmount; 
					$data[ $key + 2 ][] = $expenses[$key]->expenseDate; 
					$data[ $key + 2 ][] = $expenses[$key]->expenseNotes; 
				
				}
	
				\Excel::create('Expenses-Reports', function($excel) use($data) {
	
					// Set the title
					$excel->setTitle('Expenses Reports');
	
					// Chain the setters
					$excel->setCreator('OraSchool')->setCompany('SolutionsBricks');
	
					$excel->sheet('Expenses Reports', function($sheet) use($data) {
						$sheet->freezeFirstRow();
						$sheet->fromArray($data, null, 'A1', true,false);
					});
	
				})->download('xls');
			}
	
			if($this->export_override == "pdf"){
				$header = array ($this->panelInit->language['expenseTitle'],$this->panelInit->language['Category'],$this->panelInit->language['expenseAmount'],$this->panelInit->language['Date'],$this->panelInit->language['notes']);

				$data = array();
				foreach ($expenses as $key=>$value) {
					$expenses[$key]->expenseDate = $this->panelInit->unix_to_date($expenses[$key]->expenseDate);

					$data[ $key ] = array();
					$data[ $key ][] = $expenses[$key]->expenseTitle;
					$data[ $key ][] = $expenses[$key]->expenses_cat_name; 
					$data[ $key ][] = $this->data['panelInit']->settingsArray['currency_symbol'].$expenses[$key]->expenseAmount; 
					$data[ $key ][] = $expenses[$key]->expenseDate; 
					$data[ $key ][] = $expenses[$key]->expenseNotes; 
				
				}
	
				$doc_details = array(
									"title" => "Expenses Reports",
									"author" => $this->data['panelInit']->settingsArray['siteTitle'],
									"topMarginValue" => 10
									);
	
				if( $this->panelInit->isRTL == "1" ){
					$doc_details['is_rtl'] = true;
				}
	
				$pdfbuilder = new \PdfBuilder($doc_details);
	
				$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
					<thead><tr>";
					foreach ($header as $value) {
						$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";
					}
				$content .="</tr></thead><tbody>";
	
				foreach($data as $row)
				{
					$content .= "<tr>";
					foreach($row as $col){
						$content .="<td>".$col."</td>";
					}
					$content .= "</tr>";
				}
	
				$content .= "</tbody></table>";
	
				$pdfbuilder->table($content, array('border' => '0','align'=>'') );
				$pdfbuilder->output('Expenses-Reports.pdf');
	
				exit;
			}
		}
	}

	public function income($data){

		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$incomes = \DB::table('income')
					->leftJoin('income_cat','income_cat.id','=','income.incomeCategory')
					->where('income.incomeDate','>=',$data['fromDate'])
					->where('income.incomeDate','<=',$data['toDate'])
					->select('income.id as id',
					'income.incomeTitle as incomeTitle',
					'income.incomeAmount as incomeAmount',
					'income.incomeDate as incomeDate',
					'income.incomeCategory as incomeCategory',
					'income.incomeNotes as incomeNotes',
					'income_cat.cat_title as income_cat_name');

		$incomes = $incomes->orderBy('id','DESC')->get();

		if($this->export_override == ""){
		
			foreach ($incomes as $key=>$income) {
				$incomes[$key]->incomeDate = $this->panelInit->unix_to_date($incomes[$key]->incomeDate);
			}
	
			return $incomes;

		}else{
			
			if($this->export_override == "excel"){
				$data = array(1 => array ($this->panelInit->language['incomeTitle'],$this->panelInit->language['Category'],$this->panelInit->language['incomeAmount'],$this->panelInit->language['Date'],$this->panelInit->language['notes']));
	
				foreach ($incomes as $key=>$income) {
					$incomes[$key]->incomeDate = $this->panelInit->unix_to_date($incomes[$key]->incomeDate);

					$data[ $key + 2 ] = array();
					$data[ $key + 2 ][] = $incomes[$key]->incomeTitle;
					$data[ $key + 2 ][] = $incomes[$key]->income_cat_name; 
					$data[ $key + 2 ][] = $this->data['panelInit']->settingsArray['currency_symbol'].$incomes[$key]->incomeAmount; 
					$data[ $key + 2 ][] = $incomes[$key]->incomeDate; 
					$data[ $key + 2 ][] = $incomes[$key]->incomeNotes; 
				
				}
	
				\Excel::create('Income-Reports', function($excel) use($data) {
	
					// Set the title
					$excel->setTitle('Income Reports');
	
					// Chain the setters
					$excel->setCreator('OraSchool')->setCompany('SolutionsBricks');
	
					$excel->sheet('Income Reports', function($sheet) use($data) {
						$sheet->freezeFirstRow();
						$sheet->fromArray($data, null, 'A1', true,false);
					});
	
				})->download('xls');
			}
	
			if($this->export_override == "pdf"){
				$header = array ($this->panelInit->language['incomeTitle'],$this->panelInit->language['Category'],$this->panelInit->language['incomeAmount'],$this->panelInit->language['Date'],$this->panelInit->language['notes']);

				$data = array();
				foreach ($incomes as $key=>$income) {
					$incomes[$key]->incomeDate = $this->panelInit->unix_to_date($incomes[$key]->incomeDate);

					$data[ $key ] = array();
					$data[ $key ][] = $incomes[$key]->incomeTitle;
					$data[ $key ][] = $incomes[$key]->income_cat_name; 
					$data[ $key ][] = $this->data['panelInit']->settingsArray['currency_symbol'].$incomes[$key]->incomeAmount; 
					$data[ $key ][] = $incomes[$key]->incomeDate; 
					$data[ $key ][] = $incomes[$key]->incomeNotes; 
				
				}
	
				$doc_details = array(
									"title" => "Income Reports",
									"author" => $this->data['panelInit']->settingsArray['siteTitle'],
									"topMarginValue" => 10
									);
	
				if( $this->panelInit->isRTL == "1" ){
					$doc_details['is_rtl'] = true;
				}
	
				$pdfbuilder = new \PdfBuilder($doc_details);
	
				$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
					<thead><tr>";
					foreach ($header as $value) {
						$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";
					}
				$content .="</tr></thead><tbody>";
	
				foreach($data as $row)
				{
					$content .= "<tr>";
					foreach($row as $col){
						$content .="<td>".$col."</td>";
					}
					$content .= "</tr>";
				}
	
				$content .= "</tbody></table>";
	
				$pdfbuilder->table($content, array('border' => '0','align'=>'') );
				$pdfbuilder->output('Income-Reports.pdf');
	
				exit;
			}
		}
	}

	public function marksheetGenerationPrepare(){
		$toReturn = array();
		$toReturn['classes'] = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get()->toArray();
		$toReturn['exams'] = \exams_list::where('examAcYear',$this->panelInit->selectAcYear)->get()->toArray();
		return $toReturn;
	}

	public function biometric_users_generate(){
		return $users = \User::where('biometric_id','!=','0')->select('id','username','fullName','role','email','biometric_id')->get();
	}

	public function payroll_payments($data){
		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$payroll_history = \payroll_history::leftJoin('users', 'users.id', '=', 'payroll_history.userid')
					->where('payroll_history.pay_date','>=',$data['fromDate'])
					->where('payroll_history.pay_date','<=',$data['toDate'])
					->select('payroll_history.id as id',
					'payroll_history.salary_type as salary_type',
					'payroll_history.salary_value as salary_value',
					'payroll_history.hour_overtime as hour_overtime',
					'payroll_history.hour_count as hour_count',
					'payroll_history.pay_month as pay_month',
					'payroll_history.pay_year as pay_year',
					'payroll_history.pay_date as pay_date',
					'payroll_history.pay_amount as pay_amount',
					'payroll_history.pay_method as pay_method',
					'payroll_history.pay_comments as pay_comments',
					'users.fullName as fullName',
					'users.username as username',
					'users.email as email');

		$payroll_history = $payroll_history->orderBy('id','DESC')->get()->toArray();

		if($this->export_override == ""){
		
			foreach ($payroll_history as $key=>$payroll) {
				$payroll_history[$key]['pay_date'] = $this->panelInit->unix_to_date($payroll_history[$key]['pay_date']);
			}

			return $payroll_history;

		}else{
			if($this->export_override == "excel"){
				$data = array(1 => array ($this->panelInit->language['user'],$this->panelInit->language['salaryDetails'],$this->panelInit->language['salaryForDate'],$this->panelInit->language['Date'],$this->panelInit->language['Amount'],$this->panelInit->language['method'],$this->panelInit->language['Comments']));
	
				foreach ($payroll_history as $key=>$payroll) {
					
					$payroll_history[$key]['pay_date'] = $this->panelInit->unix_to_date($payroll_history[$key]['pay_date']);

					$data[ $key + 2 ] = array();
					$data[ $key + 2 ][0] = $payroll_history[$key]['fullName'] . " [". $payroll_history[$key]['username'] . "] " . "<br/>" . $payroll_history[$key]['email'];
					
					$data[ $key + 2 ][1] = $payroll_history[$key]['salary_type']; 
					$data[ $key + 2 ][1] .= " - ";
					if($payroll_history[$key]['salary_type'] == 'monthly'){
						$data[ $key + 2 ][1] .= $this->panelInit->language['netSalary'] . " : " . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['salary_value'];
						$data[ $key + 2 ][1] .= " - ";
						$data[ $key + 2 ][1] .= $this->panelInit->language['overtime'] . " : " . $payroll_history[$key]['hour_count'] . "x" . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['hour_overtime'];
					}

					if($payroll_history[$key]['salary_type'] == 'hourly'){
						$data[ $key + 2 ][1] .= $this->panelInit->language['rate'] . " : " . $payroll_history[$key]['hour_count'] . "x" . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['salary_value'];
					}

					$data[ $key + 2 ][2] = $payroll_history[$key]['pay_month']."/".$payroll_history[$key]['pay_year']; 
					$data[ $key + 2 ][3] = $payroll_history[$key]['pay_date']; 
					$data[ $key + 2 ][4] = $this->data['panelInit']->settingsArray['currency_symbol'].$payroll_history[$key]['pay_amount']; 
					$data[ $key + 2 ][5] = $payroll_history[$key]['pay_method']; 
					$data[ $key + 2 ][6] = $payroll_history[$key]['pay_comments']; 
				
				}
	
				\Excel::create('Payroll-Reports', function($excel) use($data) {
	
					// Set the title
					$excel->setTitle('Payroll Reports');
	
					// Chain the setters
					$excel->setCreator('OraSchool')->setCompany('SolutionsBricks');
	
					$excel->sheet('Payroll Reports', function($sheet) use($data) {
						$sheet->freezeFirstRow();
						$sheet->fromArray($data, null, 'A1', true,false);
					});
	
				})->download('xls');
			}
	
			if($this->export_override == "pdf"){
				$header = array ($this->panelInit->language['user'],$this->panelInit->language['salaryDetails'],$this->panelInit->language['salaryForDate'],$this->panelInit->language['Date'],$this->panelInit->language['Amount'],$this->panelInit->language['method'],$this->panelInit->language['Comments']);

				$data = array();
				foreach ($payroll_history as $key=>$payroll) {
					
					$payroll_history[$key]['pay_date'] = $this->panelInit->unix_to_date($payroll_history[$key]['pay_date']);

					$data[ $key ] = array();
					$data[ $key ][0] = $payroll_history[$key]['fullName'] . " [". $payroll_history[$key]['username'] . "] " . "<br/>" . $payroll_history[$key]['email'];
					
					$data[ $key ][1] = $payroll_history[$key]['salary_type']; 
					$data[ $key ][1] .= "<br>";
					if($payroll_history[$key]['salary_type'] == 'monthly'){
						$data[ $key ][1] .= $this->panelInit->language['netSalary'] . " : " . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['salary_value'];
						$data[ $key ][1] .= "<br>";
						$data[ $key ][1] .= $this->panelInit->language['overtime'] . " : " . $payroll_history[$key]['hour_count'] . "x" . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['hour_overtime'];
					}

					if($payroll_history[$key]['salary_type'] == 'hourly'){
						$data[ $key ][1] .= $this->panelInit->language['rate'] . " : " . $payroll_history[$key]['hour_count'] . "x" . $this->data['panelInit']->settingsArray['currency_symbol'] . $payroll_history[$key]['salary_value'];
					}

					$data[ $key ][2] = $payroll_history[$key]['pay_month']."/".$payroll_history[$key]['pay_year']; 
					$data[ $key ][3] = $payroll_history[$key]['pay_date']; 
					$data[ $key ][4] = $this->data['panelInit']->settingsArray['currency_symbol'].$payroll_history[$key]['pay_amount']; 
					$data[ $key ][5] = $payroll_history[$key]['pay_method']; 
					$data[ $key ][6] = $payroll_history[$key]['pay_comments']; 
				
				}
	
				$doc_details = array(
									"title" => "Payroll Reports",
									"author" => $this->data['panelInit']->settingsArray['siteTitle'],
									"topMarginValue" => 10
									);
	
				if( $this->panelInit->isRTL == "1" ){
					$doc_details['is_rtl'] = true;
				}
	
				$pdfbuilder = new \PdfBuilder($doc_details);
	
				$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
					<thead><tr>";
					foreach ($header as $value) {
						$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";
					}
				$content .="</tr></thead><tbody>";
	
				foreach($data as $row)
				{
					$content .= "<tr>";
					foreach($row as $col){
						$content .="<td>".$col."</td>";
					}
					$content .= "</tr>";
				}
	
				$content .= "</tbody></table>";
	
				$pdfbuilder->table($content, array('border' => '0','align'=>'') );
				$pdfbuilder->output('Payroll-Reports.pdf');
	
				exit;
			}
		}

	}

	public function collection($data){

		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$paymentsCollection = \paymentsCollection::leftJoin('payments','payments.id','=','paymentsCollection.invoiceId')->leftJoin('users','users.id','=','payments.paymentStudent')->where('paymentsCollection.collectionDate','>=',$data['fromDate'])->where('paymentsCollection.collectionDate','<=',$data['toDate']);
		
		if(isset($data['payment_status']) AND $data['payment_status'] != "all" ){
			$paymentsCollection = $paymentsCollection->where('collectionMethod',$data['payment_status']);
		}

		if(isset($data['payMethod']) AND $data['payMethod'] != "all" ){
			$paymentsCollection = $paymentsCollection->where('paymentsCollection.collectionMethod',$data['payMethod']);
		}

		if(isset($data['dueInv']) AND $data['dueInv'] == true){
			$paymentsCollection = $paymentsCollection->where('payments.dueDate','<',time())->where('payments.paymentStatus','!=','1');
		}

		$paymentsCollection = $paymentsCollection->select('paymentsCollection.*','users.fullName','users.email','users.username','payments.paymentTitle','payments.paymentDescription','payments.paymentAmount','payments.paymentDiscount','payments.paymentDiscounted','payments.paidAmount','payments.paymentStatus','payments.paymentDate','payments.dueDate')->get()->toArray();

		$expdata = array( 1 => array( $this->panelInit->language['InvTitle'],$this->panelInit->language['Date'],$this->panelInit->language['dueDate'],$this->panelInit->language['Amount'] ." / ". $this->panelInit->language['discoutedAmount'] ." / ".  $this->panelInit->language['paidAmount'],$this->panelInit->language['student'] ,$this->panelInit->language['Status'],$this->panelInit->language['collAmount'],$this->panelInit->language['collDate'],$this->panelInit->language['payMethod']) );
		foreach ($paymentsCollection as $key => $value) {
			$value['paymentDate'] = $this->panelInit->unix_to_date($value['paymentDate']);
			$value['dueDate'] = $this->panelInit->unix_to_date($value['dueDate']);

			$amount = $this->data['panelInit']->settingsArray['currency_symbol']." ".$value['paymentAmount'] ." / ";
			$amount .= $this->data['panelInit']->settingsArray['currency_symbol']." ".$value['paymentDiscounted'] ." / ";
			$amount .= $this->data['panelInit']->settingsArray['currency_symbol']." ".$value['paidAmount'];

			$inv_status = "";
			if($value['paymentStatus'] == '0'){
				$inv_status = $this->panelInit->language['unpaid'];
			}
			if($value['paymentStatus'] == '1'){
				$inv_status = $this->panelInit->language['paid'];
			}
			if($value['paymentStatus'] == '2'){
				$inv_status = $this->panelInit->language['ppaid'];
			}

			$collAmount = $this->data['panelInit']->settingsArray['currency_symbol']." ".$value['collectionAmount'] ;

			$value['collectionDate'] = $this->panelInit->unix_to_date($value['collectionDate']);

			$expdata[] = array($value['paymentTitle'],$value['paymentDate'],$value['dueDate'],$amount,$value['fullName']."[".$value['username']."]",$inv_status,$collAmount ,$value['collectionDate'] ,$value['collectionMethod']);
		}

		$params = array("title"=>"Collection Reports");

		\Excel::create('Collection-Reports', function($excel) use($expdata) {

			// Set the title
			$excel->setTitle('Collection-Reports');

			// Chain the setters
			$excel->setCreator('OraSchool')->setCompany('SolutionsBricks');

			$excel->sheet('Collection-Reports', function($sheet) use($expdata) {
				$sheet->freezeFirstRow();
				$sheet->fromArray($expdata, null, 'A1', true,false);
			});

		})->download('xls');		
	}
	
	public function invoiceGeneration($data){
		$content = "";
		
		$classes_list = array();
		$classes = \classes::select('id','className')->get();
		foreach ($classes as $class) {
			$classes_list[$class->id] = $class->className ;
		}

		$sections_list = array();
		$sections = \sections::select('id','sectionName','sectionTitle')->get();
		foreach ($sections as $section) {
			$sections_list[$section->id] = $section->sectionName."[".$section->sectionTitle."]" ;
		}

		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);
		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);

		$payments = \payments::leftJoin('users', 'users.id', '=', 'payments.paymentStudent')
					->where('payments.paymentDate','>=',$data['fromDate'])
					->where('payments.paymentDate','<=',$data['toDate'])
					->select('payments.id as id',
					'payments.paymentTitle as paymentTitle',
					'payments.paymentDescription as paymentDescription',
					'payments.paymentAmount as paymentAmount',
					'payments.paymentDiscount as paymentDiscount',
					'payments.paymentDiscounted as paymentDiscounted',
					'payments.paidAmount as paidAmount',
					'payments.paymentStatus as paymentStatus',
					'payments.paymentDate as paymentDate',
					'payments.dueDate as dueDate',
					'payments.paymentStudent as studentId',
					'payments.paymentRows as paymentRows',
					'users.id as studentId',
					'users.fullName as fullName',
					'users.address as address',
					'users.phoneNo as phoneNo',
					'users.email as email',
					'users.studentClass as studentClass',
					'users.studentSection as studentSection'
				);



		if(isset($data['payment_status']) AND $data['payment_status'] != "All"){
			$payments = $payments->where('paymentStatus',$data['payment_status']);
		}
		if(isset($data['dueInv']) AND $data['dueInv'] == true){
			$payments = $payments->where('dueDate','<',time())->where('paymentStatus','!=','1');
		}
		$payments = $payments->orderBy('id','DESC')->get()->toArray();

		$headers = array( $this->panelInit->language['InvTitle'],$this->panelInit->language['Date'],$this->panelInit->language['dueDate'],$this->panelInit->language['Amount'],$this->panelInit->language['discoutedAmount'],  $this->panelInit->language['paidAmount'],$this->panelInit->language['student'] ,$this->panelInit->language['Status']);
		$expdata = array();


		$doc_details = array(
							"title" => "Invoices Reports",
							"author" => $this->data['panelInit']->settingsArray['siteTitle'],
							"topMarginValue" => 10
							);

		if( $this->panelInit->isRTL == "1" ){
			$doc_details['is_rtl'] = true;
		}

		$pdfbuilder = new \PdfBuilder($doc_details);

		foreach ($payments as $key => $value) {
			$value['paymentDate'] = $this->panelInit->unix_to_date($value['paymentDate']);
			$value['dueDate'] = $this->panelInit->unix_to_date($value['dueDate']);
			if($value['dueDate'] < time()){
				$value['isDueDate'] = true;
			}

			if($value['paymentStatus'] == "1"){
				$value['paidTime'] = $this->panelInit->unix_to_date($value['paidTime']);
			}
			$value['paymentRows'] = json_decode($value['paymentRows'],true);

			$value['prices']['original'] = $value['paymentAmount'];
			$value['prices']['discount'] = $value['paymentDiscount'];
			$value['prices']['original_discounted'] = $value['prices']['original'] - $value['prices']['discount'];
			$value['prices']['tax_value'] = ($this->panelInit->settingsArray['paymentTax'] * $value['prices']['original_discounted']) /100;
			$value['prices']['total_with_tax'] = $value['prices']['original_discounted'] + $value['prices']['tax_value'];
			$value['prices']['paid_value'] = $value['paidAmount'];
			$value['prices']['total_pending'] = $value['prices']['total_with_tax'] - $value['paidAmount'];

			if( $value['prices']['discount'] != 0 ){
				$value['paymentRows'][] = array("title"=>"Discount","amount"=>$value['prices']['discount']);			
			}

			$value['paymentAmount'] = $value['prices']['original_discounted'] ;
			$value['totalWithTax'] = $value['prices']['total_with_tax'];
			$value['pendingAmount'] = $value['prices']['total_pending'];

			//Class & section
			$value['className'] = "";
			if(isset($classes_list[ $value['studentClass'] ])){
				$value['className'] = $classes_list[ $value['studentClass'] ];
			}

			$value['sectionName'] = "";
			if(isset($sections_list[ $value['studentSection'] ])){
				$value['sectionName'] = $sections_list[ $value['studentSection'] ];
			}

			$pdfbuilder->table($this->makeInvoice($value), array('border' => '0','align'=>'') );
			$pdfbuilder->addPage();
		}
		
		$pdfbuilder->output('Invoices.pdf');

		exit;
	}

	function makeInvoice($invoice){

		if(file_exists('uploads/profile/profile_'.$invoice['studentId'].'.jpg')){
			$userAssetImage = 'uploads/profile/profile_'.$invoice['studentId'].'.jpg';
		}else{
			$userAssetImage = 'uploads/profile/user.png';
		}

		$output = '<table cellspacing="0" cellpadding="4" border="0">
			<tr>
				<td width="15%"><img src="'.\URL::asset('assets/images/logo-light.png').'"></td>
				<td width="70%" style="vertical-align: middle;font-weight:bold;" ><br/><br/>'.$this->data['panelInit']->settingsArray['siteTitle'].'<br/> '.$this->panelInit->language['Invoices'].' #'.$invoice['paymentTitle'].'</td>
				<td width="15%" style="vertical-align: right;horizontal-aligh:right;" ><img width="75px" height="75px" src="'.$userAssetImage.'"></td>
			</tr>
		</table>

		<br/><br/>

		<table cellspacing="0" cellpadding="4" border="0">
			<tr>
				<td width="50%">'.$this->panelInit->language['from'].'
                    <address>
                        <strong>'.$this->data['panelInit']->settingsArray['siteTitle'].'</strong><br>'.$this->data['panelInit']->settingsArray['address'].'<br>'.$this->data['panelInit']->settingsArray['address2'].'<br>'.$this->panelInit->language['phoneNo'].': '.$this->data['panelInit']->settingsArray['phoneNo'].'<br/>'.$this->panelInit->language['email'].': '.$this->data['panelInit']->settingsArray['systemEmail'].'
                    </address>
				</td>
				<td width="50%">'.$this->panelInit->language['to'].'
                    <address>
                        <strong>'.$invoice['fullName'].'</strong><br>'.$invoice['address'].'<br>'.$this->panelInit->language['phoneNo'].': '.$invoice['phoneNo'].'<br/>'.$this->panelInit->language['email'].': '.$invoice['email'].'<br/>'.$this->panelInit->language['class'].': '.$invoice['className'];
                        if($this->data['panelInit']->settingsArray['enableSections'] == 1){
                        	$output .= '<br/>'.$this->panelInit->language['section'].': '.$invoice['sectionName'];
                        }
					$output .= '</address>
				</td>
			</tr>
		</table>
		
		<br/>
		<br/>
		<br/>

		<table cellspacing="0" cellpadding="4" border="0">
			<tr>
				<td width="100%" style="text-align: center;">';
					switch ($invoice['paymentStatus']) {
			    		case '0':
			    			$output .= '<span style="color:red; font-size:30px;font-weight:bold;">'.$this->panelInit->language['unpaid'].'</span>';
			    		break;
			    		case '1':
			    			$output .= '<span style="color:green; font-size:30px;font-weight:bold;">'.$this->panelInit->language['paid'].'</span>
			    			<span style="color:green;font-weight:bold;">
			                    <br/>
			                    '.$this->panelInit->language['payMethod'].' : '.$invoice['paidMethod'].'
			                    <br/>
			                    '.$this->panelInit->language['payDate'].' : '.$invoice['paidTime'].'
			                </span>';
			    		break;
			    		case '2':
			    			$output .= '<span style="color:green; font-size:30px;font-weight:bold;">'.$this->panelInit->language['ppaid'].'</span>';
			    		break;
			    	}
                $output .= '</center></td>
			</tr>
		</table>

		<br/>
		<br/>
		<br/>


		<table cellspacing="0" cellpadding="8" border="1">
					<thead>
                        <tr>
                            <th>'.$this->panelInit->language['Product'].'</th>
                            <th>'.$this->panelInit->language['Subtotal'].'</th>
                        </tr>
                    </thead>
                    <tbody>';
            	foreach ($invoice['paymentRows'] as $key => $row) {
                    $output .= '<tr>
                        <td>'.$row['title'].'</td>
                        <td>'.$this->data['panelInit']->settingsArray['currency_symbol'].$row['amount'].'</td>
                    </tr>';
                }
            $output .= '</tbody>
        </table>

        <br/>
		<br/>
		<br/>


		<table cellspacing="0" cellpadding="4" border="0">
			<tr>
				<td width="50%">
					<table cellspacing="0" cellpadding="8" border="0">
						<tbody>
	                    	<tr>
	                            <td>'.$this->panelInit->language['Date'].' : '.$invoice['paymentDate'].'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['dueDate'].' : '.$invoice['dueDate'].'</td>
	                        </tr>
	                    </tbody>
	       			</table>
				</td>
				<td width="50%">
					<table cellspacing="0" cellpadding="8" border="0">
						<tbody>
	                    	<tr>
	                            <td>'.$this->panelInit->language['Subtotal'].': '.$this->data['panelInit']->settingsArray['currency_symbol'].''.$invoice['prices']['original'].'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['Discount'].': '.$this->data['panelInit']->settingsArray['currency_symbol'].''.$invoice['prices']['discount'].'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['payTax'].' ('.$this->data['panelInit']->settingsArray['paymentTax'].'%):'.$this->data['panelInit']->settingsArray['currency_symbol'].''.$invoice['prices']['tax_value'].'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['Total'].': '.$this->data['panelInit']->settingsArray['currency_symbol'].''.$invoice['prices']['total_with_tax'].'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['paidAmount'].': '.$this->data['panelInit']->settingsArray['currency_symbol'].$invoice['prices']['paid_value'] .'</td>
	                        </tr>
	                        <tr>
	                            <td>'.$this->panelInit->language['pendingAmount'].': '.$this->data['panelInit']->settingsArray['currency_symbol'].$invoice['prices']['total_pending'] .'</td>
	                        </tr>';
	                        if( isset($invoice['isDueDate']) && $invoice['paymentStatus'] != 1 ){
		                        $output .= '<tr>
		                            <td style="background-color: red;color: black;">'.$this->panelInit->language['invDueDate'].'</td>
		                        </tr>';
		                    }
	                    $output .= '</tbody>
	       			</table>
				</td>
			</tr>
		</table>';

    	return $output;
	}

	public function preCert(){
		$toReturn = array();
		$toReturn['classes'] = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->select('id','className')->get()->toArray();
		$toReturn['certs'] = \certificates::select('id','certificate_name')->get()->toArray();
		return $toReturn;
	}

	public function certGetStdList(){
		$User = \User::where('role','student')->where('studentClass',\Input::get('classId'));
		if(\Input::has('sectionId')){
			$User = $User->where('studentSection',\Input::get('sectionId'));
		}
		return $User->select('id','username','fullName','email')->get();
	}

	public function certPrint($data){
		$to_return = array("certificate"=>array(),"users"=>array());

		//Prepare std cat.
		$std_cat = array();
		$student_categories = \student_categories::select('id','cat_title')->get();
		foreach ($student_categories as $key => $value) {
			$std_cat[$value->id] = $value->cat_title;
		}

		//Prepare Classes
		$classes_list = array();
		$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->select('id','className')->get();
		foreach ($classes as $key => $value) {
			$classes_list[$value->id] = $value->className;
		}

		//Prepare Section
		$sections_list = array();
		$sections = \sections::select('id','sectionName')->get();
		foreach ($sections as $key => $value) {
			$sections_list[$value->id] = $value->sectionName;
		}


		$User = \User::where('role','student')->whereIn('id',$data['selected_users'])->get()->toArray();
		foreach ($User as $key => $value) {
			$to_return['users'][$key] = array();
			$to_return['users'][$key]['user_name'] = $value['username'];
			$to_return['users'][$key]['full_name'] = $value['fullName'];
			$to_return['users'][$key]['email'] = $value['email'];
			$to_return['users'][$key]['date_of_birth'] = $this->panelInit->unix_to_date($value['birthday']);
			$to_return['users'][$key]['gender'] = $value['gender'];
			$to_return['users'][$key]['religion'] = $value['religion'];
			$to_return['users'][$key]['phone_number'] = $value['phoneNo'];
			$to_return['users'][$key]['mobile_number'] = $value['mobileNo'];
			$to_return['users'][$key]['address'] = $value['address'];
			$to_return['users'][$key]['admission_number'] = $value['admission_number'];
			$to_return['users'][$key]['admission_date'] = $this->panelInit->unix_to_date($value['admission_date']);
			$to_return['users'][$key]['roll_id'] = $value['studentRollId'];
			if(isset($std_cat[ $value['std_category'] ])){
				$to_return['users'][$key]['student_category'] = $std_cat[ $value['std_category'] ];
			}else{
				$to_return['users'][$key]['student_category'] = "";
			}
			if(isset($classes_list[ $value['studentClass'] ])){
				$to_return['users'][$key]['class_name'] = $classes_list[ $value['studentClass'] ];
			}else{
				$to_return['users'][$key]['class_name'] = "";
			}
			if(isset($classes_list[ $value['studentSection'] ])){
				$to_return['users'][$key]['section_name'] = $sections_list[ $value['studentSection'] ];
			}else{
				$to_return['users'][$key]['section_name'] = "";
			}
			
			$value['father_info'] = json_decode($value['father_info'],true);
			if(is_array($value['father_info']) AND isset($value['father_info']['name'])){
				$to_return['users'][$key]['father_name'] = $value['father_info']['name'];
			}else{
				$to_return['users'][$key]['father_name'] = "";
			}

			$value['mother_info'] = json_decode($value['mother_info'],true);
			if(is_array($value['father_info']) AND isset($value['mother_info']['name'])){
				$to_return['users'][$key]['mother_name'] = $value['mother_info']['name'];
			}else{
				$to_return['users'][$key]['mother_name'] = "";
			}

		}

		$to_return['certificate'] = \certificates::where('id',$data['certId'])->first();
		$to_return['margins'] = json_decode($to_return['certificate']->position_margins,true);

		return $to_return;
	}

	public function preCards(){
		$toReturn = array();
		$toReturn['classes'] = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->select('id','className')->get()->toArray();
		$toReturn['cards'] = \id_cards::select('id','card_name')->get()->toArray();
		return $toReturn;
	}
	
	public function cardsGetStdList(){
		$User = \User::where('role','student')->where('studentClass',\Input::get('classId'));
		if(\Input::has('sectionId')){
			$User = $User->where('studentSection',\Input::get('sectionId'));
		}
		return $User->select('id','username','fullName','email')->get();
	}

	public function cardPrint($data){
		$to_return = array("card"=>array(),"users"=>array());

		//Prepare std cat.
		$std_cat = array();
		$student_categories = \student_categories::select('id','cat_title')->get();
		foreach ($student_categories as $key => $value) {
			$std_cat[$value->id] = $value->cat_title;
		}

		//Prepare Classes
		$classes_list = array();
		$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->select('id','className')->get();
		foreach ($classes as $key => $value) {
			$classes_list[$value->id] = $value->className;
		}

		//Prepare Section
		$sections_list = array();
		$sections = \sections::select('id','sectionName')->get();
		foreach ($sections as $key => $value) {
			$sections_list[$value->id] = $value->sectionName;
		}


		$User = \User::where('role','student')->whereIn('id',$data['selected_users'])->get()->toArray();
		foreach ($User as $key => $value) {
			$to_return['users'][$key] = array();
			$to_return['users'][$key]['user_name'] = $value['username'];
			$to_return['users'][$key]['full_name'] = $value['fullName'];
			$to_return['users'][$key]['email'] = $value['email'];
			$to_return['users'][$key]['date_of_birth'] = $this->panelInit->unix_to_date($value['birthday']);
			$to_return['users'][$key]['gender'] = $value['gender'];
			$to_return['users'][$key]['religion'] = $value['religion'];
			$to_return['users'][$key]['phone_number'] = $value['phoneNo'];
			$to_return['users'][$key]['mobile_number'] = $value['mobileNo'];
			$to_return['users'][$key]['address'] = $value['address'];
			$to_return['users'][$key]['admission_number'] = $value['admission_number'];
			$to_return['users'][$key]['admission_date'] = $this->panelInit->unix_to_date($value['admission_date']);
			$to_return['users'][$key]['roll_id'] = $value['studentRollId'];
			$to_return['users'][$key]['id'] = $value['id'];
			if(isset($std_cat[ $value['std_category'] ])){
				$to_return['users'][$key]['student_category'] = $std_cat[ $value['std_category'] ];
			}else{
				$to_return['users'][$key]['student_category'] = "";
			}
			if(isset($classes_list[ $value['studentClass'] ])){
				$to_return['users'][$key]['class_name'] = $classes_list[ $value['studentClass'] ];
			}else{
				$to_return['users'][$key]['class_name'] = "";
			}
			if(isset($classes_list[ $value['studentSection'] ])){
				$to_return['users'][$key]['section_name'] = $sections_list[ $value['studentSection'] ];
			}else{
				$to_return['users'][$key]['section_name'] = "";
			}
			
			$value['father_info'] = json_decode($value['father_info'],true);
			if(is_array($value['father_info']) AND isset($value['father_info']['name'])){
				$to_return['users'][$key]['father_name'] = $value['father_info']['name'];
			}else{
				$to_return['users'][$key]['father_name'] = "";
			}

			$value['mother_info'] = json_decode($value['mother_info'],true);
			if(is_array($value['father_info']) AND isset($value['mother_info']['name'])){
				$to_return['users'][$key]['mother_name'] = $value['mother_info']['name'];
			}else{
				$to_return['users'][$key]['mother_name'] = "";
			}

		}

		$to_return['card'] = \id_cards::where('id',$data['cardId'])->first();
		$to_return['margins'] = json_decode( $to_return['card']['position_margins'] );

		return $to_return;
	}
}
