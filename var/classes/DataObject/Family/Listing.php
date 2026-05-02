<?php

namespace Pimcore\Model\DataObject\Family;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method DataObject\Family|false current()
 * @method DataObject\Family[] load()
 * @method DataObject\Family[] getData()
 * @method DataObject\Family[] getObjects()
 */

class Listing extends DataObject\Listing\Concrete
{
protected $classId = "family";
protected $className = "family";


/**
* Filter by code (Code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("code")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by name (Name)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByName ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("name")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by familyType (Family Type)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByFamilyType ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("familyType")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by description (Description/Notes)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDescription ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("description")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by designers (Designers)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDesigners ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("designers")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by designersRelation (Designers relation)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDesignersRelation ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("designersRelation")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by exchangeableBranches (Exchangeable Branches)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByExchangeableBranches ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("exchangeableBranches")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by exchangeableBranchesPartial (Exchangeable Branches Partial)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByExchangeableBranchesPartial ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("exchangeableBranchesPartial")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by suppliers (Suppliers)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterBySuppliers ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("suppliers")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by phase (Phase)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByPhase ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("phase")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by startDate (Start Date)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByStartDate ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("startDate")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by launchPeriod (Launch Period)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLaunchPeriod ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("launchPeriod")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by launchYear (Launch Year)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLaunchYear ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("launchYear")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by posMaterialProducts (Pos Material Products)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByPosMaterialProducts ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("posMaterialProducts")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by servicePartProducts (Service Part Products)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByServicePartProducts ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("servicePartProducts")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by downloadableAssets (Downloadable assets)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDownloadableAssets ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("downloadableAssets")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by attachments (Attachments)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByAttachments ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("attachments")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by publicationChannels (Publication Channels)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByPublicationChannels ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("publicationChannels")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by workingTitle (Working Title)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByWorkingTitle ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("workingTitle")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by internalFollowupDesigner (Internal Followup Designer)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByInternalFollowupDesigner ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("internalFollowupDesigner")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by magicMechanismScore (Magic Mechanism Score)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMagicMechanismScore ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("magicMechanismScore")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by storytellingShortText (Storytelling Short text)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByStorytellingShortText ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("storytellingShortText")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by storytellingLongText (Storytelling Long text)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByStorytellingLongText ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("storytellingLongText")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by qualityControlDocuments (Quality Control Documents)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByQualityControlDocuments ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("qualityControlDocuments")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by qualityControlImages (Quality Control Images)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByQualityControlImages ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("qualityControlImages")->addListingFilter($this, $data, $operator);
	return $this;
}



}
