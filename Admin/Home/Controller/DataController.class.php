<?php
namespace Home\Controller;

/**
 * DataController
 * 数据管理
 */
class DataController extends CommonController {
    /**
     * 数据备份
     * @return
     */
    public function backup() {
        $tablesInfo = M()->query('SHOW TABLE STATUS');
        $totalSize = 0;

        // 计算数据表大小
        foreach ($tablesInfo as $key => $tableInfo) {
            $tableSize = $tableInfo['Data_length']
                         + $tableInfo['Index_length'];
            $totalSize += $tableSize;
            $tablesInfo[$key]['size'] = bytes_format($tableSize);
        }

        $this->assign('tables_info', $tablesInfo);
        $this->assign('total_size', bytes_format($totalSize));
        $this->assign('table_num', count($tablesInfo));
        $this->display();
    }

    /**
     * 处理备份
     * @return
     */
    public function doBackup() {
        if (!IS_POST) {
            $this->errorReturn('访问出错');
        }

        if (!isset($_POST['tables'])) {
            $this->errorReturn('请先选择需要备份的数据表');
        }

        $dataLogic = D('Data', 'Logic');
        $result = $dataLogic->backup($_POST['tables']);

        if ($result['status'] !== $dataLogic::EXECUTE_FINISH) {
            return $this->errorReturn('无效的操作');
        }

        // 返回备份成功信息
        $info = '成功备份所选数据库表结构和数据，本次备份共生成了'
                . $result['data']['backuped_conut'] . "个SQL文件。"
                . '耗时：' . $result['data']['backuped_conut'] . '秒';

        $this->successReturn($info, U('Data/restore'));
    }

    /**
     * 数据导入
     * @return
     */
    public function restore() {
        $info = D('Data', 'Logic')->getBackupFilesInfo();

        $this->assign('total_size', $info['total_size']);
        $this->assign('info_list', $info['info_list']);
        $this->assign('files_count', count($info['info_list']));
        $this->display();
    }

    /**
     * 处理数据导入
     * @return
     */
    public function doRestore() {
        if (!IS_POST) {
            $this->errorReturn('访问出错');
        }

        $dataLogic = D('Data', 'Logic');
        $result = $dataLogic->restore($_POST['file_prefix']);

        switch ($result['status']) {
            case $dataLogic::FILE_NOT_FOUND:
                $this->errorReturn('需要导入的文件不存在');
                break ;
            
            case $dataLogic::EXECUTE_NOT_FINISH:
                $info = '如果导入SQL文件卷较大(多)导入时间可能需要几分钟甚至更久'
                        . '请耐心等待导入完成，导入期间请勿刷新本页，当前导入进度：'
                        . '<font color="red">已经导入'
                        . $result['data']['imported'] . '条Sql</font>';
                // 防止url缓存
                $url = U('Data/doRestore', array('rand_code' => rand_code(5)));
                // 返回json
                $this->successReturn($info, $url);
                break ;

            case $dataLogic::EXECUTE_FINISH:
                $info = "导入成功，耗时：{$result['data']['time']} 秒钟";
                $this->successReturn($info);
                break ;

            default:
                $this->errorReturn('无效的操作');
                break ;
        }
    }

    /**
     * 数据压缩
     * @return
     */
    public function unpack() {
        $this->display();
    }

    /**
     * 数据优化
     * @return
     */
    public function optimize() {
        $this->display();
    }
}
