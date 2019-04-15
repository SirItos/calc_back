<?php

namespace App\Http\Controllers;

use App\Models;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Connection;

use PDO;


class StoredPorcedurController extends Controller
{

//create sql string Заготовка
    private function CreateSqlString($st_method,$pr_method,$arr_res=[],$useStamp=false,$useMain=false){
        $user_id = Auth::user()->id;
        $str = 'EXEC p_'.$st_method.$pr_method.' '.$user_id;
        foreach ($this->clear_arrr($arr_res,$st_method,$useStamp,$useMain) as $key=>$item){
//            преобразуем примари id в число
//            добавляем кавычки если тип не число
            if (array_key_exists('type',$arr_res)) {
                $tmp_str = ($arr_res['type'][$key] == 'bigint' || $arr_res['type'][$key] == 'bigint identity') ? $item : "'" . $item . "'";
//            убираем тире для дат
                $tmp_str = ($arr_res['type'][$key] == 'datetime') ? str_replace('-', '', $tmp_str) : $tmp_str;
            }else{
                $tmp_str=$item;
            }
                $str .= ',' . $tmp_str;

        }
        return $str;
    }

//Get Data from SQL
    protected function getData(Request $request){
        $user_id = Auth::user()->id;
        $result = $this->getMeta($this->CreateSqlString($request->st_method,'GetData'),array($user_id,$request->id));
        return $result;
    }
    protected  function getList(Request $request){
        $result = $this->getMeta($this->CreateSqlString($request->st_method,'GetList'));
        $result=$this->pagination($request->pagination,$result,$request->search);
        return $result;
    }

//    Add data to SQL
    protected  function addData(Request $request){
//        $result=$this->CreateSqlString($request->st_method,'Add',$request->params_arr,false);
        $result = DB::select($this->CreateSqlString($request->st_method,'Add',$request->params_arr));
        return $result;
    }

    //Edit data to SQL
    protected function editData(Request $request){
//        $result=$this->CreateSqlString($request->st_method,'Edit',$request->params_arr,true,true);
        $result = DB::select($this->CreateSqlString($request->st_method,'Edit',$request->params_arr,true,true));
        return $result;
    }

//    Delete data from SQL
    protected  function deleteData(Request $request){
//        $result=$this->CreateSqlString($request->st_method,'Del',$request->params_arr,true);
        $result = DB::select($this->CreateSqlString($request->st_method,'Del',$request->params_arr,true));
//        $result=hex2bin($request->stamp);
        return $result;
    }

//    get list for select
    protected function getEnumList(Request $request){
        $user_id = Auth::user()->id;
        $result = DB::select('EXEC p_'.$request->st_method.'GetEnum ?,?',array($user_id,$request->status));
        return $result;
    }


    protected function addSelectData(Request $request){
        $result = array();
        $tmparr = $request->params_arr;
        foreach ($request->params_arr[$request->field_to_search] as $key => $value){
            foreach ($request->fields_with_arr as $key_2 => $value_2){
                $tmparr[$value_2] = $request->params_arr[$value_2][$key];
            }

            $result[] = DB::select($this->CreateSqlString($request->st_method,'Add',$tmparr));
        }
        return $result;
    }
// Edit for table with multi depend
    protected function editSelectData(Request $request){
        $tmpArr= $request->params;
        $terr_list = $request->cic_arr;
        $all_list= DB::select($this->CreateSqlString($request->st_method,'GetList'));
        foreach ($request->params[$request->st_method.'_ID'] as $primary=>$value){
            $tmpArr[$request->st_method.'_ID']=$value;
            $elem =array_values(array_filter($all_list,function($elem) use($request,$value){
                    return $elem->{$request->st_method.'_ID'} === $value;
                }));
            if ($elem){
                if (array_key_exists($primary,$request->cic_arr)) {
                    $tmpArr[$request->find_in] = $request->cic_arr[$primary];
                    $tmpArr['RecordTimestamp'] = $request->params['RecordTimestamp'][$primary];
                    $result[] = DB::select($this->CreateSqlString($request->st_method, 'Edit', $tmpArr, true, true));
                    array_splice($terr_list, array_search($request->cic_arr[$primary], $terr_list), 1);
                }
            }

        }
        foreach ($terr_list as $key=>$last){
            $tmpArr[$request->find_in] = $last;
            $result[]=DB::select($this->CreateSqlString($request->st_method,'Add',$tmpArr));
        }
        $this->deletOnEdit($all_list,$request->params,array(
               'search'=>$request->find_in,
               'stamp'=>$request->params['RecordTimestamp'],
                'table'=>$request->st_method
        ));
        return $result;
    }

    protected function deletOnEdit($sql_data, $edit_data,$props){
        foreach ($sql_data as $row){
            if(array_search($row->{$props['search']},$edit_data[$props['search']])===false){
                DB::select($this->CreateSqlString($props['table'],'Del',array(
                    'id'=>$row->{$props['table'].'_ID'},
                    'stamp'=>$edit_data['RecordTimestamp'][array_search($row->{$props['table'].'_ID'},$edit_data[$props['table'].'_ID'])]
                ),true));
            }
        }
    }
    protected function deleteSelectData(Request $request){
        $result = array();
        foreach ($request ->params_arr['id'] as $key => $value ){
            $result[] = DB::select($this->CreateSqlString($request->st_method,'Del',array(
                'id'=>$value,
                'stamp'=>$request->params_arr['RecordTimestamp'][$key]
            ),true));
        }
        return $result;
    }



    protected function getListSelect(Request $request){
        $sql_call = $this->getMeta($this->CreateSqlString($request->st_method,'GetList'));
        $grouped = $this->group_arr($sql_call,$request->grouping);
        $merged = $this->merge_arr($grouped, $request->fields_to_merge);
        if ($merged){
            $result = $this->pagination($request->pagination,$merged,$request->search);
        }else{
            $result=array();
        }

        return $result;
    }

    private function group_arr($arr,$field){
        $result = array();
        foreach ($arr as $key=>$item){
            $result[$item->{$field}][] = $item;
        }
        return $result;
    }

    private function merge_arr($arr,$fields){
        $array = json_decode(json_encode($arr), True);
        $fields_arr = json_decode(json_encode($fields), True);
        $result = null;
        $res = null;
        foreach ($array as $item){
            $result = $item[count($item)-1];
            foreach ($item as $val){
                foreach ($fields_arr as $field){
                    if (gettype($result[$field])==='string')
                        $result[$field]=array();
                    $result[$field][]=$val[$field] ;
                }
            }
            $res[]=$result;
        }
        return $res;
    }

    //Function to get meta data from SQL Call
    private function GetMeta($call,$array_settings=[]){
        $result=[];
        $pdo = DB::connection()->getPDO();
        $query = $pdo->prepare($call);
        $query -> execute($array_settings);
        foreach (range(0,$query->columnCount()-1) as $column_index){
            $meta[$query->getColumnMeta($column_index)['name']]=$query->getColumnMeta($column_index)['sqlsrv:decl_type'];
        }
        $resultQuery= $query->fetchAll(\PDO::FETCH_OBJ);
        foreach ($resultQuery as $row){
            $row->type= $meta;
            $result[]=$row;

        }
        return $resultQuery;
    }







    private function  clear_arrr($arr,$mainKey,$stamp,$useMain){
        $check_arr=array(
                'type'=>'',
                'status'=>'',
        );
        if (!$stamp){ $check_arr['RecordTimestamp']='';
        }
        if (!$useMain){
            $check_arr[$mainKey.'ID']='';
            $check_arr[$mainKey.'_ID']='';
        }
        return array_diff_key($arr,$check_arr);
    }



    //    Get table Scheme
    protected function getScheme(Request $request){
        $result =[];
        $type=[];
        $columns = DB::select("select * from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='$request->table'");
        foreach ($columns as $column){
            if (stripos($column->COLUMN_NAME,'Record')===false ){
                if (stripos($column->COLUMN_NAME,'ID')!==false){
                    $result[str_replace('ID','_ID',$column->COLUMN_NAME)]="";
                    $type[str_replace('ID','_ID',$column->COLUMN_NAME)]=$column->DATA_TYPE;
                }else{
                    $result[$column->COLUMN_NAME]='';
                    $type[$column->COLUMN_NAME]=$column->DATA_TYPE;
                }
            }

        }
        $type['RecordTimestamp']="bigint";
        $result['type']=$type;
        return  $result;

    }

//    Paginatioon and sorting
    public function pagination($pagination,$res,$search){
        $tmpR = json_decode(json_encode($res), True);

        if ($pagination['sortBy']!==null){
            if ($pagination['descending'] !==false){
                usort($tmpR, function($a,$b) use($pagination){
                    if ($this->pagination_merge($a[$pagination['sortBy']]) === $this->pagination_merge($b[$pagination['sortBy']]))
                        return 0;
                    return $this->pagination_merge($a[$pagination['sortBy']]) < $this->pagination_merge($b[$pagination['sortBy']]) ? 1: -1;
                });

            }else{
                 usort($tmpR, function($a,$b) use($pagination){
                    if ($this->pagination_merge($a[$pagination['sortBy']]) === $this->pagination_merge($b[$pagination['sortBy']]))
                        return 0;
                    return $this->pagination_merge($a[$pagination['sortBy']]) > $this->pagination_merge($b[$pagination['sortBy']]) ? 1: -1;
                });
            }

        }
        $tmpResult = ($search==='' || $search===null)?$tmpR:$this->getSearch($tmpR,$search);
        if ($pagination['rowsPerPage'] > 0){
            $result = array_slice($tmpResult,($pagination['page']-1) * $pagination['rowsPerPage'],$pagination['rowsPerPage']);
        }
        return array(
            'totalNumber'=>Count($tmpResult),
            'result'=>$result
            );


    }

//    merege item from array to string
private function pagination_merge($itm){
        $result='';
        if (gettype($itm) !== 'array'){
            $result=strtolower($itm);
        }else{
            foreach ($itm as $value){   
                $result.=strtolower($value);
            }
        }
    return $result;
}

//Search
protected function getSearch($arr,$search){
    return array_filter($arr,function($elem) use($search){
        $result = false;
            foreach($elem as $key=>$value){
                if ($key!=='type' && $key!=='RecordTimestamp') {
                    $result = (stripos ($this->pagination_merge($value), strtolower($search)) !== false) ? true : $result;
                }
            }
            return $result;
    });
}




}
