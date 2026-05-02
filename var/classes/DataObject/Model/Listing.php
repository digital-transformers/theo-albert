<?php

namespace Pimcore\Model\DataObject\Model;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method DataObject\Model|false current()
 * @method DataObject\Model[] load()
 * @method DataObject\Model[] getData()
 * @method DataObject\Model[] getObjects()
 */

class Listing extends DataObject\Listing\Concrete
{
protected $classId = "baseProduct";
protected $className = "model";


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
* Filter by frameBaseCode (Frame Base code)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByFrameBaseCode ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("frameBaseCode")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by description (Description)
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
* Filter by outlineImage (Outline Image)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByOutlineImage ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("outlineImage")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by planAttachment (Plan Attachment)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByPlanAttachment ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("planAttachment")->addListingFilter($this, $data, $operator);
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
* Filter by createDate (Create Date)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByCreateDate ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("createDate")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by shape (Shape)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByShape ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("shape")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lookAndFeel (Look And Feel)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLookAndFeel ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lookAndFeel")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by typeOfMetal (Type Of Metal)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTypeOfMetal ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("typeOfMetal")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by material (Material)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMaterial ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("material")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by materialType (Material Type)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMaterialType ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("materialType")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by metalFacepartTickness (Metal Facepart Tickness)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMetalFacepartTickness ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("metalFacepartTickness")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by metalTempleTickness (Metal Temple Tickness)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByMetalTempleTickness ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("metalTempleTickness")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by acetateFacepartTickness (Acetate Facepart Tickness)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByAcetateFacepartTickness ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("acetateFacepartTickness")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by acetateTempleTickness (Acetate Temple Tickness)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByAcetateTempleTickness ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("acetateTempleTickness")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by facepartReference (Facepart Reference (Theo))
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByFacepartReference ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("facepartReference")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeReference (Temple Reference (Theo))
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleReference ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeReference")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeLenght (Temple Lenght)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleLenght ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeLenght")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by facepartReferenceSupplier (Facepart Reference (Supplier))
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByFacepartReferenceSupplier ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("facepartReferenceSupplier")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeReferenceSupplier (Temple Reference (Supplier))
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleReferenceSupplier ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeReferenceSupplier")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lensType (Lens Type)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLensType ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lensType")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lensMounting (Lens Mounting)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLensMounting ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lensMounting")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by lensHeight (Lens Height)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByLensHeight ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("lensHeight")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by boxingSize (Boxing Size)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByBoxingSize ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("boxingSize")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by widthVisibleLens (Width Visible Lens)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByWidthVisibleLens ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("widthVisibleLens")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by distanceBetweenLens (Distance Between Lens)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByDistanceBetweenLens ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("distanceBetweenLens")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by heightVisibleLens (Height Visible Lens)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByHeightVisibleLens ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("heightVisibleLens")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by totalWidth (Total Width)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTotalWidth ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("totalWidth")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by hingeRef (Hinge Ref.)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByHingeRef ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("hingeRef")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by hingeScrewRef (Hinge Screw Ref.)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByHingeScrewRef ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("hingeScrewRef")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by rlRef (RL Ref.)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByRlRef ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("rlRef")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by rlScrewRef (RL Screw Ref.)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByRlScrewRef ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("rlScrewRef")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by totalTempleLength (Total Temple Length)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTotalTempleLength ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("totalTempleLength")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipRef (Temple Tip Ref.)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipRef ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipRef")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipSupplier (Temple Tip Supplier)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipSupplier ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipSupplier")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipColor (Temple Tip Color)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipColor ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipColor")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipColorRelation (Temple Tip Color Relation)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipColorRelation ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipColorRelation")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipMaterial (Temple Tip Material)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipMaterial ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipMaterial")->addListingFilter($this, $data, $operator);
	return $this;
}

/**
* Filter by templeTipSurface (Temple Tip Surface)
* @param string|int|float|array|Model\Element\ElementInterface $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
* @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByTempleTipSurface ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("templeTipSurface")->addListingFilter($this, $data, $operator);
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
* Filter by finalProducts (Final Products)
* @param mixed $data
* @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
* @return $this
*/
public function filterByFinalProducts ($data, $operator = '='): static
{
	$this->getClass()->getFieldDefinition("finalProducts")->addListingFilter($this, $data, $operator);
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
