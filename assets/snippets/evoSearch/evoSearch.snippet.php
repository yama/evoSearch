<?php
if(!defined('MODX_BASE_PATH')) die('What are you doing? Get out of here!');

$documents = '';
$output = '';
$noResult = isset($params['noResult']) ? $params['noResult'] : 'Ничего не найдено. Смягчите условия поиска';
$txt_original = '';

include_once('snippet.class.php');
$eSS = new evoSearchSnippet($modx, $params);

if (isset($_GET['search']) && $_GET['search'] != '') {
    $eSS->Set('txt_original', $_GET['search'], true);
    //echo '<br>'.$eSS->Get('txt_original').'<br>';

    $sql = $eSS->makeSearchSQL();
    //echo $sql;
    $q = $eSS->modx->db->query($sql);
    $documents = $eSS->makeStringFromQuery($q);
    $eSS->params['documents'] = $documents;
    $eSS->params['display'] = isset($eSS->params['display']) ? $eSS->params['display'] : "20";
    $eSS->params['paginate'] = isset($eSS->params['paginate']) ? $eSS->params['paginate'] : "pages";
    $eSS->params['show_stat'] = isset($eSS->params['show_stat']) ? $eSS->params['show_stat'] : "1";
    $eSS->params['extract'] = isset($eSS->params['extract']) ? $eSS->params['extract'] : "1";
    $eSS->params['statTpl'] = isset($eSS->params['statTpl']) ? $eSS->params['statTpl'] : '<div class="evoSearch_info">По запросу <b>[+stat_request+]</b> найдено всего <b>[+stat_total+]</b>. Показано <b>[+stat_display+]</b>, c [+stat_from+] по [+stat_to+]</div>';
    $worker = isset($eSS->params['worker']) ? $eSS->params['worker'] : "DocLister";

    $words_original = $eSS->makeWordsFromText($eSS->Get('txt_original'));
    $bulk_words_original = $eSS->makeBulkWords($words_original, false);

    $eSS->bulk_words_stemmer = array();
    foreach ($bulk_words_original as $v) {
        $eSS->bulk_words_stemmer[] = $eSS->stemmer->stem_word($v);
    }

    if ($documents == '') {
        if ($worker == 'DocLister') {
            if (!isset($eSS->params['addSearch']) || $eSS->params['addSearch'] != '0') {
                $eSS->makeAddQueryForEmptyResult($bulk_words_original);
                if ($eSS->params['extract'] == '1') {
                    $eSS->params['prepare'] = array($eSS, 'prepareExtractor');
                }
                $output .= $eSS->modx->runSnippet($worker, $eSS->params);
                if ($eSS->params['show_stat'] == '1') {
                    $output = $eSS->getSearchResultInfo() . $output;
                }
            }
        }
    } else {
        if ($worker == 'DocLister') {
            $eSS->params['sortType'] = "doclist";
            $eSS->params['idType'] = "documents";
            $eSS->params['addWhereList'] = 'c.searchable=1';
            if ($eSS->params['extract'] == '1') {
                $eSS->params['prepare'] = array($eSS, 'prepareExtractor');
            }
            $output .= $eSS->modx->runSnippet($worker, $eSS->params);
            if ($eSS->params['show_stat'] == '1') {
                $output = $eSS->getSearchResultInfo() . $output;
            }
        } else if ($worker == 'Ditto') {
            $eSS->params['extenders'] = 'nosort';
            $eSS->params['where'] = 'searchable=1';
            $output .= $eSS->modx->runSnippet($worker, $eSS->params);
        } else {
            $output .= $eSS->modx->runSnippet($worker, $eSS->params);
        }
    }

}

//print_r($params);
echo $output != '' ? $output : (!isset($_REQUEST['search']) ? '' : '<div class="noResult">'.$noResult.'</div>');
