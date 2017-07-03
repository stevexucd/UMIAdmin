<?php

namespace YM\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class UmiModel
{
    #此模型不对应任何数据表, 仅做动态数据查询
    #this model does not relate any data table, only for dynamic data operation

    protected $openCache = true;
    protected $CacheSmallThan = 100;     //根据此值是否决定缓存  will no be cached when over sized

    private $cachedTable;
    private $tableName;
    private $orderBy;
    private $order;

    public function __construct($tableName, $orderBy = '', $order = '')
    {
        $this->tableName = $tableName;
        $this->orderBy = $orderBy;
        $this->order = $order;

        if ($this->openCache && $tableName != ''){
            $minute = Config::get('umi.cache_minutes');

            #根据设定的值大小 是否进行缓存整个数据表 并且缓存此次数据库查询记录数
            #according to the size of number to see if cache the whole data table, and cache the amount number
            $tableCount = Cache::remember($tableName.'count', $minute, function () use ($tableName) {
                return DB::table($tableName)->count();
            });

            if ($tableCount > $this->CacheSmallThan) {
                $this->openCache = false;
                return;
            }

            $this->cachedTable = Cache::remember($tableName, $minute, function () use ($tableName, $orderBy, $order) {
                if ($orderBy === '' && $order === '')
                    return DB::table($tableName)->get();

                return DB::table($tableName)->orderBy($orderBy, $order)->get();
            });
        }
    }

    public function getRowById($id)
    {
        $minute = Config::get('umi.cache_minutes');

        if ($this->openCache) {
            return Cache::has($this->tableName) ?
                Cache::get($this->tableName)->where('id', $id)->first():
                Cache::remember($this->tableName . 'getRowById', $minute, function () use ($id) {
                    return DB::table($this->tableName)
                        ->where('id', $id)
                        ->first();
                });
        }

        return DB::table($this->tableName)
            ->whereIn('id', $id)
            ->first();
    }

    public function getRecordsByFields($fields)
    {
        $page = 1;//todo - per page need to go through config file, this is for test //Config::get('umi.umi_table_perPage');
        return DB::table($this->tableName)
            ->select($fields)
            ->paginate($page);
    }

    public function getRecordsByWhere($where, $value)
    {
        if ($this->openCache) {
            return $this->cachedTable
                ->where($where, $value);
        }

        $ds = DB::table($this->tableName)
            ->where($where, $value);

        if ($this->orderBy === '' && $this->order === '')
            return $ds->get();

        return $ds->order($this->orderBy, $this->order)
            ->get();
    }

    public function getSelectedTable($fields)
    {
        return DB::table($this->tableName)
            ->select($fields);
    }

    public function delete($id)
    {
        return DB::table($this->tableName)->whereId($id)->delete();
    }

    public function update($input)
    {
        $primaryKey = Config::get('umi.primary_key');
        $recordId = $input[$primaryKey];
        $fields = $this->filterFields($input);

        try {
            $count = DB::table($this->tableName)
                ->where($primaryKey, $recordId)
                ->update($fields);
            Cache::pull($this->tableName);
        } catch (\Exception $exception) {
            $count = false;
        }

        return $count;
    }

    public function insert($fieldsArr, $timestamps = false)
    {
        $fields = $this->filterFields($fieldsArr);
        if ($timestamps) {
            $fields['created_at'] = date('Y-m-d h:i:s');
            $fields['updated_at'] = date('Y-m-d h:i:s');
        }

        try {
            $count = DB::table($this->tableName)->insert($fields);
        } catch (\Exception $exception) {
            $count = false;
        }
        return $count;
    }

    public function getTableFields($tableName)
    {
        $db = env('DB_DATABASE');
        $fields = DB::table('information_schema.COLUMNS')
            ->select('COLUMN_NAME as name')
            ->where(['TABLE_SCHEMA' => $db, 'TABLE_NAME' => $tableName])
            ->get()
            ->map(function ($item) {
                return $item->name;
            });

        return $fields;
    }

#region private method
    private function filterFields($fields)
    {
        $filter = Config::get("umiEnum.fillable.$this->tableName");
        $re = [];
        array_filter(array_keys($fields), function ($key) use ($filter, &$re, $fields) {
            if (in_array($key, $filter))
                $re[$key] = $fields[$key];
        });

        if (in_array('created_at', array_keys($fields))) {
            $re['created_at'] = $fields['created_at'];
        }
        if (in_array('updated_at', array_keys($fields))) {
            $re['updated_at'] = $fields['updated_at'];
        }
        return $re;
    }
#endregion
}