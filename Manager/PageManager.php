<?php

namespace Sherlockode\AdvancedContentBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedContentBundle\Model\ContentInterface;
use Sherlockode\AdvancedContentBundle\Model\ContentTypeInterface;
use Sherlockode\AdvancedContentBundle\Model\PageInterface;
use Sherlockode\AdvancedContentBundle\Model\PageTypeInterface;

class PageManager
{
    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param ConfigurationManager   $configurationManager
     * @param EntityManagerInterface $em
     */
    public function __construct(ConfigurationManager $configurationManager, EntityManagerInterface $em)
    {
        $this->configurationManager = $configurationManager;
        $this->em = $em;
    }

    /**
     * @param PageInterface $page
     *
     * @return null|ContentTypeInterface
     */
    public function getPageContentType(PageInterface $page)
    {
        $contentTypeByPage = $this->em->getRepository($this->configurationManager->getEntityClass('content_type'))->findOneBy([
            'page' => $page
        ]);

        if ($contentTypeByPage instanceof ContentTypeInterface) {
            return $contentTypeByPage;
        }

        $pageType = $page->getPageType();
        if (!$pageType instanceof PageTypeInterface) {
            return null;
        }

        $contentTypeByPageType = $this->em->getRepository($this->configurationManager->getEntityClass('content_type'))->findOneBy([
            'pageType' => $pageType
        ]);

        if ($contentTypeByPageType instanceof ContentTypeInterface) {
            return $contentTypeByPageType;
        }

        return null;
    }

    /**
     * @param ContentTypeInterface $contentTypeToValidate
     * @param null|int             $pageTypeId
     * @param null|int             $pageId
     *
     * @return bool
     */
    public function validateContentTypeLink(ContentTypeInterface $contentTypeToValidate, $pageTypeId = null, $pageId = null)
    {
        $contentTypes = [];
        if ($pageTypeId !== null) {
            $pageType = $this->em->getRepository($this->configurationManager->getEntityClass('page_type'))->find($pageTypeId);
            if (!$pageType instanceof PageTypeInterface) {
                return false;
            }
            $contentTypes = $this->em->getRepository($this->configurationManager->getEntityClass('content_type'))->findBy([
                'pageType' => $pageType,
            ]);
        }
        if ($pageId !== null) {
            $page = $this->em->getRepository($this->configurationManager->getEntityClass('page'))->find($pageId);
            if (!$page instanceof PageInterface) {
                return false;
            }
            $contentTypes = $this->em->getRepository($this->configurationManager->getEntityClass('content_type'))->findBy([
                'page' => $page,
            ]);
        }
        if (!$contentTypeToValidate->getId() && count($contentTypes) > 0) {
            return false;
        }
        foreach ($contentTypes as $contentType) {
            if ($contentType->getId() !== $contentTypeToValidate->getId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PageInterface $page
     *
     * @return bool
     */
    public function updateContentForPage(PageInterface $page)
    {
        $contentType = $this->getPageContentType($page);
        if (!$contentType instanceof ContentTypeInterface) {
            if ($page->getContent() instanceof ContentInterface) {
                $this->em->remove($page->getContent());

                return true;
            }

            return false;
        }

        if ($page->getContent() instanceof ContentInterface) {
            if ($page->getContent()->getContentType()->getId() === $contentType->getId()) {
                return false;
            }
            $this->em->remove($page->getContent());
        }

        return true;
    }

    /**
     * @param PageTypeInterface $pageType
     *
     * @return bool
     */
    public function updatePagesAfterPageTypeRemove(PageTypeInterface $pageType)
    {
        $pages = $this->em->getRepository($this->configurationManager->getEntityClass('page'))->findBy([
            'pageType' => $pageType,
        ]);

        if (count($pages) === 0) {
            return false;
        }

        foreach ($pages as $page) {
            $page->setPageType(null);
            $this->updateContentForPage($page);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function updatePages()
    {
        $pages = $this->em->getRepository($this->configurationManager->getEntityClass('page'))->findAll();

        $shouldFlush = false;
        foreach ($pages as $page) {
            if ($this->updateContentForPage($page)) {
                $shouldFlush = true;
            }
        }

        return $shouldFlush;
    }
}
