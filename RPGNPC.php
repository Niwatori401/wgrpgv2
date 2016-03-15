<?php

require_once "Database.php";
require_once "RPGNPCStats.php";
include_once "constants.php";

class RPGNPC{

	private $_intNPCID;
	private $_strNPCName;
	private $_intCurrentHP;
	private $_objStats;
	private $_intWeight;
	private $_intHeight;
	private $_intExperienceGiven;
	private $_intGoldDropMin;
	private $_intGoldDropMax;
	private $_objEquippedWeapon;
	private $_objEquippedArmour;
	private $_objEquippedSecondary;
	private $_dtmCreatedOn;
	private $_strCreatedBy;
	private $_dtmModifiedOn;
	private $_strModifiedBy;
	
	public function RPGNPC($intNPCID = null){
		if($intNPCID){
			$this->loadNPCInfo($intNPCID);
		}
	}
	
	private function populateVarFromRow($arrNPCInfo){
		$this->setNPCID($arrNPCInfo['intNPCID']);
		$this->setNPCName($arrNPCInfo['strNPCName']);
		$this->setWeight($arrNPCInfo['intWeight']);
		$this->setHeight($arrNPCInfo['intHeight']);
		$this->setExperienceGiven($arrNPCInfo['intExperienceGiven']);
		$this->setGoldDropMin($arrNPCInfo['intGoldDropMin']);
		$this->setGoldDropMax($arrNPCInfo['intGoldDropMax']);
		$this->setCreatedOn($arrNPCInfo['dtmCreatedOn']);
		$this->setCreatedBy($arrNPCInfo['strCreatedBy']);
		$this->setModifiedOn($arrNPCInfo['dtmModifiedOn']);
		$this->setModifiedBy($arrNPCInfo['strModifiedBy']);
	}
	
	private function loadNPCInfo($intNPCID){
		$objDB = new Database();
		$arrNPCInfo = array();
			$strSQL = "SELECT *
						FROM tblnpc
							WHERE intNPCID = " . $objDB->quote($intNPCID);
			$rsResult = $objDB->query($strSQL);
			while ($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
				$arrNPCInfo['intNPCID'] = $arrRow['intNPCID'];
				$arrNPCInfo['strNPCName'] = $arrRow['strNPCName'];
				$arrNPCInfo['intWeight'] = $arrRow['intWeight'];
				$arrNPCInfo['intHeight'] = $arrRow['intHeight'];
				$arrNPCInfo['intExperienceGiven'] = $arrRow['intExperienceGiven'];
				$arrNPCInfo['intGoldDropMin'] = $arrRow['intGoldDropMin'];
				$arrNPCInfo['intGoldDropMax'] = $arrRow['intGoldDropMax'];
				$arrNPCInfo['dtmCreatedOn'] = $arrRow['dtmCreatedOn'];
				$arrNPCInfo['strCreatedBy'] = $arrRow['strCreatedBy'];
				$arrNPCInfo['dtmModifiedOn'] = $arrRow['dtmModifiedOn'];
				$arrNPCInfo['strModifiedBy'] = $arrRow['strModifiedBy'];
			}
		$this->populateVarFromRow($arrNPCInfo);
		$this->_objEquippedArmour = $this->loadEquippedArmour();
		$this->_objEquippedWeapon = $this->loadEquippedWeapon();
		$this->_objEquippedSecondary = $this->loadEquippedSecondary();
		$this->_objStats = new RPGNPCStats($intNPCID);
		$this->_objStats->loadBaseStats();
		$this->setCurrentHP($this->_objStats->getBaseStats()['intMaxHP']);
	}
	
	public function takeDamage($intDamage){
		$intDamage = max(1, $intDamage);
		$this->setCurrentHP($this->getCurrentHP() - $intDamage);
		return $intDamage;
	}
	
	public function isDead(){
		return intval($this->getCurrentHP()) <= 0 ? 1 : 0;
	}
	
	public function getCurrentHP(){
		return $this->_intCurrentHP;
	}
	
	public function setCurrentHP($intCurrentHP){
		$this->_intCurrentHP = $intCurrentHP;
	}
	
	public function loadEquippedArmour(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:%'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
		$objArmour = new RPGItem($arrRow['intItemID']);
		return $objArmour;
	}
	
	public function loadEquippedWeapon(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND (strHandType = 'Primary' OR strHandType = 'Both')
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
		$objWeapon = new RPGItem($arrRow['intItemID']);
		return $objWeapon;
	}
	
	public function loadEquippedSecondary(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND strHandType = 'Secondary'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
		$objSecondary = new RPGItem($arrRow['intItemID']);
		return $objSecondary;
	}
	
	public function getRandomDrops(){
		$objDB = new Database();
		$arrDrops = array();
		$strSQL = "SELECT intItemID, strItemName, intDropRating
					FROM tblnpcitemxr
						INNER JOIN tblitem
						USING(intItemID)
					WHERE blnDropped = 1
						AND intNPCID = " . $objDB->quote($this->getNPCID());
		$rsResult = $objDB->query($strSQL);
		while($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
			$intRand = mt_rand(0, 10000);
			if($intRand <= $arrRow['intDropRating']){
				$arrDrops[$arrRow['intItemID']] = $arrRow['strItemName'];
			}
		}
		return $arrDrops;
	}
	
	public function getEnemyBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function EnemyGainWeight($intAmount){
		$this->setWeight($this->getWeight() + $intAmount);
	}
	
	public function getModifiedMaxHP(){
		return round($this->_objStats->getBaseStats()['intMaxHP'] + ($this->_objStats->getBaseStats()['intVitality'] / 2));
	}
	
	public function getModifiedDamage(){
		return round(($this->_objStats->getBaseStats()['intStrength'] / 2) + $this->getEquippedWeapon()->getDamage());
	}
	
	public function getModifiedMagicDamage(){
		return round(($this->_objStats->getBaseStats()['intIntelligence'] / 2) + $this->getEquippedWeapon()->getMagicDamage());
	}
	
	public function getModifiedDefence(){
		return round(($this->_objStats->getBaseStats()['intVitality'] / 4) + $this->getEquippedArmour()->getDefence());
	}
	
	public function getModifiedMagicDefence(){
		return round(($this->_objStats->getBaseStats()['intIntelligence'] / 4) + $this->getEquippedArmour()->getMagicDefence());
	}
	
	public function getModifiedBlockRate(){
		return round($this->_objStats->getBaseStats()['intAgility'] / 4);
	}
	
	public function getModifiedBlock(){
		return 0.6;
	}
	
	public function getModifiedCritRate(){
		return round($this->_objStats->getBaseStats()['intDexterity'] / 4);
	}
	
	public function getModifiedCritDamage(){
		return 1.5;
	}
	
	public function getWaitTime($strWaitType){
		if($strWaitType == 'Standard'){
			// standard attack
			return round(250 - ($this->_objStats->getCombinedStats('intAgility') / 2) + (250 * $this->getImmobilityFactor()));
		}
		// skills will add on or decrease wait time by some amount
	}
	
	public function getStats(){
		return $this->_objStats;
	}
	
	public function getNPCID(){
		return $this->_intNPCID;
	}
	
	public function setNPCID($intNPCID){
		$this->_intNPCID = $intNPCID;
	}
	
	public function getNPCName(){
		return $this->_strNPCName;
	}
	
	public function setNPCName($strNPCName){
		$this->_strNPCName = $strNPCName;
	}
	
	public function getWeight(){
		return $this->_intWeight;
	}
	
	public function setWeight($intWeight){
		$this->_intWeight = $intWeight;
	}
	
	public function getEquippedArmour(){
		return $this->_objEquippedArmour;
	}
	
	public function setEquippedArmour($objArmour){
		$this->_objEquippedArmour = $objArmour;
	}
	
	public function getEquippedWeapon(){
		return $this->_objEquippedWeapon;
	}
	
	public function setEquippedWeapon($objWeapon){
		$this->_objEquippedWeapon = $objWeapon;
	}
	
	public function getEquippedSecondary(){
		return $this->_objEquippedSecondary;
	}
	
	public function setEquippedSecondary($objSecondary){
		$this->_objEquippedSecondary = $objSecondary;
	}
	
	public function getHeight(){
		return $this->_intHeight;
	}
	
	public function setHeight($intHeight){
		$this->_intHeight = $intHeight;
	}
	
	public function getExperienceGiven(){
		return $this->_intExperienceGiven;
	}
	
	public function setExperienceGiven($intExperienceGiven){
		$this->_intExperienceGiven = $intExperienceGiven;
	}
	
	public function getGoldDropMin(){
		return $this->_intGoldDropMin;
	}
	
	public function setGoldDropMin($intGoldDropMin){
		$this->_intGoldDropMin = $intGoldDropMin;
	}
	
	public function getGoldDropMax(){
		return $this->_intGoldDropMax;
	}
	
	public function setGoldDropMax($intGoldDropMax){
		$this->_intGoldDropMax = $intGoldDropMax;
	}
	
	public function getCreatedOn(){
		return $this->_dtmCreatedOn;
	}
	
	public function setCreatedOn($dtmCreatedOn){
		$this->_dtmCreatedOn = $dtmCreatedOn;
	}
	
	public function getCreatedBy(){
		return $this->_strCreatedBy;
	}
	
	public function setCreatedBy($strCreatedBy){
		$this->_strCreatedBy = $strCreatedBy;
	}
	
	public function getModifiedOn(){
		return $this->_dtmModifiedOn;
	}
	
	public function setModifiedOn($dtmModifiedOn){
		$this->_dtmModifiedOn = $dtmModifiedOn;
	}
	
	public function getModifiedBy(){
		return $this->_strModifiedBy;
	}
	
	public function setModifiedBy($strModifiedBy){
		$this->_strModifiedBy = $strModifiedBy;
	}
	
	public function getImmobilityFactor(){
		return max(0, ((($this->getBMI() / 40) / 10) - (($this->_objStats->getCombinedStats('intStrength') / 4) / 100)));
	}
	
	public function getBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function getHeightInFeet(){
		$dblFeet = $this->getHeight() / dblCM_PER_FOOT;
		$whole = floor($dblFeet);
		$fraction = $dblFeet - $whole;
		$intInches = round($fraction * intFEET_PER_INCH);
		if($intInches == 12){
			$whole++;
			$intInches = 0;
		}
		return strval($whole) . "'" . strval($intInches) . "\"";
	}
}

?>