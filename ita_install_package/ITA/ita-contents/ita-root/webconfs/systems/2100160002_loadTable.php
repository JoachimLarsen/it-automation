<?php
//   Copyright 2019 NEC Corporation
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
/* ルートディレクトリの取得 */
if ( empty($root_dir_path) ){
    $root_dir_temp = array();
    $root_dir_temp = explode( "ita-root", dirname(__FILE__) );
    $root_dir_path = $root_dir_temp[0] . "ita-root";
}

require_once ( $root_dir_path . "/libs/webindividuallibs/systems/2100160001/validator.php");
$tmpFx = function (&$aryVariant=array(),&$arySetting=array()){
    global $g;

    $arrayWebSetting = array();
    $arrayWebSetting['page_info'] = $g['objMTS']->getSomeMessage("ITACREPAR-MNU-102101");

    $tmpAry = array(
        'TT_SYS_01_JNL_SEQ_ID'=>'JOURNAL_SEQ_NO',
        'TT_SYS_02_JNL_TIME_ID'=>'JOURNAL_REG_DATETIME',
        'TT_SYS_03_JNL_CLASS_ID'=>'JOURNAL_ACTION_CLASS',
        'TT_SYS_04_NOTE_ID'=>'NOTE',
        'TT_SYS_04_DISUSE_FLAG_ID'=>'DISUSE_FLAG',
        'TT_SYS_05_LUP_TIME_ID'=>'LAST_UPDATE_TIMESTAMP',
        'TT_SYS_06_LUP_USER_ID'=>'LAST_UPDATE_USER',
        'TT_SYS_NDB_ROW_EDIT_BY_FILE_ID'=>'ROW_EDIT_BY_FILE',
        'TT_SYS_NDB_UPDATE_ID'=>'WEB_BUTTON_UPDATE',
        'TT_SYS_NDB_LUP_TIME_ID'=>'UPD_UPDATE_TIMESTAMP'
    );
 
    $table = new TableControlAgent('F_CREATE_ITEM_INFO','CREATE_ITEM_ID', $g['objMTS']->getSomeMessage("ITACREPAR-MNU-102102"), 'F_CREATE_ITEM_INFO_JNL', $tmpAry);
    $tmpAryColumn = $table->getColumns();
    $tmpAryColumn['CREATE_ITEM_ID']->setSequenceID('F_CREATE_ITEM_INFO_RIC');
    $tmpAryColumn['JOURNAL_SEQ_NO']->setSequenceID('F_CREATE_ITEM_INFO_JSQ');
    unset($tmpAryColumn);

    // QMファイル名プレフィックス
    $table->setDBMainTableLabel($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102103"));
    // エクセルのシート名
    $table->getFormatter('excel')->setGeneValue('sheetNameForEditByFile', $g['objMTS']->getSomeMessage("ITACREPAR-MNU-102104"));

    $table->setAccessAuth(true);    // データごとのRBAC設定


    // メニュー
    $c = new IDColumn('CREATE_MENU_ID',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102105"),'F_CREATE_MENU_INFO','CREATE_MENU_ID','MENU_NAME','');
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102106"));//エクセル・ヘッダでの説明
    $c->setRequired(true);//登録/更新時には、入力必須
    $table->addColumn($c);


    // 項目名
    $objVldt = new ItemNameValidator(1, 256, false);
    $c = new TextColumn('ITEM_NAME',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102107"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102108"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->setValidator($objVldt);
    $c->setRequired(true);//登録/更新時には、入力必須
    $table->addColumn($c);


    // 表示順序
    $c = new NumColumn('DISP_SEQ', $g['objMTS']->getSomeMessage("ITACREPAR-MNU-102111"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102112"));
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->setValidator(new IntNumValidator());
    $c->setRequired(true);//登録/更新時には、入力必須
    $c->setSubtotalFlag(false);
    $table->addColumn($c);

    // 必須
    $c = new IDColumn('REQUIRED',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102113"),'G_REQUIRED_MASTER','REQUIRED_ID','REQUIRED_NAME','');
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102114"));//エクセル・ヘッダでの説明
    $table->addColumn($c);


    // 一意制約
    $c = new IDColumn('UNIQUED',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102119"),'G_REQUIRED_MASTER','REQUIRED_ID','REQUIRED_NAME','');
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102120"));//エクセル・ヘッダでの説明
    $table->addColumn($c);


    // 親カラムグループ
    $c = new IDColumn('COL_GROUP_ID',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102127"),'F_COLUMN_GROUP','COL_GROUP_ID','FULL_COL_GROUP_NAME','',array('OrderByThirdColumn'=>'FULL_COL_GROUP_NAME'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102128"));//エクセル・ヘッダでの説明
    $table->addColumn($c);


    // 入力方式
    $c = new IDColumn('INPUT_METHOD_ID',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102121"),'F_INPUT_METHOD','INPUT_METHOD_ID','INPUT_METHOD_NAME','', array('OrderByThirdColumn'=>'INPUT_METHOD_ID'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102122"));//エクセル・ヘッダでの説明
    $c->setRequired(true);//登録/更新時には、入力必須
    $objVldt = new InputMethodValidator($c);
    $c->setValidator($objVldt);
    $table->addColumn($c);

    // 文字列(単一行)
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102125"));

    // 文字列(単一行)/最大バイト数
    $c = new NumColumn('MAX_LENGTH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102109"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102110"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->setSubtotalFlag(false);
    $c->setValidator(new IntNumValidator(1,8192));
    $cg->addColumn($c);

    // 文字列(単一行)/正規表現
    $c = new TextColumn('PREG_MATCH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102115"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102116"));//エクセル・ヘッダでの説明
    $c->setValidator(new PregMatchValidator(0,8192));
    $cg->addColumn($c);

    // 文字列(単一行)/初期値
    $c = new TextColumn('SINGLE_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102152"));//エクセル・ヘッダでの説明
    $c->setValidator(new SingleDefaultValueValidator(0,8192));
    $cg->addColumn($c);

    $table->addColumn($cg);

    // 文字列(複数行)
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102141"));

    // 文字列(複数行)/最大バイト数
    $c = new NumColumn('MULTI_MAX_LENGTH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102109"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102110"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->setSubtotalFlag(false);
    $c->setValidator(new IntNumValidator(1,8192));
    $cg->addColumn($c);

    // 文字列(複数行)/正規表現
    $c = new TextColumn('MULTI_PREG_MATCH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102115"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102116"));//エクセル・ヘッダでの説明
    $c->setValidator(new PregMatchValidator(0,8192));
    $cg->addColumn($c);

    // 文字列(複数行)/初期値
    $c = new MultiTextColumn('MULTI_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102152"));//エクセル・ヘッダでの説明
    $c->setValidator(new MultiDefaultValueValidator(0,8192));
    $cg->addColumn($c);

    $table->addColumn($cg);
    
    // 整数
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102129"));
    
    // 整数/最小値
    $objVldt = new IntNumValidator(null,null);
    $c = new NumColumn('INT_MIN',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102132"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102133"));
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    // 整数/最大値
    $objVldt = new IntNumValidator(null,null);
    $c = new NumColumn('INT_MAX',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102130"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102131"));
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    // 整数/初期値
    $c = new NumColumn('INT_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102153"));//エクセル・ヘッダでの説明
    $c->setSubtotalFlag(false);
    $c->setValidator(new IntDefaultValueValidator(null, null));
    $cg->addColumn($c);

    $table->addColumn($cg);

    // 小数
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102134"));
    
    // 小数/最小値
    $objVldt = new FloatNumValidator(-99999999999999,99999999999999,14);
    $c = new NumColumn('FLOAT_MIN',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102137"),14);
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102138"));
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    // 小数/最大値
    $objVldt = new FloatNumValidator(-99999999999999,99999999999999,14);
    $c = new NumColumn('FLOAT_MAX',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102135"),14);
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102136"));
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    // 小数/桁数
    $objVldt = new IntNumValidator(1,14);
    $c = new NumColumn('FLOAT_DIGIT',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102139"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102140"));
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    // 小数/初期値
    $c = new NumColumn('FLOAT_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"),14);
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102154"));//エクセル・ヘッダでの説明
    $c->setSubtotalFlag(false);
    $c->setValidator(new FloatDefaultValueValidator(-99999999999999,99999999999999,14));
    $cg->addColumn($c);

    $table->addColumn($cg);


    // 日時
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102149"));

    // 日時/初期値
    $c = new DateTimeColumn('DATETIME_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102155"));//エクセル・ヘッダでの説明
    $cg->addColumn($c);

    $table->addColumn($cg);

    // 日付
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102150"));

    // 日付/初期値
    $c = new DateColumn('DATE_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102155"));//エクセル・ヘッダでの説明
    $cg->addColumn($c);

    $table->addColumn($cg);


    // プルダウン選択
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102126"));

    // プルダウン選択/メニューグループ：メニュー：項目
    $c = new IDColumn('OTHER_MENU_LINK_ID',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102123"),'G_OTHER_MENU_LINK','LINK_ID','LINK_PULLDOWN','');
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102124"));//エクセル・ヘッダでの説明
    $cg->addColumn($c);

    // プルダウン選択/初期値
    $objVldt = new IntNumValidator(null,null);
    $c = new NumColumn('PULLDOWN_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102156"));//エクセル・ヘッダでの説明
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $c->setValidator(new PulldownDefaultValueValidator());
    $cg->addColumn($c);

    // 参照項目
    $c = new TextColumn('REFERENCE_ITEM',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102147"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102148"));//エクセル・ヘッダでの説明
    $objVldt = new ReferenceItemValidator(0, 4096, false);
    $c->setValidator($objVldt);
    $cg->addColumn($c);

    $table->addColumn($cg);

    // パスワード
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102142"));

    // パスワード/最大バイト数
    $c = new NumColumn('PW_MAX_LENGTH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102109"));
    $objVldt = new SingleTextValidator(0,30,false);
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102110"));//エクセル・ヘッダでの説明
    $c->setValidator($objVldt);
    $c->setSubtotalFlag(false);
    $cg->addColumn($c);

    $table->addColumn($cg);

    // ファイルアップロード
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102143"));

    // ファイルアップロード/ファイル最大バイト数
    $c = new NumColumn('UPLOAD_MAX_SIZE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102144"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102145"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->setSubtotalFlag(false);
    $c->setValidator(new IntNumValidator(1,4294967296));
    $cg->addColumn($c);


    $table->addColumn($cg);

    // リンク
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102146"));

    // リンク/最大バイト数
    $c = new NumColumn('LINK_LENGTH',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102109"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102110"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :inactive"');
    $c->setSubtotalFlag(false);
    $c->setValidator(new IntNumValidator(1,8192));
    $cg->addColumn($c);

    // リンク/初期値
    $c = new TextColumn('LINK_DEFAULT_VALUE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102151"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102157"));//エクセル・ヘッダでの説明
    $c->setValidator(new SingleDefaultValueValidator(0,8192));
    $cg->addColumn($c);

    $table->addColumn($cg);

    // パラメータシート参照
    $cg = new ColumnGroup($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102158"));

    // パラメータシート参照/メニューグループ：メニュー：項目
    $c = new IDColumn('TYPE3_REFERENCE',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102159"),'G_CREATE_REFERENCE_SHEET_TYPE_3','ITEM_ID','MENU_PULLDOWN','');
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102160"));//エクセル・ヘッダでの説明
    $cg->addColumn($c);

    $table->addColumn($cg);

    // 説明
    $objVldt = new MultiTextValidator(0,1024,false);
    $c = new MultiTextColumn('DESCRIPTION',$g['objMTS']->getSomeMessage("ITACREPAR-MNU-102117"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITACREPAR-MNU-102118"));//エクセル・ヘッダでの説明
    $c->getOutputType('filter_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->getOutputType('register_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->getOutputType('update_table')->setTextTagLastAttr('style = "ime-mode :active"');
    $c->setValidator($objVldt);
    $table->addColumn($c);

//----head of setting [multi-set-unique]
    $table->addUniqueColumnSet(array('CREATE_MENU_ID', 'ITEM_NAME', 'COL_GROUP_ID'));
//tail of setting [multi-set-unique]----

    $table->fixColumn();

    $table->setGeneObject('webSetting', $arrayWebSetting);
    return $table;
};
loadTableFunctionAdd($tmpFx,__FILE__);
unset($tmpFx);
?>
