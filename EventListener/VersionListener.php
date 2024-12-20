<?php

namespace Sherlockode\AdvancedContentBundle\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs; // Correct type for prePersist
use Doctrine\ORM\Event\OnFlushEventArgs;
use Sherlockode\AdvancedContentBundle\Model\ContentVersionInterface;
use Sherlockode\AdvancedContentBundle\Model\PageVersionInterface;
use Sherlockode\AdvancedContentBundle\Model\VersionInterface;

class VersionListener
{
    /**
     * Handle the prePersist event for Version entities.
     *
     * @param PrePersistEventArgs $args
     */
    public function prePersist(PrePersistEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof VersionInterface || !$entity->isAutoSave()) {
            return;
        }

        if ($entity instanceof ContentVersionInterface && $entity->getContent()->getPage() === null) {
            $versions = $entity->getContent()->getVersions();
        } elseif ($entity instanceof PageVersionInterface) {
            $versions = $entity->getPage()->getVersions();
        } else {
            return;
        }

        $count = 0;
        foreach ($versions as $version) {
            if (!$version->isAutoSave()) {
                continue;
            }
            if ($version->getUserId() !== $entity->getUserId()) {
                continue;
            }
            if ($version->getCreatedAt() < $entity->getCreatedAt()) {
                $count++;
            }
            if ($count >= 10) {
                // Keep only the last 10 drafts by the same user
                $args->getEntityManager()->remove($version);
            }
        }
    }
}
