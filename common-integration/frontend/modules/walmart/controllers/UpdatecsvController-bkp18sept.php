<?php
/**
 * Created by PhpStorm.
 * User: cedcoss
 * Date: 28/3/17
 * Time: 5:07 PM
 */
namespace frontend\modules\walmart\controllers;

use frontend\modules\walmart\components\Data;
use frontend\modules\walmart\components\Walmartapi;
use frontend\modules\walmart\components\WalmartProduct;
use frontend\modules\walmart\models\JetProduct;
use frontend\modules\walmart\models\WalmartProduct as WalmartProductModel;
use Yii;
use yii\base\Exception;
use yii\web\Response;

class UpdatecsvController extends WalmartmainController
{
    protected $walmartHelper;

    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return \Yii::$app->getResponse()->redirect(\Yii::$app->getUser()->loginUrl);
        }

        return $this->render('index');
    }
    public function actionExportproduct()
    {
        $merchant_id = Yii::$app->user->identity->id;


        if (isset($_GET['status'])) {

//            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/' . $merchant_id . '/' . $_GET['status'] . '/' . $_GET['status'] . '.csv';
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id . '/product.csv';
            if (file_exists($base_path)) {

                $file = fopen($base_path, "r");

                $encode = "\xEF\xBB\xBF"; // UTF-8 BOM
                $content = $encode . file_get_contents($base_path);
                \Yii::$app->response->sendFile($base_path);
                return $this->render('index');

            } else {

                return $this->render('index');
            }
        }else{
            $status = $_POST['export'];

            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id;

            if (file_exists($base_path)) {
                if(file_exists(\Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id. '/product.csv'))
                {
                    unlink(\Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id. '/product.csv');
                }
                rmdir($base_path);
            }
        }
        $status = $_POST['export'];
        if (empty($status)) {
            Yii::$app->session->setFlash('error', "Please select an option to export CSV");

            return $this->redirect('index');
        }

        /*if ($status && $status == 'all') {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `jet_product`.`type`='simple' ) UNION (SELECT `walmart_product_variants`.`option_id` AS `variant_id` FROM `walmart_product_variants` INNER JOIN `walmart_product` ON `walmart_product_variants`.`product_id` = `walmart_product`.`product_id` INNER JOIN `jet_product_variants` ON `walmart_product_variants`.`option_id`=`jet_product_variants`.`option_id` WHERE `walmart_product_variants`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`category` != '' AND `walmart_product`.`status`='Not Uploaded')) as `merged_data`";
        } else {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `jet_product`.`type`='simple' AND `walmart_product`.`status`='" . $status . "') UNION (SELECT `walmart_product_variants`.`option_id` AS `variant_id` FROM `walmart_product_variants` INNER JOIN `walmart_product` ON `walmart_product_variants`.`product_id` = `walmart_product`.`product_id` INNER JOIN `jet_product_variants` ON `walmart_product_variants`.`option_id`=`jet_product_variants`.`option_id` WHERE `walmart_product_variants`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`category` != ''AND `walmart_product_variants`.`status`='" . $status . "')) as `merged_data`";
        }*/
        if ($status && $status == 'all') {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " )) as `merged_data`";
        } else {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`status`='" . $status . "')) as `merged_data`";
        }
        $product = Data::sqlRecords($query, "one", "select");

        if (is_array($product)) {
            $pages = ceil(intval($product['count']) / 100);
            $session = Yii::$app->session;
            $session->set('status', $status);
            $session->set('product_page', $pages);

            return $this->render('exportproduct',
                [
                    'totalcount' => $product['count'],
                    'pages' => $pages,
                    'status' => $status
                ]
            );

        } else {
            Yii::$app->session->setFlash('error', "No Products are found.");
            return $this->redirect('index');
        }
    }

    public function actionExport()
    {
        $merchant_id = Yii::$app->user->identity->id;
        $getItemsCount = 100;
        $session = Yii::$app->session;

        $index = Yii::$app->request->post('index');
        $offset = $index * $getItemsCount;
        $limit = $getItemsCount;

        $action = $session->get('status');

        if (!file_exists(\Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id)) {
            mkdir(\Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id, 0775, true);
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id . '/product.csv';
            $file = fopen($base_path, "w");
            $headers = array('Id', 'Sku', 'Type', 'Title', 'Fullfilment_Lag_Time', 'Sku_Override', 'Product_Id_Override', 'Short_Description', 'Self_Description', 'Description', 'Product_taxcode');

            $row = array();
            foreach ($headers as $header) {
                $row[] = $header;
            }
            fputcsv($file, $row);

        }else{
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/whole-product/' . $merchant_id . '/product.csv';
            $file = fopen($base_path, "a");

            $row = array();
        }

        $productdata = array();
        $i = 0;

        if($action && $action == 'all'){
            $model = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "' LIMIT $offset,$limit", 'all');
        }else{
            $model = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status`='".$action."') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "' LIMIT $offset,$limit", 'all');
        }

        /*}*/

        //print_r("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "'");die('dfgd');
        foreach ($model as $value) {
            if ($value['sku'] == "") {
                continue;
            }
            $productdata[$i]['title'] = $value['title'];

            if (!empty($value['product_title'])) {
                $productdata[$i]['title'] = $value['product_title'];
            }
            $productdata[$i]['fulfilment_lag_time'] = $value['fulfillment_lag_time'];
            $productdata[$i]['sku_override'] = $value['sku_override'];
            $productdata[$i]['product_id_override'] = $value['product_id_override'];
            $productdata[$i]['tax_code'] = $value['tax_code'];

            if (!empty($value['long_description'])) {
                $productdata[$i]['long_description'] = $value['long_description'];
            } else {
                $productdata[$i]['long_description'] = $value['description'];
            }

            if (!empty($value['short_description'])) {
                $productdata[$i]['short_description'] = $value['short_description'];
            } else {
                $short_description = Data::trimString($value['description'], 800);

                $productdata[$i]['short_description'] = $short_description;
            }

            if (!empty($value['self_description'])) {
                $productdata[$i]['self_description'] = $value['self_description'];
            } else {
                $productdata[$i]['self_description'] = $value['title'];
            }

            /*if ($value['type'] == "simple") {*/
            $productdata[$i]['id'] = $value['product_id'];
            $productdata[$i]['sku'] = $value['sku'];
            $productdata[$i]['type'] = "No Variants";

            /*} else {
                continue;
            }*/
            $i++;
        }

        foreach ($productdata as $v) {

            $row = array();
            $row[] = $v['id'];
            $row[] = $v['sku'];
            $row[] = $v['type'];
            $row[] = $v['title'];
            $row[] = $v['fulfilment_lag_time'];
            $row[] = $v['sku_override'];
            $row[] = $v['product_id_override'];
            $row[] = $v['short_description'];
            $row[] = $v['self_description'];
            $row[] = $v['long_description'];
            $row[] = $v['tax_code'];

            fputcsv($file, $row);
        }
        fclose($file);

        $returnarr['success'] = "Product Information has been updated successfully";
        $returnarr['count'] = count($productdata);

        return json_encode($returnarr);
        /*$encode = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content = $encode . file_get_contents($base_path);
        return \Yii::$app->response->sendFile($base_path);*/
    }

    /*public function actionReadcsv()
    {

        if (Yii::$app->user->isGuest) {
            return \Yii::$app->getResponse()->redirect(\Yii::$app->getUser()->loginUrl);
        }

        $connection = Yii::$app->getDb();
        $merchant_id = Yii::$app->user->identity->id;

        if (isset($_FILES['csvfile']['name'])) {
            //var_dump($_FILES);die;
            $mimes = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv', 'text/comma-separated-values','application/octet-stream');

            if (!in_array($_FILES['csvfile']['type'], $mimes)) {
                Yii::$app->session->setFlash('error', "CSV File type Changed, Please import only CSV file");
                return $this->redirect(['index']);
            }

            $newname = $_FILES['csvfile']['name'];

            if (!file_exists(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id)) {
                mkdir(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id, 0775, true);
            }

            $target = Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id . '/' . $newname . '-' . time();
            $row = 0;
            $flag = false;
            $row1 = 0;
            if (!file_exists($target)) {
                move_uploaded_file($_FILES['csvfile']['tmp_name'], $target);
            }

            $selectedProducts = array();
            $import_errors = array();
            if (($handle = fopen($target, "r"))) {

                $row = 0;
                while (($data = fgetcsv($handle, 90000, ",")) !== FALSE) {

                //    print_r($data);die;

                    if ($row == 0 && (trim($data[13]) != 'Variant SKU' || trim($data[2]) != 'Body (HTML)')) {
                        $flag = true;
                        break;
                    }
                    $num = count($data);
                    $row++;
                    if ($row == 1)
                        continue;

                    $sku = trim($data[13]);
                    //$tax_code = trim($data[1]);
                    //$ean = $data[8];
                    $body = $data[2];
                    //$gtin = $data[11];



                    if ($sku == ''  || $body == '') {
                        $import_errors[$row] = 'Row ' . $row . ' : Invalid data.';
                        continue;
                    }

                   /*if (!is_numeric($upc) || addslashes($sku) ) {
                        $import_errors[$row] = 'Row ' . $row . ' : Invalid product_id / product data.';
                        continue;
                    }*/


                    //$upc=$gtin;
                    /*if(!$upc){
                        $upc=$ean;
                    }

                    $productData = array();
                    $productData['body'] = addslashes($body);
                    $productData['sku'] = addslashes($sku);
                    //$productData['tax_code'] = $tax_code;
                    //$productData['ean'] = $ean;

                    $selectedProducts[] = $productData;

                    //unset($upc);
                    //unset($ean);
                    //unset($gtin);
                }

               //print_r($selectedProducts);die;
                if (count($selectedProducts)) {

                    $session = Yii::$app->session;
                    $c=0;
                    $connection = Yii::$app->getDb();

                    foreach ($selectedProducts as $key => $value) {


                        $product=Data::sqlRecords("select * from jet_product as jp INNER JOIN walmart_product as wp On jp.id=wp.product_id  where sku='".addslashes($value['sku'])."' and jp.merchant_id='".$merchant_id."'","one","select");


                        //$value['upc']=str_replace('-', '', $value['upc']);

                        if($product){
                            echo $value['sku'];

                            /* $query="UPDATE `jet_product` SET description='".$value['body']."' WHERE sku='".addslashes($value['sku'])."' AND merchant_id=".$merchant_id;
                             $model = $connection->createCommand($query)->execute();
                             $query="UPDATE `walmart_product` SET long_description='".$value['body']."' WHERE product_id='".$product['product_id']."' AND merchant_id=".$merchant_id;
                             $model = $connection->createCommand($query)->execute();

                             $c++;



                        }

                        /*else{

                            echo "del/";
                             $delprod="DELETE FROM `jet_product` WHERE sku='".$value['sku']."'AND merchant_id='".$merchant_id."'";
                            $model = $connection->createCommand($delprod)->execute();
                        }
                    }

echo $c;die;
                } else {
                    if (count($import_errors)) {
                        Yii::$app->session->setFlash('error', implode('<br>', $import_errors));
                    } else {
                        Yii::$app->session->setFlash('error', "Please Upload Csv file....");
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "File not found....");
            }
        } else {
            Yii::$app->session->setFlash('error', "Please Upload Csv file....");
        }
        //return $this->redirect(['index']);
    }*/

    public function actionReadcsv()
    {
        if (Yii::$app->user->isGuest) {
            return \Yii::$app->getResponse()->redirect(\Yii::$app->getUser()->loginUrl);
        }
        $merchant_id = Yii::$app->user->identity->id;

        if (isset($_FILES['csvfile']['name'])) {
            //var_dump($_FILES);die;
            $mimes = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv', 'text/comma-separated-values','application/octet-stream');
            if (!in_array($_FILES['csvfile']['type'], $mimes)) {
                Yii::$app->session->setFlash('error', "Invalid file type. Please import only CSV file");
                return $this->redirect(['index']);
            }

            $newname = $_FILES['csvfile']['name'];

            if (!file_exists(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id)) {
                mkdir(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id, 0775, true);
            }

            $target = Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id . '/' . $newname . '-' . time();
            $row = 0;
            $flag = false;
            $row1 = 0;
            if (!file_exists($target)) {
                move_uploaded_file($_FILES['csvfile']['tmp_name'], $target);
            }

            $selectedProducts = array();
            $import_errors = array();
            if (($handle = fopen($target, "r"))) {

                $row = 0;
                while (($data = fgetcsv($handle, 90000, ",")) !== FALSE) {
                    if ($row == 0 && (trim($data[0]) != 'Id' || trim($data[1]) != 'Sku' || trim($data[2]) != 'Type'/* || trim($data[3]) != 'Title' || trim($data[4]) != 'Fullfilment_Lag_Time' || trim($data[5]) != 'Sku_Override' || trim($data[6]) != 'Product_Id_Override' || trim($data[7]) != 'Short_Description' || trim($data[8]) != 'Self_Description' || trim($data[9]) != 'Description' || trim($data[10]) != 'Product_taxcode'*/)) {
                        $flag = true;
                        $import_errors[$row] = 'Error : Invalid file. Please choose a valid file ';

                        break;
                    }
                    $num = count($data);
                    $row++;
                    if ($row == 1)
                        continue;

                    $pro_id = trim($data[0]);
                    $pro_sku = trim($data[1]);
                    $pro_type = trim($data[2]);
                    $pro_title = trim($data[3]);
                    $pro_fulfillment_lag_time = trim($data[4]);
                    $pro_price_sku_override = trim($data[5]);
                    $pro_price_product_id_override = trim($data[6]);
                    $pro_short_description = trim($data[7]);
                    $pro_self_description = trim($data[8]);
                    $pro_long_description = trim($data[9]);
                    $pro_taxcode = trim($data[10]);

                    if ($pro_id == '' || $pro_sku == '' || $pro_type == '' /* || $pro_title == '' || $pro_fulfillment_lag_time == '' || $pro_price_sku_override == '' || $pro_price_product_id_override == '' || $pro_short_description == '' || $pro_self_description == '' || $pro_long_description == '' || $pro_taxcode == ''*/) {
                        $import_errors[$row] = 'Row ' . $row . ' : Invalid data.';
                        continue;
                    }

                    if (!is_numeric($pro_id) || !is_numeric($pro_fulfillment_lag_time) /*|| !is_numeric($pro_taxcode)*/) {
                        $import_errors[$row] = 'Row ' . $row . ' : Invalid product_id / product data.';
                        continue;
                    }
                    // for checking product description is UTF-8 encoded.
                    if (mb_detect_encoding($pro_short_description) !== 'UTF-8') {
                        $pro_short_description = utf8_encode($pro_short_description);
                    }
                    if (mb_detect_encoding($pro_self_description) !== 'UTF-8') {
                        $pro_self_description = utf8_encode($pro_self_description);
                    }
                    if (mb_detect_encoding($pro_long_description) !== 'UTF-8') {
                        $pro_long_description = utf8_encode($pro_long_description);
                    }
                    
                    $productData = array();
                    $productData['id'] = $pro_id;
                    $productData['sku'] = $pro_sku;
                    $productData['type'] = $pro_type;
                    $productData['title'] = addslashes($pro_title);
                    $productData['fulfillment_lag_time'] = $pro_fulfillment_lag_time;
                    $productData['price_sku_override'] = $pro_price_sku_override;
                    $productData['price_product_id_override'] = $pro_price_product_id_override;
                    $productData['short_description'] = addslashes($pro_short_description);
                    $productData['self_description'] = addslashes($pro_self_description);
                    $productData['long_description'] = addslashes($pro_long_description);
                    $productData['tax_code'] = $pro_taxcode;
                    //$productData['upc'] = $pro_upc;

                    $productData['currency'] = CURRENCY;

                    $selectedProducts[] = $productData;
                }

                if (count($selectedProducts)) {

                    $session = Yii::$app->session;

                    $size_of_request = 10;//Number of products to be uploaded at once(in single feed)
                    $pages = (int)(ceil(count($selectedProducts) / $size_of_request));

                    return $this->render('ajaxbulkupdate', [
                        'totalcount' => count($selectedProducts),
                        'pages' => $pages,
                        'products' => json_encode($selectedProducts)
                    ]);

                } else {
                    if (count($import_errors)) {
                        Yii::$app->session->setFlash('error', implode('<br>', $import_errors));
                    } else {
                        Yii::$app->session->setFlash('error', "Please Upload Csv file....");
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "File not found....");
            }
        } else {
            Yii::$app->session->setFlash('error', "Please Upload Csv file....");
        }
        return $this->redirect(['index']);
    }

    public function actionBatchupdate()
    {

        $session = Yii::$app->session;
        $products = Yii::$app->request->post();

        $when_title = '';
        $when_fulfillment_lag_time = '';
        $when_skuoverride = '';
        $when_product_id_override = '';
        $when_short_description = '';
        $when_self_description = '';
        $when_long_description = '';
        $when_taxcode = '';
        $when_barcode = '';
        $invalid_taxcode=[];
        $id = [];
        $option_id = [];
        foreach ($products['products'] as $product) {
            if((empty($product['tax_code']) || strlen($product['tax_code']) == 7))
            {
                if ($product['type'] == 'No Variants') {
                    $id[] = $product['id'];
                    $when_title .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['title'] . '"';
                    $when_fulfillment_lag_time .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['fulfillment_lag_time'] . '"';
                    $when_skuoverride .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['price_sku_override'] . '"';
                    $when_product_id_override .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['price_product_id_override'] . '"';
                    $when_short_description .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['short_description'] . '"';
                    $when_self_description .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['self_description'] . '"';
                    $when_long_description .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['long_description'] . '"';
                    $when_taxcode .= ' WHEN ' . $product['id'] . ' THEN ' . '"' . $product['tax_code'] . '"';

                }
            }else {
                $invalid_taxcode[] = $product['sku'];
            }

        }
        $ids = implode(',', $id);
        try {
            $query = "UPDATE `walmart_product` SET 
                                    `product_title` = CASE `product_id` 
                                   " . $when_title . "
                                END, 
                                    `fulfillment_lag_time` = CASE `product_id`
                                    " . $when_fulfillment_lag_time . " 
                                END, 
                                    `sku_override` = CASE `product_id`
                                    " . $when_skuoverride . " 
                                END, 
                                    `product_id_override` = CASE `product_id`
                                    " . $when_product_id_override . " 
                                END, 
                                    `short_description` = CASE `product_id`
                                    " . $when_short_description . " 
                                END, 
                                    `self_description` = CASE `product_id`
                                    " . $when_self_description . " 
                                END, 
                                    `long_description` = CASE `product_id`
                                    " . $when_long_description . " 
                                END, 
                                    `tax_code` = CASE `product_id`
                                    " . $when_taxcode . " 
                                END
                                WHERE product_id IN (" . $ids . ")";

            Data::sqlRecords($query, null, 'update');

            $return_msg['success']['message'] = "Product(s) information successfully updated";
            $return_msg['success']['count'] = count($id);
        } catch (Exception $e) {
            $return_msg['error'] = $e->getMessage();
        }

        if (count($invalid_taxcode) > 0) {
            $return_msg['error'] = json_encode($invalid_taxcode);
        }
        return json_encode($return_msg);
    }

    public function actionIndexRetire()
    {
        if (Yii::$app->user->isGuest) {
            return \Yii::$app->getResponse()->redirect(\Yii::$app->getUser()->loginUrl);
        }

        return $this->render('index-retire');
    }

    public function actionExportretireproduct()
    {
        $merchant_id = Yii::$app->user->identity->id;

        if (isset($_GET['status'])) {
//            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/' . $merchant_id . '/' . $_GET['status'] . '/' . $_GET['status'] . '.csv';
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id. '/retire-product.csv';
            if (file_exists($base_path)) {

                $file = fopen($base_path, "r");

                $encode = "\xEF\xBB\xBF"; // UTF-8 BOM
                $content = $encode . file_get_contents($base_path);
                \Yii::$app->response->sendFile($base_path);
                return $this->render('index-retire');

            } else {
                return $this->render('index');

            }
        }else{
            $status = $_POST['export'];

            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id;

            if (file_exists($base_path)) {
                if(file_exists(\Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id. '/retire-product.csv'))
                {
                    unlink(\Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id. '/retire-product.csv');
                }
                rmdir($base_path);
            }
        }
        $status = $_POST['export'];
        if (empty($status)) {
            Yii::$app->session->setFlash('error', "Please select an option to export CSV");

            return $this->redirect('index-retire');
        }

        /*if ($status && $status == 'all') {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `jet_product`.`type`='simple') UNION (SELECT `walmart_product_variants`.`option_id` AS `variant_id` FROM `walmart_product_variants` INNER JOIN `walmart_product` ON `walmart_product_variants`.`product_id` = `walmart_product`.`product_id` INNER JOIN `jet_product_variants` ON `walmart_product_variants`.`option_id`=`jet_product_variants`.`option_id` WHERE `walmart_product_variants`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`category` != '' AND `walmart_product`.`status`='Not Uploaded')) as `merged_data`";
        } else {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `jet_product`.`type`='simple' AND `walmart_product`.`status`='" . $status . "') UNION (SELECT `walmart_product_variants`.`option_id` AS `variant_id` FROM `walmart_product_variants` INNER JOIN `walmart_product` ON `walmart_product_variants`.`product_id` = `walmart_product`.`product_id` INNER JOIN `jet_product_variants` ON `walmart_product_variants`.`option_id`=`jet_product_variants`.`option_id` WHERE `walmart_product_variants`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`category` != ''AND `walmart_product_variants`.`status`='" . $status . "')) as `merged_data`";
        }*/
        if ($status && $status == 'all') {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " )) as `merged_data`";
        } else {
            $query = "SELECT count(*) as `count` FROM ((SELECT `variant_id` FROM `walmart_product` INNER JOIN `jet_product` ON `walmart_product`.`product_id`=`jet_product`.`id` WHERE `walmart_product`.`merchant_id`=" . $merchant_id . " AND `walmart_product`.`status`='" . $status . "')) as `merged_data`";
        }
        $product = Data::sqlRecords($query, "one", "select");

        /*if($status && $status=='all')
        {
            //$product = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status` IN ('PUBLISHED','UNPUBLISHED','STAGE')) as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "'", 'all');
            print_r("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status` ='Not Uploaded') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "'");die;
            $product = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status` ='Not Uploaded') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "'", 'all');
        }else{
            $product = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status`='".$status."') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "'", 'all');
        }*/

        if (is_array($product)) {
            $pages = ceil(intval($product['count']) / 100);
            $session = Yii::$app->session;
            $session->set('status', $status);
            $session->set('product_page', $pages);

            return $this->render('retireprod',
                [
                    'totalcount' => $product['count'],
                    'pages' => $pages,
                    'status' => $status
                ]
            );

        } else {
            Yii::$app->session->setFlash('error', "No Products are found.");

            return $this->redirect('index-retire');
        }

    }


    public function actionRetireprod()
    {
        $merchant_id = Yii::$app->user->identity->id;
        $getItemsCount = 100;
        $session = Yii::$app->session;

        $index = Yii::$app->request->post('index');
        $offset = $index * $getItemsCount;
        $limit = $getItemsCount;

        $action = $session->get('status');

        if (!file_exists(\Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id)) {
            mkdir(\Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id, 0775, true);
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id . '/retire-product.csv';
            $file = fopen($base_path, "w");
            $headers = array('Sku', 'Type','Status');

            $row = array();
            foreach ($headers as $header) {
                $row[] = $header;
            }
            fputcsv($file, $row);

        }else{
            $base_path = \Yii::getAlias('@webroot') . '/var/csv_export/product/retire/' . $merchant_id . '/retire-product.csv';
            $file = fopen($base_path, "a");

            $row = array();
        }

        $productdata = array();
        $i = 0;

        if($action && $action=='all')
        {
//            $model = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status` IN ('PUBLISHED','UNPUBLISHED','STAGE')) as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "' LIMIT $index,$offset ", 'all');
            $model = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status` ='Not Uploaded' )as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "' LIMIT $offset,$limit ", 'all');
        }else{
            $model = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product` WHERE `merchant_id`='" . $merchant_id . "') as `jp` INNER JOIN (SELECT * FROM `walmart_product` WHERE `merchant_id`='" . $merchant_id . "' AND `status`='".$action."') as `wp` ON `jp`.`id`=`wp`.`product_id` WHERE `wp`.`merchant_id`= '" . $merchant_id . "' LIMIT $offset,$limit ", 'all');
        }
        foreach ($model as $value) {
            if ($value['sku'] == "") {
                continue;
            }

            if ($value['type'] == "simple") {
                $productdata[$i]['sku'] = $value['sku'];
                $productdata[$i]['type'] = "No Variants";
                $productdata[$i]['status'] = $value['status'];
                $i++;
            } else {
                $optionResult = [];

                /*$query = "SELECT option_id,option_title,option_sku,option_qty,option_unique_id,option_price,asin,option_mpn FROM `jet_product_variants` WHERE product_id='" . $value['product_id'] . "' order by option_sku='" . addslashes($value['sku']) . "' desc";
                $optionResult = Data::sqlRecords($query);*/
                if($action && $action=='all')
                {
                    $optionResult = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product_variants` WHERE `merchant_id`='" . $merchant_id . "') as `jpv` INNER JOIN (SELECT * FROM `walmart_product_variants` WHERE `merchant_id`='" . $merchant_id . "' AND `status` IN ('PUBLISHED','UNPUBLISHED','STAGE')) as `wpv` ON `jpv`.`option_id`=`wpv`.`option_id` WHERE `wpv`.`merchant_id`= '" . $merchant_id . "' AND `wpv`.`product_id`='" . $value['product_id'] . "'", 'all');

                }else{
                    $optionResult = Data::sqlRecords("SELECT * FROM (SELECT * FROM `jet_product_variants` WHERE `merchant_id`='" . $merchant_id . "') as `jpv` INNER JOIN (SELECT * FROM `walmart_product_variants` WHERE `merchant_id`='" . $merchant_id . "' AND `status`='".$action."') as `wpv` ON `jpv`.`option_id`=`wpv`.`option_id` WHERE `wpv`.`merchant_id`= '" . $merchant_id . "' AND `wpv`.`product_id`='" . $value['product_id'] . "'", 'all');

                }

                if (is_array($optionResult) && count($optionResult) > 0) {
                    foreach ($optionResult as $key => $val) {
                        if ($val['option_sku'] == "")
                            continue;
                        if ($value['sku'] == $val['option_sku']) {
                            $productdata[$i]['type'] = "Parent";
                        } else {
                            $productdata[$i]['type'] = "Variants";
                        }
                        $productdata[$i]['sku'] = $val['option_sku'];
                        $i++;

                    }
                }
            }
        }
        foreach ($productdata as $v) {

            $row = array();
            $row[] = $v['sku'];
            $row[] = $v['type'];
            $row[] = $v['status'];

            fputcsv($file, $row);
        }
        fclose($file);

        $returnarr['success'] = "Product Information has been updated successfully";
        $returnarr['count'] = count($productdata);

        return json_encode($returnarr);
    }

    public function actionReadretirecsv()
    {
        if (Yii::$app->user->isGuest) {
            return \Yii::$app->getResponse()->redirect(\Yii::$app->getUser()->loginUrl);
        }
        $merchant_id = Yii::$app->user->identity->id;

        if (isset($_FILES['csvfile']['name'])) {
            //var_dump($_FILES);die;
            $mimes = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv', 'text/comma-separated-values','application/octet-stream');
            if (!in_array($_FILES['csvfile']['type'], $mimes)) {
                Yii::$app->session->setFlash('error', "Invalid file type. Please import only CSV file");
                return $this->redirect(['index']);
            }

            $newname = $_FILES['csvfile']['name'];

            if (!file_exists(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id)) {
                mkdir(Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id, 0775, true);
            }

            $target = Yii::getAlias('@webroot') . '/var/csv_import/product/' . date('d-m-Y') . '/' . $merchant_id . '/' . $newname . '-' . time();
            $row = 0;
            $flag = false;
            $row1 = 0;
            if (!file_exists($target)) {
                move_uploaded_file($_FILES['csvfile']['tmp_name'], $target);
            }

            $selectedProducts = array();
            $import_errors = array();
            if (($handle = fopen($target, "r"))) {

                $status = WalmartProductModel::PRODUCT_STATUS_UPLOADED;
                /*$allpublishedSku = WalmartProduct::getAllProductSku($merchant_id);*/
                /*$allpublishedSku = WalmartProduct::getAllProductSku($merchant_id,$status);*/

                $row = 0;
                while (($data = fgetcsv($handle, 90000, ",")) !== FALSE) {
                    if ($row == 0 && (trim($data[0]) != 'Sku')) {
                        $flag = true;
                        break;
                    }
                    $num = count($data);
                    $row++;
                    if ($row == 1)
                        continue;

                    $pro_sku = trim($data[0]);
                    /*$pro_type = trim($data[1]);*/

                    if ($pro_sku == ''/* || $pro_type == ''*/) {
                        $import_errors[$row] = 'Row ' . $row . ' : Invalid data.';
                        continue;
                    }

                    /*if(!in_array($pro_sku,$allpublishedSku)) {
                        $import_errors[$row] = 'Row '.$row.' : '.'Sku => "'.$pro_sku.'" is invalid/not published on walmart.';
                        continue;
                    }*/

                    $productData = array();
                    $productData['sku'] = $pro_sku;
                    /*$productData['type'] = $pro_type;*/

                    $productData['currency'] = CURRENCY;

                    $selectedProducts[] = $productData;
                }

                if (count($selectedProducts)) {

                    $session = Yii::$app->session;

                    $size_of_request = 5;//Number of products to be uploaded at once(in single feed)
                    $pages = (int)(ceil(count($selectedProducts) / $size_of_request));


                    //Increase Array Indexes By 1
                    //$selectedProducts = array_combine(range(1, count($selectedProducts)), array_values($selectedProducts));

                    //$session->set('selected_products', $selectedProducts);

                    return $this->render('bulkretire', [
                        'totalcount' => count($selectedProducts),
                        'pages' => $pages,
                        'products' => json_encode($selectedProducts)
                    ]);

                } else {
                    if (count($import_errors)) {
                        Yii::$app->session->setFlash('error', implode('<br>', $import_errors));
                    } else {
                        Yii::$app->session->setFlash('error', "Please Upload Csv file....");
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "File not found....");
            }
        } else {
            Yii::$app->session->setFlash('error', "Please Upload Csv file....");
        }
        return $this->redirect(['index']);
    }

    public function actionRetireproduct()
    {

        $session = Yii::$app->session;
        $skus = Yii::$app->request->post();

        $errors = [];
        $success = [];
        foreach ($skus['products'] as $sku) {

            $retireProduct = new Walmartapi(API_USER, API_PASSWORD, CONSUMER_CHANNEL_TYPE_ID);
            $feed_data = $retireProduct->retireProduct($sku['sku']);

            if (isset($feed_data['ItemRetireResponse'])) {
                $success[] = '<b>' . $feed_data['ItemRetireResponse']['sku'] . ' : </b>' . $feed_data['ItemRetireResponse']['message'];
            } elseif (isset($feed_data['errors']['error'])) {
                if (isset($feed_data['errors']['error']['code']) && $feed_data['errors']['error']['code'] == "CONTENT_NOT_FOUND.GMP_ITEM_INGESTOR_API" && $feed_data['errors']['error']['field'] == "sku") {
                    $errors[] = $sku['sku'] . ' : Product not Uploaded on Walmart.';
                } else {
                    $errors[] = $sku['sku'] . ' : ' . $feed_data['errors']['error']['description'];
                }
            } else {
                $errors[] = $sku['sku'] . ' : Sku not retired';
            }
        }

        if (count($errors)) {
            $return_msg['error'] = /*implode('<br/>', $errors);*/ json_encode($errors);

            //$return_msg['success']['count'] = count($errors);
        }
        if (count($success)) {
            $return_msg['success']['message'] = implode('<br/>', $success);
            $return_msg['success']['count'] = count($success);
        }

        return json_encode($return_msg);
    }

}