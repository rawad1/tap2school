<?php
namespace App\Http\Controllers;

class idCardsController extends Controller {
	var $data = array();
	var $panelInit ;

	public function __construct(){
		if(app('request')->header('Authorization') != "" || \Input::has('token')){
			$this->middleware('jwt.auth');
		}else{
			$this->middleware('authApplication');
		}

		$this->panelInit = new \DashboardInit();
		$this->data['panelInit'] = $this->panelInit;
		$this->data['users'] = $this->panelInit->getAuthUser();

		if(!isset($this->data['users']->id)){
			return \Redirect::to('/');
		}

	}

	public function listAll($page = 1,$search = ""){

		if(!$this->panelInit->can( array("id_cards.list","id_cards.add_card","id_cards.edit_card","id_cards.del_card","id_cards.Download") )){
			exit;
		}

		$toReturn = \id_cards::select('id','card_name')->orderby('id','DESC')->get()->toArray();

		return $toReturn;
	}

	public function create(){

		if(!$this->panelInit->can( "id_cards.add_card" )){
			exit;
		}

		$id_cards = new \id_cards();
		$id_cards->id = \Input::get('id');
		$id_cards->card_name = \Input::get('card_name');
		if(\Input::has('header_text')){
			$id_cards->header_text = \Input::get('header_text');
		}
		if(\Input::has('main_left')){
			$id_cards->main_left = \Input::get('main_left');
		}
		if(\Input::has('main_right')){
			$id_cards->main_right = \Input::get('main_right');
		}
		if(\Input::has('footer_text')){
			$id_cards->footer_text = \Input::get('footer_text');
		}

		if (\Input::hasFile('card_image')) {
			$fileInstance = \Input::file('card_image');

			if(!$this->panelInit->validate_upload($fileInstance)){
				return $this->panelInit->apiOutput(false,$this->panelInit->language['add_card'],"Sorry, This File Type Is Not Permitted For Security Reasons ");
			}

			$newFileName = uniqid().".".$fileInstance->getClientOriginalExtension();
			$fileInstance->move('uploads/cards/',$newFileName);

			$id_cards->card_image = $newFileName;
		}
		if(\Input::has('position_margins')){
			$id_cards->position_margins = \Input::get('position_margins');
		}

		$id_cards->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['add_card'],$this->panelInit->language['card_add']);
	}

	public function fetch($id){

		if(!$this->panelInit->can( "id_cards.edit_card" )){
			exit;
		}

		$id_cards = \id_cards::where('id',$id)->first()->toArray();
		$id_cards['position_margins'] = json_decode($id_cards['position_margins'],true);
	
		return $id_cards;
	}

	public function edit($id){

		if(!$this->panelInit->can( "id_cards.edit_card" )){
			exit;
		}

		$id_cards = \id_cards::find($id);
		$id_cards->card_name = \Input::get('card_name');
		
		$id_cards->header_text = \Input::get('header_text');
		$id_cards->main_left = \Input::get('main_left');
		$id_cards->main_right = \Input::get('main_right');
		$id_cards->footer_text = \Input::get('footer_text');
		
		if (\Input::hasFile('card_image')) {
			$fileInstance = \Input::file('card_image');

			if(!$this->panelInit->validate_upload($fileInstance)){
				return $this->panelInit->apiOutput(false,$this->panelInit->language['add_card'],"Sorry, This File Type Is Not Permitted For Security Reasons ");
			}

			if($id_cards->card_image != ""){
				@unlink("uploads/cards/".$id_cards->card_image);
			}

			$newFileName = uniqid().".".$fileInstance->getClientOriginalExtension();
			$fileInstance->move('uploads/cards/',$newFileName);

			$id_cards->card_image = $newFileName;
		}
		$id_cards->position_margins = \Input::get('position_margins');

		$id_cards->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['edit_card'],$this->panelInit->language['card_edit']);
	}
	
	public function delete($id){

		if(!$this->panelInit->can( "id_cards.del_card" )){
			exit;
		}

		if ( $postDelete = \id_cards::where('id', $id)->first() ){
        	if($postDelete->card_image != ""){ @unlink('uploads/cards/'.$postDelete->card_image); }
        	
        	$postDelete->delete();
			return $this->panelInit->apiOutput(true,$this->panelInit->language['del_card'],$this->panelInit->language['card_del']);
       	}else{
       		return $this->panelInit->apiOutput(true,$this->panelInit->language['del_card'],$this->panelInit->language['card_not_exist']);
       	}
	}


}