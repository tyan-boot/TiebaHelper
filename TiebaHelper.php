<?php

/**
* Copyright 2015 Tyan Boot
* 
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
* 
*     http://www.apache.org/licenses/LICENSE-2.0
* 
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/


/**
 * Baidu tieba class
 *
 * Use for login, sign, post, comment and etc...
 *
 * @author TyanBoot
 * @link http://www.tyanboot.pw/
 */

class TiebaHelper {

	/**
	* User cookies
	* 
	* @var string
	*/
	private $cookie;
	
	/**
	* Baidu token
	* 
	* @var string
	*/
	private $token;
	
	/**
	* Forum id,which refer to an tieba
	*
	* @var int
	*/
	private $fid;
	
	/**
	* Post id,which refer to an posted topic
	*
	* @var int
	*/
	private $tid;
	
	/**
	* An unknow string,which is essential.
	* Can be found in HTML
	*
	* @var int
	*/
	private $tbs;
	
	/**
	* Tieba target
	* 
	* @var string
	*/
	private $tieba = array();
	
	/**
	* User name
	* Use for login
	* @var mixed
	*/
	private $user;
	
	/**
	* User password
	* Use only for login
	* @var mixed
	*/
	private $passwd;
	
	/**
	* TieBa liked list
	*
	* @var array
	*/
	private $TbList = array();
	
	/**
	* Whether enable debug output
	*
	* @var bool
	*/
	public $debug = false;
	
	/**
	* The number of liked tieba
	*
	* @var int
	*/
	private $tbn;
	
	/**
	* The sign states about tieba
	*
	* @var array
	*/
	private $signstat = array();
	
	/**
	* The verify code URL
	*
	* @var string
	*/
	private $codeStr = '';
	
	/**
	* The return array while login
	*
	* @var array
	*/
	private $rtn = array( 'err_no' => 0, 'imgUrl' => 0, 'cookie' => 0 );
	
	
	public function __construct($user = '', $pwd = '')
	{
		if ($user != null and $pwd != null)
		{
			$this->SetUser($user);
			$this->SetPW($pwd);
		}
	}
	
	/**
	* Login to your Baidu and have fun
	*
	* @var true
	*/
	public function Login($vcode = '')
	{
		$this->dout("Start Login...");
		
		if ($vcode == '')
		{
			$this->_LoginConstruct();
		}

		$time = $this->GetTime();
		//curl
		$ch = curl_init();
		
		$posta = array (
		'staticpage' => 'http://www.baidu.com/cache/user/html/v3Jump.html',
		'charset' => 'UTF-8',
		'token' => $this->token,
		'tpl' => 'pp',
		'apiver' => 'v3',
		'tt' => $time,
		'codestring' => $this->codeStr,
		'safeflg' => 0,
		'u' => 'http://www.baidu.com/',
		'isPhone' => 'false',
		'quick_user' => 0,
		'logintype' => 'dialogLogin',
		'username' => $this->user,
		'password' => $this->passwd,
		'verifycode' => $vcode,
		);
		$postf = $this->array2urlencode( $posta );
		
		$opt = array(
		CURLOPT_POST => true,
		CURLOPT_COOKIE => $this->cookie,
		CURLOPT_HEADER => 1,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'http://passport.baidu.com/v2/api/?login',
		CURLOPT_POSTFIELDS => $postf,
		CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2774.3 Safari/537.36"
		);
		curl_setopt_array( $ch, $opt );
		
		$get = curl_exec( $ch );
		
		//get error
		$pattern = '/(?<="err_no=)\w+/';
		$num = preg_match( $pattern, $get, $mat );
		
		$err = $mat[0];
		
		if ( $err != 0 ) {
			//print_r($get);
			//sorry, something wrong happend when try to login
			//try to find codeString
			$pattern = '/(?<=codeString=)[^&]+?(?=&)/';
			$num = preg_match( $pattern, $get, $mat );
			if ( $num == 0 ) {
				$this->rtn['err_no'] = 9999;
				return $this->rtn;
			}else {
				$this->rtn['err_no'] = $err;
				$this->rtn['imgUrl'] = 'https://passport.baidu.com/cgi-bin/genimage?' . $mat[0];
				$this->codeStr = $mat[0];
				//print_r($this->codeStr);

				$this->dout($this->rtn);
				
				return $this->rtn;
			}
	
		}else {
			//login succesed
			//and we should save cookies in order to other actions
	
			$this->dout("Login success");
			
			//BDUSS
			$pattern = '/BDUSS=\w+/';
			preg_match( $pattern, $get, $mat );
			$this->cookie .= " " . $mat[0] . "; ";
			
			//PTOKEN
			$pattern = '/PTOKEN=\w+/';
			preg_match( $pattern, $get, $mat );
			$this->cookie .= $mat[0] . "; ";
			
			//STOKEN
			$pattern = '/STOKEN=\w+/';
			preg_match( $pattern, $get, $mat );
			$this->cookie .= $mat[0] . "; ";
			
			//SAVEUSERID
			$pattern = '/SAVEUSERID=\w+/';
			preg_match( $pattern, $get, $mat );
			$this->cookie .= $mat[0] . ";";
			
			$this->rtn['err_no'] = $err;
			$this->rtn['cookie'] = $this->cookie;
			
			$this->dout($this->rtn);
			$this->dout("Cookies: ".$this->cookie);
			
			return $this->rtn;
		}
		
	}
	
	/**
	* Do something essential before login
	*
	* @var bool
	*/
	private function _LoginConstruct()
	{
		$ch = curl_init();
		
		//home url
		$hurl = 'http://www.baidu.com/';
		//token url;
		$turl = 'http://passport.baidu.com/v2/api/?getapi&tpl=mn&apiver=v3';
	
		$opt = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => 1, 		//I need some header information
			CURLOPT_URL => $hurl,
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2774.3 Safari/537.36"
		);
		curl_setopt_array( $ch, $opt );
		$get = curl_exec( $ch );
	
		//find BAIDUID
		$pattern = '/BAIDUID=\w+/';
		preg_match( $pattern, $get, $mat );
		//save
		$this->cookie =$mat[0] . ';';
	
		//get token
		curl_setopt( $ch, CURLOPT_URL, $turl );
		curl_setopt( $ch, CURLOPT_COOKIE, $this->cookie );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
	
		$get = curl_exec( $ch );
		$pattern = '/(?<="token"\s:\s")\w+/';
		preg_match( $pattern, $get, $mat );
		//save
		$this->token = $mat[0];
		
		echo "\n" . "token:" . $this->token . "\n";
		return true;
	}

	/**
	* Sign tieba via mobile API
	* 
	* @param $SignDelay Delay between sign in order to avoid verify code
	* @return bool
	*/
	public function Sign($SignDelay = '4')
	{
		$this->dout("Start sign...");
		
		//$this->tbs = '6f79ae79e799d1591432952457';
		$this->tbs = $this->GetTBS();
		//refresh tieba list
		$this->GetTbList();
	
		foreach ( $this->TbList as $TB ) {
			if ( $TB->is_sign != 1 ) {
				sleep($SignDelay);
				
				$url = 'http://tieba.baidu.com/mo/q/sign?tbs=' . $this->tbs . '&kw=' . urlencode( $TB->forum_name ) . '&is_like=1&fid=' . $TB->forum_id;
				$ch = curl_init( $url );
				$optg = array(
						CURLOPT_COOKIE => $this->cookie,
						CURLOPT_RETURNTRANSFER => true
				);
				curl_setopt_array( $ch, $optg );
				$res = curl_exec( $ch );
				$pattern = '/(?<="errmsg":")\w+/';
				preg_match($pattern, $res, $mat);
				$res = json_decode($res);
				$this->signstat[$TB->forum_name] = $res;
				$this->dout($res);
			}
		}
	}

	/**
	* Post an topic
	*
	* @param $title topic title
	* @param $content content of new topic
	* @return array
	*/

	public function Post( $title, $content )
	{
		$this->dout("Start post...");
		
		if ($this->tbs=null) $this->tbs = $this->GetTBS();
		$date = $this->GetTime();
	
		$pam =  array(
			'ie' 				=> 'utf-8', 
			'kw' 				=> $this->tieba,
			'fid'				=> $this->fid,
			'tid' 				=> 0,
			'vcode_md5'			=> '',
			'floor_num' 		=>0,
			'rich_text' 		=> 1,
			'tbs' 				=> $this->tbs,
			'content' 			=> $content,
			'title' 			=> $title,
			'prefix' 			=> '',
			'files' 			=> '[]',
			'mouse_pwd' 		=> '108,103,104,115,106,102,110,104,86,110,115,111,115,110,115,111,115,110,115,111,115,110,115,111,115,110,115,111,86,109,110,106,110,106,106,86,110,107,107,111,115,102,111,111,'. $date,
			'mouse_pwd_t' 		=> $date,
			'mouse_pwd_isclick' => 0,
			'__type__' 			=> 'thread'
			);
	
		//urlencode
		//$postf = 'ie=utf-8&kw=' . $this->tieba . '&fid=' . $this->fid . '&tid=0&vcode_md5=&floor_num=0&rich_text=1&tbs=' . $this->tbs . '&content=' . $content . '&title=' . $title . '&prefix=&files=[]&mouse_pwd=108,103,104,115,106,102,110,104,86,110,115,111,115,110,115,111,115,110,115,111,115,110,115,111,115,110,115,111,86,109,110,106,110,106,106,86,110,107,107,111,115,102,111,111,' . $date . '&mouse_pwd_t=' . $date . '&mouse_pwd_isclick=0&__type__=thread';
		$postf = $this->array2urlencode($pam);
		//curl
		$ch = curl_init();
		$opt = array(
		CURLOPT_POST => true,
		CURLOPT_COOKIE => $this->cookie,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'http://tieba.baidu.com/f/commit/thread/add',
		CURLOPT_POSTFIELDS => $postf
		);
	
		curl_setopt_array( $ch, $opt );
		//exec
		$res = curl_exec( $ch );
		$this->dout("Curl fetched");
		
		$res = json_decode( $res );
		
		$this->dout($res);
		
		$rtn['no'] = $res->no;
		$rtn['err_code'] = $res->err_code;
		$rtn['error'] = $res->error;
		
		$data = $res->data;
		foreach ( $data as $key => $row ) {
			if ( $key != "vcode" ) {
				$rtn["$key"] = $row;
			}else {
				$vcode = $row;
				foreach ( $vcode as $k => $r ) {
					$rtn["$k"] = $r;
				}
			}
		}
		
		return $rtn;
	}
	
	/**
	* Post a comment on topic
	*
	* @param $title topic title
	* @param $content content of new topic
	* @return array|bool
	*/
	public function Comment( $content, $tid = null )
	{
		$this->dout("Start comment...");
		
		if ($tid != null ) $this->tid = $tid;
		if ($this->tid == null ) return false;
	
		$date = $this->GetTime();
	
		if ($tbs == null) $this->GetTBS();
	
		$this->dout('this->fid: ' . $this->fid);
		$this->dout('this->tid: ' . $this->tid);
		
		//urlencode
		//$comt = 'ie=utf-8&kw=' . $this->tieba . '&fid=' . $this->fid . '&tid=' . $this->tid . '&vcode_md5=&floor_num=1&rich_text=1&tbs=' . $this->tbs . '&content=' . $content . '&files=[]&mouse_pwd=57,57,56,33,60,53,63,58,4,60,33,61,33,60,33,61,33,60,33,61,33,60,33,61,33,60,33,61,4,63,56,59,56,57,4,60,57,57,61,33,52,61,61,' . $date . '&mouse_pwd_t=' . $date . '&mouse_pwd_isclick=0&__type__=reply';
		
		$comt = array(
			'ie'				=> 'utf-8',
			'kw'				=> $this->tieba,
			'fid'				=> $this->fid,
			'tid'				=> $this->tid,
			'vcode_md5'			=> '',
			'floor_num'			=> 1,
			'rich_text'			=> 1,
			'tbs'				=> $this->tbs,
			'content'			=> $content,
			 'files'			=> '[]',
			'mouse_pwd'			=> '57,57,56,33,60,53,63,58,4,60,33,61,33,60,33,61,33,60,33,61,33,60,33,61,33,60,33,61,4,63,56,59,56,57,4,60,57,57,61,33,52,61,61,' . $date,
			'mouse_pwd_t' 		=> $date,
			'mouse_pwd_isclick'	=> 0,
			'__type__'			=> 'reply'
			);

		$comt = array2urlencode($comt);
		//curl
		$ch = curl_init();
		$opt = array(
		CURLOPT_POST => true,
		CURLOPT_COOKIE => $this->cookie,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'http://tieba.baidu.com/f/commit/post/add',
		CURLOPT_POSTFIELDS => $comt
		);
	
		curl_setopt_array( $ch, $opt );
		//exec
		$res = curl_exec( $ch );
		$this->dout("Curl fetched");
		
		$res = json_decode( $res );
		
		$this->dout($res);
		
		$rtn['no'] = $res->no;
		$rtn['err_code'] = $res->err_code;
		$rtn['error'] = $res->error;
		
		$data = $res->data;
		foreach ( $data as $key => $row ) {
			if ( $key != "vcode" ) {
				$rtn["$key"] = $row;
			}else {
				$vcode = $row;
				foreach ( $vcode as $k => $r ) {
					$rtn["$k"] = $r;
				}
			}
		}
	
		return $rtn;
	}

	/**
	* Convert array to an urlencoded string
	* 
	* @return string
	*/
	private function array2urlencode ( $arr )
	{
		$u = '';
		foreach ( $arr as $key => $str ) {
			$u = $u . $key . '=' . urlencode($str) . '&';
		}
		$u = substr( $u, 0, -1 );
		return $u;
	}
	
	/**
	* Get the timestamp as 13 length
	* 
	* @return int(13)
	*/
	private function GetTime()
	{
		//13位时间戳
		$date = microtime(true) * 1000;
		$date = floor($date);
		
		return $date;
	}

	/**
	* Get liked tieba list
	*
	* @return bool|array
	*/
	public function GetTbList()
	{
		$this->dout("Get tieba list...");
		
		//I use regex to match liked tieba
		$pattern = '/(?<="forumArr":\[).*(?=\],\s"ihome")/';
		//URL where can find list
		$url = 'http://tieba.baidu.com/home/main?un=' . urlencode( $this->user );
		//init an curl for get
		$ch = curl_init( $url );
		$optg = array(
				CURLOPT_COOKIE => $this->cookie,		//must sent cookie
				CURLOPT_RETURNTRANSFER => true 			//Get but do not echo
		);
		curl_setopt_array( $ch, $optg );
		$get = curl_exec( $ch );
	
		$num = preg_match( $pattern, $get, $mat );	//match
	
		if ($num ==0 or !isset($mat[0])) return false; //make sure matched
	
		$list = $mat[0];
		//convert to utf8
		$list = mb_convert_encoding( $list, "UTF-8", "GBK");
		$pat = '/\{[^\}]*\}/';
		$tbNum = preg_match_all( $pat, $list, $tbList, PREG_PATTERN_ORDER );
		$this->tbn = $tbNum;
		//$Tb = array();
		for ( $i=0;$i < $tbNum;$i++ ) {
			$this->TbList[$i] = json_decode( $tbList[0][$i] );
		}
	
		$this->dout($this->TbList);
		
		return $this->TbList;
	}
	
	/**
	* Get an tbs
	*
	* @return string|bool
	*/
	public function GetTBS()
	{
		$this->dout("Get TBS");
		
		//we can find tbs in this index.html
		$url = 'http://tieba.baidu.com/';
		
		//init an curl
		$get = curl_init();
	
		$optg = array(
			CURLOPT_COOKIE => $this->cookie,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url
		);
		curl_setopt_array( $get, $optg );
	
		//get
		$getf = curl_exec( $get );
		//regex to match tbs
		//$pattern = '/(?<=PageData.tbs\s=\s")\w+/';
		$pattern = '/(?<=PageData.tbs)\s*=\s*"\w+"/';
		$num = preg_match ( $pattern, $getf, $matches );
	
		if ($num ==0 ) return false;
	
		$pattern = '/(?<=")\w+/';
		$num = preg_match_all( $pattern, $matches[0], $mat );
	
		if($num == 0) return false;
		
		$this->tbs = $mat[0][0];
		
		$this->dout("TBS is: ".$this->tbs);
		
		return $this->tbs;
	}
	
	/**
	* Get forum id
	*
	* @return string|bool
	*/
	public function GetFid ()
	{
		$this->dout("Get Fid");
		
		$url = 'http://tieba.baidu.com/f?kw=' . $this->tieba;
		$ch = curl_init();
		$opt = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url
		);
		curl_setopt_array( $ch, $opt );
		$get = curl_exec( $ch );
		$pattern = '/(?<=forum_id":)\w+/';
		$num = preg_match( $pattern, $get, $matches );
	
		if ($num == 0) return false;
	
		$this->fid = $matches[0];
		
		$this->dout("Fid is: ".$this->fid);
		
		return $this->fid;
	}
	
	/**
	* GetSignStates
	*
	* @return array
	*/
	public function GetSignStates()
	{
		return $this->signstat;
	}
	
	/**
	* Set user cookies
	*
	* @param $cookie cookies
	*/
	public function SetCookie( $cookie )
	{
		$this->cookie = $cookie;
		$this->dout("Set cookies");
	}
	
	/**
	* Set target tieba
	*
	* @param $tieba target tieba
	*/
	public function SetTieba( $tieba )
	{
		$this->tieba = $tieba;
		$this->GetFid();
		$this->GetTBS();
		$this->dout("Set tieba name");
	}
	
	/**
	* Set user name
	*
	* @param $user user id
	*/
	public function SetUser( $user )
	{
		$this->user = $user;
		$this->dout("Set user id");
	}
	
	/**
	* Set user passwd
	*
	* @param $passwd user password
	*/
	public function SetPW( $passwd )
	{
		$this->passwd = $passwd;
		$this->dout("Set passwd");
	}
	
	/**
	* Set tid
	*
	* @param $tid tid
	*/
	public function SetTid( $tid )
	{
		$this->tid = $tid;
		$this->GetTBS();
		$this->dout("Set Tid");
	}
	
	/**
	* Debug output
	*/
	private function dout( $out, $stat = 'log' )
	{
		if ($this->debug)
		{
			if (php_sapi_name() === 'cli')
			{
				$ln = "\n";
			}else 
			{
				$ln = '<br />';
			}
			if (is_array($out) OR is_object($out))
			{
				print_r($out);
			}else 
			{
				echo date("H:i").' '."[$stat] ".$out.$ln;
			}
		}
	}
}
