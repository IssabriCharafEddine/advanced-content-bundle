<?php

namespace Sherlockode\AdvancedContentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs ;
use Doctrine\ORM\Event\PrePersistEventArgs; // Use this for prePersist
use Sherlockode\AdvancedContentBundle\Manager\ConfigurationManager;
use Sherlockode\AdvancedContentBundle\Manager\VersionManager;
use Sherlockode\AdvancedContentBundle\Model\ContentInterface;
use Sherlockode\AdvancedContentBundle\Model\PageInterface;
use Sherlockode\AdvancedContentBundle\Model\PageMetaInterface;

class PageListener
{
    private $configurationManager;
    private $versionManager;

    public function __construct(ConfigurationManager $configurationManager, VersionManager $versionManager)
    {
        $this->configurationManager = $configurationManager;
        $this->versionManager = $versionManager;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof PageInterface) {
            return;
        }

        $pageVersion = $this->versionManager->getPageVersionToLoad($entity);
        if ($pageVersion === null) {
            return;
        }

        $pageMetaVersion = $pageVersion->getPageMetaVersion();
        if ($pageMetaVersion !== null) {
            foreach ($entity->getPageMeta()->getVersions() as $version) {
                if ($version->getId() === $pageMetaVersion->getId()) {
                    $entity->getPageMeta()->setTitle($version->getTitle());
                    $entity->getPageMeta()->setSlug($version->getSlug());
                    $entity->getPageMeta()->setMetaTitle($version->getMetaTitle());
                    $entity->getPageMeta()->setMetaDescription($version->getMetaDescription());
                    break;
                }
            }
        }

        $contentVersion = $pageVersion->getContentVersion();
        if ($contentVersion !== null) {
            foreach ($entity->getContent()->getVersions() as $version) {
                if ($version->getId() === $contentVersion->getId()) {
                    $entity->getContent()->setData($contentVersion->getData());
                    break;
                }
            }
        }
    }

    public function prePersist(PrePersistEventArgs $args) // Update type hint
    {
        $object = $args->getObject();

        if (!$object instanceof PageInterface) {
            return;
        }

        if ($object->getStatus() === null) {
            $object->setStatus(PageInterface::STATUS_DRAFT);
        }
    }

    public function onFlush(OnFlushEventArgs  $args)
    {
        // Access the EntityManager and UnitOfWork
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // Merge the entities that are being inserted or updated
        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        $pages = [];
        foreach ($entities as $entity) {
            if ($entity instanceof PageInterface) {
                $pages[$entity->getId()] = $entity;
            } elseif ($entity instanceof PageMetaInterface && $entity->getPage() !== null && $entity->getPage()->getId()) {
                $pages[$entity->getPage()->getId()] = $entity->getPage();
            } elseif ($entity instanceof ContentInterface && $entity->getPage() !== null && $entity->getPage()->getId()) {
                $pages[$entity->getPage()->getId()] = $entity->getPage();
            }
        }

        // Get metadata for different entities
        $pageVersionClassMetadata = $em->getClassMetadata($this->configurationManager->getEntityClass('page_version'));
        $pageClassMetadata = $em->getClassMetadata($this->configurationManager->getEntityClass('page'));
        $pageMetaVersionClassMetadata = $em->getClassMetadata($this->configurationManager->getEntityClass('page_meta_version'));
        $contentVersionClassMetadata = $em->getClassMetadata($this->configurationManager->getEntityClass('content_version'));

        // Perform necessary actions on entities
        foreach ($pages as $page) {
            $pageVersion = $this->versionManager->getNewPageVersion($page);
            $em->persist($pageVersion);
            $uow->computeChangeSet($pageVersionClassMetadata, $pageVersion);

            if ($contentVersion = $pageVersion->getContentVersion()) {
                $em->persist($contentVersion);
                $uow->computeChangeSet($contentVersionClassMetadata, $contentVersion);
            }
            if ($pageMetaVersion = $pageVersion->getPageMetaVersion()) {
                $em->persist($pageMetaVersion);
                $uow->computeChangeSet($pageMetaVersionClassMetadata, $pageMetaVersion);
            }

            // Recompute the entity change set for the page
            $uow->recomputeSingleEntityChangeSet($pageClassMetadata, $page);
        }
    }
}
