<?php

namespace App\Models;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class  CodeGenerator
{
    private
        $modelSnakeName,
        $modelCamelName,
        $modelVariableName,
        $modelViewsDir,
        $modelCamelLcFirstName,
        $modelDatabaseName;

    public function __construct(string $modelName)
    {
        $this->modelName = ucfirst($modelName);
        $this->modelSnakeName = Str::snake($modelName);
        $this->modelCamelName = Str::studly($modelName);
        $this->modelCamelLcFirstName = lcfirst($this->modelCamelName);

        $this->modelVariableName = '$'.lcfirst($this->modelCamelName);
        $this->modelViewsDir = resource_path('views/admin/'.$this->modelSnakeName);
        $this->modelDatabaseName = $this->modelSnakeName.'s';
        $this->files = [
            'model' => app_path('Models/'.$modelName.'.php'),
            'controller' => app_path('Http/Controllers/'.$modelName.'Controller.php'),
            'viewIndex' => resource_path('views/admin/models/'.$this->modelSnakeName.'/index.blade.php'),
            'viewList' => resource_path('views/admin/models/'.$this->modelSnakeName.'/list.blade.php'),
            'viewEdit' => resource_path('views/admin/models/'.$this->modelSnakeName.'/edit.blade.php'),
        ];
    }

    public function generate(){
        try {
            $this->checkFilesExist();
            $this->createFiles();
        }catch (\Throwable $exception){
            dd($exception);
        }
    }

    public function rollback(){
        $this->destroyFiles();
        $this->deleteDatabaseTable();
    }

    private function checkFilesExist(){
        $exists_files_message = [];
        foreach($this->files as $file){
            if(file_exists($file)){
                $exists_files_message[] = 'Файл '.$file. ' существует';
            }
        }
        if(count($exists_files_message) > 0){
            $message = implode(', ',$exists_files_message);
            throw new \Exception($message. ', создание кода невозможно');
        }
    }

    private function createFiles(){
        try {
            $adminViewsDir = resource_path('views/admin');
            if(!is_dir($adminViewsDir)){
                mkdir($adminViewsDir);
            }
            $adminModelsViewsDir = resource_path('views/admin/models');
            if(!is_dir($adminModelsViewsDir)){
                mkdir($adminModelsViewsDir);
            }
            $modelViewsDir = resource_path('views/admin/models/'.$this->modelSnakeName);
            if(!file_exists( $modelViewsDir ) && !is_dir( $modelViewsDir ) ){
                mkdir($modelViewsDir);
            }
            $this->createDatabaseTable();
            foreach($this->files as $modelName => $file){
                $methodName = 'create'.ucfirst($modelName).'File';
                $this->$methodName();
            }
        }catch (\Throwable $exception){
            $this->rollback();
            dd($exception);
            throw new \Exception($exception->getMessage());
        }
    }

    private function destroyFiles(){
        foreach($this->files as $modelName => $file){
            if(file_exists($file)){
                unlink($file);
            }
        }
        try{
            $files = scandir($this->modelViewsDir);
            if(count($files) >= 2){
                rmdir($this->modelViewsDir);
            }
        }catch (\Throwable $exception){

        }
    }

    private function createModelFile(){
        $fileContent = <<< CONTENT
<?php

namespace App\Models;

use App\Traits\SingleImage;
use App\Traits\SortOrderChangeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class $this->modelName extends Model
{
    use HasFactory, SoftDeletes, SortOrderChangeable, SingleImage;
    protected \$guarded = ['id'];

    public function getPageAttribute(){
        \$route_name = '{$this->modelCamelLcFirstName}Page';
        return Route::has(\$route_name)?route(\$route_name,\$this):null;
    }
}
CONTENT;
        file_put_contents($this->files['model'], $fileContent);
    }

    private function createControllerFile(){
        $lcfistModelName = lcfirst($this->modelCamelName);
        $fileContent = <<< CONTENT
<?php

namespace App\Http\Controllers;

use App\Models\\{$this->modelCamelName};
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
Route::group(['prefix' => '{$this->modelSnakeName}', 'as' => '{$this->modelSnakeName}.'], function () {
        //{$this->modelCamelName}
        Route::get('', [{$this->modelCamelName}Controller::class, 'index'])->name('index');
        Route::get('edit/{{$lcfistModelName}?}', [{$this->modelCamelName}Controller::class, 'edit'])->name('edit');
        Route::post('update/{{$lcfistModelName}?}', [{$this->modelCamelName}Controller::class, 'update'])->name('update');
        Route::get('delete/{{$lcfistModelName}}', [{$this->modelCamelName}Controller::class, 'delete'])->name('delete');
        Route::get('change-order/{{$lcfistModelName}}/{direction}', [{$this->modelCamelName}Controller::class, 'changeOrder'])->name('change-order');
});
Route::get('/{{$this->modelCamelLcFirstName}:slug?}', [{$this->modelCamelName}Controller::class, 'show'])->name('{$this->modelCamelLcFirstName}Page');
*/

class {$this->modelCamelName}Controller extends Controller
{
    function index(){
        \$breadcrumbs = [
            ['url' => route('admin.{$this->modelSnakeName}.index'), 'label' => '{$this->modelCamelName}']
        ];
        {$this->modelVariableName}s = {$this->modelCamelName}::orderBy('sort_order')->get();
        return view('admin.models.{$this->modelSnakeName}.index', get_defined_vars());
    }
    function edit({$this->modelCamelName} {$this->modelVariableName}){
        \$breadcrumbs = [
            ['url' => route('admin.{$this->modelSnakeName}.index'), 'label' => '{$this->modelCamelName}'],
            ['url' => route('admin.{$this->modelSnakeName}.edit', {$this->modelVariableName}), 'label' => {$this->modelVariableName}->name??'новая {$this->modelCamelName}']
        ];
        return view('admin.models.$this->modelSnakeName.edit', get_defined_vars());
    }
    function update({$this->modelCamelName} {$this->modelVariableName}){
        {$this->modelVariableName}->fill(request()->all());
        {$this->modelVariableName}->slug = Str::slug(Str::lower(trim(request('slug')??request('name'))));
        if(!{$this->modelVariableName}->exists){
            {$this->modelVariableName}->sort_order = {$this->modelCamelName}::max('sort_order')+1;
        }
        {$this->modelVariableName}->saveImageFromRequest(1);
        {$this->modelVariableName}->save();
        return redirect()->route('admin.{$this->modelSnakeName}.edit', {$this->modelVariableName});
    }
    function delete({$this->modelCamelName} {$this->modelVariableName}){
        {$this->modelVariableName}->delete();
        return redirect()->route('admin.$this->modelSnakeName.index');
    }
    function show({$this->modelCamelName} {$this->modelVariableName}){
        return view('front.pages.$this->modelSnakeName', get_defined_vars());
    }
    function changeOrder({$this->modelCamelName} {$this->modelVariableName}, \$direction){
        {$this->modelVariableName}->simpleChangeOrder(\$direction);
        {$this->modelVariableName}s = {$this->modelCamelName}::orderBy('sort_order')->get();
        return view('admin.models.$this->modelSnakeName.list', get_defined_vars());
    }
}
CONTENT;
        file_put_contents($this->files['controller'], $fileContent);
    }

    private function createViewIndexFile(){
        $fileContent = <<< CONTENT
@extends('admin.common.layout')
@section('content')
    <table class="uk-table uk-table-striped uk-table-hover uk-table-small uk-table-responsive">
        <thead>
        </thead>
        <tbody data-entities-list>
        @include('admin.models.{$this->modelSnakeName}.list')
        </tbody>
    </table>
    <div class="edit-tools">
        <a href="{{route('admin.{$this->modelSnakeName}.edit')}}" class="tool add" title="создать"></a>
    </div>
@endsection
{{-- <li><a href="{{ route('admin.{$this->modelSnakeName}.index') }}">{$this->modelCamelName}</a></li> --}}
CONTENT;
        file_put_contents($this->files['viewIndex'], $fileContent);
    }

    private function createViewListFile(){
        $lcfistModelName = lcfirst($this->modelCamelName);
        $fileContent = <<< CONTENT
@foreach({$this->modelVariableName}s as {$this->modelVariableName})
    <tr class="entity" data-entity-id="{{{$this->modelVariableName}->id}}">
        <td>{{ {$this->modelVariableName}->id }}</td>
        <td>{{ {$this->modelVariableName}->name }}</td>
        <td><a href="{{ route("admin.{$this->modelSnakeName}.edit", {$this->modelVariableName}) }}">редактировать</a></td>
        @include('admin.common.change-order-tool',['up' => route('admin.{$this->modelSnakeName}.change-order', ['{$lcfistModelName}' => {$this->modelVariableName}, 'direction' => 'up']), 'down' => route('admin.{$this->modelSnakeName}.change-order', ['{$lcfistModelName}' => {$this->modelVariableName}, 'direction' => 'down'])])
        <td><a onclick="return confirm('Вы уверены?')" class="btn btn-danger" href="{{ route("admin.{$this->modelSnakeName}.delete", {$this->modelVariableName}) }}">удалить</a></td>
    </tr>
@endforeach
CONTENT;
        file_put_contents($this->files['viewList'], $fileContent);
    }

    private function createViewEditFile(){
        $fileContent = <<< CONTENT
@extends('admin.common.layout')
@section('content')
    <form id="edit-form" class="uk-padding-small uk-form-horizontal" method="POST" action="{{route('admin.{$this->modelSnakeName}.update', {$this->modelVariableName})}}" enctype="multipart/form-data">
        @csrf
        <div class="uk-margin">
            <label class="uk-form-label">Название</label>
            <div class="uk-form-controls"><input required class="uk-input uk-form-width-large" type="text" name="name" value="{{ {$this->modelVariableName}->name }}"></div>
        </div>
        <div class="uk-margin">
            @if({$this->modelVariableName}->page)
            <label class="uk-form-label"><a class="open-page" href="{{ {$this->modelVariableName}->page }}" target="_blank">slug</a></label>
            @else
            <label class="uk-form-label"><span>slug</span></label>
            @endif
            <div class="uk-form-controls"><input class="uk-input uk-form-width-large" type="text" name="slug" value="{{ {$this->modelVariableName}->slug }}"></div>
        </div>
        <div class="uk-margin">
            <label class="uk-form-label">Описание</label>
            <div class="uk-form-controls"><textarea style="height: 500px;" class="uk-input uk-form-width-large" type="text" name="description">{{ {$this->modelVariableName}->description }}</textarea></div>
            @push('scripts','initTinyMce({width:1000, selector:\'[name="description"]\', fileprefix:"'.(class_basename(\App\Models\\{$this->modelCamelName}::class)).'/'.{$this->modelVariableName}->id.'"});')
        </div>
        {{ {$this->modelVariableName}->uploadImageTool(1) }}
    </form>
    <div class="edit-tools">
        <button type="submit" form="edit-form" class="tool save" title="сохранить"></button>
    </div>
@endsection
CONTENT;
        file_put_contents($this->files['viewEdit'], $fileContent);
    }

    private function createDatabaseTable(){
        Schema::create($this->modelDatabaseName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function deleteDatabaseTable(){
        Schema::dropIfExists($this->modelDatabaseName);
    }

}

