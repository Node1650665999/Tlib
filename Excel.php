<?php


require_once APPPATH.'/libraries/PHPExcel.php';
require_once APPPATH.'/libraries/PHPExcel/IOFactory.php';

/**
 * Class Excel
 */
class Excel
{
    /**
     * Excel constructor.
     */
    function __construct()
    {
        ini_set ('memory_limit', '1000M');
        ini_set('max_execution_time', '3000');
    }


    /**
     * 导出excel
     *
     * @param string $filename 导出的文件名
     * @param array $data      sheet的数据
     */
    function export($filename='', $data=[])
    {
        $obj_excel    = new PHPExcel();
        $sheet = $obj_excel->getActiveSheet(0);
        $sheet->setTitle('sheet');

        $sheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //全局水平居中

        $sheet->fromArray($data); //为sheet填充数据
        // 生成2007excel格式的xlsx文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory:: createWriter($obj_excel, 'Excel2007');
        $objWriter->save( 'php://output');
        exit;
    }

    /**
     * 读取excel并以数组返回
     *
     * @param null $file  excel文件
     * @return array
     */
    function read($file = null)
    {
        //获取active sheet
        $inputFileType = PHPExcel_IOFactory::identify($file);
        $obj_excel     = PHPExcel_IOFactory::createReader($inputFileType); //Excel5或者excel2007
        $obj_excel->setReadDataOnly(true);  //能够获取正确的行数
        $obj_excel     = $obj_excel->load($file);
        $sheet         = $obj_excel->getActiveSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        //sheet数据
        $sheet_data =  $sheet->rangeToArray("A1:$highestCol$highestRow", null, true, false, false);
        $header          = array_shift($sheet_data);         //excel全部标题
        return [
            'header'         => $header,
            'sheet_data'     => $sheet_data,
            'rows'           => $highestRow,
            'cols'           => $highestCol
        ];
    }

}