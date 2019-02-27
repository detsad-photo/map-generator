<?php

require_once __DIR__."/engine/autoload.php";

if (!empty($_FILES['table'])){

    error_log( "Start of processing table" );

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(realpath($_FILES['table']['tmp_name']));
    $sheet = $spreadsheet->getActiveSheet();

    $maps_api = new \Yandex\Geo\Api();

    $points = [];

    $point = [];

    foreach ($sheet->getRowIterator() as $row) {

        $cell_iterator = $row->getCellIterator();
        $cell_iterator->setIterateOnlyExistingCells(FALSE);

        foreach ($cell_iterator as $i => $cell) {
            if (!empty($cell->getValue())) {
                if ($i == 'A'){
                    $point['id'] = $cell->getValue();
                } elseif ($i == 'B'){
                    $point['description'] = str_replace("\n", ', ', $cell->getValue());
                } elseif ($i == 'C'){
                    $point['persons'] = str_replace("\n", ', ', $cell->getValue());
                } elseif ($i == 'D'){
                    $point['address'] = $cell->getValue();
                } elseif ($i == 'F'){
                    $point['address'] .= ', '.str_replace("\n", ', ', $cell->getValue());
                }
            }
        }

        $repeat = false;
        foreach ($points as $temp_point){
            if ($point['address'] == $temp_point['address']){
                $repeat = true;
                break;
            }
        }

        if (!$repeat AND !empty($point) AND !empty($point['address'])){
            $points[] = $maps->GeneratePoint($point);
        }
    }

    if (!empty($points)) {

        $result = $maps->Add($points);

        if ($result !== false) {
            echo "Готово! <a href='/map.php?id=".$result."' target='_blank'>Ссылка на карту</a><br><br><br>";
        }

    }

    error_log( "End of processing table" );

}

?>

<form method="POST" enctype="multipart/form-data">
    Таблица в формате xlsx: <input type="file" name="table">
    <br>
    <button type="submit">Загрузить</button>
</form>