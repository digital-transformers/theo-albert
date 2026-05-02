<?php

/**
 * Inheritance: yes
 * Variants: no
 *
 * Fields Summary:
 * - code [input]
 * - name [input]
 * - frameBaseCode [input]
 * - description [textarea]
 * - outlineImage [image]
 * - planAttachment [advancedManyToManyRelation]
 * - posMaterialProducts [manyToManyObjectRelation]
 * - servicePartProducts [manyToManyObjectRelation]
 * - downloadableAssets [manyToManyObjectRelation]
 * - seriesCode [input]
 * - createDate [date]
 * - shape [select]
 * - lookAndFeel [select]
 * - typeOfMetal [select]
 * - material [input]
 * - materialType [select]
 * - metalFacepartTickness [numeric]
 * - metalTempleTickness [numeric]
 * - acetateFacepartTickness [numeric]
 * - acetateTempleTickness [numeric]
 * - facepartReference [input]
 * - templeReference [input]
 * - templeLenght [numeric]
 * - facepartReferenceSupplier [input]
 * - templeReferenceSupplier [input]
 * - lensType [multiselect]
 * - lensMounting [input]
 * - lensHeight [numeric]
 * - boxingSize [input]
 * - widthVisibleLens [numeric]
 * - distanceBetweenLens [numeric]
 * - heightVisibleLens [numeric]
 * - totalWidth [numeric]
 * - hingeRef [input]
 * - hingeScrewRef [input]
 * - rlRef [input]
 * - rlScrewRef [input]
 * - totalTempleLength [numeric]
 * - templeTipRef [input]
 * - templeTipSupplier [manyToManyObjectRelation]
 * - templeTipColor [select]
 * - templeTipColorRelation [manyToManyObjectRelation]
 * - templeTipMaterial [input]
 * - templeTipSurface [input]
 * - toolingSamplesGallery [imageGallery]
 * - basicUDI [input]
 * - masterUDI [input]
 * - finalProductDetails [fieldcollections]
 * - finalProducts [manyToManyObjectRelation]
 * - imageGallery [imageGallery]
 * - facebookImageGallery [imageGallery]
 * - instagramImageGallery [imageGallery]
 * - video [video]
 * - attachments [advancedManyToManyRelation]
 * - publicationChannels [multiselect]
 * - magicMechanismScore [numeric]
 * - localizedfields [localizedfields]
 * -- storytellingShortText [textarea]
 * -- storytellingLongText [wysiwyg]
 * - qualityControlDocuments [advancedManyToManyRelation]
 * - qualityControlImages [advancedManyToManyRelation]
 * - qualityControlRemarks [table]
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\Model\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByFrameBaseCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByDescription(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByOutlineImage(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByPlanAttachment(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByPosMaterialProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByServicePartProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByDownloadableAssets(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getBySeriesCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByCreateDate(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByShape(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByLookAndFeel(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTypeOfMetal(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMaterial(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMaterialType(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMetalFacepartTickness(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMetalTempleTickness(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByAcetateFacepartTickness(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByAcetateTempleTickness(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByFacepartReference(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleReference(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleLenght(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByFacepartReferenceSupplier(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleReferenceSupplier(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByLensType(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByLensMounting(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByLensHeight(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByBoxingSize(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByWidthVisibleLens(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByDistanceBetweenLens(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByHeightVisibleLens(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTotalWidth(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByHingeRef(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByHingeScrewRef(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByRlRef(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByRlScrewRef(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTotalTempleLength(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipRef(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipSupplier(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipColor(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipColorRelation(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipMaterial(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByTempleTipSurface(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByBasicUDI(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMasterUDI(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByFinalProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByAttachments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByPublicationChannels(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByMagicMechanismScore(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByLocalizedfields(string $field, mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByStorytellingShortText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByStorytellingLongText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByQualityControlDocuments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Model\Listing|\Pimcore\Model\DataObject\Model|null getByQualityControlImages(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class Model extends \Pimcore\Model\DataObject\Family
{
public const FIELD_CODE = 'code';
public const FIELD_NAME = 'name';
public const FIELD_FRAME_BASE_CODE = 'frameBaseCode';
public const FIELD_DESCRIPTION = 'description';
public const FIELD_OUTLINE_IMAGE = 'outlineImage';
public const FIELD_PLAN_ATTACHMENT = 'planAttachment';
public const FIELD_POS_MATERIAL_PRODUCTS = 'posMaterialProducts';
public const FIELD_SERVICE_PART_PRODUCTS = 'servicePartProducts';
public const FIELD_DOWNLOADABLE_ASSETS = 'downloadableAssets';
public const FIELD_SERIES_CODE = 'seriesCode';
public const FIELD_CREATE_DATE = 'createDate';
public const FIELD_SHAPE = 'shape';
public const FIELD_LOOK_AND_FEEL = 'lookAndFeel';
public const FIELD_TYPE_OF_METAL = 'typeOfMetal';
public const FIELD_MATERIAL = 'material';
public const FIELD_MATERIAL_TYPE = 'materialType';
public const FIELD_METAL_FACEPART_TICKNESS = 'metalFacepartTickness';
public const FIELD_METAL_TEMPLE_TICKNESS = 'metalTempleTickness';
public const FIELD_ACETATE_FACEPART_TICKNESS = 'acetateFacepartTickness';
public const FIELD_ACETATE_TEMPLE_TICKNESS = 'acetateTempleTickness';
public const FIELD_FACEPART_REFERENCE = 'facepartReference';
public const FIELD_TEMPLE_REFERENCE = 'templeReference';
public const FIELD_TEMPLE_LENGHT = 'templeLenght';
public const FIELD_FACEPART_REFERENCE_SUPPLIER = 'facepartReferenceSupplier';
public const FIELD_TEMPLE_REFERENCE_SUPPLIER = 'templeReferenceSupplier';
public const FIELD_LENS_TYPE = 'lensType';
public const FIELD_LENS_MOUNTING = 'lensMounting';
public const FIELD_LENS_HEIGHT = 'lensHeight';
public const FIELD_BOXING_SIZE = 'boxingSize';
public const FIELD_WIDTH_VISIBLE_LENS = 'widthVisibleLens';
public const FIELD_DISTANCE_BETWEEN_LENS = 'distanceBetweenLens';
public const FIELD_HEIGHT_VISIBLE_LENS = 'heightVisibleLens';
public const FIELD_TOTAL_WIDTH = 'totalWidth';
public const FIELD_HINGE_REF = 'hingeRef';
public const FIELD_HINGE_SCREW_REF = 'hingeScrewRef';
public const FIELD_RL_REF = 'rlRef';
public const FIELD_RL_SCREW_REF = 'rlScrewRef';
public const FIELD_TOTAL_TEMPLE_LENGTH = 'totalTempleLength';
public const FIELD_TEMPLE_TIP_REF = 'templeTipRef';
public const FIELD_TEMPLE_TIP_SUPPLIER = 'templeTipSupplier';
public const FIELD_TEMPLE_TIP_COLOR = 'templeTipColor';
public const FIELD_TEMPLE_TIP_COLOR_RELATION = 'templeTipColorRelation';
public const FIELD_TEMPLE_TIP_MATERIAL = 'templeTipMaterial';
public const FIELD_TEMPLE_TIP_SURFACE = 'templeTipSurface';
public const FIELD_TOOLING_SAMPLES_GALLERY = 'toolingSamplesGallery';
public const FIELD_BASIC_UDI = 'basicUDI';
public const FIELD_MASTER_UDI = 'masterUDI';
public const FIELD_FINAL_PRODUCT_DETAILS = 'finalProductDetails';
public const FIELD_FINAL_PRODUCTS = 'finalProducts';
public const FIELD_IMAGE_GALLERY = 'imageGallery';
public const FIELD_FACEBOOK_IMAGE_GALLERY = 'facebookImageGallery';
public const FIELD_INSTAGRAM_IMAGE_GALLERY = 'instagramImageGallery';
public const FIELD_VIDEO = 'video';
public const FIELD_ATTACHMENTS = 'attachments';
public const FIELD_PUBLICATION_CHANNELS = 'publicationChannels';
public const FIELD_MAGIC_MECHANISM_SCORE = 'magicMechanismScore';
public const FIELD_STORYTELLING_SHORT_TEXT = 'storytellingShortText';
public const FIELD_STORYTELLING_LONG_TEXT = 'storytellingLongText';
public const FIELD_QUALITY_CONTROL_DOCUMENTS = 'qualityControlDocuments';
public const FIELD_QUALITY_CONTROL_IMAGES = 'qualityControlImages';
public const FIELD_QUALITY_CONTROL_REMARKS = 'qualityControlRemarks';

protected $classId = "baseProduct";
protected $className = "model";
protected $code;
protected $name;
protected $frameBaseCode;
protected $description;
protected $outlineImage;
protected $planAttachment;
protected $posMaterialProducts;
protected $servicePartProducts;
protected $downloadableAssets;
protected $seriesCode;
protected $createDate;
protected $shape;
protected $lookAndFeel;
protected $typeOfMetal;
protected $material;
protected $materialType;
protected $metalFacepartTickness;
protected $metalTempleTickness;
protected $acetateFacepartTickness;
protected $acetateTempleTickness;
protected $facepartReference;
protected $templeReference;
protected $templeLenght;
protected $facepartReferenceSupplier;
protected $templeReferenceSupplier;
protected $lensType;
protected $lensMounting;
protected $lensHeight;
protected $boxingSize;
protected $widthVisibleLens;
protected $distanceBetweenLens;
protected $heightVisibleLens;
protected $totalWidth;
protected $hingeRef;
protected $hingeScrewRef;
protected $rlRef;
protected $rlScrewRef;
protected $totalTempleLength;
protected $templeTipRef;
protected $templeTipSupplier;
protected $templeTipColor;
protected $templeTipColorRelation;
protected $templeTipMaterial;
protected $templeTipSurface;
protected $toolingSamplesGallery;
protected $basicUDI;
protected $masterUDI;
protected $finalProductDetails;
protected $finalProducts;
protected $imageGallery;
protected $facebookImageGallery;
protected $instagramImageGallery;
protected $video;
protected $attachments;
protected $publicationChannels;
protected $magicMechanismScore;
protected $localizedfields;
protected $qualityControlDocuments;
protected $qualityControlImages;
protected $qualityControlRemarks;


/**
* @param array $values
* @return static
*/
public static function create(array $values = []): static
{
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get code - Code
* @return string|null
*/
public function getCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("code");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->code;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("code")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("code");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set code - Code
* @param string|null $code
* @return $this
*/
public function setCode(?string $code): static
{
	$this->markFieldDirty("code", true);

	$this->code = $code;

	return $this;
}

/**
* Get name - Name
* @return string|null
*/
public function getName(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("name");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->name;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("name")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("name");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set name - Name
* @param string|null $name
* @return $this
*/
public function setName(?string $name): static
{
	$this->markFieldDirty("name", true);

	$this->name = $name;

	return $this;
}

/**
* Get frameBaseCode - Frame Base code
* @return string|null
*/
public function getFrameBaseCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("frameBaseCode");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->frameBaseCode;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("frameBaseCode")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("frameBaseCode");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set frameBaseCode - Frame Base code
* @param string|null $frameBaseCode
* @return $this
*/
public function setFrameBaseCode(?string $frameBaseCode): static
{
	$this->markFieldDirty("frameBaseCode", true);

	$this->frameBaseCode = $frameBaseCode;

	return $this;
}

/**
* Get description - Description
* @return string|null
*/
public function getDescription(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("description");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->description;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("description")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("description");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set description - Description
* @param string|null $description
* @return $this
*/
public function setDescription(?string $description): static
{
	$this->markFieldDirty("description", true);

	$this->description = $description;

	return $this;
}

/**
* Get outlineImage - Outline Image
* @return \Pimcore\Model\Asset\Image|null
*/
public function getOutlineImage(): ?\Pimcore\Model\Asset\Image
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("outlineImage");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->outlineImage;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("outlineImage")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("outlineImage");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set outlineImage - Outline Image
* @param \Pimcore\Model\Asset\Image|null $outlineImage
* @return $this
*/
public function setOutlineImage(?\Pimcore\Model\Asset\Image $outlineImage): static
{
	$this->markFieldDirty("outlineImage", true);

	$this->outlineImage = $outlineImage;

	return $this;
}

/**
* Get planAttachment - Plan Attachment
* @return \Pimcore\Model\DataObject\Data\ElementMetadata[]
*/
public function getPlanAttachment(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("planAttachment");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("planAttachment")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("planAttachment")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("planAttachment");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set planAttachment - Plan Attachment
* @param \Pimcore\Model\DataObject\Data\ElementMetadata[] $planAttachment
* @return $this
*/
public function setPlanAttachment(?array $planAttachment): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("planAttachment");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getPlanAttachment();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $planAttachment);
	if (!$isEqual) {
		$this->markFieldDirty("planAttachment", true);
	}
	$this->planAttachment = $fd->preSetData($this, $planAttachment);
	return $this;
}

/**
* Get posMaterialProducts - Pos Material Products
* @return \Pimcore\Model\DataObject\PosMaterialProduct[]
*/
public function getPosMaterialProducts(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("posMaterialProducts");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("posMaterialProducts")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("posMaterialProducts")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("posMaterialProducts");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set posMaterialProducts - Pos Material Products
* @param \Pimcore\Model\DataObject\PosMaterialProduct[] $posMaterialProducts
* @return $this
*/
public function setPosMaterialProducts(?array $posMaterialProducts): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("posMaterialProducts");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getPosMaterialProducts();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $posMaterialProducts);
	if (!$isEqual) {
		$this->markFieldDirty("posMaterialProducts", true);
	}
	$this->posMaterialProducts = $fd->preSetData($this, $posMaterialProducts);
	return $this;
}

/**
* Get servicePartProducts - Service Part Products
* @return \Pimcore\Model\DataObject\ServicePartsProduct[]
*/
public function getServicePartProducts(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("servicePartProducts");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("servicePartProducts")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("servicePartProducts")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("servicePartProducts");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set servicePartProducts - Service Part Products
* @param \Pimcore\Model\DataObject\ServicePartsProduct[] $servicePartProducts
* @return $this
*/
public function setServicePartProducts(?array $servicePartProducts): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("servicePartProducts");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getServicePartProducts();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $servicePartProducts);
	if (!$isEqual) {
		$this->markFieldDirty("servicePartProducts", true);
	}
	$this->servicePartProducts = $fd->preSetData($this, $servicePartProducts);
	return $this;
}

/**
* Get downloadableAssets - Downloadable assets
* @return \Pimcore\Model\DataObject\DownloadableAsset[]
*/
public function getDownloadableAssets(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("downloadableAssets");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("downloadableAssets")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("downloadableAssets")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("downloadableAssets");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set downloadableAssets - Downloadable assets
* @param \Pimcore\Model\DataObject\DownloadableAsset[] $downloadableAssets
* @return $this
*/
public function setDownloadableAssets(?array $downloadableAssets): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("downloadableAssets");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getDownloadableAssets();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $downloadableAssets);
	if (!$isEqual) {
		$this->markFieldDirty("downloadableAssets", true);
	}
	$this->downloadableAssets = $fd->preSetData($this, $downloadableAssets);
	return $this;
}

/**
* Get seriesCode - Series Code
* @return string|null
*/
public function getSeriesCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("seriesCode");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->seriesCode;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("seriesCode")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("seriesCode");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set seriesCode - Series Code
* @param string|null $seriesCode
* @return $this
*/
public function setSeriesCode(?string $seriesCode): static
{
	$this->markFieldDirty("seriesCode", true);

	$this->seriesCode = $seriesCode;

	return $this;
}

/**
* Get createDate - Create Date
* @return \Carbon\Carbon|null
*/
public function getCreateDate(): ?\Carbon\Carbon
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("createDate");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->createDate;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("createDate")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("createDate");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set createDate - Create Date
* @param \Carbon\Carbon|null $createDate
* @return $this
*/
public function setCreateDate(?\Carbon\Carbon $createDate): static
{
	$this->markFieldDirty("createDate", true);

	$this->createDate = $createDate;

	return $this;
}

/**
* Get shape - Shape
* @return string|null
*/
public function getShape(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("shape");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->shape;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("shape")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("shape");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set shape - Shape
* @param string|null $shape
* @return $this
*/
public function setShape(?string $shape): static
{
	$this->markFieldDirty("shape", true);

	$this->shape = $shape;

	return $this;
}

/**
* Get lookAndFeel - Look And Feel
* @return string|null
*/
public function getLookAndFeel(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lookAndFeel");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lookAndFeel;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("lookAndFeel")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("lookAndFeel");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lookAndFeel - Look And Feel
* @param string|null $lookAndFeel
* @return $this
*/
public function setLookAndFeel(?string $lookAndFeel): static
{
	$this->markFieldDirty("lookAndFeel", true);

	$this->lookAndFeel = $lookAndFeel;

	return $this;
}

/**
* Get typeOfMetal - Type Of Metal
* @return string|null
*/
public function getTypeOfMetal(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("typeOfMetal");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->typeOfMetal;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("typeOfMetal")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("typeOfMetal");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set typeOfMetal - Type Of Metal
* @param string|null $typeOfMetal
* @return $this
*/
public function setTypeOfMetal(?string $typeOfMetal): static
{
	$this->markFieldDirty("typeOfMetal", true);

	$this->typeOfMetal = $typeOfMetal;

	return $this;
}

/**
* Get material - Material
* @return string|null
*/
public function getMaterial(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("material");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->material;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("material")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("material");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set material - Material
* @param string|null $material
* @return $this
*/
public function setMaterial(?string $material): static
{
	$this->markFieldDirty("material", true);

	$this->material = $material;

	return $this;
}

/**
* Get materialType - Material Type
* @return string|null
*/
public function getMaterialType(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("materialType");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->materialType;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("materialType")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("materialType");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set materialType - Material Type
* @param string|null $materialType
* @return $this
*/
public function setMaterialType(?string $materialType): static
{
	$this->markFieldDirty("materialType", true);

	$this->materialType = $materialType;

	return $this;
}

/**
* Get metalFacepartTickness - Metal Facepart Tickness
* @return float|null
*/
public function getMetalFacepartTickness(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("metalFacepartTickness");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->metalFacepartTickness;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("metalFacepartTickness")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("metalFacepartTickness");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set metalFacepartTickness - Metal Facepart Tickness
* @param float|null $metalFacepartTickness
* @return $this
*/
public function setMetalFacepartTickness(?float $metalFacepartTickness): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("metalFacepartTickness");
	$this->metalFacepartTickness = $fd->preSetData($this, $metalFacepartTickness);
	return $this;
}

/**
* Get metalTempleTickness - Metal Temple Tickness
* @return float|null
*/
public function getMetalTempleTickness(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("metalTempleTickness");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->metalTempleTickness;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("metalTempleTickness")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("metalTempleTickness");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set metalTempleTickness - Metal Temple Tickness
* @param float|null $metalTempleTickness
* @return $this
*/
public function setMetalTempleTickness(?float $metalTempleTickness): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("metalTempleTickness");
	$this->metalTempleTickness = $fd->preSetData($this, $metalTempleTickness);
	return $this;
}

/**
* Get acetateFacepartTickness - Acetate Facepart Tickness
* @return float|null
*/
public function getAcetateFacepartTickness(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("acetateFacepartTickness");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->acetateFacepartTickness;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("acetateFacepartTickness")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("acetateFacepartTickness");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set acetateFacepartTickness - Acetate Facepart Tickness
* @param float|null $acetateFacepartTickness
* @return $this
*/
public function setAcetateFacepartTickness(?float $acetateFacepartTickness): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("acetateFacepartTickness");
	$this->acetateFacepartTickness = $fd->preSetData($this, $acetateFacepartTickness);
	return $this;
}

/**
* Get acetateTempleTickness - Acetate Temple Tickness
* @return float|null
*/
public function getAcetateTempleTickness(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("acetateTempleTickness");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->acetateTempleTickness;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("acetateTempleTickness")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("acetateTempleTickness");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set acetateTempleTickness - Acetate Temple Tickness
* @param float|null $acetateTempleTickness
* @return $this
*/
public function setAcetateTempleTickness(?float $acetateTempleTickness): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("acetateTempleTickness");
	$this->acetateTempleTickness = $fd->preSetData($this, $acetateTempleTickness);
	return $this;
}

/**
* Get facepartReference - Facepart Reference (Theo)
* @return string|null
*/
public function getFacepartReference(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("facepartReference");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->facepartReference;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("facepartReference")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("facepartReference");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set facepartReference - Facepart Reference (Theo)
* @param string|null $facepartReference
* @return $this
*/
public function setFacepartReference(?string $facepartReference): static
{
	$this->markFieldDirty("facepartReference", true);

	$this->facepartReference = $facepartReference;

	return $this;
}

/**
* Get templeReference - Temple Reference (Theo)
* @return string|null
*/
public function getTempleReference(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeReference");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeReference;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeReference")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeReference");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeReference - Temple Reference (Theo)
* @param string|null $templeReference
* @return $this
*/
public function setTempleReference(?string $templeReference): static
{
	$this->markFieldDirty("templeReference", true);

	$this->templeReference = $templeReference;

	return $this;
}

/**
* Get templeLenght - Temple Lenght
* @return float|null
*/
public function getTempleLenght(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeLenght");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeLenght;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeLenght")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeLenght");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeLenght - Temple Lenght
* @param float|null $templeLenght
* @return $this
*/
public function setTempleLenght(?float $templeLenght): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("templeLenght");
	$this->templeLenght = $fd->preSetData($this, $templeLenght);
	return $this;
}

/**
* Get facepartReferenceSupplier - Facepart Reference (Supplier)
* @return string|null
*/
public function getFacepartReferenceSupplier(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("facepartReferenceSupplier");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->facepartReferenceSupplier;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("facepartReferenceSupplier")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("facepartReferenceSupplier");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set facepartReferenceSupplier - Facepart Reference (Supplier)
* @param string|null $facepartReferenceSupplier
* @return $this
*/
public function setFacepartReferenceSupplier(?string $facepartReferenceSupplier): static
{
	$this->markFieldDirty("facepartReferenceSupplier", true);

	$this->facepartReferenceSupplier = $facepartReferenceSupplier;

	return $this;
}

/**
* Get templeReferenceSupplier - Temple Reference (Supplier)
* @return string|null
*/
public function getTempleReferenceSupplier(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeReferenceSupplier");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeReferenceSupplier;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeReferenceSupplier")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeReferenceSupplier");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeReferenceSupplier - Temple Reference (Supplier)
* @param string|null $templeReferenceSupplier
* @return $this
*/
public function setTempleReferenceSupplier(?string $templeReferenceSupplier): static
{
	$this->markFieldDirty("templeReferenceSupplier", true);

	$this->templeReferenceSupplier = $templeReferenceSupplier;

	return $this;
}

/**
* Get lensType - Lens Type
* @return string[]|null
*/
public function getLensType(): ?array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lensType");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lensType;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("lensType")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("lensType");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lensType - Lens Type
* @param string[]|null $lensType
* @return $this
*/
public function setLensType(?array $lensType): static
{
	$this->markFieldDirty("lensType", true);

	$this->lensType = $lensType;

	return $this;
}

/**
* Get lensMounting - Lens Mounting
* @return string|null
*/
public function getLensMounting(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lensMounting");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lensMounting;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("lensMounting")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("lensMounting");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lensMounting - Lens Mounting
* @param string|null $lensMounting
* @return $this
*/
public function setLensMounting(?string $lensMounting): static
{
	$this->markFieldDirty("lensMounting", true);

	$this->lensMounting = $lensMounting;

	return $this;
}

/**
* Get lensHeight - Lens Height
* @return float|null
*/
public function getLensHeight(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lensHeight");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lensHeight;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("lensHeight")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("lensHeight");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lensHeight - Lens Height
* @param float|null $lensHeight
* @return $this
*/
public function setLensHeight(?float $lensHeight): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("lensHeight");
	$this->lensHeight = $fd->preSetData($this, $lensHeight);
	return $this;
}

/**
* Get boxingSize - Boxing Size
* @return string|null
*/
public function getBoxingSize(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("boxingSize");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->boxingSize;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("boxingSize")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("boxingSize");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set boxingSize - Boxing Size
* @param string|null $boxingSize
* @return $this
*/
public function setBoxingSize(?string $boxingSize): static
{
	$this->markFieldDirty("boxingSize", true);

	$this->boxingSize = $boxingSize;

	return $this;
}

/**
* Get widthVisibleLens - Width Visible Lens
* @return float|null
*/
public function getWidthVisibleLens(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("widthVisibleLens");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->widthVisibleLens;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("widthVisibleLens")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("widthVisibleLens");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set widthVisibleLens - Width Visible Lens
* @param float|null $widthVisibleLens
* @return $this
*/
public function setWidthVisibleLens(?float $widthVisibleLens): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("widthVisibleLens");
	$this->widthVisibleLens = $fd->preSetData($this, $widthVisibleLens);
	return $this;
}

/**
* Get distanceBetweenLens - Distance Between Lens
* @return float|null
*/
public function getDistanceBetweenLens(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("distanceBetweenLens");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->distanceBetweenLens;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("distanceBetweenLens")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("distanceBetweenLens");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set distanceBetweenLens - Distance Between Lens
* @param float|null $distanceBetweenLens
* @return $this
*/
public function setDistanceBetweenLens(?float $distanceBetweenLens): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("distanceBetweenLens");
	$this->distanceBetweenLens = $fd->preSetData($this, $distanceBetweenLens);
	return $this;
}

/**
* Get heightVisibleLens - Height Visible Lens
* @return float|null
*/
public function getHeightVisibleLens(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("heightVisibleLens");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->heightVisibleLens;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("heightVisibleLens")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("heightVisibleLens");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set heightVisibleLens - Height Visible Lens
* @param float|null $heightVisibleLens
* @return $this
*/
public function setHeightVisibleLens(?float $heightVisibleLens): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("heightVisibleLens");
	$this->heightVisibleLens = $fd->preSetData($this, $heightVisibleLens);
	return $this;
}

/**
* Get totalWidth - Total Width
* @return float|null
*/
public function getTotalWidth(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("totalWidth");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->totalWidth;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("totalWidth")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("totalWidth");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set totalWidth - Total Width
* @param float|null $totalWidth
* @return $this
*/
public function setTotalWidth(?float $totalWidth): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("totalWidth");
	$this->totalWidth = $fd->preSetData($this, $totalWidth);
	return $this;
}

/**
* Get hingeRef - Hinge Ref.
* @return string|null
*/
public function getHingeRef(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("hingeRef");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->hingeRef;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("hingeRef")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("hingeRef");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set hingeRef - Hinge Ref.
* @param string|null $hingeRef
* @return $this
*/
public function setHingeRef(?string $hingeRef): static
{
	$this->markFieldDirty("hingeRef", true);

	$this->hingeRef = $hingeRef;

	return $this;
}

/**
* Get hingeScrewRef - Hinge Screw Ref.
* @return string|null
*/
public function getHingeScrewRef(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("hingeScrewRef");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->hingeScrewRef;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("hingeScrewRef")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("hingeScrewRef");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set hingeScrewRef - Hinge Screw Ref.
* @param string|null $hingeScrewRef
* @return $this
*/
public function setHingeScrewRef(?string $hingeScrewRef): static
{
	$this->markFieldDirty("hingeScrewRef", true);

	$this->hingeScrewRef = $hingeScrewRef;

	return $this;
}

/**
* Get rlRef - RL Ref.
* @return string|null
*/
public function getRlRef(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("rlRef");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->rlRef;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("rlRef")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("rlRef");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set rlRef - RL Ref.
* @param string|null $rlRef
* @return $this
*/
public function setRlRef(?string $rlRef): static
{
	$this->markFieldDirty("rlRef", true);

	$this->rlRef = $rlRef;

	return $this;
}

/**
* Get rlScrewRef - RL Screw Ref.
* @return string|null
*/
public function getRlScrewRef(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("rlScrewRef");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->rlScrewRef;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("rlScrewRef")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("rlScrewRef");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set rlScrewRef - RL Screw Ref.
* @param string|null $rlScrewRef
* @return $this
*/
public function setRlScrewRef(?string $rlScrewRef): static
{
	$this->markFieldDirty("rlScrewRef", true);

	$this->rlScrewRef = $rlScrewRef;

	return $this;
}

/**
* Get totalTempleLength - Total Temple Length
* @return float|null
*/
public function getTotalTempleLength(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("totalTempleLength");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->totalTempleLength;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("totalTempleLength")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("totalTempleLength");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set totalTempleLength - Total Temple Length
* @param float|null $totalTempleLength
* @return $this
*/
public function setTotalTempleLength(?float $totalTempleLength): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("totalTempleLength");
	$this->totalTempleLength = $fd->preSetData($this, $totalTempleLength);
	return $this;
}

/**
* Get templeTipRef - Temple Tip Ref.
* @return string|null
*/
public function getTempleTipRef(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipRef");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeTipRef;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipRef")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipRef");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipRef - Temple Tip Ref.
* @param string|null $templeTipRef
* @return $this
*/
public function setTempleTipRef(?string $templeTipRef): static
{
	$this->markFieldDirty("templeTipRef", true);

	$this->templeTipRef = $templeTipRef;

	return $this;
}

/**
* Get templeTipSupplier - Temple Tip Supplier
* @return \Pimcore\Model\DataObject\Supplier[]
*/
public function getTempleTipSupplier(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipSupplier");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("templeTipSupplier")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipSupplier")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipSupplier");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipSupplier - Temple Tip Supplier
* @param \Pimcore\Model\DataObject\Supplier[] $templeTipSupplier
* @return $this
*/
public function setTempleTipSupplier(?array $templeTipSupplier): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("templeTipSupplier");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getTempleTipSupplier();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $templeTipSupplier);
	if (!$isEqual) {
		$this->markFieldDirty("templeTipSupplier", true);
	}
	$this->templeTipSupplier = $fd->preSetData($this, $templeTipSupplier);
	return $this;
}

/**
* Get templeTipColor - Temple Tip Color
* @return string|null
*/
public function getTempleTipColor(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipColor");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeTipColor;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipColor")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipColor");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipColor - Temple Tip Color
* @param string|null $templeTipColor
* @return $this
*/
public function setTempleTipColor(?string $templeTipColor): static
{
	$this->markFieldDirty("templeTipColor", true);

	$this->templeTipColor = $templeTipColor;

	return $this;
}

/**
* Get templeTipColorRelation - Temple Tip Color Relation
* @return \Pimcore\Model\DataObject\Color[]
*/
public function getTempleTipColorRelation(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipColorRelation");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("templeTipColorRelation")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipColorRelation")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipColorRelation");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipColorRelation - Temple Tip Color Relation
* @param \Pimcore\Model\DataObject\Color[] $templeTipColorRelation
* @return $this
*/
public function setTempleTipColorRelation(?array $templeTipColorRelation): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("templeTipColorRelation");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getTempleTipColorRelation();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $templeTipColorRelation);
	if (!$isEqual) {
		$this->markFieldDirty("templeTipColorRelation", true);
	}
	$this->templeTipColorRelation = $fd->preSetData($this, $templeTipColorRelation);
	return $this;
}

/**
* Get templeTipMaterial - Temple Tip Material
* @return string|null
*/
public function getTempleTipMaterial(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipMaterial");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeTipMaterial;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipMaterial")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipMaterial");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipMaterial - Temple Tip Material
* @param string|null $templeTipMaterial
* @return $this
*/
public function setTempleTipMaterial(?string $templeTipMaterial): static
{
	$this->markFieldDirty("templeTipMaterial", true);

	$this->templeTipMaterial = $templeTipMaterial;

	return $this;
}

/**
* Get templeTipSurface - Temple Tip Surface
* @return string|null
*/
public function getTempleTipSurface(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("templeTipSurface");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->templeTipSurface;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("templeTipSurface")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("templeTipSurface");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set templeTipSurface - Temple Tip Surface
* @param string|null $templeTipSurface
* @return $this
*/
public function setTempleTipSurface(?string $templeTipSurface): static
{
	$this->markFieldDirty("templeTipSurface", true);

	$this->templeTipSurface = $templeTipSurface;

	return $this;
}

/**
* Get toolingSamplesGallery - Tooling Samples
* @return \Pimcore\Model\DataObject\Data\ImageGallery|null
*/
public function getToolingSamplesGallery(): ?\Pimcore\Model\DataObject\Data\ImageGallery
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("toolingSamplesGallery");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->toolingSamplesGallery;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("toolingSamplesGallery")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("toolingSamplesGallery");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set toolingSamplesGallery - Tooling Samples
* @param \Pimcore\Model\DataObject\Data\ImageGallery|null $toolingSamplesGallery
* @return $this
*/
public function setToolingSamplesGallery(?\Pimcore\Model\DataObject\Data\ImageGallery $toolingSamplesGallery): static
{
	$this->markFieldDirty("toolingSamplesGallery", true);

	$this->toolingSamplesGallery = $toolingSamplesGallery;

	return $this;
}

/**
* Get basicUDI - Basic UDI
* @return string|null
*/
public function getBasicUDI(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("basicUDI");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->basicUDI;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("basicUDI")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("basicUDI");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set basicUDI - Basic UDI
* @param string|null $basicUDI
* @return $this
*/
public function setBasicUDI(?string $basicUDI): static
{
	$this->markFieldDirty("basicUDI", true);

	$this->basicUDI = $basicUDI;

	return $this;
}

/**
* Get masterUDI - Master UDI
* @return string|null
*/
public function getMasterUDI(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("masterUDI");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->masterUDI;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("masterUDI")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("masterUDI");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set masterUDI - Master UDI
* @param string|null $masterUDI
* @return $this
*/
public function setMasterUDI(?string $masterUDI): static
{
	$this->markFieldDirty("masterUDI", true);

	$this->masterUDI = $masterUDI;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getFinalProductDetails(): ?\Pimcore\Model\DataObject\Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("finalProductDetails");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("finalProductDetails")->preGetData($this);
	return $data;
}

/**
* Set finalProductDetails - Final product details
* @param \Pimcore\Model\DataObject\Fieldcollection|null $finalProductDetails
* @return $this
*/
public function setFinalProductDetails(?\Pimcore\Model\DataObject\Fieldcollection $finalProductDetails): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("finalProductDetails");
	$this->finalProductDetails = $fd->preSetData($this, $finalProductDetails);
	return $this;
}

/**
* Get finalProducts - Final Products
* @return \Pimcore\Model\DataObject\Frame[]
*/
public function getFinalProducts(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("finalProducts");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("finalProducts")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("finalProducts")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("finalProducts");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set finalProducts - Final Products
* @param \Pimcore\Model\DataObject\Frame[] $finalProducts
* @return $this
*/
public function setFinalProducts(?array $finalProducts): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("finalProducts");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getFinalProducts();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $finalProducts);
	if (!$isEqual) {
		$this->markFieldDirty("finalProducts", true);
	}
	$this->finalProducts = $fd->preSetData($this, $finalProducts);
	return $this;
}

/**
* Get imageGallery - Image Gallery
* @return \Pimcore\Model\DataObject\Data\ImageGallery|null
*/
public function getImageGallery(): ?\Pimcore\Model\DataObject\Data\ImageGallery
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("imageGallery");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->imageGallery;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("imageGallery")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("imageGallery");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set imageGallery - Image Gallery
* @param \Pimcore\Model\DataObject\Data\ImageGallery|null $imageGallery
* @return $this
*/
public function setImageGallery(?\Pimcore\Model\DataObject\Data\ImageGallery $imageGallery): static
{
	$this->markFieldDirty("imageGallery", true);

	$this->imageGallery = $imageGallery;

	return $this;
}

/**
* Get facebookImageGallery - Facebook Image Gallery
* @return \Pimcore\Model\DataObject\Data\ImageGallery|null
*/
public function getFacebookImageGallery(): ?\Pimcore\Model\DataObject\Data\ImageGallery
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("facebookImageGallery");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->facebookImageGallery;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("facebookImageGallery")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("facebookImageGallery");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set facebookImageGallery - Facebook Image Gallery
* @param \Pimcore\Model\DataObject\Data\ImageGallery|null $facebookImageGallery
* @return $this
*/
public function setFacebookImageGallery(?\Pimcore\Model\DataObject\Data\ImageGallery $facebookImageGallery): static
{
	$this->markFieldDirty("facebookImageGallery", true);

	$this->facebookImageGallery = $facebookImageGallery;

	return $this;
}

/**
* Get instagramImageGallery - Instagram Image Gallery
* @return \Pimcore\Model\DataObject\Data\ImageGallery|null
*/
public function getInstagramImageGallery(): ?\Pimcore\Model\DataObject\Data\ImageGallery
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("instagramImageGallery");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->instagramImageGallery;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("instagramImageGallery")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("instagramImageGallery");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set instagramImageGallery - Instagram Image Gallery
* @param \Pimcore\Model\DataObject\Data\ImageGallery|null $instagramImageGallery
* @return $this
*/
public function setInstagramImageGallery(?\Pimcore\Model\DataObject\Data\ImageGallery $instagramImageGallery): static
{
	$this->markFieldDirty("instagramImageGallery", true);

	$this->instagramImageGallery = $instagramImageGallery;

	return $this;
}

/**
* Get video - Video
* @return \Pimcore\Model\DataObject\Data\Video|null
*/
public function getVideo(): ?\Pimcore\Model\DataObject\Data\Video
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("video");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->video;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("video")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("video");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set video - Video
* @param \Pimcore\Model\DataObject\Data\Video|null $video
* @return $this
*/
public function setVideo(?\Pimcore\Model\DataObject\Data\Video $video): static
{
	$this->markFieldDirty("video", true);

	$this->video = $video;

	return $this;
}

/**
* Get attachments - Attachments
* @return \Pimcore\Model\DataObject\Data\ElementMetadata[]
*/
public function getAttachments(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("attachments");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("attachments")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("attachments")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("attachments");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set attachments - Attachments
* @param \Pimcore\Model\DataObject\Data\ElementMetadata[] $attachments
* @return $this
*/
public function setAttachments(?array $attachments): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("attachments");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getAttachments();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $attachments);
	if (!$isEqual) {
		$this->markFieldDirty("attachments", true);
	}
	$this->attachments = $fd->preSetData($this, $attachments);
	return $this;
}

/**
* Get publicationChannels - Publication Channels
* @return string[]|null
*/
public function getPublicationChannels(): ?array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("publicationChannels");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->publicationChannels;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("publicationChannels")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("publicationChannels");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set publicationChannels - Publication Channels
* @param string[]|null $publicationChannels
* @return $this
*/
public function setPublicationChannels(?array $publicationChannels): static
{
	$this->markFieldDirty("publicationChannels", true);

	$this->publicationChannels = $publicationChannels;

	return $this;
}

/**
* Get magicMechanismScore - Magic Mechanism Score
* @return float|null
*/
public function getMagicMechanismScore(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("magicMechanismScore");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->magicMechanismScore;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("magicMechanismScore")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("magicMechanismScore");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set magicMechanismScore - Magic Mechanism Score
* @param float|null $magicMechanismScore
* @return $this
*/
public function setMagicMechanismScore(?float $magicMechanismScore): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("magicMechanismScore");
	$this->magicMechanismScore = $fd->preSetData($this, $magicMechanismScore);
	return $this;
}

/**
* Get localizedfields - 
* @return \Pimcore\Model\DataObject\Localizedfield|null
*/
public function getLocalizedfields(): ?\Pimcore\Model\DataObject\Localizedfield
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("localizedfields");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("localizedfields")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("localizedfields")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("localizedfields");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Get storytellingShortText - Storytelling Short text
* @return string|null
*/
public function getStorytellingShortText(?string $language = null): ?string
{
	$data = $this->getLocalizedfields()->getLocalizedValue("storytellingShortText", $language);
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("storytellingShortText");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Get storytellingLongText - Storytelling Long text
* @return string|null
*/
public function getStorytellingLongText(?string $language = null): ?string
{
	$data = $this->getLocalizedfields()->getLocalizedValue("storytellingLongText", $language);
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("storytellingLongText");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set localizedfields - 
* @param \Pimcore\Model\DataObject\Localizedfield|null $localizedfields
* @return $this
*/
public function setLocalizedfields(?\Pimcore\Model\DataObject\Localizedfield $localizedfields): static
{
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getLocalizedfields();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$this->markFieldDirty("localizedfields", true);
	$this->markFieldDirty("localizedfields", true);

	$this->localizedfields = $localizedfields;

	return $this;
}

/**
* Set storytellingShortText - Storytelling Short text
* @param string|null $storytellingShortText
* @return $this
*/
public function setStorytellingShortText (?string $storytellingShortText, ?string $language = null): static
{
	$isEqual = false;
	$this->getLocalizedfields()->setLocalizedValue("storytellingShortText", $storytellingShortText, $language, !$isEqual);

	return $this;
}

/**
* Set storytellingLongText - Storytelling Long text
* @param string|null $storytellingLongText
* @return $this
*/
public function setStorytellingLongText (?string $storytellingLongText, ?string $language = null): static
{
	$isEqual = false;
	$this->getLocalizedfields()->setLocalizedValue("storytellingLongText", $storytellingLongText, $language, !$isEqual);

	return $this;
}

/**
* Get qualityControlDocuments - Quality Control Documents
* @return \Pimcore\Model\DataObject\Data\ElementMetadata[]
*/
public function getQualityControlDocuments(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("qualityControlDocuments");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("qualityControlDocuments")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("qualityControlDocuments")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("qualityControlDocuments");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set qualityControlDocuments - Quality Control Documents
* @param \Pimcore\Model\DataObject\Data\ElementMetadata[] $qualityControlDocuments
* @return $this
*/
public function setQualityControlDocuments(?array $qualityControlDocuments): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("qualityControlDocuments");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getQualityControlDocuments();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $qualityControlDocuments);
	if (!$isEqual) {
		$this->markFieldDirty("qualityControlDocuments", true);
	}
	$this->qualityControlDocuments = $fd->preSetData($this, $qualityControlDocuments);
	return $this;
}

/**
* Get qualityControlImages - Quality Control Images
* @return \Pimcore\Model\DataObject\Data\ElementMetadata[]
*/
public function getQualityControlImages(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("qualityControlImages");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("qualityControlImages")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("qualityControlImages")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("qualityControlImages");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set qualityControlImages - Quality Control Images
* @param \Pimcore\Model\DataObject\Data\ElementMetadata[] $qualityControlImages
* @return $this
*/
public function setQualityControlImages(?array $qualityControlImages): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("qualityControlImages");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getQualityControlImages();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $qualityControlImages);
	if (!$isEqual) {
		$this->markFieldDirty("qualityControlImages", true);
	}
	$this->qualityControlImages = $fd->preSetData($this, $qualityControlImages);
	return $this;
}

/**
* Get qualityControlRemarks - Quality Remarks
* @return array
*/
public function getQualityControlRemarks(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("qualityControlRemarks");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->qualityControlRemarks;

	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("qualityControlRemarks")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("qualityControlRemarks");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain() ?? [];
	}

	return $data ?? [];
}

/**
* Set qualityControlRemarks - Quality Remarks
* @param array|null $qualityControlRemarks
* @return $this
*/
public function setQualityControlRemarks(?array $qualityControlRemarks): static
{
	$this->markFieldDirty("qualityControlRemarks", true);

	$this->qualityControlRemarks = $qualityControlRemarks;

	return $this;
}

}
