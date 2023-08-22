<?php 
include __DIR__.'/../init.php';


//dp_json(['d'=>get_user(1)]);
/*
$q = dp_get_terms_tree('store');
$t = dp_get_term(1);
dp_json(['data'=>$q,'t'=>$t]); 
*/

$q = dp_get_nodes([
	'type'=>'mac',	
	'nid[>=]'=>0,
	'status'=>1,
	//'type'  => ['mac','article'],
	
	//'status'=>[0,1],
	//'LIMIT'=>"0,1",
	//'title[~]'=>'article',
	'ORDER'=>['nid'=>'DESC'],
]); 
dp_json(['data'=>$q]); 