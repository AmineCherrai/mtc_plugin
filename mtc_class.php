<?php

/**
 Copyright 2013-2014 Amine Cherrai

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

  @author Amine Cherrai
*/

Class MTC_CLASS
{

	// Change this to false in production enivirement
	protected $debug 		= 	true;

	/** 
		DATABASE Configuration
	*/
	private $db_host		=	"localhost";
	private $db_name 		=	"ticket_db";
	private $db_tx_table 	=	"mtc_tx";
	private $db_driver 		=	"mysql";
	private $db_user 		=	"root";
	private $db_pass 		=	"0rt@";

	private $connection	= null;

	/**
		MTC Configuration
	*/

	// GateWay URL [test]
	private $actionSLK		=	"https://golden.maroccommerce.com/test/pay.cgi";
	// Secret Key [test]
	private $SLKSecretKey	=	"3b6d5de9571886f9bbf36473bafe6ca8";
	// Store ID [test]
	private $storeId 		= 	"401121";

	protected $ticketUrl 	= 	"http://www.aminecherrai.com/mtc/";
	protected $offerURL 	=	"index.php";
	protected $updateURL 	= 	"update.php";
	protected $bookURL 		= 	"book.php";

	protected $language		=	"";
	protected $symbolCur 	=  	"" ;
	protected $merchantType =	0;

	/**
		Order Info
	*/

	protected $cardId;
	protected $txId;
	protected $totalAmountTx;
	protected $cardDate;
	protected $txDate;

	/**
		Customer Info
	*/

	protected $name;
	protected $address;
	protected $city;
	protected $state;
	protected $country;
	protected $postCode;
	protected $tel;
	protected $email;

	protected $checksum;
	protected $mtcChecksum;
	protected $html  		=	"";

	function __construct()
	{
		if($this->debug)
		{
			ini_set('display_startup_errors',1);
			ini_set('display_errors',1);
			error_reporting(-1);
		}

		$this->offerURL 	=	$this->ticketUrl.$this->offerURL;
		$this->updateURL 	= 	$this->ticketUrl.$this->updateURL;
		$this->bookURL 		= 	$this->ticketUrl.$this->bookURL;
	}


	public function pay($language, $symbolCur, $totalAmountTx, $name, $address, $city, $state, $postCode, $country, $email, $tel)
	{		
		$this->language			=	$this->sqlinjection($this->xss($language));
		$this->symbolCur		=	$this->sqlinjection($this->xss($symbolCur));
		$this->totalAmountTx	=	$this->sqlinjection($this->xss($totalAmountTx));
		$this->cardDate 		= 	date("Y-m-d H:i:s");
		$this->name				=	$this->sqlinjection($this->xss($name));
		$this->address			=	$this->sqlinjection($this->xss($address));
		$this->city				=	$this->sqlinjection($this->xss($city));
		$this->state			=	$this->sqlinjection($this->xss($state));
		$this->postCode			=	$this->sqlinjection($this->xss($postCode));
		$this->country			=	$this->sqlinjection($this->xss($country));
		$this->email			=	$this->sqlinjection($this->xss($email));
		$this->tel				=	$this->sqlinjection($this->xss($tel));

		$this->cardId	= intval($this->saveOrder());

		if( (is_integer($this->cardId)) && ($this->cardId>0) )
		{
			// build checksum
			$this->setChecksum($this->actionSLK);

			// render autosubmit form 
			$this->formRender();
		}
		else
		{
			/**
				TODO :
				custom error message
			*/
			echo "We can't save your order";
		}
	}

	public function book($cardId, $email, $totalAmountTx, $mtcChecksum, $txId)
	{

		$this->cardId			=	intval($this->sqlinjection($this->xss($cardId)));
		$this->email			=	$this->sqlinjection($this->xss($email));
		$this->totalAmountTx	=	$this->sqlinjection($this->xss($totalAmountTx));
		$this->mtcChecksum 		=	$this->sqlinjection($this->xss($mtcChecksum));
		$this->txId				=	$this->sqlinjection($this->xss($txId));
		$this->txDate	 		= 	date("Y-m-d H:i:s");

		// build checksum
		$this->setChecksum($this->bookURL);

		if( (is_numeric($this->cardId)) && ($this->cardId>0) && (is_numeric($this->txId)) && ($this->txId>0) && ($this->mtcChecksum === $this->checksum) )
		{
			// Check Order
			$sqlQuery  = "SELECT COUNT(`card_id`) as 'card_id' FROM $this->db_tx_table ";
			$sqlQuery .= "WHERE `paid` = 0 AND `card_id` = $this->cardId";
			
			$countCard = $this->query($sqlQuery, true);
			$countCard = intval($countCard[1]['card_id']);

			if( $countCard === 1 )
			{
				// Insert Order
				$sqlQuery  = "UPDATE $this->db_tx_table ";
				$sqlQuery .= "SET `tx_id` = $this->txId, `tx_date` = '$this->txDate', `paid` = 1 ";
				$sqlQuery .= "WHERE `paid` = 0 AND `card_id` = $this->cardId";

				$this->query($sqlQuery);

				echo "1;" . $this->cardId . ";".date("Ymd").";2";
			}
			else
			{
				echo "0;Null;Null;Null";
			}			
		}
		else
		{
			echo "0;Null;Null;Null";
		}
	}

	public function update($cardId, $mtcChecksum, $totalAmountTx)
	{
		$this->cardId			=	intval($this->sqlinjection($this->xss($cardId)));
		$this->mtcChecksum 		=	$this->sqlinjection($this->xss($mtcChecksum));
		$this->totalAmountTx	=	$this->sqlinjection($this->xss($totalAmountTx));

		// build checksum
		$this->setChecksum($this->updateURL);

		if( (is_numeric($this->cardId)) && ($this->cardId>0) && ($this->mtcChecksum === $this->checksum) )
		{
			// Check Order
			$sqlQuery  = "SELECT tx_id FROM $this->db_tx_table ";
			$sqlQuery .= "WHERE `paid` = 1 AND `card_id` = $this->cardId AND `tx_id` > 0";
			
			$this->txId = $this->query($sqlQuery, true);

			if( count($this->txId) > 0 )
			{
				$this->txId = intval($this->txId[1]['tx_id']);

				/**
					TODO :
					custom error message
				*/
				$this->html  = "Votre commande a bien été acceptée sous la référence n°  $this->cardId<br />";
				$this->html .= "et le paiement n° $this->txId";
			}
			else
			{
				/**
					TODO :
					custom error message
				*/
				$this->html = "There is no unpaied order: ".$this->cardId;
			}
		}
		else
		{
			/**
				TODO :
				custom error message
			*/
			$this->html = "Echec de traitement de la commande !";
		}


		echo $this->html;
	}

	/**
		ORDER FUNCTIONS
	*/

	private function saveOrder()
	{
		// Insert Order
		$sqlQuery = "INSERT INTO $this->db_tx_table ";
		$sqlQuery .= "(card_amount, full_name, email, tel, address, state, city, post_code, country, curency, card_date) VALUES ";
		$sqlQuery .= "($this->totalAmountTx, '$this->name', '$this->email', '$this->tel', '$this->address', '$this->state', '$this->city', '$this->postCode', '$this->country', '$this->symbolCur', '$this->cardDate')";
		
		$this->query($sqlQuery);

		return $this->insertedId();
	}

	private function getOrder($cardId)
	{
		$sqlQuery = "SELECT * FROM $this->db_tx_table WHERE `card_id` = $cardId";
		return $this->query($sqlQuery, true);
	}

	/**
		MTC FUNCTIONS
	*/

	private function formRender()
	{
		$this->html .= "<form name='paymentForm' id='paymentForm' action='$this->actionSLK'  method='post'>";
		$this->html .= "<input type='hidden' name='storeId' value='$this->storeId'><br />";
		$this->html .= "<input type='hidden' name='langue' value='$this->language'><br />";
		$this->html .= "<input type='hidden' name='offerURL' value='$this->offerURL'><br />";
		$this->html .= "<input type='hidden' name='updateURL' value='$this->updateURL'><br />";
		$this->html .= "<input type='hidden' name='bookURL' value='$this->bookURL'><br />";
		$this->html .= "<input type='hidden' name='cartId' value='$this->cardId'><br />";
		$this->html .= "<input type='hidden' name='totalAmountTx' value='$this->totalAmountTx'><br />";
		$this->html .= "<input type='hidden' name='symbolCur' value='$this->symbolCur'><br />";
		$this->html .= "<input type='hidden' name='name' value='$this->name'><br />";
		$this->html .= "<input type='hidden' name='address' value='$this->address'><br />";
		$this->html .= "<input type='hidden' name='city' value='$this->city'><br />";
		$this->html .= "<input type='hidden' name='state' value='$this->state'><br />";
		$this->html .= "<input type='hidden' name='country' value='$this->country'><br />";
		$this->html .= "<input type='hidden' name='postCode' value='$this->postCode'><br />";
		$this->html .= "<input type='hidden' name='tel' value='$this->tel'><br />";
		$this->html .= "<input type='hidden' name='email' value='$this->email'><br />";
		$this->html .= "<input type='hidden' name='merchantType' value='$this->merchantType'><br />";
		$this->html .= "<input type='hidden' name='checksum' value='$this->checksum'><br />";
		$this->html .= "</form>";
		$this->html .= "<script language='JavaScript'>";
		$this->html .= "//document.forms['paymentForm'].submit();";
		$this->html .= "</script>";

		echo $this->html;
	}

	private function setChecksum($action)
	{
		$dataMD5		=	$action . $this->storeId . $this->cardId . $this->totalAmountTx . $this->email . $this->SLKSecretKey;
		$this->checksum	=	MD5($this->utf8entities(rawurlencode($dataMD5)));
	}

	/**
		DATABASE FUNCTIONS
	*/

	private function getConnection()
	{
		if(is_null($this->connection))
		{
			$host 			= $this->db_host;
			$dbname 		= $this->db_name;
			$db_driver 		= $this->db_driver;
			$db_user 		= $this->db_user;
			$db_password 	= $this->db_pass;
				
			try
			{
				$this->connection = new PDO("$db_driver:host=$host;dbname=$dbname", $db_user, $db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
				($this->debug)?
					$this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING)
					:$this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
				$this->mQuotes = get_magic_quotes_gpc();
			}
			catch(PDOException $e)
			{
				echo $e->getMessage();
			}
		}
		return $this->connection;
	}

	public function rowsCount()
	{
		return $this->sth->rowCount();
	}
	
	public function insertedId()
	{
		return $this->getConnection()->lastInsertId();
	}

	public function query($sqlQuery, $fetch = false)
	{
		$this->sth = $this->getConnection()->prepare($sqlQuery);
		$this->sth->execute();
		return ($fetch)?$this->fetch():true;
	}
	
	private function fetch($sth = null)
	{
		if(!is_null($sth))$this->sth=$sth;
		$fields = array();
		$data = array();
		$d=1;
		$numfields = $this->sth->columnCount();
	
		for ($i = 0; $i < $numfields; ++$i)
		{
			$filedName = $this->sth->getColumnMeta($i);
			array_push($fields,$filedName);
		}
	
		while($row=$this->sth->fetch())
		{
			for ($i = 0; $i < $numfields; ++$i)
			{
				if(!$this->mQuotes)$row[$i] = stripslashes($row[$i]);
				$data[$d][$fields[$i]['name']] = $row[$i];
			}
			++$d;
		}
		return $data;
	}

	/**
		HELPER FUNCTIONS
	*/
	private function utf8entities($source)
	{
		//    array used to figure what number to decrement from character order value 
		//    according to number of characters used to map unicode to ascii by utf-8
		$decrement[4] = 240;
		$decrement[3] = 224;
		$decrement[2] = 192;
		$decrement[1] = 0;

		//    the number of bits to shift each charNum by
		$shift[1][0] = 0;
		$shift[2][0] = 6;
		$shift[2][1] = 0;
		$shift[3][0] = 12;
		$shift[3][1] = 6;
		$shift[3][2] = 0;
		$shift[4][0] = 18;
		$shift[4][1] = 12;
		$shift[4][2] = 6;
		$shift[4][3] = 0;

		$pos = 0;
		$len = strlen($source);
		$encodedString = '';
		while ($pos < $len)
		{
			$charPos = substr($source, $pos, 1);
			$asciiPos = ord($charPos);
			if ($asciiPos < 128)
			{
				$encodedString .= htmlentities($charPos);
				$pos++;
				continue;
			}

			$i=1;
			if (($asciiPos >= 240) && ($asciiPos <= 255))  // 4 chars representing one unicode character
				$i=4;
			else if (($asciiPos >= 224) && ($asciiPos <= 239))  // 3 chars representing one unicode character
			 	$i=3;
			else if (($asciiPos >= 192) && ($asciiPos <= 223))  // 2 chars representing one unicode character
			 	$i=2;
			else  // 1 char (lower ascii)
			 	$i=1;
			$thisLetter = substr($source, $pos, $i);
			$pos += $i;

			//       process the string representing the letter to a unicode entity
			$thisLen = strlen($thisLetter);
			$thisPos = 0;
			$decimalCode = 0;
			while ($thisPos < $thisLen)
			{
			 	$thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
			 	if ($thisPos == 0)
			 	{
			    	$charNum = intval($thisCharOrd - $decrement[$thisLen]);
			    	$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			 	}
			 	else
			 	{
			    	$charNum = intval($thisCharOrd - 128);
			    	$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			 	}
			 
			 	$thisPos++;
			}

			$encodedLetter = '&#'. str_pad($decimalCode, ($thisLen==1)?3:5, '0', STR_PAD_LEFT).';';
			$encodedString .= $encodedLetter;
		}

		return $encodedString;
	}

	public function xss($value)
	{
		$value = htmlspecialchars($value);
		return $value;
	}
	
	public function sqlinjection($value)
	{
		if(!get_magic_quotes_gpc())$value =addslashes($value);($value);
		return $value;
	}
}

//EOF