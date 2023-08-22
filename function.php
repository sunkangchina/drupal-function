<?php 

use Drupal\node\Entity\Node;
use Drupal\file\FileInterface; 
use Drupal\Core\Url;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\HttpFoundation\JsonResponse; 
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
/**
* 条件查寻数据

$q = dp_get_nodes([
	'type'  => 'test',
	'nid[>=]'=>1,
	'ORDER'=>['nid'=>'DESC'],
]); 
*/
function dp_get_nodes($where,$return_node = true){
	$LIMIT    = $where['LIMIT'];
	$or    = $where['OR'];
	$sort  = $where['ORDER'];
	unset($where['OR'],$where['ORDER'],$where['LIMIT']);
	$query = Drupal::entityTypeManager()->getStorage('node');  
	$query = $query->getQuery()->accessCheck(false);  
	$op_in = [
		'<>'=>'BETWEEN',
		'><'=>'NOT BETWEEN',
		'in'=>'IN',
		'!in'=>'NOT IN',
		'null'=>'IS NULL',
		'!null'=>'IS NOT NULL',
		'~'=>'LIKE',
		'!~'=>'NOT LIKE',
		'ext'=>'EXISTS',
		'!not'=>'NOT EXISTS',
		'='=>'=', 
		'<'=>'<', 
		'>'=>'>', 
		'>='=>'>=', 
		'<='=>'<=', 
	];
	foreach($where as $k=>$v){
		$end = ''; 
		if($k && is_string($k) && strpos($k,'[') !== false){
			$ori = $k;
			$k   = substr($k,0,strpos($k,'['));
			$end = substr($ori,strpos($ori,'[')+1,-1);  
		}
		if(is_array($v)){
			if(!$end){
				$end = "in";
			}
		} 
		if(isset($op_in[$end])){
			$end = $op_in[$end];
		}  
		if(strpos($end,'LIKE') !== false){
			$v = "%$v%";
		}
		$query = $query->condition($k,$v,$end); 	
	} 
	//andConditionGroup orConditionGroup
	$or_cond = '';
	if($or){
		$or_cond = $query->orConditionGroup()
	   		->condition('type', 'ariticle');
	} 
	if($or_cond){
		$query = $query->condition($or_cond); 	
	}	
	if($ORDER){
		foreach($ORDER as $k=>$v){
			$query = $query->sort('nid', 'DESC');
		}
	}
	if($LIMIT){
		if(is_string($LIMIT)){
			$arr = explode(",",$LIMIT);
			$query = $query->range($arr[0], $arr[1]);
		} else if(is_array($LIMIT)){
			$arr = $LIMIT;
			$query = $query->range($arr[0], $arr[1]);
		}
	} 
	$res = $query->execute();
	$sql = $query->__toString(); 
	if(!$res){
		return;
	}
	if(!$return_node){
		return $res;
	}else{
		return dp_get_nodes_by_nid($res);
	}
	
}
/**
* SAVE NODE 
*/
function dp_save_node($type,$data = []){
	$data['type'] = $type;
	$node = Node::create($data);
	return $node->save();
}
/**
* 
* $all = dp_get_nodes_by_nid([1,5]);  
* print_r($all);exit;
*/
function dp_get_nodes_by_nid(array $node_id = [])
{
	$all = Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_id);  
	foreach($all as $v){ 
		$arr  =  $v->toArray();  
		dp_get_node_row($arr);
		$list[] = $arr;
	}
	return $list;
}
/**
*
* get_node(1)
*/
function dp_get_node(int $node_id)
{
	$one = Node::load($node_id)->toArray();   
	return $one;
}
/**
* 取文件信息
*/
function dp_get_file($file_id)
{
	$file_storage = Drupal::entityTypeManager()->getStorage('file');
	$file = $file_storage->load($file_id); 
	if ($file instanceof FileInterface) {  
 	  $file_url = str_replace("public://", "/".PublicStream::basePath()."/", $file->getFileUri()); 
	  return [
	  		'name'=> $file->getFilename(),
	  		'uri' => $file->getFileUri(), 
	  		'url' => $file_url,
	  		'size'=> $file->getSize(),
	  		'ctime'=> $file->getCreatedTime(), 
	  		'mime' => $file->getMimeType(), 
	  ];
	}
} 
/**
* 处理node
*/
function dp_get_node_row(&$all)
{
	foreach($all as $k=>&$v){
		if(strpos($k,'_image')!==false){
			foreach($v as &$vv){
				$value = dp_get_file($vv['target_id']);
				$all[$k."_value"]  = $value['url'];	
				$vv['values'] = $value;	
			} 
		}
		if(strpos($k,'_term_id')!==false){
			foreach($v as &$vv){
				$value = dp_get_term($vv['target_id']);
				$all[$k."_value"]  = $value['name'];	
				$vv['values'] = $value;	
			} 
		}

		if($k == 'uid'){
			foreach($v as &$vv){ 
				$value = dp_get_user($vv['target_id']);
				$all[$k."_value"]  = $value['name'];	
				$vv['values'] = $value;	
			} 
		}
	}
	//处理value
	foreach($all as $k=>&$val){
		if(!$val[1] && isset($val[0]['value'])){
				$val = $val[0]['value'];
		} 
	}
	$all['created_at'] = date("Y-m-d H:i:s",$all['created']);
	$all['updated_at'] = date("Y-m-d H:i:s",$all['changed']); 
}
/**
* json
*/
function dp_json(array $data = [])
{ 
  // 构建要返回的数据
  $data['_time'] = date("Y-m-d H:i:s"); 
  // 创建JSON响应
  $response = new JsonResponse($data); 
  // 设置响应头，指定JSON格式
  $response->headers->set('Content-Type', 'application/json'); 
  $response->send(); 
  exit();
}
/**
* 取用户信息
*/
function dp_get_user($uid,$field = [])
{  
	$user = \Drupal::entityTypeManager()->getStorage('user')->load($uid); 
	$entity_field_manager = \Drupal::service('entity_field.manager');
	$list['id']    = $uid; 
	$list['name']  = $user->getAccountName();
	$list['email'] = $user->getEmail();
	if($field){
		foreach($field as $v){
			$list[$v] = $user->get($v)->value;
		}
	}
	return $list;
}
/**
* 取term
*/
function dp_get_term($term_id)
{
	 $term = Drupal::entityTypeManager()
	  ->getStorage('taxonomy_term')->load($term_id);
	 $list = [];
	 if ($term && $term instanceof Term) { 
		  $name = $term->getName();
		  $desc = $term->getDescription();
		  $list = [
		  	'name'=>$name,
		  	'desc'=>$desc,
		  ];
	 }
	 return $list;
}
/**
* 取所有term
*/
function dp_get_terms()
{
	$terms = Drupal::entityTypeManager()
	  ->getStorage('taxonomy_term')
	  ->loadMultiple();
	$list = [];
  foreach ($terms as $term) {
  		$name = $term->getName();
		  $desc = $term->getDescription();
		  $list[] = [
		  	'name'=>$name,
		  	'desc'=>$desc,
		  ];
	}
	return $list;
}

function dp_get_terms_tree($taxonomy_name)
{ 
	$list = []; 
	$term_tree = \Drupal::service('entity_type.manager')
	  ->getStorage('taxonomy_term')
	  ->loadTree($taxonomy_name);  
	foreach ($term_tree as $term) { 
	  // 获取分类词汇名称及其相关属性 
	  $tid = $term->tid;
	  $name = $term->name;
	  $desc = $term->description;
	  $depth = $term->depth;
	  $list[] = [ 
		  	'id'  =>$tid,
		  	'name'=>$name,
		  	'desc'=>$desc,
		  	'pid'=>$depth,
		];
	}
	$res = dp_array_to_tree($list);
	return array_values($res);
}

function dp_array_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0, $my_id = '')
{
    $tree = array();
    if (is_array($list)) {
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    if ($my_id && $my_id == $list[$key]['id']) {
                    } else {
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
    }
    return $tree;
}