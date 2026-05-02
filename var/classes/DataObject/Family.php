<?php

/**
 * Inheritance: yes
 * Variants: no
 *
 * Fields Summary:
 * - code [input]
 * - name [input]
 * - familyType [select]
 * - description [textarea]
 * - designers [multiselect]
 * - designersRelation [manyToManyRelation]
 * - exchangeableBranches [select]
 * - exchangeableBranchesPartial [input]
 * - suppliers [advancedManyToManyObjectRelation]
 * - phase [select]
 * - startDate [date]
 * - launchPeriod [select]
 * - launchYear [numeric]
 * - posMaterialProducts [manyToManyObjectRelation]
 * - servicePartProducts [manyToManyObjectRelation]
 * - downloadableAssets [manyToManyObjectRelation]
 * - basePrice [fieldcollections]
 * - pricing [fieldcollections]
 * - imageGallery [imageGallery]
 * - facebookImageGallery [imageGallery]
 * - instagramImageGallery [imageGallery]
 * - video [video]
 * - attachments [advancedManyToManyRelation]
 * - publicationChannels [multiselect]
 * - workingTitle [input]
 * - internalFollowupDesigner [select]
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
* @method static \Pimcore\Model\DataObject\Family\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByCode(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByFamilyType(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByDescription(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByDesigners(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByDesignersRelation(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByExchangeableBranches(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByExchangeableBranchesPartial(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getBySuppliers(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByPhase(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByStartDate(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByLaunchPeriod(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByLaunchYear(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByPosMaterialProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByServicePartProducts(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByDownloadableAssets(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByAttachments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByPublicationChannels(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByWorkingTitle(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByInternalFollowupDesigner(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByMagicMechanismScore(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByLocalizedfields(string $field, mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByStorytellingShortText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByStorytellingLongText(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByQualityControlDocuments(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\Family\Listing|\Pimcore\Model\DataObject\Family|null getByQualityControlImages(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class Family extends Concrete
{
public const FIELD_CODE = 'code';
public const FIELD_NAME = 'name';
public const FIELD_FAMILY_TYPE = 'familyType';
public const FIELD_DESCRIPTION = 'description';
public const FIELD_DESIGNERS = 'designers';
public const FIELD_DESIGNERS_RELATION = 'designersRelation';
public const FIELD_EXCHANGEABLE_BRANCHES = 'exchangeableBranches';
public const FIELD_EXCHANGEABLE_BRANCHES_PARTIAL = 'exchangeableBranchesPartial';
public const FIELD_SUPPLIERS = 'suppliers';
public const FIELD_PHASE = 'phase';
public const FIELD_START_DATE = 'startDate';
public const FIELD_LAUNCH_PERIOD = 'launchPeriod';
public const FIELD_LAUNCH_YEAR = 'launchYear';
public const FIELD_POS_MATERIAL_PRODUCTS = 'posMaterialProducts';
public const FIELD_SERVICE_PART_PRODUCTS = 'servicePartProducts';
public const FIELD_DOWNLOADABLE_ASSETS = 'downloadableAssets';
public const FIELD_BASE_PRICE = 'basePrice';
public const FIELD_PRICING = 'pricing';
public const FIELD_IMAGE_GALLERY = 'imageGallery';
public const FIELD_FACEBOOK_IMAGE_GALLERY = 'facebookImageGallery';
public const FIELD_INSTAGRAM_IMAGE_GALLERY = 'instagramImageGallery';
public const FIELD_VIDEO = 'video';
public const FIELD_ATTACHMENTS = 'attachments';
public const FIELD_PUBLICATION_CHANNELS = 'publicationChannels';
public const FIELD_WORKING_TITLE = 'workingTitle';
public const FIELD_INTERNAL_FOLLOWUP_DESIGNER = 'internalFollowupDesigner';
public const FIELD_MAGIC_MECHANISM_SCORE = 'magicMechanismScore';
public const FIELD_STORYTELLING_SHORT_TEXT = 'storytellingShortText';
public const FIELD_STORYTELLING_LONG_TEXT = 'storytellingLongText';
public const FIELD_QUALITY_CONTROL_DOCUMENTS = 'qualityControlDocuments';
public const FIELD_QUALITY_CONTROL_IMAGES = 'qualityControlImages';
public const FIELD_QUALITY_CONTROL_REMARKS = 'qualityControlRemarks';

protected $classId = "family";
protected $className = "family";
protected $code;
protected $name;
protected $familyType;
protected $description;
protected $designers;
protected $designersRelation;
protected $exchangeableBranches;
protected $exchangeableBranchesPartial;
protected $suppliers;
protected $phase;
protected $startDate;
protected $launchPeriod;
protected $launchYear;
protected $posMaterialProducts;
protected $servicePartProducts;
protected $downloadableAssets;
protected $basePrice;
protected $pricing;
protected $imageGallery;
protected $facebookImageGallery;
protected $instagramImageGallery;
protected $video;
protected $attachments;
protected $publicationChannels;
protected $workingTitle;
protected $internalFollowupDesigner;
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
* Get familyType - Family Type
* @return string|null
*/
public function getFamilyType(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("familyType");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->familyType;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("familyType")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("familyType");
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
* Set familyType - Family Type
* @param string|null $familyType
* @return $this
*/
public function setFamilyType(?string $familyType): static
{
	$this->markFieldDirty("familyType", true);

	$this->familyType = $familyType;

	return $this;
}

/**
* Get description - Description/Notes
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
* Set description - Description/Notes
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
* Get designers - Designers
* @return string[]|null
*/
public function getDesigners(): ?array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("designers");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->designers;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("designers")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("designers");
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
* Set designers - Designers
* @param string[]|null $designers
* @return $this
*/
public function setDesigners(?array $designers): static
{
	$this->markFieldDirty("designers", true);

	$this->designers = $designers;

	return $this;
}

/**
* Get designersRelation - Designers relation
* @return \Pimcore\Model\DataObject\Designer[]
*/
public function getDesignersRelation(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("designersRelation");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("designersRelation")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("designersRelation")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("designersRelation");
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
* Set designersRelation - Designers relation
* @param \Pimcore\Model\DataObject\Designer[] $designersRelation
* @return $this
*/
public function setDesignersRelation(?array $designersRelation): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("designersRelation");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getDesignersRelation();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $designersRelation);
	if (!$isEqual) {
		$this->markFieldDirty("designersRelation", true);
	}
	$this->designersRelation = $fd->preSetData($this, $designersRelation);
	return $this;
}

/**
* Get exchangeableBranches - Exchangeable Branches
* @return string|null
*/
public function getExchangeableBranches(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("exchangeableBranches");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->exchangeableBranches;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("exchangeableBranches")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("exchangeableBranches");
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
* Set exchangeableBranches - Exchangeable Branches
* @param string|null $exchangeableBranches
* @return $this
*/
public function setExchangeableBranches(?string $exchangeableBranches): static
{
	$this->markFieldDirty("exchangeableBranches", true);

	$this->exchangeableBranches = $exchangeableBranches;

	return $this;
}

/**
* Get exchangeableBranchesPartial - Exchangeable Branches Partial
* @return string|null
*/
public function getExchangeableBranchesPartial(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("exchangeableBranchesPartial");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->exchangeableBranchesPartial;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("exchangeableBranchesPartial")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("exchangeableBranchesPartial");
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
* Set exchangeableBranchesPartial - Exchangeable Branches Partial
* @param string|null $exchangeableBranchesPartial
* @return $this
*/
public function setExchangeableBranchesPartial(?string $exchangeableBranchesPartial): static
{
	$this->markFieldDirty("exchangeableBranchesPartial", true);

	$this->exchangeableBranchesPartial = $exchangeableBranchesPartial;

	return $this;
}

/**
* Get suppliers - Suppliers
* @return \Pimcore\Model\DataObject\Data\ObjectMetadata[]
*/
public function getSuppliers(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("suppliers");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("suppliers")->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("suppliers")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("suppliers");
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
* Set suppliers - Suppliers
* @param \Pimcore\Model\DataObject\Data\ObjectMetadata[] $suppliers
* @return $this
*/
public function setSuppliers(?array $suppliers): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("suppliers");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
		return $this->getSuppliers();
	});
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $suppliers);
	if (!$isEqual) {
		$this->markFieldDirty("suppliers", true);
	}
	$this->suppliers = $fd->preSetData($this, $suppliers);
	return $this;
}

/**
* Get phase - Phase
* @return string|null
*/
public function getPhase(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("phase");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->phase;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("phase")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("phase");
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
* Set phase - Phase
* @param string|null $phase
* @return $this
*/
public function setPhase(?string $phase): static
{
	$this->markFieldDirty("phase", true);

	$this->phase = $phase;

	return $this;
}

/**
* Get startDate - Start Date
* @return \Carbon\Carbon|null
*/
public function getStartDate(): ?\Carbon\Carbon
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("startDate");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->startDate;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("startDate")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("startDate");
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
* Set startDate - Start Date
* @param \Carbon\Carbon|null $startDate
* @return $this
*/
public function setStartDate(?\Carbon\Carbon $startDate): static
{
	$this->markFieldDirty("startDate", true);

	$this->startDate = $startDate;

	return $this;
}

/**
* Get launchPeriod - Launch Period
* @return string|null
*/
public function getLaunchPeriod(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("launchPeriod");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->launchPeriod;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("launchPeriod")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("launchPeriod");
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
* Set launchPeriod - Launch Period
* @param string|null $launchPeriod
* @return $this
*/
public function setLaunchPeriod(?string $launchPeriod): static
{
	$this->markFieldDirty("launchPeriod", true);

	$this->launchPeriod = $launchPeriod;

	return $this;
}

/**
* Get launchYear - Launch Year
* @return float|null
*/
public function getLaunchYear(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("launchYear");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->launchYear;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("launchYear")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("launchYear");
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
* Set launchYear - Launch Year
* @param float|null $launchYear
* @return $this
*/
public function setLaunchYear(?float $launchYear): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("launchYear");
	$this->launchYear = $fd->preSetData($this, $launchYear);
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
* Get workingTitle - Working Title
* @return string|null
*/
public function getWorkingTitle(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("workingTitle");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->workingTitle;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("workingTitle")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("workingTitle");
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
* Set workingTitle - Working Title
* @param string|null $workingTitle
* @return $this
*/
public function setWorkingTitle(?string $workingTitle): static
{
	$this->markFieldDirty("workingTitle", true);

	$this->workingTitle = $workingTitle;

	return $this;
}

/**
* Get internalFollowupDesigner - Internal Followup Designer
* @return string|null
*/
public function getInternalFollowupDesigner(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("internalFollowupDesigner");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->internalFollowupDesigner;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("internalFollowupDesigner")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("internalFollowupDesigner");
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
* Set internalFollowupDesigner - Internal Followup Designer
* @param string|null $internalFollowupDesigner
* @return $this
*/
public function setInternalFollowupDesigner(?string $internalFollowupDesigner): static
{
	$this->markFieldDirty("internalFollowupDesigner", true);

	$this->internalFollowupDesigner = $internalFollowupDesigner;

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
