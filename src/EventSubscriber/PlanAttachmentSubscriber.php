<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Property\Predefined;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlanAttachmentSubscriber implements EventSubscriberInterface
{
    private const PLAN_TYPE_METADATA_KEY = 'planType';

    private const PLAN_TYPE_PROPERTY_KEYS = [
        'Plan' => 'plan_attachment_folder_plan',
        'Technical sheet' => 'plan_attachment_folder_technical_sheet',
        'Components plan' => 'plan_attachment_folder_components_plan',
        'Colur chart' => 'plan_attachment_folder_colur_chart',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -10],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -10],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function onPreSave(DataObjectEvent $event): void
    {
        if ($this->isVersionOnlyOrAutoSave($event)) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof Concrete || strtolower((string) $object->getClassName()) !== 'model') {
            return;
        }

        if (!method_exists($object, 'getPlanAttachment')) {
            return;
        }

        $attachments = $object->getPlanAttachment(['unpublished' => true]) ?: [];
        if (!is_array($attachments)) {
            return;
        }

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof ElementMetadata) {
                continue;
            }

            $asset = $attachment->getElement();
            if (!$asset instanceof Asset || $asset instanceof AssetFolder) {
                continue;
            }

            $this->movePlanAttachment($object, $attachment, $asset);
        }
    }

    private function isVersionOnlyOrAutoSave(DataObjectEvent $event): bool
    {
        return ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true);
    }

    /**
     * @throws ValidationException
     */
    private function movePlanAttachment(Concrete $object, ElementMetadata $attachment, Asset $asset): void
    {
        $planType = $this->getPlanType($attachment);
        $baseFolderPath = $this->getBaseFolderPath($planType);
        $code = $this->getModelCode($object);
        $targetFolderPath = $this->buildTargetFolderPath($baseFolderPath, $code);
        $targetFolder = $this->getOrCreateFolder($targetFolderPath);

        $this->moveAssetToFolder($asset, $targetFolder);
    }

    /**
     * @throws ValidationException
     */
    private function getPlanType(ElementMetadata $attachment): string
    {
        $data = $attachment->getData();
        $planTypeData = $data[self::PLAN_TYPE_METADATA_KEY] ?? '';
        $planType = is_scalar($planTypeData) ? trim((string) $planTypeData) : '';

        if ($planType === '') {
            throw new ValidationException('Select a plan attachment Type before saving the model.');
        }

        if (!array_key_exists($planType, self::PLAN_TYPE_PROPERTY_KEYS)) {
            throw new ValidationException(sprintf(
                'Unsupported plan attachment Type "%s". Allowed values are: %s.',
                $planType,
                implode(', ', array_keys(self::PLAN_TYPE_PROPERTY_KEYS))
            ));
        }

        return $planType;
    }

    /**
     * @throws ValidationException
     */
    private function getBaseFolderPath(string $planType): string
    {
        $propertyKey = self::PLAN_TYPE_PROPERTY_KEYS[$planType];
        $property = Predefined::getByKey($propertyKey);

        if (!$property instanceof Predefined) {
            throw new ValidationException(sprintf(
                'Missing predefined property "%s" for plan attachment Type "%s".',
                $propertyKey,
                $planType
            ));
        }

        $folderPath = trim((string) $property->getData());
        if ($folderPath === '') {
            throw new ValidationException(sprintf(
                'Predefined property "%s" for plan attachment Type "%s" must contain an asset folder path.',
                $propertyKey,
                $planType
            ));
        }

        return $this->normalizeAssetFolderPath($folderPath);
    }

    /**
     * @throws ValidationException
     */
    private function getModelCode(Concrete $object): string
    {
        if (!method_exists($object, 'getCode')) {
            throw new ValidationException('The model Code field is not available for plan attachment folder creation.');
        }

        $code = trim((string) ($object->getCode() ?? ''));
        if ($code === '') {
            throw new ValidationException('Set the model Code before saving a Plan Attachment.');
        }

        return $code;
    }

    /**
     * @throws ValidationException
     */
    private function buildTargetFolderPath(string $baseFolderPath, string $code): string
    {
        $folderName = ElementService::getValidKey($code, 'asset');
        if ($folderName === '') {
            throw new ValidationException('The model Code cannot be converted into a valid asset folder name.');
        }

        return ElementService::correctPath(($baseFolderPath === '/' ? '' : $baseFolderPath) . '/' . $folderName);
    }

    private function normalizeAssetFolderPath(string $folderPath): string
    {
        if (!str_starts_with($folderPath, '/')) {
            $folderPath = '/' . $folderPath;
        }

        return ElementService::correctPath($folderPath);
    }

    /**
     * @throws ValidationException
     */
    private function getOrCreateFolder(string $folderPath): AssetFolder
    {
        $existing = Asset::getByPath($folderPath);
        if ($existing instanceof Asset && !$existing instanceof AssetFolder) {
            throw new ValidationException(sprintf(
                'Plan attachment target path "%s" exists but is not an asset folder.',
                $folderPath
            ));
        }

        if ($existing instanceof AssetFolder) {
            return $existing;
        }

        $folder = AssetService::createFolderByPath($folderPath);
        if (!$folder instanceof AssetFolder) {
            throw new ValidationException(sprintf('Could not create plan attachment asset folder "%s".', $folderPath));
        }

        return $folder;
    }

    /**
     * @throws ValidationException
     */
    private function moveAssetToFolder(Asset $asset, AssetFolder $targetFolder): void
    {
        if ($asset->getParentId() === $targetFolder->getId()) {
            return;
        }

        $filename = (string) $asset->getFilename();
        if ($filename === '') {
            throw new ValidationException('The plan attachment asset cannot be moved because it has no filename.');
        }

        $asset->setFilename(ElementService::getSafeCopyName($filename, $targetFolder));
        $asset->setParent($targetFolder);

        try {
            $asset->save();
        } catch (\Throwable $e) {
            throw new ValidationException(sprintf(
                'Could not move plan attachment asset "%s" to "%s": %s',
                $filename,
                $targetFolder->getRealFullPath(),
                $e->getMessage()
            ), 0, $e);
        }
    }
}
