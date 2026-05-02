<?php

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - code [input]
 * - mainColorCode [input]
 * - name [input]
 * - itemGroup [manyToManyObjectRelation]
 * - supplier [manyToOneRelation]
 * - composedColors [advancedManyToManyObjectRelation]
 * - components [advancedManyToManyObjectRelation]
 * - posMaterialProducts [manyToManyObjectRelation]
 * - servicePartProducts [manyToManyObjectRelation]
 * - downloadableAssets [manyToManyObjectRelation]
 * - artBase [manyToOneRelation]
 * - seriesCode [input]
 * - ecomFileName [input]
 * - netMass [numeric]
 * - productSegment [select]
 * - parentItem [manyToOneRelation]
 * - lifeCycle [select]
 * - collectionCycle [select]
 * - exchangeCode [input]
 * - activeFrom [date]
 * - leadTime [numeric]
 * - lensColor [select]
 * - basicUDI [input]
 * - masterUDI [input]
 * - countryOfOrigin [country]
 * - dsArtCat [input]
 * - dsType [input]
 * - dsSize [input]
 * - dsTarif [multiselect]
 * - intrastatCode [input]
 * - basePrice [fieldcollections]
 * - pricing [fieldcollections]
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
* @method static \Pimcore\Model\DataObject\Frame\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByMainColorCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByItemGroup(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getBySupplier(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByComposedColors(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByComponents(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByPosMaterialProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByServicePartProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByDownloadableAssets(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByArtBase(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getBySeriesCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByEcomFileName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByNetMass(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByProductSegment(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByParentItem(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByLifeCycle(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByCollectionCycle(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByExchangeCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByActiveFrom(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByLeadTime(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByLensColor(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByBasicUDI(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByMasterUDI(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByCountryOfOrigin(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByDsArtCat(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByDsType(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByDsSize(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByDsTarif(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByIntrastatCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByAttachments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByPublicationChannels(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByMagicMechanismScore(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByLocalizedfields(string $field, mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByStorytellingShortText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByStorytellingLongText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByQualityControlDocuments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Frame\Listing|\Pimcore\Model\DataObject\Frame|null getByQualityControlImages(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class Frame extends \Pimcore\Model\DataObject\Model
{
public const FIELD_CODE = 'code';
public const FIELD_MAIN_COLOR_CODE = 'mainColorCode';
public const FIELD_NAME = 'name';
public const FIELD_ITEM_GROUP = 'itemGroup';
public const FIELD_SUPPLIER = 'supplier';
public const FIELD_COMPOSED_COLORS = 'composedColors';
public const FIELD_COMPONENTS = 'components';
public const FIELD_POS_MATERIAL_PRODUCTS = 'posMaterialProducts';
public const FIELD_SERVICE_PART_PRODUCTS = 'servicePartProducts';
public const FIELD_DOWNLOADABLE_ASSETS = 'downloadableAssets';
public const FIELD_ART_BASE = 'artBase';
public const FIELD_SERIES_CODE = 'seriesCode';
public const FIELD_ECOM_FILE_NAME = 'ecomFileName';
public const FIELD_NET_MASS = 'netMass';
public const FIELD_PRODUCT_SEGMENT = 'productSegment';
public const FIELD_PARENT_ITEM = 'parentItem';
public const FIELD_LIFE_CYCLE = 'lifeCycle';
public const FIELD_COLLECTION_CYCLE = 'collectionCycle';
public const FIELD_EXCHANGE_CODE = 'exchangeCode';
public const FIELD_ACTIVE_FROM = 'activeFrom';
public const FIELD_LEAD_TIME = 'leadTime';
public const FIELD_LENS_COLOR = 'lensColor';
public const FIELD_BASIC_UDI = 'basicUDI';
public const FIELD_MASTER_UDI = 'masterUDI';
public const FIELD_COUNTRY_OF_ORIGIN = 'countryOfOrigin';
public const FIELD_DS_ART_CAT = 'dsArtCat';
public const FIELD_DS_TYPE = 'dsType';
public const FIELD_DS_SIZE = 'dsSize';
public const FIELD_DS_TARIF = 'dsTarif';
public const FIELD_INTRASTAT_CODE = 'intrastatCode';
public const FIELD_BASE_PRICE = 'basePrice';
public const FIELD_PRICING = 'pricing';
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

protected $classId = "finishedProduct";
protected $className = "frame";
protected $code;
protected $mainColorCode;
protected $name;
protected $itemGroup;
protected $supplier;
protected $composedColors;
protected $components;
protected $posMaterialProducts;
protected $servicePartProducts;
protected $downloadableAssets;
protected $artBase;
protected $seriesCode;
protected $ecomFileName;
protected $netMass;
protected $productSegment;
protected $parentItem;
protected $lifeCycle;
protected $collectionCycle;
protected $exchangeCode;
protected $activeFrom;
protected $leadTime;
protected $lensColor;
protected $basicUDI;
protected $masterUDI;
protected $countryOfOrigin;
protected $dsArtCat;
protected $dsType;
protected $dsSize;
protected $dsTarif;
protected $intrastatCode;
protected $basePrice;
protected $pricing;
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
* Get mainColorCode - Main Color Code
* @return string|null
*/
public function getMainColorCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("mainColorCode");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->mainColorCode;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set mainColorCode - Main Color Code
* @param string|null $mainColorCode
* @return $this
*/
public function setMainColorCode(?string $mainColorCode): static
{
	$this->markFieldDirty("mainColorCode", true);

	$this->mainColorCode = $mainColorCode;

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
* Get itemGroup - Item Group
* @return \Pimcore\Model\DataObject\SAPItemGroup[]
*/
public function getItemGroup(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("itemGroup");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("itemGroup")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set itemGroup - Item Group
* @param \Pimcore\Model\DataObject\SAPItemGroup[] $itemGroup
* @return $this
*/
public function setItemGroup(?array $itemGroup): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("itemGroup");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getItemGroup();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $itemGroup);
	if (!$isEqual) {
		$this->markFieldDirty("itemGroup", true);
	}
	$this->itemGroup = $fd->preSetData($this, $itemGroup);
	return $this;
}

/**
* Get supplier - Supplier
* @return \Pimcore\Model\DataObject\Supplier|null
*/
public function getSupplier(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("supplier");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("supplier")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set supplier - Supplier
* @param \Pimcore\Model\DataObject\Supplier|null $supplier
* @return $this
*/
public function setSupplier(?\Pimcore\Model\Element\AbstractElement $supplier): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("supplier");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getSupplier();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $supplier);
	if (!$isEqual) {
		$this->markFieldDirty("supplier", true);
	}
	$this->supplier = $fd->preSetData($this, $supplier);
	return $this;
}

/**
* Get composedColors - Composed Colors
* @return \Pimcore\Model\DataObject\Data\ObjectMetadata[]
*/
public function getComposedColors(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("composedColors");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("composedColors")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set composedColors - Composed Colors
* @param \Pimcore\Model\DataObject\Data\ObjectMetadata[] $composedColors
* @return $this
*/
public function setComposedColors(?array $composedColors): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("composedColors");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getComposedColors();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $composedColors);
	if (!$isEqual) {
		$this->markFieldDirty("composedColors", true);
	}
	$this->composedColors = $fd->preSetData($this, $composedColors);
	return $this;
}

/**
* Get components - Components
* @return \Pimcore\Model\DataObject\Data\ObjectMetadata[]
*/
public function getComponents(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("components");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("components")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set components - Components
* @param \Pimcore\Model\DataObject\Data\ObjectMetadata[] $components
* @return $this
*/
public function setComponents(?array $components): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("components");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getComponents();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $components);
	if (!$isEqual) {
		$this->markFieldDirty("components", true);
	}
	$this->components = $fd->preSetData($this, $components);
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
	$currentData = $this->getPosMaterialProducts();
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
	$currentData = $this->getServicePartProducts();
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
	$currentData = $this->getDownloadableAssets();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $downloadableAssets);
	if (!$isEqual) {
		$this->markFieldDirty("downloadableAssets", true);
	}
	$this->downloadableAssets = $fd->preSetData($this, $downloadableAssets);
	return $this;
}

/**
* Get artBase - Art Base
* @return \Pimcore\Model\DataObject\Model|null
*/
public function getArtBase(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("artBase");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("artBase")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set artBase - Art Base
* @param \Pimcore\Model\DataObject\Model|null $artBase
* @return $this
*/
public function setArtBase(?\Pimcore\Model\Element\AbstractElement $artBase): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("artBase");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getArtBase();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $artBase);
	if (!$isEqual) {
		$this->markFieldDirty("artBase", true);
	}
	$this->artBase = $fd->preSetData($this, $artBase);
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
* Get ecomFileName - Ecom File Name
* @return string|null
*/
public function getEcomFileName(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("ecomFileName");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->ecomFileName;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set ecomFileName - Ecom File Name
* @param string|null $ecomFileName
* @return $this
*/
public function setEcomFileName(?string $ecomFileName): static
{
	$this->markFieldDirty("ecomFileName", true);

	$this->ecomFileName = $ecomFileName;

	return $this;
}

/**
* Get netMass - Net Mass
* @return float|null
*/
public function getNetMass(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("netMass");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->netMass;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set netMass - Net Mass
* @param float|null $netMass
* @return $this
*/
public function setNetMass(?float $netMass): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("netMass");
	$this->netMass = $fd->preSetData($this, $netMass);
	return $this;
}

/**
* Get productSegment - Product Segment
* @return string|null
*/
public function getProductSegment(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("productSegment");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->productSegment;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set productSegment - Product Segment
* @param string|null $productSegment
* @return $this
*/
public function setProductSegment(?string $productSegment): static
{
	$this->markFieldDirty("productSegment", true);

	$this->productSegment = $productSegment;

	return $this;
}

/**
* Get parentItem - Parent Item
* @return \Pimcore\Model\DataObject\Model|null
*/
public function getParentItem(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("parentItem");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("parentItem")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set parentItem - Parent Item
* @param \Pimcore\Model\DataObject\Model|null $parentItem
* @return $this
*/
public function setParentItem(?\Pimcore\Model\Element\AbstractElement $parentItem): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("parentItem");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getParentItem();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $parentItem);
	if (!$isEqual) {
		$this->markFieldDirty("parentItem", true);
	}
	$this->parentItem = $fd->preSetData($this, $parentItem);
	return $this;
}

/**
* Get lifeCycle - Life Cycle
* @return string|null
*/
public function getLifeCycle(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lifeCycle");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lifeCycle;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lifeCycle - Life Cycle
* @param string|null $lifeCycle
* @return $this
*/
public function setLifeCycle(?string $lifeCycle): static
{
	$this->markFieldDirty("lifeCycle", true);

	$this->lifeCycle = $lifeCycle;

	return $this;
}

/**
* Get collectionCycle - Collection Cycle
* @return string|null
*/
public function getCollectionCycle(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("collectionCycle");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->collectionCycle;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set collectionCycle - Collection Cycle
* @param string|null $collectionCycle
* @return $this
*/
public function setCollectionCycle(?string $collectionCycle): static
{
	$this->markFieldDirty("collectionCycle", true);

	$this->collectionCycle = $collectionCycle;

	return $this;
}

/**
* Get exchangeCode - Exchange Code
* @return string|null
*/
public function getExchangeCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("exchangeCode");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->exchangeCode;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set exchangeCode - Exchange Code
* @param string|null $exchangeCode
* @return $this
*/
public function setExchangeCode(?string $exchangeCode): static
{
	$this->markFieldDirty("exchangeCode", true);

	$this->exchangeCode = $exchangeCode;

	return $this;
}

/**
* Get activeFrom - Active From
* @return \Carbon\Carbon|null
*/
public function getActiveFrom(): ?\Carbon\Carbon
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("activeFrom");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->activeFrom;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set activeFrom - Active From
* @param \Carbon\Carbon|null $activeFrom
* @return $this
*/
public function setActiveFrom(?\Carbon\Carbon $activeFrom): static
{
	$this->markFieldDirty("activeFrom", true);

	$this->activeFrom = $activeFrom;

	return $this;
}

/**
* Get leadTime - Lead Time
* @return float|null
*/
public function getLeadTime(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("leadTime");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->leadTime;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set leadTime - Lead Time
* @param float|null $leadTime
* @return $this
*/
public function setLeadTime(?float $leadTime): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("leadTime");
	$this->leadTime = $fd->preSetData($this, $leadTime);
	return $this;
}

/**
* Get lensColor - Lens Color
* @return string|null
*/
public function getLensColor(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("lensColor");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->lensColor;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set lensColor - Lens Color
* @param string|null $lensColor
* @return $this
*/
public function setLensColor(?string $lensColor): static
{
	$this->markFieldDirty("lensColor", true);

	$this->lensColor = $lensColor;

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
* Get countryOfOrigin - Country Of Origin
* @return string|null
*/
public function getCountryOfOrigin(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("countryOfOrigin");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->countryOfOrigin;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set countryOfOrigin - Country Of Origin
* @param string|null $countryOfOrigin
* @return $this
*/
public function setCountryOfOrigin(?string $countryOfOrigin): static
{
	$this->markFieldDirty("countryOfOrigin", true);

	$this->countryOfOrigin = $countryOfOrigin;

	return $this;
}

/**
* Get dsArtCat - DS_ArtCat
* @return string|null
*/
public function getDsArtCat(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("dsArtCat");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->dsArtCat;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set dsArtCat - DS_ArtCat
* @param string|null $dsArtCat
* @return $this
*/
public function setDsArtCat(?string $dsArtCat): static
{
	$this->markFieldDirty("dsArtCat", true);

	$this->dsArtCat = $dsArtCat;

	return $this;
}

/**
* Get dsType - DS_Type
* @return string|null
*/
public function getDsType(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("dsType");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->dsType;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set dsType - DS_Type
* @param string|null $dsType
* @return $this
*/
public function setDsType(?string $dsType): static
{
	$this->markFieldDirty("dsType", true);

	$this->dsType = $dsType;

	return $this;
}

/**
* Get dsSize - DS_Size
* @return string|null
*/
public function getDsSize(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("dsSize");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->dsSize;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set dsSize - DS_Size
* @param string|null $dsSize
* @return $this
*/
public function setDsSize(?string $dsSize): static
{
	$this->markFieldDirty("dsSize", true);

	$this->dsSize = $dsSize;

	return $this;
}

/**
* Get dsTarif - Ds Tarif
* @return string[]|null
*/
public function getDsTarif(): ?array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("dsTarif");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->dsTarif;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set dsTarif - Ds Tarif
* @param string[]|null $dsTarif
* @return $this
*/
public function setDsTarif(?array $dsTarif): static
{
	$this->markFieldDirty("dsTarif", true);

	$this->dsTarif = $dsTarif;

	return $this;
}

/**
* Get intrastatCode - Intrastat Code
* @return string|null
*/
public function getIntrastatCode(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("intrastatCode");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->intrastatCode;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set intrastatCode - Intrastat Code
* @param string|null $intrastatCode
* @return $this
*/
public function setIntrastatCode(?string $intrastatCode): static
{
	$this->markFieldDirty("intrastatCode", true);

	$this->intrastatCode = $intrastatCode;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getBasePrice(): ?\Pimcore\Model\DataObject\Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("basePrice");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("basePrice")->preGetData($this);
	return $data;
}

/**
* Set basePrice - Base Price
* @param \Pimcore\Model\DataObject\Fieldcollection|null $basePrice
* @return $this
*/
public function setBasePrice(?\Pimcore\Model\DataObject\Fieldcollection $basePrice): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("basePrice");
	$this->basePrice = $fd->preSetData($this, $basePrice);
	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getPricing(): ?\Pimcore\Model\DataObject\Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("pricing");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("pricing")->preGetData($this);
	return $data;
}

/**
* Set pricing - Pricing
* @param \Pimcore\Model\DataObject\Fieldcollection|null $pricing
* @return $this
*/
public function setPricing(?\Pimcore\Model\DataObject\Fieldcollection $pricing): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("pricing");
	$this->pricing = $fd->preSetData($this, $pricing);
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
	$currentData = $this->getAttachments();
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
	$currentData = $this->getLocalizedfields();
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
	$currentData = $this->getQualityControlDocuments();
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
	$currentData = $this->getQualityControlImages();
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
