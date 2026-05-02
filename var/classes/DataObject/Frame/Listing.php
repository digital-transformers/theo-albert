<?php

namespace Pimcore\Model\DataObject\Frame;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method DataObject\Frame|false current()
 * @method DataObject\Frame[] load()
 * @method DataObject\Frame[] getData()
 * @method DataObject\Frame[] getObjects()
 */

class Listing extends DataObject\Listing\Concrete
{
protected $classId = "finishedProduct";
protected $className = "frame";


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
* Filter by mainColorCode (Main Color Code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMainColorCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("mainColorCode")->addListingFilter($this, $data, $operator);
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
* Filter by itemGroup (Item Group)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByItemGroup ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("itemGroup")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by supplier (Supplier)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterBySupplier ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("supplier")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by composedColors (Composed Colors)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByComposedColors ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("composedColors")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by components (Components)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByComponents ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("components")->addListingFilter($this, $data, $operator);
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
* Filter by artBase (Art Base)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByArtBase ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("artBase")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by seriesCode (Series Code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterBySeriesCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("seriesCode")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by ecomFileName (Ecom File Name)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByEcomFileName ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("ecomFileName")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by netMass (Net Mass)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByNetMass ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("netMass")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by productSegment (Product Segment)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByProductSegment ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("productSegment")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by parentItem (Parent Item)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByParentItem ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("parentItem")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lifeCycle (Life Cycle)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLifeCycle ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lifeCycle")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by collectionCycle (Collection Cycle)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByCollectionCycle ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("collectionCycle")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by exchangeCode (Exchange Code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByExchangeCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("exchangeCode")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by activeFrom (Active From)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByActiveFrom ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("activeFrom")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by leadTime (Lead Time)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLeadTime ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("leadTime")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lensColor (Lens Color)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLensColor ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lensColor")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by basicUDI (Basic UDI)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByBasicUDI ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("basicUDI")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by masterUDI (Master UDI)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMasterUDI ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("masterUDI")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by countryOfOrigin (Country Of Origin)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByCountryOfOrigin ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("countryOfOrigin")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by dsArtCat (DS_ArtCat)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDsArtCat ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("dsArtCat")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by dsType (DS_Type)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDsType ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("dsType")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by dsSize (DS_Size)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDsSize ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("dsSize")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by dsTarif (Ds Tarif)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDsTarif ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("dsTarif")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by intrastatCode (Intrastat Code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByIntrastatCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("intrastatCode")->addListingFilter($this, $data, $operator);
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
