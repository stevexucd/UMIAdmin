@extends('umi::layouts.model')

@section('content')

    <div class="col-sm-12">
        <h3 class="header smaller lighter blue">
            <i class="ace-icon fa fa-bullhorn"></i>
            Edit Fields
        </h3>

        {!! $message !!}
        @if (!$actionAvailable)
            <div class="alert alert-danger">
                Currently you can not edit this record until meet the requirements !
                <br /><br /><p>
                    <button class="btn btn-sm btn-danger disabled" disabled>Edit</button>
                    <button class="btn btn-sm btn-info" id="clsDelete">Close</button>
                </p>
            </div>
        @else
            <div class="alert alert-warning">
                <p>
                    <form class="form-horizontal" method="post" action="{{url("edit")}}/{{$table}}">
                        {!! csrf_field() !!}

                        {!! $display !!}

                        <input type="hidden" name="hidden_tn" value="{{$table}}">
                        <input type="hidden" name="hidden_ti" value="{{$recordId}}">
                        <input type="hidden" name="hidden_afv" value="{{$activeFieldValue}}">
                    </form>
                </p>
            </div>
        @endif

    </div>
<script>

    //关闭所有模态窗口
    //close all model windows
    $('#cls').click(function () {
        parent.layer.closeAll();
    });

    //生成一个蒙版和加载图标
    //create a shade and a loading icon
    $('#actionEdit').click(function () {
        layer.load(0, {
            shade: [0.8,'#000000']
        });
    });

</script>
@endsection
