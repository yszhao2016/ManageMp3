<?php
/*************************************
 *	音乐处理---H_music.php
 *	创建人: huang_xiang
 *	创建时间：2009-2-17 15:09
 *	更新时间：2009-2-18 13:37
 *	Email：yin273642232@163.com QQ:273642232
 *************************************/
/*************************************
实例代码
include_once("H_music.php");													//引入文件
$H_music = new H_music();														//产生对象
$H_music -> H_ck_size($file);													//检测文件完整型;返回-ture/false;$file-文件路径';
$H_music -> H_get_info($file);													//获得文件信息;返回-文件信息数组;$file-文件路径';
$pa = array('Title' => '天香', 'AlbumTitle' => '我的专辑名称','Artist' => '黄祥-huangxiang','Description' => '描述','Year' => '2009-2-18 12:35','Genre' => '流派','Copyright' => '版权');	//只能设置这些信息
$H_music -> H_set_info($file, $pa));											//重写文件信息;返回-ture/false;$file-文件路径.$pa-修改信息';
 *************************************/
//Start Class
class H_music{
    public $H_debug = false;
    public $H_desce;					//描述
    public $H_ROOT_DIR;					//文件地址路径
    public $_wma = false;
    public $_mp3 = false;


    public function __construct(){
        //当实例化一个对象的时候，这个对象的这个方法首先被调用
        return '';
    }
    public function __destruct(){
        //当删除一个对象或对象操作终止的时候，调用该方法
        return '';
    }
    public function __get($key){
        //当试图读取一个并不存在的属性的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __set($key,$val){
        //当试图向一个并不存在的属性写入值的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __call($key,$args){
        //当试图调用一个对象并不存在的方法时，调用该方法
        return '['.$key.'] Function not find';
    }
    public function __toString(){
        //当打印一个对象的时候被调用
        return $this -> H_desce();
    }
    public function __clone(){
        //当对象被克隆时，被调用
        return "clone";
    }
    public function H_desce(){
        //返回描述
        $this -> H_desce .= '类名:H_music-音乐处理;';
        $this -> H_desce .= '函数:H_ck_size($file),返回:ture/false-检测文件完整型,$file-文件路径;';
        $this -> H_desce .= '函数:H_get_info($file),返回:文件信息数组-获得文件信息,$file-文件路径;';
        $this -> H_desce .= '函数:H_set_info($file, $pa)),返回:ture/false-重写文件信息,$file-文件路径.$pa-修改信息;';
        return $this -> H_desce;
    }

    //检测文件完整型
    public function H_ck_size($file){
        $handler = &$this -> _get_handler($file);
        if (!$handler) return false;
        return $handler -> check_size($file);
    }

    //获得文件信息
    public function H_get_info($file){
        $handler = &$this -> _get_handler($file);
        if (!$handler) return false;
        return $handler -> get_info($file);
    }

    //重写文件信息
    public function H_set_info($file, $pa){
        //写入信息支持: Title(名称), Artist(艺术家),Copyright(版权),Description (描述),Year(年代),Genre (流派),AlbumTitle(专辑标题)
        if (!is_writable($file)){
            trigger_error('AudioExif: file `' . $file . '` can not been overwritten', E_USER_WARNING);
            return false;
        }
        $handler = &$this -> _get_handler($file);
        if (!$handler) return false;
        return $handler -> set_info($file, $pa);
    }

    //根据类型设定对象
    public function &_get_handler($file){
        $ext = strtolower(strrchr($file, '.'));
        $ret = false;
        if ($ext == '.mp3'){	// MP3
            $ret = &$this -> _mp3;
            if (!$ret) $ret = new _Mp3Exif();
        }else if ($ext == '.wma'){	// wma
            $ret = &$this -> _wma;
            if (!$ret) $ret = new _WmaExif();
        }else{	// unknown
            trigger_error('AudioExif not supported `' . $ext . '` file.', E_USER_WARNING);
        }
        return $ret;
    }
}//End class

class _AudioExif{
    public $H_desce = 'AUDIO_class';
    public $fd;
    public $head;
    public $head_off;
    public $head_buf;

    public function __construct(){
        //当实例化一个对象的时候，这个对象的这个方法首先被调用
        return '';
    }
    public function __destruct(){
        //当删除一个对象或对象操作终止的时候，调用该方法
        return '';
    }
    public function __get($key){
        //当试图读取一个并不存在的属性的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __set($key,$val){
        //当试图向一个并不存在的属性写入值的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __call($key,$args){
        //当试图调用一个对象并不存在的方法时，调用该方法
        return '['.$key.'] Function not find';
    }
    public function __toString(){
        //当打印一个对象的时候被调用
        return $this -> H_desce();
    }
    public function __clone(){
        //当对象被克隆时，被调用
        return "clone";
    }
    // init the file handler
    public function _file_init($fpath, $write = false){
        $mode = ($write ? 'rb+' : 'rb');
        $this -> fd = @fopen($fpath, $mode);
        if (!$this -> fd){
            trigger_error('AudioExif: `' . $fpath . '` can not be opened with mode `' . $mode . '`', E_USER_WARNING);
            return false;
        }
        $this -> head = false;
        $this -> head_off = 0;
        $this -> head_buf = '';
        return true;
    }

    // read buffer from the head_buf & move the off pointer
    public function _read_head_buf($len){
        if ($len <= 0) return NULL;
        $buf = substr($this -> head_buf, $this -> head_off, $len);
        $this -> head_off += strlen($buf);
        return $buf;
    }

    // read one short value
    public function _read_head_short(){
        $ord1 = ord(substr($this -> head_buf, $this -> head_off, 1));
        $ord2 = ord(substr($this -> head_buf, $this -> head_off+1, 1));
        $this -> head_off += 2;
        return ($ord1 + ($ord2 << 8));
    }

    // save the file head
    public function _file_save($head, $olen, $nlen = 0){
        if ($nlen == 0) $nlen = strlen($head);
        if ($nlen == $olen){
            // shorter
            flock($this -> fd, LOCK_EX);
            fseek($this -> fd, 0, SEEK_SET);
            fwrite($this -> fd, $head, $nlen);
            flock($this -> fd, LOCK_UN);
        }else{
            // longer, buffer required
            $stat = fstat($this -> fd);
            $fsize = $stat['size'];

            // buf required (4096?) 应该不会 nlen - olen > 4096 吧
            $woff = 0;
            $roff = $olen;

            // read first buffer
            flock($this -> fd, LOCK_EX);
            fseek($this -> fd, $roff, SEEK_SET);
            $buf = fread($this -> fd, 4096);

            // seek to start
            fseek($this -> fd, $woff, SEEK_SET);
            fwrite($this -> fd, $head, $nlen);
            $woff += $nlen;

            // seek to woff & write the data
            do{
                $buf2 = $buf;
                $roff += 4096;
                if ($roff < $fsize){
                    fseek($this -> fd, $roff, SEEK_SET);
                    $buf = fread($this -> fd, 4096);
                }
                // save last buffer
                $len2 = strlen($buf2);
                fseek($this -> fd, $woff, SEEK_SET);
                fwrite($this -> fd, $buf2, $len2);
                $woff += $len2;
            }while($roff < $fsize);
            ftruncate($this -> fd, $woff);
            flock($this -> fd, LOCK_UN);
        }
    }

    // close the file
    public function _file_deinit(){
        if ($this -> fd){
            fclose($this -> fd);
            $this -> fd = false;
        }
    }
}//End class
// wma class
class _WmaExif extends _AudioExif{
    public $H_desce = 'WMA_class';
    public $items1 = array('Title', 'Artist', 'Copyright', 'Description', 'Reserved');
    public $items2 = array('Year', 'Genre', 'AlbumTitle');

    public function __construct(){
        //当实例化一个对象的时候，这个对象的这个方法首先被调用
        return '';
    }
    public function __destruct(){
        //当删除一个对象或对象操作终止的时候，调用该方法
        return '';
    }
    public function __get($key){
        //当试图读取一个并不存在的属性的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __set($key,$val){
        //当试图向一个并不存在的属性写入值的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __call($key,$args){
        //当试图调用一个对象并不存在的方法时，调用该方法
        return '['.$key.'] Function not find';
    }
    public function __toString(){
        //当打印一个对象的时候被调用
        return $this -> H_desce();
    }
    public function __clone(){
        //当对象被克隆时，被调用
        return "clone";
    }

    // check file size (length) maybe invalid file
    public function check_size($file){
        $ret = false;
        if (!$this -> _file_init($file)) return true;
        if ($this -> _init_header()){
            $buf = fread($this -> fd, 24);
            $tmp = unpack('H32id/Vlen/H8unused', $buf);
            if ($tmp['id'] == '3626b2758e66cf11a6d900aa0062ce6c'){
                $stat = fstat($this -> fd);
                $ret = ($stat['size'] == ($this -> head['len'] + $tmp['len']));
            }
        }
        $this -> _file_deinit();
        return $ret;
    }

    // set info (save the infos)
    public function set_info($file, $pa){
        // check the pa
        settype($pa, 'array');
        if (!$this -> _file_init($file, true)) return false;
        if (!$this -> _init_header()){
            $this -> _file_deinit();
            return false;
        }

        // parse the old header & generate the new header
        $head_body = '';
        $st_found = $ex_found = false;
        $head_num = $this -> head['num'];
        while (($tmp = $this -> _get_head_frame()) && ($head_num > 0)){
            $head_num--;
            if ($tmp['id'] == '3326b2758e66cf11a6d900aa0062ce6c'){	// Standard Info
                // 1-4
                $st_found = true;
                $st_body1 = $st_body2 = '';
                $lenx = unpack('v5', $this -> _read_head_buf(10));
                $tmp['len'] -= 34;	// 10 + 24
                for ($i = 0; $i < count($this -> items1); $i++){
                    $l = $lenx[$i+1];
                    $k = $this -> items1[$i];
                    $tmp['len'] -= $l;

                    $data = $this -> _read_head_buf($l);
                    if (isset($pa[$k])) $data = gbk_dbcs($pa[$k]);

                    $st_body2 .= $data;
                    $st_body1 .= pack('v', strlen($data));
                }
                // left length
                if ($tmp['len'] > 0) $st_body2 .= $this -> _read_head_buf($tmp['len']);

                // save to head_body
                $head_body .= pack('H32VH8', $tmp['id'], strlen($st_body1 . $st_body2)+24, $tmp['unused']);
                $head_body .= $st_body1 . $st_body2;
            }else if ($tmp['id'] == '40a4d0d207e3d21197f000a0c95ea850'){	// extended info
                $ex_found = true;

                $inum = $this -> _read_head_short();
                $inum2 = $inum;
                $tmp['len'] -= 26;	// 24 + 2
                $et_body = '';
                while ($tmp['len'] > 0 && $inum > 0){
                    // attribute name
                    $nlen = $this -> _read_head_short();
                    $nbuf = $this -> _read_head_buf($nlen);

                    // the flag & value  length
                    $flag = $this -> _read_head_short();
                    $vlen = $this -> _read_head_short();
                    $vbuf = $this -> _read_head_buf($vlen);

                    // set the length
                    $tmp['len'] -= (6 + $nlen + $vlen);
                    $inum--;

                    // save the data?
                    $name = dbcs_gbk($nbuf);
                    $k = substr($name, 3);
                    if (in_array($k, $this -> items2) && isset($pa[$k])){
                        $vbuf = gbk_dbcs($pa[$k]);
                        $vlen = strlen($vbuf);
                        unset($pa[$k]);
                    }
                    $et_body .= pack('v', $nlen) . $nbuf . pack('vv', $flag, $vlen) . $vbuf;
                }
                // new tag insert
                foreach ($this -> items2 as $k){
                    if (isset($pa[$k])){
                        $inum2++;
                        $nbuf = gbk_dbcs('WM/' . $k);
                        $nlen = strlen($nbuf);
                        $vbuf = gbk_dbcs($pa[$k]);
                        $vlen = strlen($vbuf);
                        $et_body .= pack('v', $nlen) . $nbuf . pack('vv', 0, $vlen) . $vbuf;
                    }
                }
                // left buf?
                if ($tmp['len'] > 0) $et_body .= $this -> _read_head_buf($tmp['len']);

                // save to head_body
                $head_body .= pack('H32VH8v', $tmp['id'], strlen($et_body)+26, $tmp['unused'], $inum2);
                $head_body .= $et_body;
            }else{
                // just keep other head frame
                $head_body .= pack('H32VH8', $tmp['id'], $tmp['len'], $tmp['unused']);
                if ($tmp['len'] > 24) $head_body .= $this -> _read_head_buf($tmp['len']-24);
            }
        }
        // st not found
        if(!$st_found){
            $st_body1 = $st_body2 = '';
            foreach ($this -> items1 as $k){
                $data = (isset($pa[$k]) ? gbk_dbcs($pa[$k]) : "");
                $st_body1 .= pack('v', strlen($data));
                $st_body2 .= $data;
            }
            // save to head_body
            $head_body .= pack('H32Va4', '3326b2758e66cf11a6d900aa0062ce6c', strlen($st_body1 . $st_body2)+24, '');
            $head_body .= $st_body1 . $st_body2;
            $this -> head['num']++;
        }
        // ex not found?
        if (!$ex_found){
            $inum = 0;
            $et_body = '';
            foreach ($this -> items2 as $k){
                $nbuf = gbk_dbcs('WM/' . $k);
                $vbuf = (isset($pa[$k]) ? gbk_dbcs($pa[$k]) : "");
                $et_body .= pack('v', strlen($nbuf)) . $nbuf . pack('vv', 0, strlen($vbuf)) . $vbuf;
                $inum++;
            }
            $head_body .= pack('H32Va4v', '40a4d0d207e3d21197f000a0c95ea850', strlen($et_body)+26, '', $inum);
            $head_body .= $et_body;
            $this -> head['num']++;
        }

        // after save
        $new_len = strlen($head_body) + 30;
        $old_len = $this -> head['len'];
        if ($new_len < $old_len){
            $head_body .= str_repeat("\0", $old_len - $new_len);
            $new_len = $old_len;
        }
        $tmp = $this -> head;
        $head_buf = pack('H32VVVH4', $tmp['id'], $new_len, $tmp['len2'], $tmp['num'], $tmp['unused']);
        $head_buf .= $head_body;
        $this -> _file_save($head_buf, $old_len, $new_len);

        // close the file & return
        $this -> _file_deinit();
        return true;
    }

    // get info
    public function get_info($file){
        $ret = array();
        if (!$this -> _file_init($file)) return false;
        if (!$this -> _init_header()){
            $this -> _file_deinit();
            return false;
        }

        // get the data from head_buf
        $head_num = $this -> head['num'];	// num of head_frame
        while (($tmp = $this -> _get_head_frame()) && $head_num > 0){
            $head_num--;
            if ($tmp['id'] == '3326b2758e66cf11a6d900aa0062ce6c'){	// Standard Info
                $lenx = unpack('v*', $this -> _read_head_buf(10));
                for ($i = 1; $i <= count($this -> items1); $i++){
                    $k = $this->items1[$i-1];
                    $ret[$k] = dbcs_gbk($this -> _read_head_buf($lenx[$i]));
                }
            }else if ($tmp['id'] == '40a4d0d207e3d21197f000a0c95ea850'){	// Extended Info
                $inum = $this -> _read_head_short();
                $tmp['len'] -= 26;
                while ($inum > 0 && $tmp['len'] > 0){
                    // attribute name
                    $nlen = $this -> _read_head_short();
                    $nbuf = $this -> _read_head_buf($nlen);

                    // the flag & value  length
                    $flag = $this -> _read_head_short();
                    $vlen = $this -> _read_head_short();
                    $vbuf = $this -> _read_head_buf($vlen);

                    // update the XX
                    $tmp['len'] -= (6 + $nlen + $vlen);
                    $inum--;

                    $name = dbcs_gbk($nbuf);
                    $k = substr($name, 3);
                    if (in_array($k, $this -> items2)){	// all is string value (refer to falg for other tags)
                        $ret[$k] = dbcs_gbk($vbuf);
                    }
                }
            }else{	// skip only
                if ($tmp['len'] > 24) $this -> head_off += ($tmp['len'] - 24);
            }
        }
        $this -> _file_deinit();
        return $ret;
    }

    // get the header?
    public function _init_header(){
        fseek($this -> fd, 0, SEEK_SET);
        $buf = fread($this -> fd, 30);
        if (strlen($buf) != 30) return false;
        $tmp = unpack('H32id/Vlen/Vlen2/Vnum/H4unused', $buf);
        if ($tmp['id'] != '3026b2758e66cf11a6d900aa0062ce6c')
            return false;

        $this -> head_buf = fread($this -> fd, $tmp['len'] - 30);
        $this -> head = $tmp;
        return true;
    }

    // _get_head_frame()
    function _get_head_frame(){
        $buf = $this -> _read_head_buf(24);
        if (strlen($buf) != 24) return false;
        $tmp = unpack('H32id/Vlen/H8unused', $buf);
        return $tmp;
    }
}

// mp3 class (if not IDv2 then select IDv1)
class _Mp3Exif extends _AudioExif{
    public $H_desce = 'MP3_class';
    public $head1;
    public $genres = array('Blues','Classic Rock','Country','Dance','Disco','Funk','Grunge','Hip-Hop','Jazz','Metal','New Age','Oldies','Other','Pop','R&B','Rap','Reggae','Rock','Techno','Industrial','Alternative','Ska','Death Metal','Pranks','Soundtrack','Euro-Techno','Ambient','Trip-Hop','Vocal','Jazz+Funk','Fusion','Trance','Classical','Instrumental','Acid','House','Game','Sound Clip','Gospel','Noise','AlternRock','Bass','Soul','Punk','Space','Meditative','Instrumental Pop','Instrumental Rock','Ethnic','Gothic','Darkwave','Techno-Industrial','Electronic','Pop-Folk','Eurodance','Dream','Southern Rock','Comedy','Cult','Gangsta','Top 40','Christian Rap','Pop/Funk','Jungle','Native American','Cabaret','New Wave','Psychadelic','Rave','Showtunes','Trailer','Lo-Fi','Tribal','Acid Punk','Acid Jazz','Polka','Retro','Musical','Rock & Roll','Hard Rock','Unknown');

    public function __construct(){
        //当实例化一个对象的时候，这个对象的这个方法首先被调用
        return '';
    }
    public function __destruct(){
        //当删除一个对象或对象操作终止的时候，调用该方法
        return '';
    }
    public function __get($key){
        //当试图读取一个并不存在的属性的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __set($key,$val){
        //当试图向一个并不存在的属性写入值的时候被调用
        return '['.$key.'] Variable not find';
    }
    public function __call($key,$args){
        //当试图调用一个对象并不存在的方法时，调用该方法
        return '['.$key.'] Function not find';
    }
    public function __toString(){
        //当打印一个对象的时候被调用
        return $this -> H_desce();
    }
    public function __clone(){
        //当对象被克隆时，被调用
        return "clone";
    }
    // MP3 always return true
    public function check_size($file){
        return true;
    }

    // get info
    public function get_info($file){
        if (!$this -> _file_init($file)) return false;
        $ret = false;
        if ($this -> _init_header()){
            $ret = ($this -> head ? $this -> _get_v2_info() : $this -> _get_v1_info());
            $ret['meta'] = $this -> _get_meta_info();
        }
        $this -> _file_deinit();
        return $ret;
    }

    // set info
    public function set_info($file, $pa){
        if (!$this -> _file_init($file, true)) return false;
        if ($this -> _init_header()){
            // always save v1 info
            $this -> _set_v1_info($pa);
            // set v2 first if need
            $this -> _set_v2_info($pa);
        }
        $this -> _file_deinit();
        return true;
    }

    // get the header information[v1+v2], call after file_init
    public function _init_header(){
        $this -> head1 = false;
        $this -> head = false;

        // try to get ID3v1 first
        fseek($this -> fd, -128, SEEK_END);
        $buf = fread($this -> fd, 128);
        if(strlen($buf) == 128 && substr($buf, 0, 3) == 'TAG'){
            $tmp = unpack('a3id/a30Title/a30Artist/a30AlbumTitle/a4Year/a28Description/CReserved/CTrack/CGenre', $buf);
            $this -> head1 = $tmp;
        }

        // try to get ID3v2
        fseek($this -> fd, 0, SEEK_SET);
        $buf = fread($this -> fd, 10);
        if (strlen($buf) == 10 && substr($buf, 0, 3) == 'ID3'){
            $tmp = unpack('a3id/Cver/Crev/Cflag/C4size', $buf);
            $tmp['size'] = ($tmp['size1']<<21)|($tmp['size2']<<14)|($tmp['size3']<<7)|$tmp['size4'];
            unset($tmp['size1'], $tmp['size2'], $tmp['size3'], $tmp['size4']);

            $this -> head = $tmp;
            $this -> head_buf = fread($this -> fd, $tmp['size']);
        }
        return ($this -> head1 || $this -> head);
    }

    // get v1 info
    public function _get_v1_info(){
        $ret = array();
        $tmpa = array('Title', 'Artist', 'Copyright', 'Description', 'Year', 'AlbumTitle');
        foreach ($tmpa as $tmp){
            $ret[$tmp] = $this -> head1[$tmp];
            if ($pos = strpos($ret[$tmp], "\0")) $ret[$tmp] = substr($ret[$tmp], 0, $pos);
        }

        // count the Genre, [Track]
        if ($this -> head1['Reserved'] == 0) $ret['Track'] = $this -> head1['Track'];
        else $ret['Description'] .= chr($ret['Reserved']) . chr($ret['Track']);

        // Genre_idx
        $g = $this -> head1['Genre'];
        if (!isset($this -> genres[$g])) $ret['Genre'] = 'Unknown';
        else $ret['Genre'] = $this -> genres[$g];

        // return the value
        $ret['ID3v1'] = 'yes';
        return $ret;
    }

    // get v2 info
    public function _get_v2_info(){
        $ret = array();
        $items = array(	'TCOP'=>'Copyright', 'TPE1'=>'Artist', 'TIT2'=>'Title', 'TRCK'=> 'Track',
            'TCON'=>'Genre', 'COMM'=>'Description', 'TYER'=>'Year', 'TALB'=>'AlbumTitle');
        while (true){
            $buf = $this -> _read_head_buf(10);
            if (strlen($buf) != 10) break;
            $tmp = unpack('a4fid/Nsize/nflag', $buf);
            if ($tmp['size'] == 0) break;
            $tmp['dat'] = $this -> _read_head_buf($tmp['size']);

            // 0x6000 (11000000 00000000)
            if ($tmp['flag'] & 0x6000) continue;

            // mapping the data
            if ($k = $items[$tmp['fid']]){	// If first char is "\0", just skip
                if (substr($tmp['dat'], 0, 1) == "\0") $tmp['dat'] = substr($tmp['dat'], 1);
                $ret[$k] = $tmp['dat'];
            }
        }

        // reset the genre
        if ($g = $ret['Genre']){
            if (substr($g,0,1) == '(' && substr($g,-1,1) == ')') $g = substr($g, 1, -1);
            if (is_numeric($g)){
                $g = intval($g);
                $ret['Genre'] = (isset($this->genres[$g]) ? $this->genres[$g] : 'Unknown');
            }
        }

        $ret['ID3v1'] = 'no';
        return $ret;
    }

    // get meta info of MP3
    public function _get_meta_info(){
        // seek to the lead buf: 0xff
        $off = 0;
        if ($this -> head) $off = $this -> head['size'] + 10;
        fseek($this -> fd, $off, SEEK_SET);
        while (!feof($this -> fd)){
            $skip = ord(fread($this -> fd, 1));
            if ($skip == 0xff) break;
        }
        if ($skip != 0xff) return false;
        $buf = fread($this -> fd, 3);
        if (strlen($buf) != 3) return false;
        $tmp = unpack('C3', $buf);
        if (($tmp[1] & 0xf0) != 0xf0) return false;

        // get the meta info
        $meta = array();

        // get mpeg version
        $meta['mpeg']	= ($tmp[1] & 0x08 ? 1 : 2);
        $meta['layer']	= ($tmp[1] & 0x04) ? (($tmp[1] & 0x02) ? 1 : 2) : (($tmp[1] & 0x02) ? 3 : 0);
        $meta['epro']	= ($tmp[1] & 0x01) ? 'no' : 'yes';

        // bit rates
        $bit_rates = array(
            1 => array(
                1 => array(0,32,64,96,128,160,192,224,256,288,320,352,384,416,448,0),
                2 => array(0,32,48,56,64,80,96,112,128,160,192,224,256,320,384,0),
                3 => array(0,32,40,48,56,64,80,96,112,128,160,192,224,256,320,0)
            ),
            2 => array(
                1 => array(0,32,48,56,64,80,96,112,128,144,160,176,192,224,256,0),
                2 => array(0,8,16,24,32,40,48,56,64,80,96,112,128,144,160,0),
                3 => array(0,8,16,24,32,40,48,56,64,80,96,112,128,144,160,0)
            )
        );
        $i = $meta['mpeg'];
        $j = $meta['layer'];
        $k = ($tmp[2]>>4);
        $meta['bitrate'] = $bit_rates[$i][$j][$k];

        // sample rates <采样率>
        $sam_rates = array(1=>array(44100,48000,32000,0), 2=>array(22050,24000,16000,0));
        $meta['samrate'] = $sam_rates[$i][$k];
        $meta["padding"] = ($tmp[2] & 0x02) ? 'on' : 'off';
        $meta["private"] = ($tmp[2] & 0x01) ? 'on' : 'off';

        // mode & mode_ext
        $k = ($tmp[3]>>6);
        $channel_modes = array('stereo', 'joint stereo', 'dual channel', 'single channel');
        $meta['mode'] = $channel_modes[$k];

        $k = (($tmp[3]>>4) & 0x03);
        $extend_modes = array('MPG_MD_LR_LR', 'MPG_MD_LR_I', 'MPG_MD_MS_LR', 'MPG_MD_MS_I');
        $meta['ext_mode'] = $extend_modes[$k];

        $meta['copyright'] = ($tmp[3] & 0x08) ? 'yes' : 'no';
        $meta['original'] = ($tmp[3] & 0x04) ? 'yes' : 'no';

        $emphasis = array('none', '50/15 microsecs', 'rreserved', 'CCITT J 17');
        $k = ($tmp[3] & 0x03);
        $meta['emphasis'] = $emphasis[$k];

        return $meta;
    }

    // set v1 info
    public function _set_v1_info($pa){
        // ID3v1 (simpled)
        $off = -128;
        if (!($tmp = $this -> head1)){
            $off = 0;
            $tmp['id'] = 'TAG';
            $tmp['Title'] = $tmp['Artist'] = $tmp['AlbumTitle'] = $tmp['Year'] = $tmp['Description'] = '';
            $tmp['Reserved'] = $tmp['Track'] = $tmp['Genre'] = 0;
        }

        // basic items
        $items = array('Title', 'Artist', 'Copyright', 'Description', 'Year', 'AlbumTitle');
        foreach ($items as $k){
            if (isset($pa[$k])) $tmp[$k] = $pa[$k];
        }

        // genre index
        if (isset($pa['Genre'])){
            $g = 0;
            foreach ($this->genres as $gtmp){
                if (!strcasecmp($gtmp, $pa['Genre']))
                    break;
                $g++;
            }
            $tmp['Genre'] = $g;
        }
        if (isset($pa['Track'])) $tmp['Track'] = intval($pa['Track']);

        // pack the data
        $buf = pack('a3a30a30a30a4a28CCC',	$tmp['id'], $tmp['Title'], $tmp['Artist'], $tmp['AlbumTitle'],
            $tmp['Year'], $tmp['Description'], 0, $tmp['Track'], $tmp['Genre']);

        flock($this -> fd, LOCK_EX);
        fseek($this -> fd, $off, SEEK_END);
        fwrite($this -> fd, $buf, 128);
        flock($this -> fd, LOCK_UN);
    }

    // set v2 info
    public function _set_v2_info($pa){
        if (!$this -> head){	// insert ID3
            return;	// 没有就算了
            /**
            $tmp = array('id'=>'ID3','ver'=>3,'rev'=>0,'flag'=>0);
            $tmp['size'] = -10;	// +10 => 0
            $this->head = $tmp;
            $this->head_buf = '';
            $this->head_off = 0;
             **/
        }
        $items = array(	'TCOP'=>'Copyright', 'TPE1'=>'Artist', 'TIT2'=>'Title', 'TRAC'=>'Track',
            'TCON'=>'Genre', 'COMM'=>'Description', 'TYER'=>'Year', 'TALB'=>'AlbumTitle');

        $head_body = '';
        while (true){
            $buf = $this -> _read_head_buf(10);
            if (strlen($buf) != 10) break;
            $tmp = unpack('a4fid/Nsize/nflag', $buf);
            if ($tmp['size'] == 0) break;
            $data = $this -> _read_head_buf($tmp['size']);

            if (($k = $items[$tmp['fid']]) && isset($pa[$k])){
                // the data should prefix by "\0" [replace]
                $data = "\0" . $pa[$k];
                unset($pa[$k]);
            }
            $head_body .= pack('a4Nn', $tmp['fid'], strlen($data), $tmp['flag']) . $data;
        }
        // reverse the items & set the new tags
        $items = array_flip($items);
        foreach ($pa as $k => $v){
            if ($fid = $items[$k]){
                $head_body .= pack('a4Nn', $fid, strlen($v) + 1, 0) . "\0" . $v;
            }
        }

        // new length
        $new_len = strlen($head_body) + 10;
        $old_len = $this -> head['size'] + 10;
        if ($new_len < $old_len){
            $head_body .= str_repeat("\0", $old_len - $new_len);
            $new_len = $old_len;
        }

        // count the size1,2,3,4, no include the header
        // 较为变态的算法... :p (28bytes integer)
        $size = array();
        $nlen = $new_len - 10;
        for ($i = 4; $i > 0; $i--){
            $size[$i] = ($nlen & 0x7f);
            $nlen >>= 7;
        }
        $tmp = $this->head;
        //echo "old_len : $old_len new_len: $new_len\n";
        $head_buf = pack('a3CCCCCCC', $tmp['id'], $tmp['ver'], $tmp['rev'], $tmp['flag'],
            $size[1], $size[2], $size[3], $size[4]);
        $head_buf .= $head_body;
        $this -> _file_save($head_buf, $old_len, $new_len);
    }
}
// DBCS => gb2312
function dbcs_gbk($str){
    // strip the last "\0\0"
    $str = substr($str, 0, -2);
    return mb_convert_encoding($str, 'GBK', 'UCS-2LE');
}

// gb2312 => DBCS
function gbk_dbcs($str){
    $str  = mb_convert_encoding($str, 'UCS-2LE', 'GBK');
    $str .= "\0\0";
    return $str;
}

//$AE = new H_music;
//	$file = '手放开李圣杰.mp3';
//	$AE->CheckSize($file);
//	echo "<pre>";
//	print_r($AE->GetInfo($file));
//	echo "</pre>";
//	$pa = array('Title' => '我无语', 'AlbumTitle' => '真的专辑名称','Artist' => '黄祥-huangxiang','Description' => '描述',
//				'Year' => '2009-2-17 17:55','Genre' => '流派','Copyright' => '版权','Track' => 'Track');
//	$AE->SetInfo($file, $pa);

$filePath = __DIR__."/file";
$files  = scandir($filePath);
foreach($files as $filename)
{
    if($filename == '.'|| $filename == ".."){
        continue;
    }

    $mp3_file = $filePath.'/'.$filename;
    $AE = new H_music;

//	$AE->H_ck_size($mp3_file);
//	echo "<pre>";
//	print_r($AE->H_get_info($mp3_file));
//    $test = $AE->H_get_info($mp3_file);
//    echo mb_detect_encoding($test['Title'], array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
//	print_r($test);
//    echo "</pre>";
	$pa = array('Title' => iconv("UTF-8", "GBK",rtrim($filename,'.mp3') ),'Artist' => iconv("UTF-8", "GBK", '陈果'),);
	$AE->H_set_info($mp3_file, $pa);

}
