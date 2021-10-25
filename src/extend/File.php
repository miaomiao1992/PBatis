<?php
/**文件处理类
 * Created by Yang333
 * 2021-05-08 创建
 */

namespace pbatis\extend;

class File
{

    private $fileName = null;//文件名
    private $filePath;//文件路径,含文件名
    private $fileSize;//文件大小
    private $fileType;//文件类型，即文件后缀
    private $error;//上传错误码

    public function __construct($name = 'name')
    {
        if (isset($_FILES['file'][$name])) {
            $this->fileName = $_FILES['file'][$name];//文件名
            $this->filePath = $_FILES['file']['tmp_name'];//文件路径
            $this->fileSize = $_FILES['file']['size'];
            $arr = pathinfo($this->fileName);
            $this->fileType = $arr['extension'];
            $this->error = $_FILES['file']['error'];
        }

    }

    /**移动文件到某个路径
     * @param $path
     * @return bool
     */
    public function move($path)
    {
        if (!file_exists($path)) {
            mkdir($path);
        }
        $newFileName = date('YmdHis', time()) . rand(100, 1000) . '.' . $this->fileType;
        if (move_uploaded_file($this->filePath, $path . '/' . $newFileName)) {
            $this->filePath = $path . '/' . $newFileName;
            return true;
        }
        return false;

    }

    /**判断文件类型
     * @param string[] $allowType
     * @return bool
     */
    public function isType($allowType = ['csv'])
    {
        if ($this->fileName === null) {
            return false;
        }
        if (!in_array($this->fileType, $allowType)) {
            return false;
        }
        return true;
    }

    /**获取文件名
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**获取文件完整路径（含文件名和后缀）
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**获取文件大小，单位KB
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**获取文件类型
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**获取文件上传错误码
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}