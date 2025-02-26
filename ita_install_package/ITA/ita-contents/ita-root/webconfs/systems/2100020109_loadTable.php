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
//////////////////////////////////////////////////////////////////////
//
//  【処理概要】
//    ・WebDBCore機能を用いたWebページの中核設定を行う。
//  【特記事項】
//   ●●●●●●●● 
//    ・カラムの増減する場合「tmp_file_COL_IDSOP_17・del_flag_COL_IDSOP_17」
//      のKey名の変更が必要となる場合がある。
//   ●●●●●●●● 
//
//////////////////////////////////////////////////////////////////////

$tmpFx = function (&$aryVariant=array(),&$arySetting=array()){
    global $g;

    global $root_dir_path;
    if ( empty($root_dir_path) ){
        $root_dir_temp = array();
        $root_dir_temp = explode( "ita-root", dirname(__FILE__) );
        $root_dir_path = $root_dir_temp[0] . "ita-root";
    }

    // 共通モジュールをロード
    require_once ($root_dir_path . '/libs/backyardlibs/ansible_driver/AnsibleCommonLib.php');

    $arrayWebSetting = array();
    $arrayWebSetting['page_info'] = $g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302040");
/*
Ansible（Legacy）代入値管理
*/
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

    $table = new TableControlAgent('D_B_ANSIBLE_LNS_VARS_ASSIGN','ASSIGN_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302050"), 'D_B_ANSIBLE_LNS_VARS_ASSIGN_JNL', $tmpAry);
    $tmpAryColumn = $table->getColumns();
    $tmpAryColumn['ASSIGN_ID']->setSequenceID('B_ANSIBLE_LNS_VARS_ASSIGN_RIC');
    $tmpAryColumn['JOURNAL_SEQ_NO']->setSequenceID('B_ANSIBLE_LNS_VARS_ASSIGN_JSQ');
    unset($tmpAryColumn);

    // ----VIEWをコンテンツソースにする場合、構成する実体テーブルを更新するための設定
    $table->setDBMainTableHiddenID('B_ANSIBLE_LNS_VARS_ASSIGN');
    $table->setDBJournalTableHiddenID('B_ANSIBLE_LNS_VARS_ASSIGN_JNL');
    // 利用時は、更新対象カラムに、「$c->setHiddenMainTableColumn(true);」を付加すること
    // VIEWをコンテンツソースにする場合、構成する実体テーブルを更新するための設定----


    //動的プルダウンの作成用
    $table->setJsEventNamePrefix(true);


    // QMファイル名プレフィックス
    $table->setDBMainTableLabel($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302060"));
    // エクセルのシート名
    $table->getFormatter('excel')->setGeneValue('sheetNameForEditByFile',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302070"));

    $table->setAccessAuth(true);    // データごとのRBAC設定


    //----オペレーション
    $c = new IDColumn('OPERATION_NO_UAPK',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302080"),'E_OPERATION_LIST','OPERATION_NO_UAPK','OPERATION','E_OPE_FOR_PULLDOWN_LNS',array('OrderByThirdColumn'=>'OPERATION_NO_UAPK'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302090"));//エクセル・ヘッダでの説明

    $c->setEvent('update_table', 'onchange', 'operation_upd');
    $c->setEvent('register_table', 'onchange', 'operation_reg');

    $c->setJournalTableOfMaster('E_OPERATION_LIST_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('OPERATION_NO_UAPK');
    $c->setJournalDispIDOfMaster('OPERATION');
    $c->setRequired(true);//登録/更新時には、入力必須

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    $table->addColumn($c);
    //オペレーション----



    //----作業パターン
    $objFunction01 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $aryVariant['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.PATTERN_ID KEY_COLUMN "
                   .",TAB_2.PATTERN    DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK          TAB_1 "
                   ." LEFT JOIN E_ANSIBLE_LNS_PATTERN TAB_2 ON (TAB_1.PATTERN_ID = TAB_2.PATTERN_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ";

        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;

        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };

    $objFunction02 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $aryVariant['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.PATTERN_ID KEY_COLUMN "
                   .",TAB_2.PATTERN    DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK          TAB_1 "
                   ." LEFT JOIN E_ANSIBLE_LNS_PATTERN TAB_2 ON (TAB_1.PATTERN_ID = TAB_2.PATTERN_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ";
        
        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;
        
        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };
    
    $objFunction03 = function($objCellFormatter, $rowData, $aryVariant){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $rowData['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.PATTERN_ID KEY_COLUMN "
                   .",TAB_2.PATTERN    DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK          TAB_1 "
                   ." LEFT JOIN E_ANSIBLE_LNS_PATTERN TAB_2 ON (TAB_1.PATTERN_ID = TAB_2.PATTERN_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ";

        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;

        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[$row['KEY_COLUMN']]= $row['DISP_COLUMN'];
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $aryRetBody = array($retBool, $intErrorType, $aryErrMsgBody, $strErrMsg, $aryDataSet);
        return $aryRetBody;
    };

    // REST/excel/csv入力用 ホスト名
    $c = new IDColumn('REST_SYSTEM_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303040"),'E_ANS_LNS_STM_LIST','SYSTEM_ID','HOST_PULLDOWN','',array('OrderByThirdColumn'=>'SYSTEM_ID'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303050"));//エクセル・ヘッダでの説明

    //コンテンツのソースがヴューの場合、登録/更新の対象外
    $c->setHiddenMainTableColumn(false);

    //エクセル/CSVからのアップロード対象
    $c->setAllowSendFromFile(true);

    //REST/excel/csv以外は非表示
    $c->getOutputType('filter_table')->setVisible(false);
    $c->getOutputType('print_table')->setVisible(false);
    $c->getOutputType('update_table')->setVisible(false);
    $c->getOutputType('register_table')->setVisible(false);
    $c->getOutputType('delete_table')->setVisible(false);
    $c->getOutputType('print_journal_table')->setVisible(false);
    $c->getOutputType('excel')->setVisible(true);
    $c->getOutputType('csv')->setVisible(true);
    $c->getOutputType('json')->setVisible(true);

    $c->setJournalTableOfMaster('E_ANS_LNS_STM_LIST_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('SYSTEM_ID');
    $c->setJournalDispIDOfMaster('HOST_PULLDOWN');
    //登録/更新時には、必須でない
    $c->setRequired(false);
    $c->setRequiredMark(true);

    $table->addColumn($c);


    // REST/excel/csv入力用 Movement+変数名
    $c = new IDColumn('REST_VARS_LINK_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-302095"),'E_ANS_LNS_PTN_VAR_LIST','VARS_LINK_ID','PTN_VAR_PULLDOWN','',array('OrderByThirdColumn'=>'VARS_LINK_ID'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303080"));

    //コンテンツのソースがヴューの場合、登録/更新の対象外
    $c->setHiddenMainTableColumn(false);

    //エクセル/CSVからのアップロード対象
    $c->setAllowSendFromFile(true);

    //REST/excel/csv以外は非表示
    $c->getOutputType('filter_table')->setVisible(false);
    $c->getOutputType('print_table')->setVisible(false);
    $c->getOutputType('update_table')->setVisible(false);
    $c->getOutputType('register_table')->setVisible(false);
    $c->getOutputType('delete_table')->setVisible(false);
    $c->getOutputType('print_journal_table')->setVisible(false);
    $c->getOutputType('excel')->setVisible(true);
    $c->getOutputType('csv')->setVisible(true);
    $c->getOutputType('json')->setVisible(true);

    $c->setJournalTableOfMaster('E_ANS_LNS_PTN_VAR_LIST_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('VARS_LINK_ID');
    $c->setJournalDispIDOfMaster('PTN_VAR_PULLDOWN');
    //登録/更新時には、必須でない
    $c->setRequired(false);
    $c->setRequiredMark(true);

    $table->addColumn($c);


    // RestAPI/Excel/CSVからの登録の場合に組み合わせバリデータで退避したPATTERN_IDを設定する。
    $tmpObjFunction = function($objColumn, $strEventKey, &$exeQueryData, &$reqOrgData=array(), &$aryVariant=array()){
                global    $g;
                $boolRet = true;
                $intErrorType = null;
                $aryErrMsgBody = array();
                $strErrMsg = "";
                $strErrorBuf = "";

                $modeValue = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_MODE"];
                if( $modeValue=="DTUP_singleRecRegister" || $modeValue=="DTUP_singleRecUpdate" ){
                    if(strlen($g['PATTERN_ID_UPDATE_VALUE']) !== 0){
                        $exeQueryData[$objColumn->getID()] = $g['PATTERN_ID_UPDATE_VALUE'];
                    }
                }else if( $modeValue=="DTUP_singleRecDelete" ){
                }
                $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$strErrorBuf);
                return $retArray;
    };

    $c = new IDColumn('PATTERN_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303010"),'E_ANSIBLE_LNS_PATTERN','PATTERN_ID','PATTERN','',array('OrderByThirdColumn'=>'PATTERN_ID'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303020"));//エクセル・ヘッダでの説明

    $strSetInnerText = $g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303030");
    $objVarBFmtUpd = new SelectTabBFmt();
    $objVarBFmtUpd->setFADJsEvent('onChange','pattern_upd');
    $objVarBFmtUpd->setNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFunctionForGetSelectList($objFunction03);

    $objOTForUpd = new OutputType(new ReqTabHFmt(), $objVarBFmtUpd);
    $objOTForUpd->setFunctionForGetFADSelectList($objFunction01);

    $objVarBFmtReg = new SelectTabBFmt();
    $objVarBFmtReg->setFADJsEvent('onChange','pattern_reg');
    $objVarBFmtReg->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtReg->setFunctionForGetSelectList($objFunction03);

    $objVarBFmtReg->setSelectWaitingText($strSetInnerText);
    $objOTForReg = new OutputType(new ReqTabHFmt(), $objVarBFmtReg);
    $objOTForReg->setFunctionForGetFADSelectList($objFunction02);

    $c->setOutputType('update_table',$objOTForUpd);
    $c->setOutputType('register_table',$objOTForReg);

    $c->setEvent('update_table','onChange','pattern_upd',array());
    $c->setEvent('register_table','onChange','pattern_reg',array());

    $c->setJournalTableOfMaster('E_ANSIBLE_LNS_PATTERN_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('PATTERN_ID');
    $c->setJournalDispIDOfMaster('PATTERN');

    // 必須チェックは組合せバリデータで行う。
    $c->setRequired(false);
    $c->setRequiredMark(true);//必須マークのみ付与

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    //エクセル/CSVからのアップロードを禁止する。
    $c->setAllowSendFromFile(false);

    // REST/excel/csvで項目無効
    $c->getOutputType('excel')->setVisible(false);
    $c->getOutputType('csv')->setVisible(false);
    $c->getOutputType('json')->setVisible(false);

    // データベース更新前のファンクション登録
    $c->setFunctionForEvent('beforeTableIUDAction',$tmpObjFunction);

    $table->addColumn($c);
    //作業パターン----


    //----ホスト
    $objFunction01 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $aryVariant['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.SYSTEM_ID     KEY_COLUMN "
                   .",TAB_2.HOST_PULLDOWN DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK TAB_1 "
                   ." LEFT JOIN E_STM_LIST   TAB_2 ON (TAB_1.SYSTEM_ID = TAB_2.SYSTEM_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;

        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };

    $objFunction02 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $aryVariant['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.SYSTEM_ID     KEY_COLUMN "
                   .",TAB_2.HOST_PULLDOWN DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK TAB_1 "
                   ." LEFT JOIN E_STM_LIST   TAB_2 ON (TAB_1.SYSTEM_ID = TAB_2.SYSTEM_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;

        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };
    
    $objFunction03 = function($objCellFormatter, $rowData, $aryVariant){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strOperationNumeric = $rowData['OPERATION_NO_UAPK'];

        $strQuery = "SELECT "
                   ." TAB_1.SYSTEM_ID     KEY_COLUMN "
                   .",TAB_2.HOST_PULLDOWN DISP_COLUMN "
                   .",TAB_2.ACCESS_AUTH ACCESS_AUTH "
                   .",TAB_1.ACCESS_AUTH ACCESS_AUTH_01 "
                   ."FROM "
                   ." B_ANSIBLE_LNS_PHO_LINK TAB_1 "
                   ." LEFT JOIN E_STM_LIST   TAB_2 ON (TAB_1.SYSTEM_ID = TAB_2.SYSTEM_ID) "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG IN ('0') "
                   ." AND TAB_2.DISUSE_FLAG IN ('0') "
                   ." AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['OPERATION_NO_UAPK'] = $strOperationNumeric;

        if( 0 < strlen($strOperationNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[$row['KEY_COLUMN']]= $row['DISP_COLUMN'];
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $aryRetBody = array($retBool, $intErrorType, $aryErrMsgBody, $strErrMsg, $aryDataSet);
        return $aryRetBody;
    };

    // RestAPI/Excel/CSVからの登録の場合に組み合わせバリデータで退避したSYSTEM_IDを設定する。
    $tmpObjFunction = function($objColumn, $strEventKey, &$exeQueryData, &$reqOrgData=array(), &$aryVariant=array()){
                global    $g;
                $boolRet = true;
                $intErrorType = null;
                $aryErrMsgBody = array();
                $strErrMsg = "";
                $strErrorBuf = "";

                $modeValue = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_MODE"];
                if( $modeValue=="DTUP_singleRecRegister" || $modeValue=="DTUP_singleRecUpdate" ){
                    if(strlen($g['SYSTEM_ID_UPDATE_VALUE']) !== 0){
                        $exeQueryData[$objColumn->getID()] = $g['SYSTEM_ID_UPDATE_VALUE'];
                    }
                }else if( $modeValue=="DTUP_singleRecDelete" ){
                }
                $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$strErrorBuf);
                return $retArray;
    };

    $c = new IDColumn('SYSTEM_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303040"),'E_STM_LIST','SYSTEM_ID','HOST_PULLDOWN','',array('OrderByThirdColumn'=>'SYSTEM_ID'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303050"));//エクセル・ヘッダでの説明

    $strSetInnerText = $g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303060");
    $objVarBFmtUpd = new SelectTabBFmt();
    $objVarBFmtUpd->setFADJsEvent('onChange','pattern_upd');
    $objVarBFmtUpd->setNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFunctionForGetSelectList($objFunction03);

    $objOTForUpd = new OutputType(new ReqTabHFmt(), $objVarBFmtUpd);
    $objOTForUpd->setFunctionForGetFADSelectList($objFunction01);

    $objVarBFmtReg = new SelectTabBFmt();
    $objVarBFmtReg->setFADJsEvent('onChange','pattern_reg');
    $objVarBFmtReg->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtReg->setFunctionForGetSelectList($objFunction03);

    $objVarBFmtReg->setSelectWaitingText($strSetInnerText);
    $objOTForReg = new OutputType(new ReqTabHFmt(), $objVarBFmtReg);
    $objOTForReg->setFunctionForGetFADSelectList($objFunction02);

    $c->setOutputType('update_table',$objOTForUpd);
    $c->setOutputType('register_table',$objOTForReg);

    $c->setEvent('update_table','onChange','pattern_upd',array());
    $c->setEvent('register_table','onChange','pattern_reg',array());

    $c->setJournalTableOfMaster('E_STM_LIST_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('SYSTEM_ID');
    $c->setJournalDispIDOfMaster('HOST_PULLDOWN');

    // 必須チェックは組合せバリデータで行う。
    $c->setRequired(false);//登録/更新時には、入力必須
    $c->setRequiredMark(true);//必須マークのみ付与//必須マークのみ付与

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    //エクセル/CSVからのアップロードを禁止する。
    $c->setAllowSendFromFile(false);

    // REST/excel/csvで項目無効
    $c->getOutputType('excel')->setVisible(false);
    $c->getOutputType('csv')->setVisible(false);
    $c->getOutputType('json')->setVisible(false);

    // データベース更新前のファンクション登録
    $c->setFunctionForEvent('beforeTableIUDAction',$tmpObjFunction);

    $table->addColumn($c);
    //ホスト----

    //----変数名
    $objFunction01 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strPatternIdNumeric = $aryVariant['PATTERN_ID'];


        $strQuery = "SELECT "
                   ." TAB_1.VARS_LINK_ID       KEY_COLUMN "
                   .",TAB_1.VARS_LINK_PULLDOWN DISP_COLUMN "
                   .",TAB_1.ACCESS_AUTH                   "
                   .",TAB_1.ACCESS_AUTH_01 ACCESS_AUTH_01 "
                   .",TAB_1.ACCESS_AUTH_02 ACCESS_AUTH_02 "
                   ."FROM "
                   ." D_ANS_LNS_PTN_VARS_LINK_VFP TAB_1 "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG = ('0') "
                   ." AND TAB_1.PATTERN_ID = :PATTERN_ID "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['PATTERN_ID']        = $strPatternIdNumeric;

        if( 0 < strlen($strPatternIdNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };

    $objFunction02 = function($objOutputType, $aryVariant, $arySetting, $aryOverride, $objColumn){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();
        
        $strFxName = "";
        
        $strPatternIdNumeric = $aryVariant['PATTERN_ID'];

        $strQuery = "SELECT "
                   ." TAB_1.VARS_LINK_ID       KEY_COLUMN "
                   .",TAB_1.VARS_LINK_PULLDOWN DISP_COLUMN "
                   .",TAB_1.ACCESS_AUTH                   "
                   .",TAB_1.ACCESS_AUTH_01 ACCESS_AUTH_01 "
                   .",TAB_1.ACCESS_AUTH_02 ACCESS_AUTH_02 "
                   ."FROM "
                   ." D_ANS_LNS_PTN_VARS_LINK_VFP TAB_1 "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG = ('0') "
                   ." AND TAB_1.PATTERN_ID = :PATTERN_ID "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['PATTERN_ID']        = $strPatternIdNumeric;

        if( 0 < strlen($strPatternIdNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }
            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[]= $row;
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryDataSet);
        return $retArray;
    };

    $objFunction03 = function($objCellFormatter, $rowData, $aryVariant){
        global $g;
        $retBool = false;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $aryDataSet = array();

        $strFxName = "";

        $strPatternIdNumeric = $rowData['PATTERN_ID'];

        $strQuery = "SELECT "
                   ." TAB_1.VARS_LINK_ID       KEY_COLUMN "
                   .",TAB_1.VARS_LINK_PULLDOWN DISP_COLUMN "
                   .",TAB_1.ACCESS_AUTH                   "
                   .",TAB_1.ACCESS_AUTH_01 ACCESS_AUTH_01 "
                   .",TAB_1.ACCESS_AUTH_02 ACCESS_AUTH_02 "
                   ."FROM "
                   ." D_ANS_LNS_PTN_VARS_LINK_VFP TAB_1 "
                   ."WHERE "
                   ." TAB_1.DISUSE_FLAG = ('0') "
                   ." AND TAB_1.PATTERN_ID = :PATTERN_ID "
                   ."ORDER BY KEY_COLUMN ASC ";

        $aryForBind['PATTERN_ID']        = $strPatternIdNumeric;

        if( 0 < strlen($strPatternIdNumeric) ){
            // ログインユーザーのロール・ユーザー紐づけ情報を内部展開
            $obj = new RoleBasedAccessControl($g['objDBCA']);
            $ret  = $obj->getAccountInfo($g['login_id']);
            if($ret === false) {
                $intErrorType = 500;
                $retBool = false;
            }

            $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);
            if( $aryRetBody[0] === true ){
                $objQuery = $aryRetBody[1];
                while($row = $objQuery->resultFetch() ){
                    // レコード毎のアクセス権を判定
                    list($ret,$permission) = $obj->chkOneRecodeMultiAccessPermission($row);
                    if($ret === false) {
                        $intErrorType = 500;
                        $retBool = false;
                        break;
                    }else{
                        if($permission === true){
                            $aryDataSet[$row['KEY_COLUMN']]= $row['DISP_COLUMN'];
                        }
                    }
                }
                unset($objQuery);
                $retBool = true;
            }else{
                $intErrorType = 500;
                $retBool = false;
            }
        }
        $aryRetBody = array($retBool, $intErrorType, $aryErrMsgBody, $strErrMsg, $aryDataSet);
        return $aryRetBody;
    };

    // RestAPI/Excel/CSVからの登録の場合に組み合わせバリデータで退避したVARS_LINK_IDを設定する。
    $tmpObjFunction = function($objColumn, $strEventKey, &$exeQueryData, &$reqOrgData=array(), &$aryVariant=array()){
                global    $g;
                $boolRet = true;
                $intErrorType = null;
                $aryErrMsgBody = array();
                $strErrMsg = "";
                $strErrorBuf = "";

                $modeValue = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_MODE"];
                if( $modeValue=="DTUP_singleRecRegister" || $modeValue=="DTUP_singleRecUpdate" ){
                    if(strlen($g['VARS_LINK_ID_UPDATE_VALUE']) !== 0){
                        $exeQueryData[$objColumn->getID()] = $g['VARS_LINK_ID_UPDATE_VALUE'];
                    }
                }else if( $modeValue=="DTUP_singleRecDelete" ){
                }
                $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$strErrorBuf);
                return $retArray;
    };

    $c = new IDColumn('VARS_LINK_ID',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303070"),'D_ANS_LNS_PTN_VARS_LINK','VARS_LINK_ID','VARS_LINK_PULLDOWN','D_ANS_LNS_PTN_VARS_LINK_VFP');
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303080"));//エクセル・ヘッダでの説明

    $strSetInnerText = $g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303090");
    $objVarBFmtUpd = new SelectTabBFmt();
    $objVarBFmtUpd->setNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtUpd->setFunctionForGetSelectList($objFunction03);
    

    $objOTForUpd = new OutputType(new ReqTabHFmt(), $objVarBFmtUpd);
    $objOTForUpd->setFunctionForGetFADSelectList($objFunction01);

    $objVarBFmtReg = new SelectTabBFmt();
    $objVarBFmtReg->setSelectWaitingText($strSetInnerText);
    $objVarBFmtReg->setFADNoOptionMessageText($strSetInnerText);
    $objVarBFmtReg->setFunctionForGetSelectList($objFunction03);
    $objOTForReg = new OutputType(new ReqTabHFmt(), $objVarBFmtReg);
    $objOTForReg->setFunctionForGetFADSelectList($objFunction02);

    $c->setOutputType('update_table',$objOTForUpd);
    $c->setOutputType('register_table',$objOTForReg);

    $c->setJournalTableOfMaster('D_ANS_LNS_PTN_VARS_LINK_JNL');
    $c->setJournalSeqIDOfMaster('JOURNAL_SEQ_NO');
    $c->setJournalLUTSIDOfMaster('LAST_UPDATE_TIMESTAMP');
    $c->setJournalKeyIDOfMaster('VARS_LINK_ID');
    $c->setJournalDispIDOfMaster('VARS_LINK_PULLDOWN');

    // 必須チェックは組合せバリデータで行う。
    $c->setRequired(false);//登録/更新時には、入力必須
    $c->setRequiredMark(true);//必須マークのみ付与

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    //エクセル/CSVからのアップロードを禁止する。
    $c->setAllowSendFromFile(false);

    // REST/excel/csvで項目無効
    $c->getOutputType('excel')->setVisible(false);
    $c->getOutputType('csv')->setVisible(false);
    $c->getOutputType('json')->setVisible(false);

    // データベース更新前のファンクション登録
    $c->setFunctionForEvent('beforeTableIUDAction',$tmpObjFunction);

    $table->addColumn($c);
    //変数名----

    $cg1 = new ColumnGroup($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-310000"));
    $cg2 = new ColumnGroup($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-310001"));

    //----Sensitive設定
    $c = new IDColumn('SENSITIVE_FLAG',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-9010002000"), 'B_SENSITIVE_FLAG', 'VARS_SENSITIVE', 'VARS_SENSITIVE_SELECT', '', array('SELECT_ADD_FOR_ORDER'=>array('VARS_SENSITIVE'), 'ORDER'=>'ORDER BY ADD_SELECT_1'));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-9010002010")); //エクセル・ヘッダでの説明
    $c->setJournalTableOfMaster('B_SENSITIVE_FLAG_JNL');
    $c->setRequired(true); //登録/更新時には、入力必須
    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);
    $c->setDefaultValue("register_table", 1);

    $cg2->addColumn($c);
    //Sensitive設定----

    //----具体値
    $objVldt = new MultiTextValidator(0,8192,false);
    $c = new SensitiveMultiTextColumn('VARS_ENTRY',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-304010"), 'SENSITIVE_FLAG');
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-304020"));//エクセル・ヘッダでの説明
    $c->setValidator($objVldt);

    $c->setRequired(false);     //登録/更新時には、任意入力

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    $cg2->addColumn($c);
    $cg1->addColumn($cg2);
    //具体値----

    // ---- 具体値ファイル
    $c = new FileUploadColumn('VARS_ENTRY_FILE',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-310002"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-310003"));//エクセル・ヘッダでの説明
    $c->setMaxFileSize(4*1024*1024*1024);//単位はバイト
    $c->setAllowSendFromFile(false);//エクセル/CSVからのアップロードを禁止する。
    $c->setFileHideMode(true);
    $c->setHiddenMainTableColumn(true);
    $c->setAllowUploadColmnSendRestApi(true);   //REST APIからのアップロード可否。FileUploadColumnのみ有効(default:false)
    $cg1->addColumn($c);

    $table->addColumn($cg1);
    // 具体値ファイル----

    //----代入順序
    $c = new NumColumn('ASSIGN_SEQ',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-304051"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-304052"));//エクセル・ヘッダでの説明
    $c->setSubtotalFlag(false);

    $c->setValidator(new IntNumValidator(1,null));

    //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setHiddenMainTableColumn(true);

    $table->addColumn($c);
    //代入順序----

    //----具体値でTPF変数使用有無
    // 具体値のテンプレート変数設定有無を設定
    $tmpObjFunction = function($objColumn, $strEventKey, &$exeQueryData, &$reqOrgData=array(), &$aryVariant=array()){
         global    $g;
         $boolRet = true;
         $intErrorType = null;
         $aryErrMsgBody = array();
         $strErrMsg = "";
         $strErrorBuf = "";

         $modeValue = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_MODE"];
         if( $modeValue=="DTUP_singleRecRegister" || $modeValue=="DTUP_singleRecUpdate" ){
             if(strlen($g['VARS_ENTRY_USE_TPFVARS_VAULE']) !== 0){
                 $exeQueryData[$objColumn->getID()] = $g['VARS_ENTRY_USE_TPFVARS_VAULE'];
             }
         }
         $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$strErrorBuf);
         return $retArray;
    };

    $c = new TextColumn('VARS_ENTRY_USE_TPFVARS',$g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303085"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-303086"));//エクセル・ヘッダでの説明
    $c->setRequired(false);             //登録/更新時には、任意入力
    $c->setHiddenMainTableColumn(true); //コンテンツのソースがヴューの場合、登録/更新の対象とする
    $c->setAllowSendFromFile(false);    //エクセル/CSVからのアップロードを禁止する。

    $c->getOutputType('filter_table')->setVisible(false);
    $c->getOutputType('print_table')->setVisible(false);
    $c->getOutputType('update_table')->setVisible(false);
    $c->getOutputType('register_table')->setVisible(false);
    $c->getOutputType('delete_table')->setVisible(false);
    $c->getOutputType('print_journal_table')->setVisible(false);
    $c->getOutputType('excel')->setVisible(false);
    $c->getOutputType('csv')->setVisible(false);
    $c->getOutputType('json')->setVisible(false);

    $c->setFunctionForEvent('beforeTableIUDAction',$tmpObjFunction);

    $table->addColumn($c);
    //具体値でTPF変数使用有無----


    // 登録/更新/廃止/復活があった場合、データベースを更新した事をマークする。
    $tmpObjFunction = function($objColumn, $strEventKey, &$exeQueryData, &$reqOrgData=array(), &$aryVariant=array()){
        $boolRet = true;
        $intErrorType = null;
        $aryErrMsgBody = array();
        $strErrMsg = "";
        $strErrorBuf = "";
        $strFxName = "";

        $modeValue = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_MODE"];
        if( $modeValue=="DTUP_singleRecRegister" || $modeValue=="DTUP_singleRecUpdate" || $modeValue=="DTUP_singleRecDelete" ){
            if( $modeValue=="DTUP_singleRecDelete" ){
                // 廃止の場合のみ
                $modeValue_sub = $aryVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_SUB_MODE"];//['mode_sub'];("on"/"off")
                if( $modeValue_sub == "on" ){

                    $strQuery = "UPDATE A_PROC_LOADED_LIST "
                               ."SET LOADED_FLG='0' ,LAST_UPDATE_TIMESTAMP = NOW(6) "
                               ."WHERE ROW_ID IN (2100020002) ";

                    $aryForBind = array();

                    $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);

                    if( $aryRetBody[0] !== true ){
                        $boolRet = false;
                        $strErrMsg = $aryRetBody[2];
                        $intErrorType = 500;
                    }
                }
            }
            // 具体値にテンプレート変数が登録されているか判定する。
            $var_match = array();
            $match_str = "/{{(\s)" . "TPF_" . "[a-zA-Z0-9_]*(\s)}}/";
            $var_exec  = false;
            if($modeValue == "DTUP_singleRecRegister") {
                $val          = isset($aryVariant['arySqlExe_register_table']['VARS_ENTRY'])?
                                      $aryVariant['arySqlExe_register_table']['VARS_ENTRY']:'';
                $ret = preg_match_all($match_str,$val,$var_match);
                if(($ret !== false) && ($ret > 0)){
                    $var_exec  = true;
                }
            }
            if($modeValue == "DTUP_singleRecUpdate") {
                // 変更前と変更後の具体値確認
                $val          = isset($aryVariant['edit_target_row']['VARS_ENTRY'])?
                                      $aryVariant['edit_target_row']['VARS_ENTRY']:'';
                $ret = preg_match_all($match_str,$val,$var_match);
                if(($ret !== false) && ($ret > 0)){
                    $var_exec  = true;
                }else{
                    $val          = isset($aryVariant['arySqlExe_update_table']['VARS_ENTRY'])?
                                          $aryVariant['arySqlExe_update_table']['VARS_ENTRY']:'';
                    $ret = preg_match_all($match_str,$val,$var_match);
                    if(($ret !== false) && ($ret > 0)){
                        $var_exec  = true;
                    }
                }
            }
            if($modeValue == "DTUP_singleRecDelete") {
                $val          = isset($aryVariant['edit_target_row']['VARS_ENTRY'])?
                                      $aryVariant['edit_target_row']['VARS_ENTRY']:'';
                $ret = preg_match_all($match_str,$val,$var_match);
                if(($ret !== false) && ($ret > 0)){
                    $var_exec  = true;
                }
            }
            if( $var_exec === true)
            {
                $strQuery = "UPDATE A_PROC_LOADED_LIST "
                           ."SET LOADED_FLG='0' ,LAST_UPDATE_TIMESTAMP = NOW(6) "
                           ."WHERE ROW_ID IN (2100020001) ";

                $aryForBind = array();

                $aryRetBody = singleSQLExecuteAgent($strQuery, $aryForBind, $strFxName);

                if( $aryRetBody[0] !== true ){
                    $boolRet = false;
                    $strErrMsg = $aryRetBody[2];
                    $intErrorType = 500;
                }
            }
        }
        $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$strErrorBuf);
        return $retArray;
    };
    $tmpAryColumn = $table->getColumns();
    $tmpAryColumn['ASSIGN_ID']->setFunctionForEvent('beforeTableIUDAction',$tmpObjFunction);


//----head of setting [multi-set-unique]
    // マルチユニークキーをオペレーション+作業パターン+ホスト+変数名+代入順序に設定
    $table->addUniqueColumnSet(array('OPERATION_NO_UAPK','PATTERN_ID','SYSTEM_ID','VARS_LINK_ID','ASSIGN_SEQ'));
//tail of setting [multi-set-unique]----

    $table->fixColumn();

    //----組み合わせバリデータ----
    $tmpAryColumn = $table->getColumns();
    $objLU4UColumn = $tmpAryColumn[$table->getRequiredUpdateDate4UColumnID()];

    $objFunction = function($objClientValidator, $value, $strNumberForRI, $arrayRegData, $arrayVariant){
        global $g;
        $retBool = true;
        $retStrBody = '';

        $strModeId = "";
        $modeValue_sub = "";

        $query = "";

        $boolExecuteContinue = true;
        $boolSystemErrorFlag = false;

        $pattan_tbl         = "E_ANSIBLE_LNS_PATTERN";      // モード毎

        $aryVariantForIsValid = $objClientValidator->getVariantForIsValid();

        if(array_key_exists("TCA_PRESERVED", $arrayVariant)){
            if(array_key_exists("TCA_ACTION", $arrayVariant["TCA_PRESERVED"])){
                $aryTcaAction = $arrayVariant["TCA_PRESERVED"]["TCA_ACTION"];
                $strModeId = $aryTcaAction["ACTION_MODE"];
            }
        }

        unset($g['VARS_ENTRY_USE_TPFVARS_VAULE']);

        if($strModeId == "DTUP_singleRecDelete"){
            //----更新前のレコードから、各カラムの値を取得
            $intOperationNoUAPK = isset($arrayVariant['edit_target_row']['OPERATION_NO_UAPK'])?
                                        $arrayVariant['edit_target_row']['OPERATION_NO_UAPK']:null;
            $intPatternId       = isset($arrayVariant['edit_target_row']['PATTERN_ID'])?
                                        $arrayVariant['edit_target_row']['PATTERN_ID']:null;
            $intSystemId        = isset($arrayVariant['edit_target_row']['SYSTEM_ID'])?
                                        $arrayVariant['edit_target_row']['SYSTEM_ID']:null;
            $intVarsLinkId      = isset($arrayVariant['edit_target_row']['VARS_LINK_ID'])?
                                        $arrayVariant['edit_target_row']['VARS_LINK_ID']:null;
            $intRestVarsLinkId  = isset($arrayVariant['edit_target_row']['REST_VARS_LINK_ID'])?
                                        $arrayVariant['edit_target_row']['REST_VARS_LINK_ID']:null;
            $intRestSystemId    = isset($arrayVariant['edit_target_row']['REST_SYSTEM_ID'])?
                                        $arrayVariant['edit_target_row']['REST_SYSTEM_ID']:null;

            $modeValue_sub = $arrayVariant["TCA_PRESERVED"]["TCA_ACTION"]["ACTION_SUB_MODE"];//['mode_sub'];("on"/"off")
            if( $modeValue_sub == "on" ){
                //----廃止の場合はチェックしない
                $boolExecuteContinue = false;
                //廃止の場合はチェックしない----
            }else{
                if( strlen($intOperationNoUAPK) === 0 || strlen($intPatternId) === 0 ||  strlen($intSystemId) === 0 || strlen($intVarsLinkId) === 0 ){
                    $boolSystemErrorFlag = true;
                }
            }
            //更新前のレコードから、各カラムの値を取得----
        }else if( $strModeId == "DTUP_singleRecUpdate" || $strModeId == "DTUP_singleRecRegister" ){
            $intOperationNoUAPK       = array_key_exists('OPERATION_NO_UAPK',$arrayRegData)?
                                           $arrayRegData['OPERATION_NO_UAPK']:null;
            $intPatternId             = array_key_exists('PATTERN_ID',$arrayRegData)?
                                           $arrayRegData['PATTERN_ID']:null;
            $intSystemId              = array_key_exists('SYSTEM_ID',$arrayRegData)?
                                           $arrayRegData['SYSTEM_ID']:null;
            $intVarsLinkId            = array_key_exists('VARS_LINK_ID',$arrayRegData)?
                                           $arrayRegData['VARS_LINK_ID']:null;
            $intRestVarsLinkId        = array_key_exists('REST_VARS_LINK_ID',$arrayRegData)?
                                           $arrayRegData['REST_VARS_LINK_ID']:null;
            $intRestSystemId          = array_key_exists('REST_SYSTEM_ID',$arrayRegData)?
                                           $arrayRegData['REST_SYSTEM_ID']:null;
            $intVarsEntry             = array_key_exists('VARS_ENTRY',$arrayRegData)?
                                           $arrayRegData['VARS_ENTRY']:null;

            // 具体値にテンプレート変数が設定されているか判定
            $g['VARS_ENTRY_USE_TPFVARS_VAULE'] = "0";
            $match_str = "/{{(\s)" . "TPF_" . "[a-zA-Z0-9_]*(\s)}}/";
            $ret = preg_match_all($match_str,$intVarsEntry,$var_match);
            if(($ret !== false) && ($ret > 0)){
                $g['VARS_ENTRY_USE_TPFVARS_VAULE'] = "1";
            }
        }
        // カラムの増減する場合「tmp_file_COL_IDSOP_17・del_flag_COL_IDSOP_17」
        // のKey名の変更が必要となる場合がある。
        $UpLoadFile = "tmp_file_COL_IDSOP_17";
        $DelFlag    = "del_flag_COL_IDSOP_17";
        $tgtTableName = "B_ANSIBLE_LNS_VARS_ASSIGN";
        // $g['ModeType']  03_registerTable.php/04_updateTable.php/05_deleteTable.phpで設定
        // 0:[ブラウザからの新規登録
        // 1:[EXCEL]からの新規登録
        // 2:[CSV]からの新規登録
        // 3:[JSON]からの新規登録
        // 4:[ブラウザからの新規登録(SQLトランザクション無し)
        list($ret,$boolSystemErrorFlag,$retStrBody) = chkSpecificsValueInput($arrayRegData, $arrayVariant, $g['objMTS'], $UpLoadFile, $DelFlag, $g['ModeType'], $tgtTableName);
        if($ret === false) {
            $boolExecuteContinue = false;
            $retBool = false;
        }

        $g['PATTERN_ID_UPDATE_VALUE']        = "";
        $g['VARS_LINK_ID_UPDATE_VALUE']      = "";
        //----呼出元がUIがRestAPI/Excel/CSVかを判定
        // PATTERN_ID;未設定 VARS_LINK_ID:未設定 REST_VARS_LINK_ID:設定 => RestAPI/Excel/CSV
        // その他はUI
        if( $boolExecuteContinue === true && $boolSystemErrorFlag === false){
            if((strlen($intPatternId)          === 0) &&
               (strlen($intVarsLinkId)         === 0) &&
               (strlen($intRestVarsLinkId)     !== 0)){
                $query =  "SELECT                                             "
                         ."  TBL_A.VARS_LINK_ID,                              "
                         ."  TBL_A.PATTERN_ID,                                "
                         ."  COUNT(*) AS VARS_LINK_ID_CNT                     "
                         ."FROM                                               "
                         ."  E_ANS_LNS_PTN_VAR_LIST TBL_A                     "      //モード毎
                         ."WHERE                                              "
                         ."  TBL_A.VARS_LINK_ID    = :VARS_LINK_ID   AND      "
                         ."  TBL_A.DISUSE_FLAG     = '0'                      ";
                $aryForBind = array();
                $aryForBind['VARS_LINK_ID'] = $intRestVarsLinkId;
                $retArray = singleSQLExecuteAgent($query, $aryForBind, "NONAME_FUNC(VARS_MULTI_CHECK)");
                if( $retArray[0] === true ){
                    $objQuery =& $retArray[1];
                    $intCount = 0;
                    $row = $objQuery->resultFetch();
                    if( $row['VARS_LINK_ID_CNT'] == '1' ){
                        $intVarsLinkId                     = $row['VARS_LINK_ID'];
                        $intPatternId                      = $row['PATTERN_ID'];
                        $g['PATTERN_ID_UPDATE_VALUE']      = $intPatternId;
                        $g['VARS_LINK_ID_UPDATE_VALUE']    = $intVarsLinkId;
                    }else if( $row['VARS_LINK_ID_CNT'] == '0' ){
                        $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90077");
                        $retBool = false;
                        $boolExecuteContinue = false;
                    }else{
                        web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                        $boolSystemErrorFlag = true;
                    }
                    unset($row);
                    unset($objQuery);
                }else{
                    web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                    $boolSystemErrorFlag = true;
                }
                unset($retArray);
            }
        }

        $g['SYSTEM_ID_UPDATE_VALUE']        = "";
        //----呼出元がUIがRestAPI/Excel/CSVかを判定
        // SYSTEM_ID;未設定 REST_SYSTEM_ID:設定 => RestAPI/Excel/CSV
        // その他はUI
        if( $boolExecuteContinue === true && $boolSystemErrorFlag === false){
            if((strlen($intSystemId)         === 0) &&
               (strlen($intRestSystemId)     !== 0)){
                $retBool = false;
                $boolExecuteContinue = false;
                $query = " SELECT "
                         ."   COUNT(*) AS HOST_CNT "
                         ."FROM "
                         ."   C_STM_LIST TBL_A  "
                         ." WHERE "
                         ."   TBL_A.SYSTEM_ID    = :SYSTEM_ID AND "
                         ."   TBL_A.DISUSE_FLAG  = '0' ";

                $aryForBind = array();
                $aryForBind['SYSTEM_ID']     = $intRestSystemId;
                $retArray = singleSQLExecuteAgent($query, $aryForBind, "NONAME_FUNC(VARS_MULTI_CHECK)");
                if( $retArray[0] === true ){
                    $objQuery =& $retArray[1];
                    $intCount = 0;
                    $row = $objQuery->resultFetch();
                    if( $row['HOST_CNT'] == '1' ){
                        $intSystemId                 = $intRestSystemId;
                        $g['SYSTEM_ID_UPDATE_VALUE'] = $intRestSystemId;
                        $retBool = true;
                        $boolExecuteContinue = true;
                    }else if( $row['HOST_CNT'] == '0' ){
                        $boolExecuteContinue = false;
                        $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90075");
                    }else{
                        web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                        $boolSystemErrorFlag = true;
                    }
                    unset($row);
                    unset($objQuery);
                }else{
                    web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                    $boolSystemErrorFlag = true;
                }
                unset($retArray);
            }
        }
        //呼出元がUIがRestAPI/Excel/CSVかを判定----

        //----必須入力チェック
        if( $boolExecuteContinue === true && $boolSystemErrorFlag === false){
            if( strlen($intPatternId) === 0 ){
                $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90130");
                $boolExecuteContinue = false;
                $retBool = false;
            }
            else if( strlen($intSystemId) === 0 ){
                $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90076");
                $boolExecuteContinue = false;
                $retBool = false;
            }
            else if( strlen($intVarsLinkId) === 0 ){
                $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90070");
                $boolExecuteContinue = false;
                $retBool = false;
            }
        }
        //必須入力チェック----

        //----作業パターンのチェック
        if( $boolExecuteContinue === true && $boolSystemErrorFlag === false){
            $retBool = false;
            $boolExecuteContinue = false;
            $query = " SELECT "
                     ."   COUNT(*) AS PATTAN_CNT "
                     ." FROM "
                     ."   $pattan_tbl TBL_A  "
                     ." WHERE "
                     ."   TBL_A.PATTERN_ID   = :PATTERN_ID   AND "
                     ."   TBL_A.DISUSE_FLAG  = '0' ";

            $aryForBind = array();
            $aryForBind['PATTERN_ID']     = $intPatternId;
            $retArray = singleSQLExecuteAgent($query, $aryForBind, "NONAME_FUNC(VARS_MULTI_CHECK)");
            if( $retArray[0] === true ){
                $objQuery =& $retArray[1];
                $intCount = 0;
                $row = $objQuery->resultFetch();
                if( $row['PATTAN_CNT'] == '1' ){
                    $retBool = true;
                    $boolExecuteContinue = true;
                }else if( $row['PATTAN_CNT'] == '0' ){
                    $boolExecuteContinue = false;
                    $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-ERR-90063");
                }else{
                    web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                    $boolSystemErrorFlag = true;
                }
                unset($row);
                unset($objQuery);
            }else{
                web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                $boolSystemErrorFlag = true;
            }
            unset($retArray);
        }
        //作業パターンのチェック----

        if( $boolExecuteContinue === true ){
            $retBool = false;
            $query = "SELECT "
                    ." COUNT(*) REC_COUNT "
                    ."FROM "
                    ." B_ANSIBLE_LNS_PHO_LINK TAB_1 "
                    ." LEFT JOIN D_ANS_LNS_PTN_VARS_LINK_VFP TAB_2 ON (TAB_1.PATTERN_ID = TAB_2.PATTERN_ID) "
                    ."WHERE "
                    ." TAB_1.DISUSE_FLAG = '0' "
                    ."AND TAB_2.DISUSE_FLAG = '0' "
                    ."AND TAB_1.OPERATION_NO_UAPK = :OPERATION_NO_UAPK "
                    ."AND TAB_1.PATTERN_ID = :PATTERN_ID "
                    ."AND TAB_1.SYSTEM_ID = :SYSTEM_ID "
                    ."AND TAB_2.VARS_LINK_ID = :VARS_LINK_ID ";

            $aryForBind['OPERATION_NO_UAPK'] = $intOperationNoUAPK;
            $aryForBind['PATTERN_ID'] = $intPatternId;
            $aryForBind['SYSTEM_ID'] = $intSystemId;
            $aryForBind['VARS_LINK_ID'] = $intVarsLinkId;

            $retArray = singleSQLExecuteAgent($query, $aryForBind, "NONAME_FUNC(VARS_MULTI_CHECK)");
            if( $retArray[0] === true ){
                $objQuery =& $retArray[1];
                $intCount = 0;
                $aryDiscover = array();
                $row = $objQuery->resultFetch();
                unset($objQuery);
                if( $row['REC_COUNT'] == '1' ){
                    $retBool = true;
                }else if( $row['REC_COUNT'] == '0' ){
                    $retStrBody = $g['objMTS']->getSomeMessage("ITAANSIBLEH-MNU-304050");
                }else{
                    web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                    $boolSystemErrorFlag = true;
                }
            }else{
                web_log("DB Access error file:" . basename(__FILE__) . " line:" . __LINE__);
                $boolSystemErrorFlag = true;
            }
        }

        if( $boolSystemErrorFlag === true ){
            $retBool = false;
            //----システムエラー
            $retStrBody = $g['objMTS']->getSomeMessage("ITAWDCH-ERR-3001");
        }

        if($retBool===false){
            $objClientValidator->setValidRule($retStrBody);
        }
        return $retBool;
    };

    $objVarVali = new VariableValidator();
    $objVarVali->setErrShowPrefix(false);
    $objVarVali->setFunctionForIsValid($objFunction);
    $objVarVali->setVariantForIsValid(array());

    $objLU4UColumn->addValidator($objVarVali);
    //組み合わせバリデータ----

    $table->setGeneObject('webSetting', $arrayWebSetting);
    return $table;
};
loadTableFunctionAdd($tmpFx,__FILE__);
unset($tmpFx);
?>
