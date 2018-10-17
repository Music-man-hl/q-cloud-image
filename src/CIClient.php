<?php
/**
 * api defination of QCloud Image.
 */
namespace MusicManHl\QcloudImage;

use MusicManHl\QcloudImage\HttpClient;
use MusicManHl\QcloudImage\Error;
use MusicManHl\QcloudImage\Auth;
use MusicManHl\QcloudImage\Conf;

class CIClient {

	public function __construct($appid, $secretId, $secretKey, $bucket){
		$this->bucket = $bucket;
		$this->auth = new Auth($appid, $secretId, $secretKey);
		$this->http = new HttpClient();
		$this->conf = new Conf();
	}

    public function useHttp()
    {
        $this->conf->useHttp();
    }

    public function useHttps()
    {
        $this->conf->useHttps();
    }

    public function setTimeout($timeout)
    {
        $this->conf->setTimeout($timeout);
    }

    /**
     * 使用新服务器域名 recognition.image.myqcloud.com<br>
     * <br>
     * 如果你:<br>
     * 1.正在使用人脸识别系列功能( https://cloud.tencent.com/product/FaceRecognition/developer )<br>
     * 2.并且是通过旧域名访问的<br>
     * 那么: 请继续使用旧域名
     */
    public function useNewDomain()
    {
        $this->conf->useNewDomain();
    }

    /**
     * 使用旧服务器域名 recognition.image.myqcloud.com<br>
     * <br>
     * 如果你:<br>
     * 1.正在使用人脸识别系列功能( https://cloud.tencent.com/product/FaceRecognition/developer )<br>
     * 2.并且是通过旧域名访问的<br>
     * 那么: 请继续使用旧域名
     */
    public function useOldDomain()
    {
        $this->conf->useOldDomain();
    }

    public function setProxy($proxy)
    {
        $this->http->setProxy($proxy);
    }

	/**
	 * 黄图识别
	 * @param  array(associative) $picture   识别的图片
	 *                 * @param  array(associative) $pictures   Person的人脸图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
	 *                  以上两种指定其一即可，如果指定多个，则优先使用urls，其次 files
	 *
	 * @return array|string
	 */
	public function pornDetect($picture) {

	    if (!$picture || !is_array($picture)) {
	        return Error::json(Error::$Param, 'param picture must be array');
	    }

	    $reqUrl = $this->conf->buildUrl('/detection/pornDetect');
	    $headers = $this->baseHeaders();
	    $files = $this->baseParams();

	    if (isset($picture['urls'])) {
	        $headers[] = 'Content-Type:application/json';
	        $files['url_list'] = $picture['urls'];

	        $data = json_encode($files);
	    } else if (isset($picture['files'])){
	        $index = 0;

	        foreach ($picture['files'] as $file) {
	            if(PATH_SEPARATOR==';'){    // WIN OS
	                $path = iconv("UTF-8","gb2312//IGNORE",$file);
	            } else {
	                $path = $file;
	            }

	            $path = realpath($path);
	            if (!file_exists($path)) {
	               return Error::json(Error::$FilePath, 'file '.$file.' not exist');
	            }

	            if (function_exists('curl_file_create')) {
	                $files["image[$index]"] = curl_file_create($path);
	            } else {
	                $files["image[$index]"] = '@'.($path);
	            }
	            $index++;
	        }

	        $data = $files;
	    } else {
	        return Error::json(Error::$Param, 'param picture is illegal');
	    }

	    return $this->doRequest(array(
	        'url' => $reqUrl,
	        'method' => 'POST',
	        'data' => $data,
	        'header' => $headers,
	        'timeout' => $this->conf->timeout()
	    ));
	}

	/**
	 * 标签识别
	 * @param  array(associative) $picture   识别的图片
	 *                 * @param  array(associative) $picture
	 *                  url    array: 指定图片的url数组
	 *                  file   array: 指定图片的路径数组
	 *                  buffer string: 指定图片的内容
	 *								  base64 string: 指定图片的内容
	 *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次base64,其次 file，再次 buffer。
	 *
	 * @return string
	 */
	public function tagDetect($picture) {

	    if (!$picture || !is_array($picture)) {
	        return Error::json(Error::$Param, 'param picture must be array');
	    }

	    $reqUrl = $this->conf->buildUrl('/v1/detection/imagetag_detect');
	    $headers = $this->baseHeaders();
	    $headers[] = 'Content-Type:application/json';
	    $files = $this->baseParams();

	    if (isset($picture['url'])) {
	        $files['url'] = $picture['url'];
	    } else if (isset($picture['file'])) {
	        if(PATH_SEPARATOR==';') {    // WIN OS
	            $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
	        } else {
	            $path = $picture['file'];
	        }

	        $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
            }

            $files['image'] = base64_encode(file_get_contents($filePath));
	    } else if (isset($picture['buffer'])) {
	       $files['image'] = base64_encode($picture['buffer']);
			}else if (isset($picture['base64'])) {
			 	$files['image'] = $picture['base64'];
			} else {
	        return Error::json(Error::$Param, 'param picture is illegal');
	    }

	    $data = json_encode($files);
	    return $this->doRequest(array(
	        'url' => $reqUrl,
	        'method' => 'POST',
	        'data' => $data,
	        'header' => $headers,
	        'timeout' => $this->conf->timeout()
	    ));
	}

	/**
	 * 身份证识别
	 * @param  array(associative) $picture   识别的图片
	 *                 * @param  array(associative) $pictures   Person的人脸图片
	 *                  urls    array: 指定图片的url数组
	 *                  files   array: 指定图片的路径数组
	 *                  buffers array: 指定图片的内容
	 *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
	 * @param $cardType int 0为身份证有照片的一面，1为身份证有国徽的一面
	 * @return array|string
	 */
	public function idcardDetect($picture, $cardType=0) {

	    if (!$picture || !is_array($picture)) {
	        return Error::json(Error::$Param, 'param picture must be array');
	    }

	    if ($cardType !== 0 && $cardType !== 1) {
            return Error::json(Error::$Param, 'param cardType error');
        }

	    $reqUrl = $this->conf->buildUrl('/ocr/idcard');
	    $headers = $this->baseHeaders();
	    $files = $this->baseParams();
	    $files['card_type'] = $cardType;
	    if (isset($picture['urls'])) {
	        $headers[] = 'Content-Type:application/json';
	        $files['url_list'] = $picture['urls'];

	        $data = json_encode($files);
	    } else if (isset($picture['files'])){
	        $index = 0;

	        foreach ($picture['files'] as $file) {
	            if(PATH_SEPARATOR==';'){    // WIN OS
	                $path = iconv("UTF-8","gb2312//IGNORE",$file);
	            } else {
	                $path = $file;
	            }

	            $path = realpath($path);
	            if (!file_exists($path)) {
	                return Error::json(Error::$FilePath, 'file '.$file.' not exist');
	            }

	            if (function_exists('curl_file_create')) {
	                $files["image[$index]"] = curl_file_create($path);
	            } else {
	                $files["image[$index]"] = '@'.($path);
	            }
	            $index++;
	        }

	        $data = $files;
	    } else if (isset($picture['buffers'])){
	        $index = 0;

	        foreach ($picture['buffers'] as $buffer) {
	            $files["image[$index]"] = $buffer;

	            $index++;
	        }

	        $data = $files;
	    } else {
	        return Error::json(Error::$Param, 'param picture is illegal');
	    }

	    return $this->doRequest(array(
	        'url' => $reqUrl,
	        'method' => 'POST',
	        'data' => $data,
	        'header' => $headers,
	        'timeout' => $this->conf->timeout()
	    ));
	}

	/**
	 * 名片识别v2
	 * @param  array(associative) $picture   识别的图片
	 *                 * @param  array(associative) $pictures   Person的人脸图片
	 *                  urls    array: 指定图片的url数组
	 *                  files   array: 指定图片的路径数组
	 *                  buffers array: 指定图片的内容
	 *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
	 * @return array|string
	 */
	public function namecardV2Detect($picture) {

	    if (!$picture || !is_array($picture)) {
	        return Error::json(Error::$Param, 'param picture must be array');
	    }
        $reqUrl = $this->conf->buildUrl('/ocr/businesscard');
	    $headers = $this->baseHeaders();
	    $files = $this->baseParams();
	    if (isset($picture['urls'])) {
	        $headers[] = 'Content-Type:application/json';
	        $files['url_list'] = $picture['urls'];

	        $data = json_encode($files);
	    } else if (isset($picture['files'])){
	        $index = 0;

	        foreach ($picture['files'] as $file) {
	            if(PATH_SEPARATOR==';'){    // WIN OS
	                $path = iconv("UTF-8","gb2312//IGNORE",$file);
	            } else {
	                $path = $file;
	            }

	            $path = realpath($path);
	            if (!file_exists($path)) {
	                return Error::json(Error::$FilePath, 'file '.$file.' not exist');
	            }

	            if (function_exists('curl_file_create')) {
	                $files["image[$index]"] = curl_file_create($path);
	            } else {
	                $files["image[$index]"] = '@'.($path);
	            }
	            $index++;
	        }

	        $data = $files;
	    } else if (isset($picture['buffers'])){
	        $index = 0;

	        foreach ($picture['buffers'] as $buffer) {
	            $files["image[$index]"] = $buffer;

	            $index++;
	        }

	        $data = $files;
	    } else {
	        return Error::json(Error::$Param, 'param picture is illegal');
	    }

	    return $this->doRequest(array(
	        'url' => $reqUrl,
	        'method' => 'POST',
	        'data' => $data,
	        'header' => $headers,
	        'timeout' => $this->conf->timeout()
	    ));
	}

    /**
     * 行驶证驾驶证识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   证件的图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  buffers array: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param type int 表示识别类型，0表示行驶证，1表示驾驶证
     * @return array|string
     */
    public function drivingLicence($picture, $type=0){

        if (!is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        if ($type !== 0 && $type !== 1) {
            return Error::json(Error::$Param, 'param type error');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/drivinglicence');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $files['type'] = $type;
            $data = json_encode($files);
        } else {
            $files['type'] = strval($type);
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 车牌号识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   车牌号的图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  buffers array: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array|string
     */
    public function plate($picture){

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/plate');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 银行卡识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   车牌号的图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  buffers array: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array|string
     */
    public function bankcard ($picture){

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/bankcard');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 营业执照识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   车牌号的图片
     *                  urls    array: 指定图片的url数组
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files
     * @return array|string
     */
    public function bizlicense ($picture){

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/bizlicense');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 通用印刷体识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   车牌号的图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files
     * @return array|string
     */
    public function general ($picture){

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/general');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 手写体识别
     * @param  array(associative) $picture   识别的图片
     *                 * @param  array(associative) $pictures   车牌号的图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files
     * @return array|string
     */
    public function handwriting ($picture){

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/handwriting');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 创建Person
     * @param  string $personId  创建的Person的ID
     * @param  array  $groupIds  创建的Person需要加入的Group
     * @param  array(associative) $picture   创建的Person的人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  string $personName  创建的Person的名字
     * @param  string $tag       为创建的Person打标签
     *
     * @return array|string
     */
	public function faceNewPerson($personId, $groupIds, $picture, $personName=NULL, $tag=NULL) {

        if (! is_array($groupIds)) {
            return Error::json(Error::$Param, 'param groupIds must be array');
        }
        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/newperson');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);
        if ($personName) {
            $files['person_name'] = strval($personName);
        }
        if ($tag) {
            $files['tag'] = strval($tag);
        }
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['group_ids'] = $groupIds;
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            $index = 0;
            foreach ($groupIds as $groupId) {
                $files["group_ids[".strval($index++)."]"] = strval($groupId);
            }

            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);

                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }
        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 删除Person
     * @param  string $personId  删除的Person的ID
     *
     * @return array|string
     */
	public function faceDelPerson($personId) {
        $reqUrl = $this->conf->buildUrl('/face/delperson');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 为Person 添加人脸
     * @param  string $personId  创建的Person的ID
     * @param  array(associative) $pictures   Person的人脸图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  buffers array: 指定图片的内容数组
     *                  以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，再次 buffers。
     * @param  string $tag       为face打标签
     *
     * @return array|string
     */
	public function faceAddFace($personId, $pictures, $tag=NULL) {
        if (! is_array($pictures)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/addface');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);
        if ($tag) {
            $files['tag'] = strval($tag);
        }
        if (isset($pictures['urls']) && is_array($pictures['urls'])) {
            $headers[] = 'Content-Type:application/json';
            $files['urls'] = $pictures['urls'];
            $data = json_encode($files);
        } else {
            if (isset($pictures['files']) && is_array($pictures['files'])) {
                $index = 0;
                foreach ($pictures['files'] as $picture) {
                    if(PATH_SEPARATOR==';'){    // WIN OS
                        $path = iconv("UTF-8","gb2312//IGNORE",$picture);
                    } else {
                        $path = $picture;
                    }

                    $filePath = realpath($path);
                    if (! file_exists($filePath)) {
                        return Error::json(Error::$FilePath, 'file '.$picture.' not exist');
                    }

                    if (function_exists('curl_file_create')) {
                        $files["images[$index]"] = curl_file_create($filePath);
                    } else {
                        $files["images[$index]"] = '@' . $filePath;
                    }
                    $index ++;
                }
            } else if (isset($pictures['buffers']) && is_array($pictures['buffers'])) {
                $index = 0;
                foreach ($pictures['buffers'] as $buffer) {
                    $files["images[".$index++."]"] = $buffer;
                }
            } else {
                return Error::json(Error::$Param, 'param pictures is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 删除face
     * @param  string $personId  操作的Person的ID
     * @param  array  $faceIds   删除的face的ID数组
     *
     * @return array|string
     */
	public function faceDelFace($personId, $faceIds) {

        if (! is_array($faceIds)) {
            return Error::json(Error::$Param, 'param faceIds must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/delface');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);
        $files['face_ids'] = $faceIds;

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 设置信息
     * @param  string $personId    操作的Person的ID
     * @param  string $personName  Person的名字
     * @param  string $tag         为Person打标签
     *
     * @return array|string
     */
	public function faceSetInfo($personId, $personName=NULL, $tag=NULL) {
        $reqUrl = $this->conf->buildUrl('/face/setinfo');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);
        if ($personName) {
            $files['person_name'] = strval($personName);
        }
        if ($tag) {
            $files['tag'] = strval($tag);
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 获取信息
     * @param  string $personId    操作的Person的ID
     *
     * @return array|string
     */
	public function faceGetInfo($personId) {
        $reqUrl = $this->conf->buildUrl('/face/getinfo');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 获取app下的组列表
     *
     * @return array|string
     */
	public function faceGetGroupIds() {
        $reqUrl = $this->conf->buildUrl('/face/getgroupids');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 获取group下的person列表
     * @param  string $groupId    操作的GroupID
     *
     * @return array|string
     */
	public function faceGetPersonIds($groupId) {
        $reqUrl = $this->conf->buildUrl('/face/getpersonids');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['group_id'] = strval($groupId);

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 获取person的face列表
     * @param  string $personId    操作的Person的ID
     *
     * @return array|string
     */
	public function faceGetFaceIds($personId) {
        $reqUrl = $this->conf->buildUrl('/face/getfaceids');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 获取face的信息
     * @param  string $faceId    操作的FaceID
     *
     * @return array|string
     */
	public function faceGetFaceInfo($faceId) {
        $reqUrl = $this->conf->buildUrl('/face/getfaceinfo');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        $files['face_id'] = strval($faceId);

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 识别指定的图片属于哪个人
     * @param  array  $groupId   需要对比的GroupId
     * @param  array(associative) $picture   Person的人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     *
     * @return array|string
     */
	public function faceIdentify($groupId, $picture) {
        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/identify');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['group_id'] = strval($groupId);
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 识别指定的图片是不是指定的person
     * @param  array  $personId   需要对比的person
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     *
     * @return array|string
     */
	public function faceVerify($personId, $picture) {
        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/verify');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['person_id'] = strval($personId);
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 对比两张图片是否是同一个人
     * @param  array(associative) $pictureA   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  array(associative) $pictureB   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     *
     * @return array|string
     */
	public function faceCompare($pictureA, $pictureB) {
        if (! is_array($pictureA)) {
            return Error::json(Error::$Param, 'param pictureA must be array');
        }
        if (! is_array($pictureB)) {
            return Error::json(Error::$Param, 'param pictureB must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/compare');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();

        if (isset($pictureA['url'])) {
            $files['urlA'] = $pictureA['url'];
        } else if (isset($pictureA['file'])) {
            if(PATH_SEPARATOR==';'){    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$pictureA['file']);
            } else {
                $path = $pictureA['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$pictureA['file'].' not exist');
            }

            if (function_exists('curl_file_create')) {
                $files['imageA'] = curl_file_create($filePath);
            } else {
                $files['imageA'] = '@' . $filePath;
            }
        } else if (isset($pictureA['buffer'])) {
            $files['imageA'] = $pictureA['buffer'];
        } else {
            return Error::json(Error::$Param, 'param pictureA is illegal');
        }

        if (isset($pictureB['url'])) {
            $files['urlB'] = $pictureB['url'];
        } else if (isset($pictureB['file'])) {
            $filePath = realpath($pictureB['file']);
            if(PATH_SEPARATOR==';'){    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$pictureB['file']);
            } else {
                $path = $pictureB['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$pictureB['file'].' not exist');
            }

            if (function_exists('curl_file_create')) {
                $files['imageB'] = curl_file_create($filePath);
            } else {
                $files['imageB'] = '@' . $filePath;
            }
        } else if (isset($pictureB['buffer'])) {
            $files['imageB'] = $pictureB['buffer'];
        } else {
            return Error::json(Error::$Param, 'param pictureB is illegal');
        }

        if (isset($pictureA['url']) && isset($pictureB['ur'])) {
            $headers[] = 'Content-Type:application/json';
            $data = json_encode($files);
        } else {
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 检测图中的人脸
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  int  $mode  检测模式，0为检测所有人脸，1为检测最大的人脸
     *
     * @return array|string
     */
	public function faceDetect($picture, $mode=0) {
        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }
        if ($mode !== 0 && $mode !== 1) {
            return Error::json(Error::$Param, 'param mode error');
        }

        $reqUrl = $this->conf->buildUrl('/face/detect');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['mode'] = $mode;
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            $files['mode'] = strval($mode);
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);

                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 定位图中人脸的五官信息
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  int  $mode  检测模式，0为检测所有人脸，1为检测最大的人脸
     *
     * @return string    http请求响应
     */
	public function faceShape($picture, $mode=0) {

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }
        if ($mode !== 0 && $mode !== 1) {
            return Error::json(Error::$Param, 'param mode error');
        }

        $reqUrl = $this->conf->buildUrl('/face/shape');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['mode'] = $mode;
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            $files['mode'] = strval($mode);
            if (isset($picture['file'])) {
                 if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 多脸检索
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file
     * @param  array  $idtype  group_id:单个id，group_ids：多个id
     *
     * @return string    http请求响应
     */
    public function multidentify($picture, $idtype){

        if (!$picture || !is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/multidentify');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();

        if (isset($picture['url'])) {   //url
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];

            if (isset($idtype['group_id'])) {
                $files['group_id'] = $idtype['group_id'];
            } else if (isset($idtype['group_ids'])) {
                $files['group_ids'] = $idtype['group_ids'];
            } else {
                return Error::json(Error::$Param, 'param idtype is illegal');
            }
        } else if (isset($picture['file'])) {
            $headers[] = 'Content-Type:multipart/form-data';
            if(PATH_SEPARATOR==';') {    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
            } else {
                $path = $picture['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
            }

            if (function_exists('curl_file_create')) {
                $files["image"] = curl_file_create($filePath);
            } else {
                $files["image"] = '@'.($filePath);
            }

            if (isset($idtype['group_id'])) {
                $files['group_id'] = $idtype['group_id'];
            } else if (isset($idtype['group_ids'])) {
                if(!isset($picture['url'])){
                    $index = 0;

                    foreach ($idtype['group_ids'] as $id) {
                        $files["group_ids[$index]"] = $id;
                        $index++;
                    }
                }

            } else {
                return Error::json(Error::$Param, 'param idtype is illegal');
            }
        } else if (isset($picture['buffer'])) {
            $files['image'] = $picture['buffer'];
        } else {
            return Error::json(Error::$Param, 'param picture is illegal');
        }


        if(isset($picture['url'])){
            $data = json_encode($files);
        }else if(isset($picture['file'])){
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 人脸静态活体检测
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  int  $mode  检测模式，0为检测所有人脸，1为检测最大的人脸
     *
     * @return string    http请求响应
     */
    public function liveDetectPicture ($picture, $sign) {

        if (!$picture || !is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/livedetectpicture');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();

        if (isset($picture['url'])) {
            $files['url'] = $picture['url'];
            $files['sign'] = $sign;
        } else if (isset($picture['file'])) {
            $files['sign'] = $sign;
            if(PATH_SEPARATOR==';') {    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
            } else {
                $path = $picture['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
            }

            $files['image'] = base64_encode(file_get_contents($filePath));
        } else if (isset($picture['buffer'])) {
            $files['image'] = base64_encode($picture['buffer']);
        } else {
            return Error::json(Error::$Param, 'param picture is illegal');
        }

        $data = json_encode($files);
        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
    }

    /**
     * 检测图片中的人和给定的信息是否匹配
     * @param  string  $idcardNumber   身份证号
     * @param  string  $idcardName     姓名
     * @param  array(associative) $picture   人脸图片
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     *
     * @return string    http请求响应
     */
	public function faceIdCardCompare($idcardNumber, $idcardName, $picture) {

        if (! is_array($picture)) {
            return Error::json(Error::$Param, 'param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/idcardcompare');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['idcard_number'] = strval($idcardNumber);
        $files['idcard_name'] = strval($idcardName);

        if (isset($picture['url'])) {
            $headers[] = 'Content-Type:application/json';
            $files['url'] = $picture['url'];
            $data = json_encode($files);
        } else {
            if (isset($picture['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$picture['file']);
                } else {
                    $path = $picture['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$picture['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['image'] = curl_file_create($filePath);
                } else {
                    $files['image'] = '@' . $filePath;
                }
            } else if (isset($picture['buffer'])) {
                $files['image'] = $picture['buffer'];
            } else {
                return Error::json(Error::$Param, 'param picture is illegal');
            }
            $data = $files;
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $data,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 活体检测第一步：获取唇语（验证码）
     * @param  string $seq    指定一个sessionId，若使用，请确保id唯一。
     *
     * @return string    http请求响应
     */
	public function faceLiveGetFour($seq=NULL) {
        $reqUrl = $this->conf->buildUrl('/face/livegetfour');
        $headers = $this->baseHeaders();
        $headers[] = 'Content-Type:application/json';
        $files = $this->baseParams();
        if ($seq) {
            $files['seq'] = strval($seq);
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => json_encode($files),
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 活体检测第二步：检测
     * @param  string  $validate    faceLiveGetFour获取的验证码
     * @param  array(associative) $video     拍摄的视频
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer。
     * @param  bool  $compareFlag   是否将视频中的人和card图片比对
     * @param  array(associative) $card      人脸图片
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer。
     * @param  string $seq    指定一个sessionId，若使用，请确保id唯一。
     *
     * @return string    http请求响应
     */
	public function faceLiveDetectFour($validate, $video, $compareFlag, $card=NULL, $seq=NULL) {
        if (! is_array($video)) {
            return Error::json(Error::$Param, 'param video must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/livedetectfour');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['validate_data'] = strval($validate);

        if (isset($video['file'])) {
            if(PATH_SEPARATOR==';'){    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$video['file']);
            } else {
                $path = $video['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$video['file'].' not exist');
            }

            if (function_exists('curl_file_create')) {
                $files['video'] = curl_file_create($filePath);
            } else {
                $files['video'] = '@' . $filePath;
            }
        } else if (isset($video['buffer'])) {
            $files['video'] = $video['buffer'];
        } else {
            return Error::json(Error::$Param, 'param video is illegal');
        }

        if ($compareFlag) {
            if (! is_array($card)) {
                return Error::json(Error::$Param, 'param card must be array');
            }
            if (isset($card['file'])) {
                if(PATH_SEPARATOR==';'){    // WIN OS
                    $path = iconv("UTF-8","gb2312//IGNORE",$card['file']);
                } else {
                    $path = $card['file'];
                }

                $filePath = realpath($path);
                if (! file_exists($filePath)) {
                    return Error::json(Error::$FilePath, 'file '.$card['file'].' not exist');
                }

                if (function_exists('curl_file_create')) {
                    $files['card'] = curl_file_create($filePath);
                } else {
                    $files['card'] = '@' . $filePath;
                }
            } else if (isset($card['buffer'])) {
                $files['card'] = $card['buffer'];
            } else {
                return Error::json(Error::$Param, 'param card is illegal');
            }
            $files['compare_flag'] = 'true';
        } else {
            $files['compare_flag'] = 'false';
        }
        if ($seq) {
            $files['seq'] = strval($seq);
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $files,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}

    /**
     * 活体检测第二步：检测--对比指定身份信息
     * @param  string  $validate    faceLiveGetFour获取的验证码
     * @param  array(associative) $video     拍摄的视频
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer。
     * @param  string  $idcardNumber   身份证号
     * @param  string  $idcardName     姓名
     * @param  string  $seq    指定一个sessionId，若使用，请确保id唯一。
     *
     * @return string    http请求响应
     */
	public function faceIdCardLiveDetectFour($validate, $video, $idcardNumber, $idcardName, $seq=NULL) {
        if (! is_array($video)) {
            return Error::json(Error::$Param, 'param video must be array');
        }

        $reqUrl = $this->conf->buildUrl('/face/idcardlivedetectfour');
        $headers = $this->baseHeaders();
        $files = $this->baseParams();
        $files['validate_data'] = strval($validate);
        $files['idcard_number'] = strval($idcardNumber);
        $files['idcard_name'] = strval($idcardName);

        if (isset($video['file'])) {
            if(PATH_SEPARATOR==';'){    // WIN OS
                $path = iconv("UTF-8","gb2312//IGNORE",$video['file']);
            } else {
                $path = $video['file'];
            }

            $filePath = realpath($path);
            if (! file_exists($filePath)) {
                return Error::json(Error::$FilePath, 'file '.$video['file'].' not exist');
            }

            if (function_exists('curl_file_create')) {
                $files['video'] = curl_file_create($filePath);
            } else {
                $files['video'] = '@' . $filePath;
            }
        } else if (isset($video['buffer'])) {
            $files['video'] = $video['buffer'];
        } else {
            return Error::json(Error::$Param, 'param video is illegal');
        }

        if ($seq) {
            $files['seq'] = strval($seq);
        }

        return $this->doRequest(array(
            'url' => $reqUrl,
            'method' => 'POST',
            'data' => $files,
            'header' => $headers,
            'timeout' => $this->conf->timeout()
        ));
	}


    /**
     * send http request
     * @param  array $request http请求信息
     *                   url        : 请求的url地址
     *                   method     : 请求方法，'get', 'post', 'put', 'delete', 'head'
     *                   data       : 请求数据，如有设置，则method为post
     *                   header     : 需要设置的http头部
     *                   host       : 请求头部host
     *                   timeout    : 请求超时时间
     *                   cert       : ca文件路径
     *                   ssl_version: SSL版本号
     * @return string    http请求响应
     */
    private function doRequest($request) {
        $result = $this->http->sendRequest($request);
		$json = json_decode($result, true);
		if ($json) {
			$json['http_code'] = $this->http->statusCode();
			return json_encode($json);
		}

		return Error::json(Error::$Network, "response is not json: ".$result, $this->http->statusCode());
    }

    private function baseHeaders() {
        return array (
            'Authorization:'.$this->auth->getSign($this->bucket),
            'User-Agent:'.Conf::getUa($this->auth->getAppId()),
        );
    }
    private function baseParams() {
        return array (
            'appid' => $this->auth->getAppId(),
            'bucket' => $this->bucket,
        );
    }

	private $bucket;
	private $auth;
	private $http;
	private $conf;
}
