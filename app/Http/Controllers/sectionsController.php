<?php
namespace App\Http\Controllers;

class sectionsController extends Controller {

	var $data = array();
	var $panelInit ;
	var $layout = 'dashboard';

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
	}

	public function listAll($page = 1)
	{
		if(!$this->panelInit->can( array("sections.list","sections.addSection","sections.editSection","sections.delSection") )){
			exit;
		}

		$toReturn = array();
		$classesIn = array();
		
		$toReturn['sections'] = \DB::table('sections')
					->leftJoin('classes','classes.id','=','sections.classId')
					->select('sections.id as id',
					'sections.sectionName as sectionName',
					'sections.sectionTitle as sectionTitle',
					'sections.classId as classId',
					'sections.teacherId as teacherId')
					->where('classes.classAcademicYear',$this->panelInit->selectAcYear);

		if( \Input::has('searchInput') ){
			$keyword = \Input::get('searchInput');
			$toReturn['sections'] = $toReturn['sections']->where(function($query) use ($keyword){
											$query->where('sections.sectionName','like','%'.$keyword.'%');
											$query->orWhere('sections.sectionTitle','like','%'.$keyword.'%');
										});
		}

		$toReturn['totalItems'] = $toReturn['sections']->count();
		$toReturn['sections'] = $toReturn['sections']->orderby('sections.id','desc')->orderby('sections.classId','desc')->take('20')->skip( 20 * ($page - 1) )->get();
		
		foreach ($toReturn['sections'] as $key => $section) {

			$classesIn[] = $section->classId;
			$toReturn['sections'][$key]->teacherId = json_decode($toReturn['sections'][$key]->teacherId,true);

		}

		$toReturn['classes'] = array();
		$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get();
		foreach ($classes as $value) {
			$toReturn['classes'][$value->id] = $value->className;
		}

		$toReturn['teachers'] = array();
		$teachers = \User::where('role','teacher')->get();
		foreach ($teachers as $value) {
			$toReturn['teachers'][$value->id] = $value->fullName;
		}

		return $toReturn;
	}

	public function delete($id){

		if(!$this->panelInit->can( "sections.delSection" )){
			exit;
		}

		if ( $postDelete = \sections::where('id', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delSection'],$this->panelInit->language['sectionDeleted'] );
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delSection'],$this->panelInit->language['sectionNotExist'] );
        }
	}

	public function create(){

		if(!$this->panelInit->can( "sections.addSection" )){
			exit;
		}

		$sections = new \sections();
		$sections->sectionName = \Input::get('sectionName');
		$sections->sectionTitle = \Input::get('sectionTitle');
		$sections->classId = \Input::get('classId');
		$sections->teacherId = json_encode(\Input::get('teacherId'));
		$sections->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['addSection'],$this->panelInit->language['sectionAdded']);
	}

	function fetch($id){

		if(!$this->panelInit->can( "sections.editSection" )){
			exit;
		}

		$sections = \sections::where('id',$id)->first()->toArray();
		$sections['teacherId'] = json_decode($sections['teacherId'],true);
		return $sections;
	}

	function edit($id){

		if(!$this->panelInit->can( "sections.editSection" )){
			exit;
		}

		$sections = \sections::find($id);
		$sections->sectionName = \Input::get('sectionName');
		$sections->sectionTitle = \Input::get('sectionTitle');
		$sections->classId = \Input::get('classId');
		$sections->teacherId = json_encode(\Input::get('teacherId'));
		$sections->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editSection'],$this->panelInit->language['sectionUpdated']);
	}

}
